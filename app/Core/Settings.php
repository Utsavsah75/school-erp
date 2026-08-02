<?php

namespace App\Core;

/**
 * Runtime-configurable settings (Registration & Security admin panel),
 * backed by the `admin_settings` key/value table. Falls back to
 * config()/.env values when a key hasn't been set in the DB yet (or the
 * migration hasn't been run), so the app degrades gracefully instead of
 * breaking on a fresh checkout.
 *
 * Usage:
 *   Settings::get('otp_ttl_minutes', 10);
 *   Settings::set('otp_ttl_minutes', 5, $adminUserId);
 *   Settings::bool('recaptcha_enabled');
 */
class Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** Config-fallback keys for settings that also exist in config/app.php (dot notation). */
    private const CONFIG_FALLBACK = [
        'otp_length'                  => 'otp.length',
        'otp_ttl_minutes'             => 'otp.ttl_minutes',
        'otp_max_attempts'            => 'otp.max_attempts',
        'otp_resend_cooldown_seconds' => 'otp.resend_cooldown_seconds',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadCache();

        if (self::$cache !== null && array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        if (isset(self::CONFIG_FALLBACK[$key])) {
            return config(self::CONFIG_FALLBACK[$key], $default);
        }

        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function set(string $key, string $value, ?int $updatedBy = null): void
    {
        try {
            $db = Database::getInstance();
            $db->query(
                'INSERT INTO `admin_settings` (`setting_key`, `setting_value`, `updated_by`)
                 VALUES (:k, :v, :u)
                 ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_by` = VALUES(`updated_by`)',
                ['k' => $key, 'v' => $value, 'u' => $updatedBy]
            );
        } catch (\Throwable $e) {
            error_log('[SETTINGS] Failed to save ' . $key . ': ' . $e->getMessage());
        }
        self::$cache = null; // force reload next read
    }

    /** @param array<string,string> $values */
    public static function setMany(array $values, ?int $updatedBy = null): void
    {
        foreach ($values as $key => $value) {
            self::set($key, (string) $value, $updatedBy);
        }
    }

    /** All settings as a flat key => value array, for rendering the admin form. */
    public static function all(): array
    {
        self::loadCache();
        return self::$cache ?? [];
    }

    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }
        try {
            $db = Database::getInstance();
            $rows = $db->query('SELECT `setting_key`, `setting_value` FROM `admin_settings`')->fetchAll();
            self::$cache = [];
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable $e) {
            // Table doesn't exist yet (migration not run) — degrade to config() fallback only.
            self::$cache = [];
        }
    }
}
