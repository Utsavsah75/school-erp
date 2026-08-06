<?php

namespace App\Core\Sms;

/** Sends SMS via Twilio's REST API using a plain cURL call (no SDK dependency). */
class TwilioSmsDriver implements SmsDriverInterface
{
    public function __construct(
        private string $sid,
        private string $token,
        private string $from
    ) {
    }

    public function send(string $toPhone, string $message): bool
    {
        if ($this->sid === '' || $this->token === '' || $this->from === '') {
            error_log('[SMS ERROR] Twilio driver selected but credentials are not configured.');
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => "{$this->sid}:{$this->token}",
            CURLOPT_POSTFIELDS => http_build_query([
                'To'   => $toPhone,
                'From' => $this->from,
                'Body' => $message,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            error_log('[SMS ERROR] Twilio request failed: ' . $error);
            return false;
        }
        if ($status < 200 || $status >= 300) {
            error_log("[SMS ERROR] Twilio returned HTTP {$status}.");
            return false;
        }
        return true;
    }
}
