<?php

namespace App\Models;

use App\Core\Model;

class Otp extends Model
{
    protected string $table = 'otps';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'channel', 'purpose', 'code_hash', 'destination',
        'attempts', 'max_attempts', 'expires_at', 'consumed_at',
    ];

    /** Latest outstanding (unconsumed) OTP for a user/purpose/channel, regardless of expiry. */
    public function latestOutstanding(int $userId, string $purpose, string $channel): array|false
    {
        $rows = $this->db->query(
            'SELECT * FROM `otps`
             WHERE `user_id` = :uid AND `purpose` = :purpose AND `channel` = :channel AND `consumed_at` IS NULL
             ORDER BY `id` DESC LIMIT 1',
            ['uid' => $userId, 'purpose' => $purpose, 'channel' => $channel]
        )->fetch();
        return $rows ?: false;
    }

    /** Invalidate (consume) any outstanding OTPs for a user/purpose/channel so only the newest one is usable. */
    public function invalidateOutstanding(int $userId, string $purpose, string $channel): void
    {
        $this->db->query(
            'UPDATE `otps` SET `consumed_at` = NOW()
             WHERE `user_id` = :uid AND `purpose` = :purpose AND `channel` = :channel AND `consumed_at` IS NULL',
            ['uid' => $userId, 'purpose' => $purpose, 'channel' => $channel]
        );
    }

    public function incrementAttempts(int $id): void
    {
        $this->db->query('UPDATE `otps` SET `attempts` = `attempts` + 1 WHERE `id` = :id', ['id' => $id]);
    }

    public function markConsumed(int $id): void
    {
        $this->db->query('UPDATE `otps` SET `consumed_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }
}
