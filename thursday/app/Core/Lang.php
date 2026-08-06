<?php

namespace App\Core;

/**
 * Minimal i18n layer. Two languages for now: English ('en') and
 * Nepali ('np'). The chosen language is remembered in the session
 * (so it survives across pages for both guests and logged-in users)
 * and mirrored into a long-lived cookie so it's still picked up on
 * the very first request of a new session (e.g. after the browser
 * was closed), before Session::start() would otherwise have anything
 * to go on.
 *
 * Usage in views:
 *   <?= t('dashboard') ?>
 *   <?= t('unread_messages', ['count' => 3]) ?>   // "3 unread messages"
 */
class Lang
{
    public const DEFAULT = 'en';
    public const SUPPORTED = ['en', 'np'];

    private static string $current = self::DEFAULT;
    /** @var array<string,string>|null */
    private static ?array $strings = null;

    /** Call once during bootstrap, after Session::start(). */
    public static function start(): void
    {
        $lang = Session::get('lang');

        if (!$lang && isset($_COOKIE['school_erp_lang'])) {
            $lang = $_COOKIE['school_erp_lang'];
        }

        self::set(is_string($lang) ? $lang : self::DEFAULT);
    }

    public static function set(string $code): void
    {
        if (!in_array($code, self::SUPPORTED, true)) {
            $code = self::DEFAULT;
        }

        self::$current = $code;
        self::$strings = null; // force reload on next get()

        Session::set('lang', $code);
        setcookie('school_erp_lang', $code, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => false, // read client-side too (e.g. for <html lang> on cached pages)
            'samesite' => 'Lax',
        ]);
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function label(?string $code = null): string
    {
        return match ($code ?? self::$current) {
            'np' => 'नेपाली',
            default => 'English',
        };
    }

    /** Translate a key. Falls back to English, then to the key itself so missing strings never break a page. */
    public static function get(string $key, array $replace = []): string
    {
        $strings = self::strings(self::$current);
        $value = $strings[$key] ?? self::strings(self::DEFAULT)[$key] ?? $key;

        foreach ($replace as $search => $val) {
            $value = str_replace(':' . $search, (string) $val, $value);
        }

        return $value;
    }

    /** @return array<string,string> */
    private static function strings(string $code): array
    {
        static $cache = [];

        if (isset($cache[$code])) {
            return $cache[$code];
        }

        $file = dirname(__DIR__) . '/Lang/' . $code . '.php';
        $cache[$code] = is_file($file) ? require $file : [];

        return $cache[$code];
    }
}
