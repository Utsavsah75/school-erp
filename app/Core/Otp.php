<?php

namespace App\Core;

use App\Models\Otp as OtpModel;

/**
 * Generates and verifies one-time codes for password recovery, delivered
 * over email or SMS (see Mailer / Sms). Handles expiration, attempt
 * limiting, and reuse prevention so callers never touch the otps table
 * directly.
 */
class Otp
{
    public static function generate(int $userId, string $channel, string $destination, string $purpose = 'password_reset'): string
    {
        $model = new OtpModel();

        // Only one outstanding code per user/purpose/channel at a time.
        $model->invalidateOutstanding($userId, $purpose, $channel);

        $length = config('otp.length', 6);
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);
        $code = (string) random_int($min, $max);

        $model->insert([
            'user_id'      => $userId,
            'channel'      => $channel,
            'purpose'      => $purpose,
            'code_hash'    => hash('sha256', $code),
            'destination'  => $destination,
            'attempts'     => 0,
            'max_attempts' => config('otp.max_attempts', 5),
            'expires_at'   => date('Y-m-d H:i:s', time() + config('otp.ttl_minutes', 10) * 60),
        ]);

        return $code;
    }

    /** @return array{ok: bool, message: string} */
    public static function verify(int $userId, string $channel, string $purpose, string $submittedCode): array
    {
        $model = new OtpModel();
        $row = $model->latestOutstanding($userId, $purpose, $channel);

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

        $model->markConsumed((int) $row['id']);
        return ['ok' => true, 'message' => 'Code verified.'];
    }
}
