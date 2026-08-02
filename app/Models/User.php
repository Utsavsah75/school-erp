<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'full_name', 'email', 'password', 'role', 'phone', 'username', 'two_factor_enabled',
        'is_active', 'last_login_at', 'remember_token',
        'email_verified_at', 'verification_token', 'verification_token_expires_at',
    ];

    protected array $searchable = ['full_name', 'email', 'phone', 'username'];

    public function findByEmail(string $email): array|false
    {
        return $this->findBy('email', $email);
    }

    public function usernameExists(string $username, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM `users` WHERE `username` = :username';
        $params = ['username' => $username];
        if ($ignoreId !== null) {
            $sql .= ' AND `id` != :id';
            $params['id'] = $ignoreId;
        }
        $row = $this->raw($sql, $params);
        return (int) ($row[0]['c'] ?? 0) > 0;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM `users` WHERE `email` = :email';
        $params = ['email' => $email];
        if ($ignoreId !== null) {
            $sql .= ' AND `id` != :id';
            $params['id'] = $ignoreId;
        }
        $row = $this->raw($sql, $params);
        return (int) ($row[0]['c'] ?? 0) > 0;
    }

    /** @return array<int,array> */
    public function byRole(string $role): array
    {
        return $this->where(['role' => $role], 'full_name', 'ASC');
    }

    /**
     * Issue a new email-verification token (24h expiry). Returns the raw
     * token — only its hash is persisted, same pattern as PasswordReset.
     */
    public function setVerificationToken(int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $this->update($userId, [
            'verification_token' => hash('sha256', $rawToken),
            'verification_token_expires_at' => date('Y-m-d H:i:s', time() + 86400),
        ]);
        return $rawToken;
    }

    public function findByValidVerificationToken(string $rawToken): array|false
    {
        $user = $this->findBy('verification_token', hash('sha256', $rawToken));
        if (!$user) {
            return false;
        }
        if (empty($user['verification_token_expires_at']) || strtotime($user['verification_token_expires_at']) < time()) {
            return false;
        }
        return $user;
    }

    public function markEmailVerified(int $userId): void
    {
        $this->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);
    }
}
