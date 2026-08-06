<?php

namespace App\Core;

/** Requires the user to be logged in. */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please log in to continue.');
            redirect(url('login'));
            return false;
        }
        return true;
    }
}
