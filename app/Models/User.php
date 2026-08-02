<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'full_name', 'email', 'password', 'role', 'phone', 'username', 'employee_code', 'two_factor_enabled',
        'two_factor_secret', 'two_factor_recovery_codes',
        'is_active', 'last_login_at', 'remember_token',
        'email_verified_at', 'verification_token', 'verification_token_expires_at',
        'phone_verified_at', 'failed_login_attempts', 'locked_until',
        'password_changed_at', 'must_change_password', 'registered_via', 'registration_ip',
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

    // ------------------------------------------------------------
    // Multi-method login lookups
    // ------------------------------------------------------------

    public function findByUsername(string $username): array|false
    {
        return $this->findBy('username', $username);
    }

    public function findByPhone(string $phone): array|false
    {
        return $this->findBy('phone', $phone);
    }

    public function findByEmployeeCode(string $employeeCode): array|false
    {
        return $this->findBy('employee_code', $employeeCode);
    }

    public function phoneExists(string $phone, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM `users` WHERE `phone` = :phone';
        $params = ['phone' => $phone];
        if ($ignoreId !== null) {
            $sql .= ' AND `id` != :id';
            $params['id'] = $ignoreId;
        }
        $row = $this->raw($sql, $params);
        return (int) ($row[0]['c'] ?? 0) > 0;
    }

    public function employeeCodeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM `users` WHERE `employee_code` = :code';
        $params = ['code' => $code];
        if ($ignoreId !== null) {
            $sql .= ' AND `id` != :id';
            $params['id'] = $ignoreId;
        }
        $row = $this->raw($sql, $params);
        return (int) ($row[0]['c'] ?? 0) > 0;
    }

    // ------------------------------------------------------------
    // Account lockout (brute-force protection)
    // ------------------------------------------------------------

    public function isLocked(array $user): bool
    {
        return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    public function registerFailedAttempt(int $userId, int $maxAttempts, int $lockMinutes): void
    {
        $user = $this->find($userId);
        if (!$user) {
            return;
        }
        $attempts = (int) $user['failed_login_attempts'] + 1;
        $data = ['failed_login_attempts' => $attempts];
        if ($attempts >= $maxAttempts) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
        }
        $this->update($userId, $data);
    }

    public function resetFailedAttempts(int $userId): void
    {
        $this->update($userId, ['failed_login_attempts' => 0, 'locked_until' => null]);
    }

    // ------------------------------------------------------------
    // Two-Factor Authentication
    // ------------------------------------------------------------

    public function enableTwoFactor(int $userId, string $secret, string $hashedRecoveryCodesJson): void
    {
        $this->update($userId, [
            'two_factor_enabled'        => 1,
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => $hashedRecoveryCodesJson,
        ]);
    }

    public function disableTwoFactor(int $userId): void
    {
        $this->update($userId, [
            'two_factor_enabled'        => 0,
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function markPhoneVerified(int $userId): void
    {
        $this->update($userId, ['phone_verified_at' => date('Y-m-d H:i:s')]);
    }
}
