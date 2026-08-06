<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Otp;
use App\Core\Session;
use App\Core\Sms;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\UserSecurityQuestion;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(url('dashboard'));
        }
        $this->view('auth/login', [
            'errors'           => Session::getErrors(),
            'unverifiedEmail'  => Session::flash('unverified_email'),
            'recaptchaSite'    => \App\Core\Recaptcha::isEnabled() ? \App\Core\Recaptcha::siteKey() : null,
        ], null);
    }

    /**
     * Handles every "identifier + password" login tab (Email, Username,
     * Student ID, Employee ID). The tab picks `login_type`; Phone + OTP
     * login uses phoneOtpStart()/phoneOtpVerify() below instead since it's
     * passwordless. Account lockout, rate limiting (route middleware), and
     * the 2FA handoff are all handled inside Auth::attemptByIdentifier().
     */
    public function login(): void
    {
        $type = $_POST['login_type'] ?? 'email';
        if (!in_array($type, Auth::IDENTIFIER_TYPES, true)) {
            $type = 'email';
        }

        $rules = ['password' => 'required'];
        $rules[$type] = $type === 'email' ? 'required|email' : 'required';
        $data = $this->validate($rules);

        if (!\App\Core\Recaptcha::verify($_POST['g-recaptcha-response'] ?? null)) {
            Session::flash('error', 'reCAPTCHA verification failed. Please try again.');
            Session::setOldInput($data);
            $this->redirect(url('login'));
            return;
        }

        $identifier = $data[$type];
        $remember = !empty($_POST['remember']);

        $result = Auth::attemptByIdentifier($type, $identifier, $data['password'], $remember);

        switch ($result['status']) {
            case 'ok':
                // Auth::attemptByIdentifier() has already started the
                // session at this point, so log_activity()'s Auth::id()
                // correctly attributes this to the user who just logged in.
                log_activity('login_success', "Logged in via {$type} ('{$identifier}').");
                Session::clearOldInput();
                $this->redirect(url('dashboard'));
                return;

            case '2fa_required':
                $this->redirect(url('2fa/verify'));
                return;

            case 'unverified_email':
                Session::setOldInput(['email' => $identifier]);
                Session::flash('error', 'Please verify your email address before logging in.');
                Session::flash('unverified_email', $identifier);
                $this->redirect(url('login'));
                return;

            case 'locked':
                log_activity('account_locked', "Login blocked — account locked ('{$identifier}').");
                Session::flash('error', $result['message']);
                $this->redirect(url('login'));
                return;

            case 'inactive':
                Session::flash('error', $result['message']);
                $this->redirect(url('login'));
                return;

            default: // invalid
                // No session exists yet, so this can't be attributed to a
                // user_id — the attempted identifier goes in the
                // description instead so it still shows up as a Failed
                // Login row on the Activity Log.
                log_activity('login_failed', "Failed login attempt via {$type} for '{$identifier}'.");
                Session::flash('error', 'Invalid credentials, or your account is inactive.');
                Session::setOldInput($data);
                $this->redirect(url('login'));
                return;
        }
    }

    // ------------------------------------------------------------
    // Phone + OTP login (passwordless)
    // ------------------------------------------------------------

    public function phoneOtpStart(): void
    {
        $data = $this->validate(['phone' => 'required|phone']);

        if (!\App\Core\Recaptcha::verify($_POST['g-recaptcha-response'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'], 422);
            return;
        }

        $result = Auth::startPhoneOtpLogin($data['phone']);
        $this->json($result, $result['ok'] ? 200 : 422);
    }

    public function phoneOtpVerify(): void
    {
        $data = $this->validate(['phone' => 'required|phone', 'code' => 'required']);
        $remember = !empty($_POST['remember']);

        $result = Auth::verifyPhoneOtpLogin($data['phone'], $data['code'], $remember);

        if ($result['status'] === 'ok') {
            $this->json(['ok' => true, 'redirect' => url('dashboard')]);
            return;
        }
        if ($result['status'] === '2fa_required') {
            $this->json(['ok' => true, 'redirect' => url('2fa/verify')]);
            return;
        }
        $this->json(['ok' => false, 'message' => $result['message']], 422);
    }

    public function logout(): void
    {
        // Log while the session (and therefore Auth::id()) is still active —
        // Auth::logout() below tears it down.
        log_activity('logout', 'Logged out.');
        Auth::logout();
        $this->redirect(url('login'));
    }

    // ------------------------------------------------------------
    // Email verification
    // ------------------------------------------------------------

    /**
     * Sends the verification email for a freshly created (or resent) account.
     * Call this wherever a `users` row is created (self-registration, or an
     * admin "add user" flow) to wire in verification without duplicating
     * the token/email logic.
     */
    /** @return array{success: bool, transport: string, error: ?string} */
    public static function sendVerificationEmail(array $user): array
    {
        $rawToken = (new User())->setVerificationToken($user['id']);
        $verifyUrl = absolute_url('verify-email/' . $rawToken);

        $html = "<h2>Welcome to School ERP</h2>"
            . "<p>Hi {$user['full_name']},</p>"
            . "<p>Please verify your email address to activate your School ERP account. This link expires in 24 hours.</p>"
            . "<p><a href=\"{$verifyUrl}\">{$verifyUrl}</a></p>"
            . "<p>If you didn't request this account, you can safely ignore this email.</p>";

        return Mailer::send($user['email'], $user['full_name'], 'Verify your School ERP email address', $html);
    }

    public function verifyEmail(string $token): void
    {
        $userModel = new User();
        $user = $userModel->findByValidVerificationToken($token);

        if (!$user) {
            Session::flash('error', 'This verification link is invalid or has expired. Please request a new one.');
            $this->redirect(url('login'));
            return;
        }

        $userModel->markEmailVerified($user['id']);
        Session::flash('success', 'Your email has been verified. You can now log in.');
        $this->redirect(url('login'));
    }

    public function resendVerification(): void
    {
        $data = $this->validate(['email' => 'required|email']);

        // Checked before the user lookup so this message never depends on
        // whether the account exists — only on whether mail is set up at all.
        if (!Mailer::isConfigured()) {
            Session::flash('error', 'Email sending is not configured on this server yet. Please contact your administrator, or see Settings > Mail Setup.');
            $this->redirect(url('login'));
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        if ($user && empty($user['email_verified_at'])) {
            self::sendVerificationEmail($user);
        }

        // Same message whether or not the account exists/needs verification —
        // avoids leaking which addresses are registered.
        Session::flash('success', 'If that email exists and needs verification, a new verification link has been sent.');
        $this->redirect(url('login'));
    }

    // ------------------------------------------------------------
    // Forgot / Reset password
    // ------------------------------------------------------------

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', [
            'errors' => Session::getErrors(),
        ], null);
    }

    public function sendResetLink(): void
    {
        $data = $this->validate(['email' => 'required|email']);

        // Checked before the user lookup so this message never depends on
        // whether the account exists — only on whether mail is set up at all.
        if (!Mailer::isConfigured()) {
            Session::flash('error', 'Email sending is not configured on this server yet. Please contact your administrator, or see Settings > Mail Setup.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        $sendFailed = false;
        $sendError = null;

        // Same success message whether or not the email exists — avoids
        // leaking which addresses are registered. A genuine delivery failure
        // (e.g. wrong SMTP credentials) is surfaced honestly instead, since
        // that only happens for real accounts and is a configuration bug the
        // admin needs to know about, not a secret worth protecting.
        if ($user) {
            $resetModel = new PasswordReset();
            $rawToken = $resetModel->createToken($user['id']);
            $resetUrl = absolute_url('reset-password/' . $rawToken);

            $html = "<p>Hi {$user['full_name']},</p>"
                . "<p>Click the link below to reset your School ERP password. This link expires in 1 hour.</p>"
                . "<p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
                . "<p>If you didn't request this, you can safely ignore this email.</p>";

            $result = Mailer::send($user['email'], $user['full_name'], 'Reset your School ERP password', $html);
            if (!$result['success']) {
                $sendFailed = true;
                $sendError = $result['error'];
                error_log('[PASSWORD RESET] Failed to send reset link to ' . $user['email'] . ': ' . $sendError);
            }
        }

        if ($sendFailed) {
            Session::flash('error', 'We found your account but could not send the email: ' . $sendError);
        } else {
            Session::flash('success', 'If that email exists in our system, a password reset link has been sent.');
        }
        $this->redirect(url('forgot-password'));
    }

    public function showResetPassword(string $token): void
    {
        $resetModel = new PasswordReset();
        $record = $resetModel->findValidByToken($token);

        if (!$record) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $this->view('auth/reset-password', [
            'token'  => $token,
            'errors' => Session::getErrors(),
        ], null);
    }

    public function resetPassword(): void
    {
        $data = $this->validate([
            'token'                 => 'required',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $resetModel = new PasswordReset();
        $record = $resetModel->findValidByToken($data['token']);

        if (!$record) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $userModel = new User();
        $userModel->update($record['user_id'], ['password' => Auth::hashPassword($data['password'])]);
        $resetModel->invalidateForUser($record['user_id']);

        Session::flash('success', 'Your password has been reset. Please log in.');
        $this->redirect(url('login'));
    }

    // ------------------------------------------------------------
    // Forgot password — email/SMS OTP channel
    // ------------------------------------------------------------

    public function sendResetOtp(): void
    {
        $data = $this->validate([
            'channel' => 'required|in:email,sms',
        ]);
        $channel = $data['channel'];
        $ttl = config('otp.ttl_minutes', 10);
        $userModel = new User();

        if ($channel === 'email') {
            if (!Mailer::isConfigured()) {
                Session::flash('error', 'Email sending is not configured on this server yet. Please contact your administrator, or see Settings > Mail Setup.');
                $this->redirect(url('forgot-password'));
                return;
            }
            $emailData = $this->validate(['email' => 'required|email']);
            $user = $userModel->findByEmail($emailData['email']);
        } else {
            // SMS channel looks the account up by the mobile number entered,
            // not by email — the phone on file is also what the code is sent to.
            $phoneData = $this->validate(['phone' => 'required|phone']);
            $user = $userModel->findByPhone($phoneData['phone']);
        }

        $sendFailed = false;
        $sendError = null;

        if ($user) {
            if ($channel === 'sms') {
                $code = Otp::generate((int) $user['id'], 'sms', $user['phone']);
                Sms::send($user['phone'], "Your School ERP password reset code is {$code}. It expires in {$ttl} minutes.");
            } else {
                $code = Otp::generate((int) $user['id'], 'email', $user['email']);
                $html = "<p>Hi {$user['full_name']},</p><p>Your School ERP password reset code is:</p>"
                    . "<h2 style=\"letter-spacing:4px;\">{$code}</h2>"
                    . "<p>This code expires in {$ttl} minutes. If you didn't request this, you can ignore this email.</p>";
                $result = Mailer::send($user['email'], $user['full_name'], 'Your School ERP password reset code', $html);
                if (!$result['success']) {
                    $sendFailed = true;
                    $sendError = $result['error'];
                    error_log('[PASSWORD RESET OTP] Failed to send code to ' . $user['email'] . ': ' . $sendError);
                }
            }

            Session::set('pwreset_otp_user_id', $user['id']);
            Session::set('pwreset_otp_channel', $channel);
        }

        if ($sendFailed) {
            Session::flash('error', 'We found your account but could not send the code: ' . $sendError);
            $this->redirect(url('forgot-password'));
            return;
        }

        Session::flash('success', 'If that account exists and supports this method, a verification code has been sent.');
        $this->redirect(url('forgot-password/otp/verify'));
    }

    public function showVerifyResetOtp(): void
    {
        if (!Session::has('pwreset_otp_user_id')) {
            Session::flash('error', 'Please request a new verification code.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $this->view('auth/otp-verify', [
            'channel' => Session::get('pwreset_otp_channel'),
            'errors'  => Session::getErrors(),
        ], null);
    }

    public function verifyResetOtp(): void
    {
        $data = $this->validate(['code' => 'required']);

        $userId = Session::get('pwreset_otp_user_id');
        $channel = Session::get('pwreset_otp_channel');

        if (!$userId) {
            Session::flash('error', 'Please request a new verification code.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $result = Otp::verify((int) $userId, $channel, 'password_reset', $data['code']);

        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            $this->redirect(url('forgot-password/otp/verify'));
            return;
        }

        Session::remove('pwreset_otp_user_id');
        Session::remove('pwreset_otp_channel');
        Session::set('pwreset_verified_user_id', $userId);

        $this->redirect(url('forgot-password/otp/reset'));
    }

    // ------------------------------------------------------------
    // Forgot password — security-question fallback
    // (only reachable when features.security_questions_enabled is true —
    // routes are not registered otherwise, see config/routes.php)
    // ------------------------------------------------------------

    public function showSetupSecurityQuestions(): void
    {
        $this->view('auth/security-questions-setup', [
            'questions' => (new UserSecurityQuestion())->forUser(Auth::id()),
            'errors'    => Session::getErrors(),
        ]);
    }

    public function saveSecurityQuestions(): void
    {
        $data = $this->validate([
            'question_1' => 'required|min:5',
            'answer_1'   => 'required|min:2',
            'question_2' => 'required|min:5',
            'answer_2'   => 'required|min:2',
            'question_3' => 'required|min:5',
            'answer_3'   => 'required|min:2',
        ]);

        (new UserSecurityQuestion())->replaceForUser(Auth::id(), [
            ['question' => $data['question_1'], 'answer' => $data['answer_1']],
            ['question' => $data['question_2'], 'answer' => $data['answer_2']],
            ['question' => $data['question_3'], 'answer' => $data['answer_3']],
        ]);

        Session::flash('success', 'Your security questions have been saved.');
        $this->redirect(url('security-questions/setup'));
    }

    public function showSecurityQuestionFallback(): void
    {
        $email = trim((string) ($_GET['email'] ?? ''));
        $questions = [];

        if ($email !== '') {
            $user = (new User())->findByEmail($email);
            if ($user) {
                $questions = (new UserSecurityQuestion())->forUser($user['id']);
            }
            if (empty($questions)) {
                Session::flash('error', 'No security questions are on file for that account. Please try another recovery method.');
                $this->redirect(url('forgot-password'));
                return;
            }
        }

        $this->view('auth/security-questions-fallback', [
            'email'     => $email,
            'questions' => $questions,
            'errors'    => Session::getErrors(),
        ], null);
    }

    public function verifySecurityAnswers(): void
    {
        $data = $this->validate(['email' => 'required|email']);
        $answers = $_POST['answers'] ?? [];

        $user = (new User())->findByEmail($data['email']);

        if ($user && (new UserSecurityQuestion())->verifyAnswers($user['id'], is_array($answers) ? $answers : [])) {
            Session::set('pwreset_verified_user_id', $user['id']);
            $this->redirect(url('forgot-password/otp/reset'));
            return;
        }

        Session::flash('error', 'One or more answers were incorrect.');
        $this->redirect(url('forgot-password/security-questions?email=' . urlencode($data['email'])));
    }

    // ------------------------------------------------------------
    // Shared "set new password" step for the OTP and security-question
    // recovery paths (link-based reset uses resetPassword() above instead).
    // ------------------------------------------------------------

    public function showResetPasswordAfterVerification(): void
    {
        if (!Session::has('pwreset_verified_user_id')) {
            Session::flash('error', 'Please verify your identity again to reset your password.');
            $this->redirect(url('forgot-password'));
            return;
        }

        $this->view('auth/reset-password-verified', [
            'errors' => Session::getErrors(),
        ], null);
    }

    public function resetPasswordAfterVerification(): void
    {
        $data = $this->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $userId = Session::get('pwreset_verified_user_id');
        if (!$userId) {
            Session::flash('error', 'Please verify your identity again to reset your password.');
            $this->redirect(url('forgot-password'));
            return;
        }

        (new User())->update($userId, ['password' => Auth::hashPassword($data['password'])]);
        (new PasswordReset())->invalidateForUser($userId);
        Session::remove('pwreset_verified_user_id');

        Session::flash('success', 'Your password has been reset. Please log in.');
        $this->redirect(url('login'));
    }

    // ------------------------------------------------------------
    // Admin-triggered password reset (students, teachers, parents, staff
    // share the one `users` table, so this one action covers every role)
    // ------------------------------------------------------------

    public function adminSendReset(int $id): void
    {
        Auth::authorize('users');

        $user = (new User())->find($id);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->back();
            return;
        }

        if (!Mailer::isConfigured()) {
            Session::flash('error', 'Cannot send — SMTP is not configured yet. Visit Settings > Mail Setup to configure it, then try again.');
            $this->back();
            return;
        }

        $rawToken = (new PasswordReset())->createToken($user['id']);
        $resetUrl = absolute_url('reset-password/' . $rawToken);

        $html = "<p>Hi {$user['full_name']},</p>"
            . "<p>An administrator has requested a password reset for your School ERP account. "
            . "Click the link below to set a new password. This link expires in 1 hour.</p>"
            . "<p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
            . "<p>If you didn't expect this, please contact your school administrator.</p>";

        $result = Mailer::send($user['email'], $user['full_name'], 'Reset your School ERP password', $html);

        if ($result['success']) {
            Session::flash('success', "Password reset link sent successfully to {$user['email']}.");
        } else {
            error_log('[ADMIN RESET] Failed to send reset link to ' . $user['email'] . ': ' . $result['error']);
            Session::flash('error', 'Could not send the email: ' . $result['error']);
        }
        $this->back();
    }

    // ------------------------------------------------------------
    // Change password (logged-in user)
    // ------------------------------------------------------------

    public function showChangePassword(): void
    {
        $this->view('auth/change-password', [
            'errors' => Session::getErrors(),
        ]);
    }

    public function changePassword(): void
    {
        $data = $this->validate([
            'current_password'      => 'required',
            'password'               => 'required|min:8|confirmed',
            'password_confirmation'  => 'required',
        ]);

        $user = Auth::user();
        if (!$user || !password_verify($data['current_password'], $user['password'])) {
            Session::flash('error', 'Your current password is incorrect.');
            $this->redirect(url('change-password'));
            return;
        }

        (new User())->update($user['id'], ['password' => Auth::hashPassword($data['password'])]);

        Session::flash('success', 'Your password has been updated.');
        $this->redirect(url('dashboard'));
    }
}
