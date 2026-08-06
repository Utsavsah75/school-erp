<?php

namespace App\Core;

/**
 * Thin wrapper around PHP sessions: starts a hardened session, and provides
 * get/set/flash helpers used throughout the app (auth state, CSRF tokens,
 * flash alerts for redirects, old-input repopulation on validation errors).
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';

        session_name($config['session']['name']);
        session_set_cookie_params([
            'lifetime' => 0, // session cookie; "remember me" is handled separately
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        // Absolute session timeout.
        $lifetime = $config['session']['lifetime'];
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > $lifetime) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    /**
     * Regenerate the session id (call on login to prevent session fixation).
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /** One-time "flash" data — read once, then auto-cleared. */
    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $data = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $data;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }

    /** Store the previously-submitted form input, for repopulating a form after a validation error. */
    public static function setOldInput(array $input): void
    {
        self::set('_old_input', $input);
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        $old = self::get('_old_input', []);
        return $old[$key] ?? $default;
    }

    public static function clearOldInput(): void
    {
        self::remove('_old_input');
    }

    /** Store validation errors for the next request (redirect-back pattern). */
    public static function setErrors(array $errors): void
    {
        self::set('_errors', $errors);
    }

    public static function getErrors(): array
    {
        $errors = self::get('_errors', []);
        self::remove('_errors');
        return $errors;
    }
}
