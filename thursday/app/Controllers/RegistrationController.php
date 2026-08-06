<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Recaptcha;
use App\Core\RegistrationOtp;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Sms;
use App\Core\Validator;
use App\Models\ParentModel;
use App\Models\StaffInvite;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

/**
 * Public self-registration wizard covering every method/role in the spec:
 *
 *   Methods (how the registrant proves ownership of a contact channel):
 *     - mobile_otp   : Mobile Number + SMS OTP
 *     - email_code   : Email + Verification Code
 *     - phone_email  : Phone + Email, both verified
 *
 *   Roles (who the account belongs to, and what extra linking is required):
 *     - student  : Admission No. required; OTP goes to the PARENT on file
 *                  ("Student Registration — Admission No. + Parent OTP")
 *     - parent   : Child's Admission No. required to link; OTP goes to the
 *                  parent's own phone/email as entered
 *     - teacher  : Employee Code required, matched against an existing
 *                  `teachers` row; OTP goes to the contact on file for it
 *     - staff    : Employee Code required, matched against a Super-Admin-
 *                  issued invite (see StaffInvite); OTP goes to the
 *                  contact on file for the invite
 *
 *   Admin accounts are never created through this public wizard — see
 *   showCreateAdmin()/storeAdmin() below, gated to role:super_admin.
 *
 * State for an in-progress registration lives entirely server-side in the
 * session under 'reg_wizard' (never in the OTP table, and the password is
 * hashed the moment it's collected — see start()) keyed by a random
 * session_token that also scopes the registration_otps rows.
 */
class RegistrationController extends Controller
{
    private const METHODS = ['mobile_otp', 'email_code', 'phone_email'];
    private const ROLES = ['student', 'parent', 'teacher', 'staff'];

    // ------------------------------------------------------------
    // Step 0 — role & method selection
    // ------------------------------------------------------------

    public function showRoleSelect(): void
    {
        if (Auth::check()) {
            $this->redirect(url('dashboard'));
            return;
        }
        if (!Settings::bool('registration_enabled', true)) {
            Session::flash('error', 'New account registration is currently disabled. Please contact your school administrator.');
            $this->redirect(url('login'));
            return;
        }
        $this->view('auth/register-role-select', [], null);
    }

    public function showWizard(): void
    {
        if (Auth::check()) {
            $this->redirect(url('dashboard'));
            return;
        }
        if (!Settings::bool('registration_enabled', true)) {
            Session::flash('error', 'New account registration is currently disabled. Please contact your school administrator.');
            $this->redirect(url('login'));
            return;
        }

        $role = $_GET['role'] ?? '';
        $method = $_GET['method'] ?? 'mobile_otp';

        if (!in_array($role, self::ROLES, true)) {
            $this->redirect(url('register'));
            return;
        }
        if (!in_array($method, self::METHODS, true)) {
            $method = 'mobile_otp';
        }

        // Fresh wizard state for this role/method (starting over clears any
        // half-finished previous attempt so stale OTPs can't leak across).
        $token = bin2hex(random_bytes(32));
        Session::set('reg_wizard', [
            'token'  => $token,
            'role'   => $role,
            'method' => $method,
            'step'   => 'details',
        ]);

        $this->view('auth/register-wizard', [
            'role'          => $role,
            'method'        => $method,
            'token'         => $token,
            'recaptchaSite' => Recaptcha::isEnabled() ? Recaptcha::siteKey() : null,
            'otpLength'     => Settings::int('otp_length', 6),
            'otpTtl'        => Settings::int('otp_ttl_minutes', 5),
            'errors'        => Session::getErrors(),
        ], null);
    }

    // ------------------------------------------------------------
    // Step 1 — submit details, send OTP(s)  (AJAX, JSON)
    // ------------------------------------------------------------

