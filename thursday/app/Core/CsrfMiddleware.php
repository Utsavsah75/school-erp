<?php

namespace App\Core;

/** Verifies the CSRF token on state-changing (POST/PUT/PATCH/DELETE) requests. */
class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_verify($token)) {
                http_response_code(419);
                echo '<h1>419 - Page Expired</h1><p>Your session expired. Please go back and try again.</p>';
                exit;
            }
        }
        return true;
    }
}
