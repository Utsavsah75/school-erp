<?php

namespace App\Core;

use App\Core\Sms\LogSmsDriver;
use App\Core\Sms\TwilioSmsDriver;
use App\Core\Sms\VonageSmsDriver;

/**
 * Sends SMS (OTP codes, notifications) through a pluggable gateway driver
 * selected via config('sms.driver'). Defaults to logging the message to
 * storage/logs/sms.log so OTP flows work in development without a live
 * SMS account configured.
 */
class Sms
{
    public static function send(string $toPhone, string $message): bool
    {
        $driver = self::resolveDriver();

        try {
            return $driver->send($toPhone, $message);
        } catch (\Throwable $e) {
            error_log('[SMS ERROR] ' . $e->getMessage());
            return false;
        }
    }

    private static function resolveDriver(): Sms\SmsDriverInterface
    {
        return match (config('sms.driver', 'log')) {
            'twilio' => new TwilioSmsDriver(
                config('sms.twilio.sid', ''),
                config('sms.twilio.token', ''),
                config('sms.twilio.from', '')
            ),
            'vonage' => new VonageSmsDriver(
                config('sms.vonage.key', ''),
                config('sms.vonage.secret', ''),
                config('sms.vonage.from', '')
            ),
            default => new LogSmsDriver(),
        };
    }
}
