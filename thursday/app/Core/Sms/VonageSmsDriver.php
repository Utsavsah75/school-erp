<?php

namespace App\Core\Sms;

/** Sends SMS via Vonage's (Nexmo) REST API using a plain cURL call (no SDK dependency). */
class VonageSmsDriver implements SmsDriverInterface
{
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private string $from
    ) {
    }

    public function send(string $toPhone, string $message): bool
    {
        if ($this->apiKey === '' || $this->apiSecret === '' || $this->from === '') {
            error_log('[SMS ERROR] Vonage driver selected but credentials are not configured.');
            return false;
        }

        $ch = curl_init('https://rest.nexmo.com/sms/json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'to'         => $toPhone,
                'from'       => $this->from,
                'text'       => $message,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            error_log('[SMS ERROR] Vonage request failed: ' . $error);
            return false;
        }

        $decoded = json_decode((string) $response, true);
        $status = $decoded['messages'][0]['status'] ?? null;
        if ($status !== '0') {
            error_log('[SMS ERROR] Vonage rejected the message: ' . (string) $response);
            return false;
        }
        return true;
    }
}
