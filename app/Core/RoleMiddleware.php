<?php

namespace App\Core;

/** Restricts a route to one or more roles. */
class RoleMiddleware implements MiddlewareInterface
{
    private array $roles;

    public function __construct(array|string $roles)
    {
        $this->roles = is_array($roles) ? $roles : [$roles];
    }

    public function handle(): bool
    {
        if (!Auth::check()) {
            redirect(url('login'));
            return false;
        }
        if (!Auth::hasRole($this->roles) && !Auth::hasRole(ROLE_SUPER_ADMIN)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
        return true;
    }
}
