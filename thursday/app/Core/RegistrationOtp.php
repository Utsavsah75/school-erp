<?php

namespace App\Core;

use App\Models\RegistrationOtp as RegistrationOtpModel;

/**
 * Generates and verifies one-time codes for the public registration
 * wizard, i.e. BEFORE a `users` row exists. Keyed by a random per-attempt
 * session token (see RegistrationController::sessionToken()) rather than
 * a user id. Mirrors App\Core\Otp's expiry / attempt-limit / reuse-prevention
 * behaviour exactly, against the separate `registration_otps` table.
 */
class RegistrationOtp
{
    public static function generate(string $sessionToken, string $channel, string $destination, string $purpose): string
    {
        $model = new RegistrationOtpModel();
        $model->invalidateOutstanding($sessionToken, $purpose, $channel);

        $length = Settings::int('otp_length', 6);
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);
        $code = (string) random_int($min, $max);

        $model->insert([
            'session_token' => $sessionToken,
            'channel'       => $channel,
            'purpose'       => $purpose,
            'code_hash'     => hash('sha256', $code),
            'destination'   => $destination,
            'attempts'      => 0,
            'max_attempts'  => Settings::int('otp_max_attempts', 5),
            'expires_at'    => date('Y-m-d H:i:s', time() + Settings::int('otp_ttl_minutes', 5) * 60),
        ]);

        return $code;
    }

    /** @return array{ok: bool, message: string} */
    public static function verify(string $sessionToken, string $channel, string $purpose, string $submittedCode): array
    {
        $model = new RegistrationOtpModel();
        $row = $model->latestOutstanding($sessionToken, $purpose, $channel);

        if (!$row) {
            return ['ok' => false, 'message' => 'No active code found for this request. Please request a new one.'];
        }

        if (strtotime($row['expires_at']) < time()) {
            $model->markConsumed((int) $row['id']);
            return ['ok' => false, 'message' => 'This code has expired. Please request a new one.'];
        }

        if ((int) $row['attempts'] >= (int) $row['max_attempts']) {
            $model->markConsumed((int) $row['id']);
            return ['ok' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        if (!hash_equals($row['code_hash'], hash('sha256', $submittedCode))) {
            $model->incrementAttempts((int) $row['id']);
            $remaining = (int) $row['max_attempts'] - ((int) $row['attempts'] + 1);
            return ['ok' => false, 'message' => "Incorrect code. {$remaining} attempt(s) remaining."];
        }

        $model->markVerified((int) $row['id']);
        return ['ok' => true, 'message' => 'Code verified.'];
    }

    public static function isVerified(string $sessionToken, string $purpose, string $channel): bool
    {
        return (new RegistrationOtpModel())->isVerified($sessionToken, $purpose, $channel);
    }

    /** Seconds remaining before a resend is allowed (0 if a resend is allowed now). */
    public static function resendCooldownRemaining(string $sessionToken, string $purpose, string $channel): int
    {
        $model = new RegistrationOtpModel();
        $rows = $model->raw(
            'SELECT `created_at` FROM `registration_otps`
             WHERE `session_token` = :t AND `purpose` = :purpose AND `channel` = :channel
             ORDER BY `id` DESC LIMIT 1',
            ['t' => $sessionToken, 'purpose' => $purpose, 'channel' => $channel]
        );
        if (empty($rows)) {
            return 0;
        }
        $elapsed = time() - strtotime($rows[0]['created_at']);
        $cooldown = Settings::int('otp_resend_cooldown_seconds', 60);
        return max(0, $cooldown - $elapsed);
    }
}
