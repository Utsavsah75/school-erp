<?php

namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    protected string $table = 'password_resets';
    protected string $primaryKey = 'id';

    protected array $fillable = ['user_id', 'token', 'expires_at'];

    public function createToken(int $userId): string
    {
        // Invalidate any previous outstanding tokens for this user.
        $this->db->query('DELETE FROM `password_resets` WHERE `user_id` = :uid', ['uid' => $userId]);

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $this->insert([
            'user_id'    => $userId,
            'token'      => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        return $rawToken; // the raw token is what goes in the emailed link
    }

    public function findValidByToken(string $rawToken): array|false
    {
        $hashedToken = hash('sha256', $rawToken);
        $row = $this->findBy('token', $hashedToken);
        if (!$row) {
            return false;
        }
        if (strtotime($row['expires_at']) < time()) {
            $this->delete($row['id']);
            return false;
        }
        return $row;
    }

    public function invalidateForUser(int $userId): void
    {
        $this->db->query('DELETE FROM `password_resets` WHERE `user_id` = :uid', ['uid' => $userId]);
    }
}
