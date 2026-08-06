<?php

namespace App\Core;

use App\Models\LoginThrottle;

/**
 * Fixed-window rate limiting for sensitive POST endpoints (login,
 * registration OTP requests, forgot-password). Keyed by a bucket name
 * (per-route) + an identifier (a named POST field, e.g. "email" — falls
 * back to "ip-only" when the field is absent/not yet submitted) + the
 * client IP, so one abusive IP can't lock out a legitimate shared IP's
 * other users, and one guessed identifier can't be hammered from many IPs
 * without eventually tripping the identifier-wide count either.
 *
 * Registered via route middleware string: 'throttle:bucket,max,windowSecs,blockMins[,postField]'
 * e.g. 'throttle:login,5,60,15,email' — 5 attempts per 60s window, then
 * blocked for 15 minutes.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $bucket,
        private int $maxAttempts,
        private int $windowSeconds,
        private int $blockMinutes,
        private ?string $identifierField = null
    ) {
    }

    public function handle(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $identifier = 'ip-only';
        if ($this->identifierField !== null) {
            $value = trim((string) ($_POST[$this->identifierField] ?? ''));
            if ($value !== '') {
                $identifier = mb_strtolower($value);
            }
        }

        try {
            $model = new LoginThrottle();
            $result = $model->upsertHit($this->bucket, $identifier, $ip, $this->windowSeconds, $this->maxAttempts, $this->blockMinutes);
        } catch (\Throwable $e) {
            // login_throttle table missing (migration not run yet) — fail open.
            error_log('[RATE LIMIT] ' . $e->getMessage());
            return true;
        }

        if (!empty($result['blocked'])) {
            $minutes = max(1, (int) ceil(($result['retry_after'] ?? 60) / 60));
            $message = "Too many attempts. Please try again in {$minutes} minute(s).";
            http_response_code(429);
            $this->respond($message);
            return false;
        }

        return true;
    }

    private function respond(string $message): void
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => $message]);
            exit;
        }
        Session::flash('error', $message);
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        redirect($ref);
    }

    /** Resets the throttle counter for an identifier — call on a successful login. */
    public static function reset(string $bucket, string $identifierValue): void
    {
        try {
            (new LoginThrottle())->reset($bucket, mb_strtolower($identifierValue), $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        } catch (\Throwable $e) {
            // no-op — table missing is not fatal here
        }
    }
}
