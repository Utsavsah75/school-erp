<?php

namespace App\Core;

/**
 * Google reCAPTCHA v2 ("I'm not a robot" checkbox) verification.
 * Off by default (Settings::bool('recaptcha_enabled')) so the app works
 * out of the box without Google keys configured — enable it, and set
 * site/secret keys, from Settings > Registration & Security.
 */
class Recaptcha
{
    public static function isEnabled(): bool
    {
        return Settings::bool('recaptcha_enabled', false) && self::siteKey() !== '';
    }

    public static function siteKey(): string
    {
        return (string) Settings::get('recaptcha_site_key', '');
    }

    /**
     * Verifies the g-recaptcha-response token from a submitted form.
     * Always returns true when reCAPTCHA is disabled/unconfigured, so
     * callers can call this unconditionally before processing a form.
     */
    public static function verify(?string $token): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $secret = (string) Settings::get('recaptcha_secret_key', '');
        if ($secret === '' || empty($token)) {
            return false;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'secret'   => $secret,
                        'response' => $token,
                        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    ]),
                    'timeout' => 5,
                ],
            ]);
            $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            if ($result === false) {
                // Google unreachable — fail open rather than locking everyone
                // out of registration/login because of a network hiccup.
                error_log('[RECAPTCHA] Verification request failed (network).');
                return true;
            }
            $data = json_decode($result, true);
            return !empty($data['success']);
        } catch (\Throwable $e) {
            error_log('[RECAPTCHA] Verification error: ' . $e->getMessage());
            return true;
        }
    }
}
