<?php

namespace App\Core;

/**
 * Middleware run before a route's controller action. Each returns true to
 * let the request continue, or handles the response itself (redirect/abort)
 * and returns false to stop the chain.
 */
interface MiddlewareInterface
{
    public function handle(): bool;
}
