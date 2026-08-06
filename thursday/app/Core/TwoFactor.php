<?php

namespace App\Core;

/**
 * Time-based One-Time Password (TOTP, RFC 6238) two-factor authentication.
 * Self-contained (no external library) so it works without an extra
 * composer package — same philosophy as Mailer/Sms falling back cleanly
 * when nothing is configured.
 *
 * Compatible with Google Authenticator, Microsoft Authenticator, Authy,
 * 1Password, etc. — standard 30-second, 6-digit, SHA1 TOTP.
 */
class TwoFactor
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO = 'sha1';

    /** Generates a random Base32 secret (for provisioning a new authenticator app). */
    public static function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    /** otpauth:// URI for QR-code provisioning in an authenticator app. */
    public static function provisioningUri(string $secret, string $accountLabel, string $issuer = 'School ERP'): string
    {
        $label = rawurlencode($issuer . ':' . $accountLabel);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /** Verifies a 6-digit code, allowing ±1 time step (30s) of clock drift. */
    public static function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }
        $timeStep = (int) floor(time() / self::PERIOD);
        for ($drift = -1; $drift <= 1; $drift++) {
            if (hash_equals(self::codeAt($secret, $timeStep + $drift), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function codeAt(string $secret, int $timeStep): string
    {
        $key = self::base32Decode($secret);
        $data = pack('N*', 0) . pack('N*', $timeStep);
        $hash = hash_hmac(self::ALGO, $data, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);
        $otp = $binary % (10 ** self::DIGITS);
        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
        $bits = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }

    /** Generates one-time-use recovery codes (shown once at 2FA setup). Returns plaintext codes. */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }
        return $codes;
    }

    /** Hashes recovery codes for storage (JSON array of hashes in users.two_factor_recovery_codes). */
    public static function hashRecoveryCodes(array $plainCodes): string
    {
        return json_encode(array_map(fn($c) => hash('sha256', strtoupper(trim($c))), $plainCodes));
    }

    /** Checks a submitted recovery code against the stored hash list; returns the remaining hash list with it removed, or null if invalid. */
    public static function consumeRecoveryCode(string $storedJson, string $submitted): ?string
    {
        $hashes = json_decode($storedJson, true);
        if (!is_array($hashes)) {
            return null;
        }
        $submittedHash = hash('sha256', strtoupper(trim($submitted)));
        $index = array_search($submittedHash, $hashes, true);
        if ($index === false) {
            return null;
        }
        unset($hashes[$index]);
        return json_encode(array_values($hashes));
    }
}
