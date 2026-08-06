<?php

namespace App\Core;

/** Requires the user to be logged OUT (e.g. the login page itself). */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (Auth::check()) {
            redirect(url('dashboard'));
            return false;
        }
        return true;
    }
}
