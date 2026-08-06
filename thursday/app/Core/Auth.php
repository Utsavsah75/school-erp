<?php

namespace App\Core;

use App\Core\Exceptions\UnverifiedEmailException;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

/**
 * Handles authentication state: login/logout, "remember me" persistent
 * login, the currently logged-in user, and role/permission checks.
 */
class Auth
{
    private const REMEMBER_COOKIE = 'school_erp_remember';

    /**
     * Attempt to log a user in. Returns the user row on success, or null.
     */
    public static function attempt(string $email, string $password, bool $remember = false): ?array
    {
        $userModel = new User();
        $user = $userModel->findBy('email', $email);

        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        if ((int) $user['is_active'] !== 1) {
            return null;
        }

        if (config('features.email_verification_required', true) && empty($user['email_verified_at'])) {
            throw new UnverifiedEmailException($user);
        }

        self::login($user);

        if ($remember) {
            self::setRememberCookie($user, $userModel);
        }

        $userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('user_name', $user['full_name']);
    }

    public static function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            (new User())->update($userId, ['remember_token' => null]);
        }
        self::clearRememberCookie();
        Session::destroy();
    }

    public static function check(): bool
    {
        if (Session::has('user_id')) {
            return true;
        }
        return self::loginViaRememberCookie();
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    public static function name(): ?string
    {
        return Session::get('user_name');
    }

    /**
     * Fetch the full current user row (fresh from DB).
     */
    public static function user(): ?array
    {
        $id = self::id();
        if (!$id) {
            return null;
        }
        $user = (new User())->find($id);
        return $user ?: null;
    }

    public static function hasRole(string|array $roles): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($role, $roles, true);
    }

    /**
     * True if the current user's role is permitted for the given module key
     * (see config/constants.php MODULE_PERMISSIONS).
     */
    public static function can(string $module): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        if ($role === ROLE_SUPER_ADMIN) {
            return true; // super_admin bypasses module permission checks
        }
        $allowed = MODULE_PERMISSIONS[$module] ?? [];
        return in_array($role, $allowed, true);
    }

    /**
     * Aborts with a 403 page if the current user cannot access $module.
     */
    public static function authorize(string $module): void
    {
        if (!self::can($module)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
    }

    // ------------------------------------------------------------------
    // Remember-me (persistent login) via a secure, httponly cookie holding
    // "selector:validator". The validator is hashed and stored in
    // users.remember_token so a stolen cookie value alone is not enough
    // to reconstruct a valid session; the DB comparison uses hash_equals.
    // ------------------------------------------------------------------

    private static function setRememberCookie(array $user, User $userModel): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);

        $userModel->update($user['id'], ['remember_token' => $selector . ':' . $hashedValidator]);

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $days = $config['session']['remember_me_days'];

        setcookie(
            self::REMEMBER_COOKIE,
            $selector . ':' . $validator,
            [
                'expires'  => time() + ($days * 86400),
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private static function loginViaRememberCookie(): bool
    {
        if (empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }

        $parts = explode(':', $_COOKIE[self::REMEMBER_COOKIE]);
        if (count($parts) !== 2) {
            return false;
        }
        [$selector, $validator] = $parts;

        $userModel = new User();
        $users = $userModel->raw(
            'SELECT * FROM `users` WHERE `remember_token` LIKE :sel LIMIT 1',
            ['sel' => $selector . ':%']
        );
        $user = $users[0] ?? null;

        if (!$user || empty($user['remember_token'])) {
            self::clearRememberCookie();
            return false;
        }

        [, $storedHash] = explode(':', $user['remember_token'], 2);
        if (!hash_equals($storedHash, hash('sha256', $validator))) {
            self::clearRememberCookie();
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            return false;
        }

        self::login($user);
        return true;
    }

    private static function clearRememberCookie(): void
    {
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // ------------------------------------------------------------------
    // Multi-method login: Email/Username/Student ID/Employee ID + Password,
    // used by the tabbed login form. Adds account-lockout tracking and a
    // 2FA-pending handoff on top of the same rules as attempt() above.
    // ------------------------------------------------------------------

    public const IDENTIFIER_TYPES = ['email', 'username', 'student_id', 'employee_id'];

    public static function isAccountLocked(array $user): bool
    {
        return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    public static function lockoutRemainingMinutes(array $user): int
    {
        if (empty($user['locked_until'])) {
            return 0;
        }
        return max(0, (int) ceil((strtotime($user['locked_until']) - time()) / 60));
    }

    /**
     * @return array{status: string, user: ?array, message: ?string}
     * status one of: ok | invalid | locked | inactive | unverified_email | 2fa_required
     */
    public static function attemptByIdentifier(string $type, string $identifier, string $password, bool $remember = false): array
    {
        $userModel = new User();
        $user = self::resolveByIdentifier($type, $identifier, $userModel);

        $maxAttempts = Settings::int('max_failed_login_attempts', 5);
        $lockMinutes = Settings::int('account_lock_minutes', 15);

        if (!$user) {
            return ['status' => 'invalid', 'user' => null, 'message' => 'Invalid credentials.'];
        }

        if (self::isAccountLocked($user)) {
            $mins = self::lockoutRemainingMinutes($user);
            return ['status' => 'locked', 'user' => null, 'message' => "Too many failed attempts. Please try again in {$mins} minute(s)."];
        }

        if (!password_verify($password, $user['password'])) {
            $userModel->registerFailedAttempt((int) $user['id'], $maxAttempts, $lockMinutes);
            return ['status' => 'invalid', 'user' => null, 'message' => 'Invalid credentials.'];
        }

        if ((int) $user['is_active'] !== 1) {
            return ['status' => 'inactive', 'user' => null, 'message' => 'Your account is inactive. Please contact your administrator.'];
        }

        // Only the Email login method is gated on email verification — a
        // Student/Employee ID or Username login shouldn't be blocked by an
        // unverified email that may not even be the credential in use.
        if ($type === 'email' && config('features.email_verification_required', true) && empty($user['email_verified_at'])) {
            return ['status' => 'unverified_email', 'user' => $user, 'message' => 'Please verify your email address before logging in.'];
        }

        $userModel->resetFailedAttempts((int) $user['id']);

        if (!empty($user['two_factor_enabled'])) {
            Session::set('pending_2fa_user_id', $user['id']);
            Session::set('pending_2fa_remember', $remember);
            return ['status' => '2fa_required', 'user' => $user, 'message' => null];
        }

        self::completeLogin($user, $remember);
        return ['status' => 'ok', 'user' => $user, 'message' => null];
    }

    private static function resolveByIdentifier(string $type, string $identifier, User $userModel): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $user = match ($type) {
            'email'    => $userModel->findByEmail($identifier) ?: null,
            'username' => $userModel->findByUsername($identifier) ?: null,
            'phone'    => $userModel->findByPhone($identifier) ?: null,
            'student_id' => (function () use ($identifier, $userModel) {
                $student = (new Student())->findByAdmissionNumber($identifier);
                return ($student && !empty($student['user_id'])) ? ($userModel->find($student['user_id']) ?: null) : null;
            })(),
            'employee_id' => (function () use ($identifier, $userModel) {
                $teacher = (new Teacher())->findByEmployeeNumber($identifier);
                if ($teacher && !empty($teacher['user_id'])) {
                    $user = $userModel->find($teacher['user_id']);
                    if ($user) {
                        return $user;
                    }
                }
                return $userModel->findByEmployeeCode($identifier) ?: null;
            })(),
            default => null,
        };

        return $user ?: null;
    }

    /** Finalizes a login that already passed all credential/2FA checks. */
    public static function completeLogin(array $user, bool $remember = false): void
    {
        $userModel = new User();
        self::login($user);
        if ($remember) {
            self::setRememberCookie($user, $userModel);
        }
        $userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        if (Settings::bool('login_alert_email_enabled', false) && !empty($user['email']) && Mailer::isConfigured()) {
            $when = date('d M Y, h:i A');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $html = "<p>Hi {$user['full_name']},</p><p>Your School ERP account was just signed in to on {$when} from IP {$ip}.</p>"
                . "<p>If this wasn't you, please change your password immediately and contact your administrator.</p>";
            Mailer::send($user['email'], $user['full_name'], 'New sign-in to your School ERP account', $html);
        }
    }

    // ------------------------------------------------------------------
    // Phone + OTP login (passwordless) — used by the "Phone + OTP" login tab.
    // ------------------------------------------------------------------

    /** @return array{ok: bool, message: string} */
    public static function startPhoneOtpLogin(string $phone): array
    {
        $user = (new User())->findByPhone(trim($phone));
        if (!$user) {
            return ['ok' => false, 'message' => 'No account found with that phone number.'];
        }
        if ((int) $user['is_active'] !== 1) {
            return ['ok' => false, 'message' => 'Your account is inactive. Please contact your administrator.'];
        }
        if (self::isAccountLocked($user)) {
            $mins = self::lockoutRemainingMinutes($user);
            return ['ok' => false, 'message' => "Too many failed attempts. Please try again in {$mins} minute(s)."];
        }

        $code = Otp::generate((int) $user['id'], 'sms', $user['phone'], 'login_phone');
        $ttl = Settings::int('otp_ttl_minutes', config('otp.ttl_minutes', 10));
        Sms::send($user['phone'], "Your School ERP login code is {$code}. It expires in {$ttl} minutes.");
        Session::set('phone_login_user_id', $user['id']);

        return ['ok' => true, 'message' => 'A login code has been sent to your phone.'];
    }

    /** @return array{status: string, user: ?array, message: ?string} */
    public static function verifyPhoneOtpLogin(string $phone, string $code, bool $remember = false): array
    {
        $userId = Session::get('phone_login_user_id');
        $user = $userId ? (new User())->find($userId) : null;
        if (!$user || trim($phone) !== $user['phone']) {
            return ['status' => 'invalid', 'user' => null, 'message' => 'Please request a new code.'];
        }

        $result = Otp::verify((int) $user['id'], 'sms', 'login_phone', $code);
        if (!$result['ok']) {
            return ['status' => 'invalid', 'user' => null, 'message' => $result['message']];
        }

        Session::remove('phone_login_user_id');
        (new User())->resetFailedAttempts((int) $user['id']);

        if (!empty($user['two_factor_enabled'])) {
            Session::set('pending_2fa_user_id', $user['id']);
            Session::set('pending_2fa_remember', $remember);
            return ['status' => '2fa_required', 'user' => $user, 'message' => null];
        }

        self::completeLogin($user, $remember);
        return ['status' => 'ok', 'user' => $user, 'message' => null];
    }

    // ------------------------------------------------------------------
    // Two-Factor Authentication — completing a login that was parked at
    // '2fa_required' by attemptByIdentifier()/verifyPhoneOtpLogin().
    // ------------------------------------------------------------------

    public static function hasPending2fa(): bool
    {
        return Session::has('pending_2fa_user_id');
    }

    /** @return array{ok: bool, message: ?string} */
    public static function completePending2fa(string $code): array
    {
        $userId = Session::get('pending_2fa_user_id');
        $user = $userId ? (new User())->find($userId) : null;
        if (!$user) {
            return ['ok' => false, 'message' => 'Your login session has expired. Please log in again.'];
        }

        $remember = (bool) Session::get('pending_2fa_remember', false);
        $verified = !empty($user['two_factor_secret']) && TwoFactor::verify($user['two_factor_secret'], $code);

        if (!$verified && !empty($user['two_factor_recovery_codes'])) {
            $remainingJson = TwoFactor::consumeRecoveryCode($user['two_factor_recovery_codes'], $code);
            if ($remainingJson !== null) {
                (new User())->update((int) $user['id'], ['two_factor_recovery_codes' => $remainingJson]);
                $verified = true;
            }
        }

        if (!$verified) {
            return ['ok' => false, 'message' => 'Invalid authentication code. Please try again.'];
        }

        Session::remove('pending_2fa_user_id');
        Session::remove('pending_2fa_remember');
        self::completeLogin($user, $remember);
        return ['ok' => true, 'message' => null];
    }
}