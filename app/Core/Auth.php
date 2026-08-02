<?php

namespace App\Core;

use App\Core\Exceptions\UnverifiedEmailException;
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
}