<?php

namespace App\Core\Sms;

/**
 * Default SMS driver: no real gateway configured, so the message is
 * appended to storage/logs/sms.log — mirrors Mailer's dev fallback so the
 * OTP flows can be exercised locally without a live SMS account.
 */
class LogSmsDriver implements SmsDriverInterface
{
    public function send(string $toPhone, string $message): bool
    {
        $logPath = dirname(__DIR__, 3) . '/storage/logs/sms.log';
        $entry = sprintf(
            "[%s] To: %s\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $toPhone,
            str_repeat('-', 60),
            $message
        );
        file_put_contents($logPath, $entry, FILE_APPEND);
        return true;
    }
}