    public function start(): void
    {
        $wizard = Session::get('reg_wizard');
        if (!$wizard || ($_POST['token'] ?? '') !== $wizard['token']) {
            $this->json(['ok' => false, 'message' => 'Your registration session has expired. Please start again.'], 422);
            return;
        }

        if (!Recaptcha::verify($_POST['g-recaptcha-response'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'], 422);
            return;
        }

        $role = $wizard['role'];
        $method = $wizard['method'];

        $rules = [
            'full_name' => 'required|min:2|max:150',
            'password'  => 'required|strong_password|confirmed',
        ];
        if (in_array($method, ['mobile_otp', 'phone_email'], true)) {
            $rules['phone'] = 'required|phone';
        }
        if (in_array($method, ['email_code', 'phone_email'], true)) {
            $rules['email'] = 'required|email';
        }
        if ($role === 'student' || $role === 'parent') {
            $rules['admission_number'] = 'required';
        }
        if ($role === 'teacher' || $role === 'staff') {
            $rules['employee_code'] = 'required';
        }

        $validator = new Validator($_POST, $rules);
        if ($validator->fails()) {
            $this->json(['ok' => false, 'message' => $validator->firstError(), 'errors' => $validator->errors()], 422);
            return;
        }

        $fullName = trim((string) $_POST['full_name']);
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $userModel = new User();

        if ($phone !== '' && $userModel->phoneExists($phone)) {
            $this->json(['ok' => false, 'message' => 'An account with this phone number already exists.'], 422);
            return;
        }
        if ($email !== '' && $userModel->emailExists($email)) {
            $this->json(['ok' => false, 'message' => 'An account with this email address already exists.'], 422);
            return;
        }

        // Resolve role-specific linking + who actually receives the OTP.
        $link = $this->resolveRoleLink($role, $_POST);
        if (!$link['ok']) {
            $this->json(['ok' => false, 'message' => $link['message']], 422);
            return;
        }

        $targets = $this->resolveVerificationTargets($method, $phone, $email, $link);
        if (empty($targets)) {
            $this->json(['ok' => false, 'message' => 'No valid contact method available to verify.'], 422);
            return;
        }

        // Persist the wizard's collected data server-side — password hashed
        // immediately so a raw password is never sitting in the session.
        $wizard['data'] = [
            'full_name'        => $fullName,
            'phone'            => $phone,
            'email'            => $email,
            'password_hash'    => Auth::hashPassword((string) $_POST['password']),
            'admission_number' => trim((string) ($_POST['admission_number'] ?? '')),
            'employee_code'    => trim((string) ($_POST['employee_code'] ?? '')),
        ];
        $wizard['link'] = $link;
        $wizard['targets'] = array_map(fn($t) => $t['channel'], $targets);
        $wizard['verified'] = [];
        $wizard['step'] = 'verify';
        Session::set('reg_wizard', $wizard);

        $sent = [];
        foreach ($targets as $target) {
            $code = RegistrationOtp::generate($wizard['token'], $target['channel'], $target['destination'], 'register');
            $ttl = Settings::int('otp_ttl_minutes', 5);
            if ($target['channel'] === 'sms') {
                Sms::send($target['destination'], "Your School ERP registration code is {$code}. It expires in {$ttl} minutes.");
            } else {
                $html = "<p>Hi {$fullName},</p><p>Your School ERP registration verification code is:</p>"
                    . "<h2 style=\"letter-spacing:4px;\">{$code}</h2>"
                    . "<p>This code expires in {$ttl} minutes. If you didn't request this, you can ignore this email.</p>";
                Mailer::send($target['destination'], $fullName, 'Your School ERP verification code', $html);
            }
            $sent[] = ['channel' => $target['channel'], 'masked' => $this->mask($target['destination'])];
        }

        $this->json(['ok' => true, 'message' => 'Verification code(s) sent.', 'targets' => $sent]);
    }

    // ------------------------------------------------------------
    // Step 2 — verify OTP(s)  (AJAX, JSON)
    // ------------------------------------------------------------

    public function verifyOtp(): void
    {
        $wizard = Session::get('reg_wizard');
        if (!$wizard || ($_POST['token'] ?? '') !== $wizard['token'] || ($wizard['step'] ?? '') !== 'verify') {
            $this->json(['ok' => false, 'message' => 'Your registration session has expired. Please start again.'], 422);
            return;
        }

        $channel = $_POST['channel'] ?? '';
        $code = trim((string) ($_POST['code'] ?? ''));
        if (!in_array($channel, ['sms', 'email'], true) || $code === '') {
            $this->json(['ok' => false, 'message' => 'Please enter the verification code.'], 422);
            return;
        }

        $result = RegistrationOtp::verify($wizard['token'], $channel, 'register', $code);
        if (!$result['ok']) {
            $this->json(['ok' => false, 'message' => $result['message']]);
            return;
        }

        $wizard['verified'][] = $channel;
        $wizard['verified'] = array_values(array_unique($wizard['verified']));
        Session::set('reg_wizard', $wizard);

        $remaining = array_diff($wizard['targets'], $wizard['verified']);
        $this->json([
            'ok'              => true,
            'message'         => 'Verified.',
            'all_verified'    => empty($remaining),
        ]);
    }

    public function resendOtp(): void
    {
        $wizard = Session::get('reg_wizard');
        if (!$wizard || ($_POST['token'] ?? '') !== $wizard['token'] || ($wizard['step'] ?? '') !== 'verify') {
            $this->json(['ok' => false, 'message' => 'Your registration session has expired. Please start again.'], 422);
            return;
        }

        $channel = $_POST['channel'] ?? '';
        if (!in_array($channel, $wizard['targets'] ?? [], true)) {
            $this->json(['ok' => false, 'message' => 'Invalid channel.'], 422);
            return;
        }

        $cooldown = RegistrationOtp::resendCooldownRemaining($wizard['token'], 'register', $channel);
        if ($cooldown > 0) {
            $this->json(['ok' => false, 'message' => "Please wait {$cooldown}s before requesting another code.", 'cooldown' => $cooldown], 429);
            return;
        }

        $destination = $channel === 'sms' ? $wizard['data']['phone'] : $wizard['data']['email'];
        if (($wizard['link']['contact_channel'] ?? null) === $channel) {
            $destination = $wizard['link']['contact_destination'];
        }

        $code = RegistrationOtp::generate($wizard['token'], $channel, $destination, 'register');
        $ttl = Settings::int('otp_ttl_minutes', 5);
        $fullName = $wizard['data']['full_name'];

        if ($channel === 'sms') {
            Sms::send($destination, "Your School ERP registration code is {$code}. It expires in {$ttl} minutes.");
        } else {
            $html = "<p>Hi {$fullName},</p><p>Your new School ERP verification code is:</p>"
                . "<h2 style=\"letter-spacing:4px;\">{$code}</h2><p>This code expires in {$ttl} minutes.</p>";
            Mailer::send($destination, $fullName, 'Your School ERP verification code', $html);
        }

        $this->json(['ok' => true, 'message' => 'A new code has been sent.']);
    }

    // ------------------------------------------------------------
    // Step 3 — create the account once every required channel is verified
    // ------------------------------------------------------------

    public function complete(): void
    {
        $wizard = Session::get('reg_wizard');
        if (!$wizard || ($_POST['token'] ?? '') !== $wizard['token'] || ($wizard['step'] ?? '') !== 'verify') {
            $this->json(['ok' => false, 'message' => 'Your registration session has expired. Please start again.'], 422);
            return;
        }

        $remaining = array_diff($wizard['targets'], $wizard['verified'] ?? []);
        if (!empty($remaining)) {
            $this->json(['ok' => false, 'message' => 'Please verify every code sent to you before continuing.'], 422);
            return;
        }

        $role = $wizard['role'];
        $data = $wizard['data'];
        $link = $wizard['link'];
        $userModel = new User();

        // Re-check uniqueness right before insert — time has passed since
        // step 1 and another registration could have completed meanwhile.
        if ($data['phone'] !== '' && $userModel->phoneExists($data['phone'])) {
            $this->json(['ok' => false, 'message' => 'An account with this phone number already exists.'], 422);
            return;
        }
        if ($data['email'] !== '' && $userModel->emailExists($data['email'])) {
            $this->json(['ok' => false, 'message' => 'An account with this email address already exists.'], 422);
            return;
        }

        $registeredVia = $wizard['method'] . '_' . $role;

        $userId = $userModel->insert([
            'full_name'          => $data['full_name'],
            'email'              => $data['email'] !== '' ? $data['email'] : null,
            'phone'              => $data['phone'] !== '' ? $data['phone'] : null,
            'password'           => $data['password_hash'],
            'role'               => $link['assigned_role'],
            'is_active'          => 1,
            'email_verified_at'  => in_array('email', $wizard['verified'], true) ? date('Y-m-d H:i:s') : null,
            'phone_verified_at'  => in_array('sms', $wizard['verified'], true) ? date('Y-m-d H:i:s') : null,
            'registered_via'     => $registeredVia,
            'registration_ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // Link the new login to its existing profile row (student/teacher/parent),
        // or consume the staff invite.
        $this->finalizeRoleLink($role, $userId, $link, $data);

        Session::remove('reg_wizard');

        // Welcome email — best-effort, never blocks account creation.
        if ($data['email'] !== '') {
            $html = "<h2>Welcome to School ERP</h2><p>Hi {$data['full_name']},</p>"
                . "<p>Your account has been created successfully. You can now log in at "
                . "<a href=\"" . url('login') . "\">" . url('login') . "</a>.</p>";
            Mailer::send($data['email'], $data['full_name'], 'Welcome to School ERP', $html);
        }

        $this->json([
            'ok'       => true,
            'message'  => $link['pending_approval']
                ? 'Registration successful. Your account is pending administrator approval before you can log in.'
                : 'Registration successful. You can now log in.',
            'redirect' => url('login'),
        ]);
    }

    // ------------------------------------------------------------
    // Role-specific linking / lookups
    // ------------------------------------------------------------

    /**
     * @return array{ok: bool, message?: string, assigned_role?: string,
     *   pending_approval?: bool, contact_channel?: string, contact_destination?: string,
     *   student_id?: int, parent_id?: int, teacher_id?: int, invite_id?: int}
     */
    private function resolveRoleLink(string $role, array $post): array
    {
        switch ($role) {
            case 'student':
                $student = (new Student())->findByAdmissionNumber(trim((string) ($post['admission_number'] ?? '')));
                if (!$student) {
                    return ['ok' => false, 'message' => 'No student record found for that Admission Number. Please check with your school office.'];
                }
                if (!empty($student['user_id'])) {
                    return ['ok' => false, 'message' => 'This student already has a portal login. Use "Forgot password" if you need access.'];
                }
                $parent = !empty($student['parent_id']) ? (new ParentModel())->find($student['parent_id']) : false;
                if (!$parent || (empty($parent['phone']) && empty($parent['email']))) {
                    return ['ok' => false, 'message' => 'No parent contact is on file for this student, so we cannot send a verification code. Please contact your school office.'];
                }
                $channel = !empty($parent['phone']) ? 'sms' : 'email';
                $destination = $channel === 'sms' ? $parent['phone'] : $parent['email'];
                return [
                    'ok' => true, 'assigned_role' => ROLE_STUDENT, 'pending_approval' => false,
                    'student_id' => (int) $student['id'],
                    'contact_channel' => $channel, 'contact_destination' => $destination,
                ];

            case 'parent':
                $student = (new Student())->findByAdmissionNumber(trim((string) ($post['admission_number'] ?? '')));
                if (!$student) {
                    return ['ok' => false, 'message' => 'No student record found for that Admission Number. Please check with your school office.'];
                }
                $parent = !empty($student['parent_id']) ? (new ParentModel())->find($student['parent_id']) : false;
                if (!$parent) {
                    return ['ok' => false, 'message' => 'No parent/guardian record is linked to this student. Please contact your school office.'];
                }
                if (!empty($parent['user_id'])) {
                    return ['ok' => false, 'message' => 'This parent record already has a portal login. Use "Forgot password" if you need access.'];
                }
                return ['ok' => true, 'assigned_role' => ROLE_PARENT, 'pending_approval' => false, 'parent_id' => (int) $parent['id']];

            case 'teacher':
                $teacher = (new Teacher())->findByEmployeeNumber(trim((string) ($post['employee_code'] ?? '')));
                if (!$teacher) {
                    return ['ok' => false, 'message' => 'No teacher record found for that Employee Code. Please check with your school administrator.'];
                }
                if (!empty($teacher['user_id'])) {
                    return ['ok' => false, 'message' => 'This teacher record already has a portal login. Use "Forgot password" if you need access.'];
                }
                return ['ok' => true, 'assigned_role' => ROLE_TEACHER, 'pending_approval' => false, 'teacher_id' => (int) $teacher['id']];

            case 'staff':
                $invite = (new StaffInvite())->findUsableByCode(trim((string) ($post['employee_code'] ?? '')));
                if (!$invite) {
                    return ['ok' => false, 'message' => 'That Employee Code is invalid, already used, or has expired. Please contact your school administrator for a new invite.'];
                }
                return [
                    'ok' => true, 'assigned_role' => $invite['role'], 'pending_approval' => true,
                    'invite_id' => (int) $invite['id'],
                ];

            default:
                return ['ok' => false, 'message' => 'Unknown registration type.'];
        }
    }

    /** @return array<int,array{channel:string,destination:string}> */
    private function resolveVerificationTargets(string $method, string $phone, string $email, array $link): array
    {
        // Student flow: the code always goes to the parent's channel on
        // file, regardless of the chosen method.
        if (isset($link['contact_channel'])) {
            return [['channel' => $link['contact_channel'], 'destination' => $link['contact_destination']]];
        }

        $targets = [];
        if (in_array($method, ['mobile_otp', 'phone_email'], true) && $phone !== '') {
            $targets[] = ['channel' => 'sms', 'destination' => $phone];
        }
        if (in_array($method, ['email_code', 'phone_email'], true) && $email !== '') {
            $targets[] = ['channel' => 'email', 'destination' => $email];
        }
        return $targets;
    }

    private function finalizeRoleLink(string $role, int $userId, array $link, array $data): void
    {
        switch ($role) {
            case 'student':
                (new Student())->update($link['student_id'], ['user_id' => $userId]);
                break;
            case 'parent':
                (new ParentModel())->update($link['parent_id'], ['user_id' => $userId]);
                break;
            case 'teacher':
                (new Teacher())->update($link['teacher_id'], ['user_id' => $userId]);
                break;
            case 'staff':
                (new StaffInvite())->markUsed($link['invite_id'], $userId);
                (new User())->update($userId, ['employee_code' => $data['employee_code'], 'is_active' => 0]);
                break;
        }
    }

    private function mask(string $destination): string
    {
        if (str_contains($destination, '@')) {
            [$local, $domain] = explode('@', $destination, 2);
            $visible = mb_substr($local, 0, 2);
            return $visible . str_repeat('*', max(1, mb_strlen($local) - 2)) . '@' . $domain;
        }
        $len = strlen($destination);
        return str_repeat('*', max(0, $len - 4)) . substr($destination, -4);
    }

    // ------------------------------------------------------------
    // Admin Registration (Super Admin only) — direct creation, no
    // self-service OTP; the admin sets the initial password and the new
    // account is emailed a "your account was created" notice when mail
    // is configured.
    // ------------------------------------------------------------

    public function showCreateAdmin(): void
    {
        $this->view('auth/register-admin', [
            'roles'   => ADMIN_CREATABLE_ROLES,
            'errors'  => Session::getErrors(),
        ]);
    }

    public function storeAdmin(): void
    {
        $data = $this->validate([
            'full_name' => 'required|min:2|max:150',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|phone',
            'username'  => 'nullable|username_format|unique:users,username',
            'role'      => 'required|in:' . implode(',', ADMIN_CREATABLE_ROLES),
            'password'  => 'required|strong_password|confirmed',
        ]);

        $userModel = new User();
        $userId = $userModel->insert([
            'full_name'            => $data['full_name'],
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'username'             => $data['username'] ?? null,
            'role'                 => $data['role'],
            'password'             => Auth::hashPassword($data['password']),
            'is_active'            => 1,
            'email_verified_at'    => date('Y-m-d H:i:s'), // admin-created accounts don't need self-verification
            'registered_via'       => 'admin_created',
            'must_change_password' => 1,
        ]);

        if (Mailer::isConfigured()) {
            $html = "<p>Hi {$data['full_name']},</p>"
                . "<p>An administrator has created a School ERP account for you with the role of <strong>" . role_label($data['role']) . "</strong>.</p>"
                . "<p>Email: {$data['email']}<br>Temporary password: <strong>" . e($data['password']) . "</strong></p>"
                . "<p>Please log in and change your password immediately: <a href=\"" . url('login') . "\">" . url('login') . "</a></p>";
            Mailer::send($data['email'], $data['full_name'], 'Your School ERP account has been created', $html);
        }

        log_activity('admin_create_user', "Created {$data['role']} account for {$data['full_name']} ({$data['email']})");
        Session::flash('success', "Account created for {$data['full_name']}.");
        $this->redirect(url('admin/register'));
    }
}
