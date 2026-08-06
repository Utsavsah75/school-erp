<?php

namespace App\Models;

use App\Core\Model;

class RegistrationOtp extends Model
{
    protected string $table = 'registration_otps';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'session_token', 'channel', 'purpose', 'destination',
        'code_hash', 'attempts', 'max_attempts', 'expires_at', 'consumed_at', 'verified_at',
    ];

    public function latestOutstanding(string $sessionToken, string $purpose, string $channel): array|false
    {
        $rows = $this->db->query(
            'SELECT * FROM `registration_otps`
             WHERE `session_token` = :t AND `purpose` = :purpose AND `channel` = :channel AND `consumed_at` IS NULL
             ORDER BY `id` DESC LIMIT 1',
            ['t' => $sessionToken, 'purpose' => $purpose, 'channel' => $channel]
        )->fetch();
        return $rows ?: false;
    }

    public function invalidateOutstanding(string $sessionToken, string $purpose, string $channel): void
    {
        $this->db->query(
            'UPDATE `registration_otps` SET `consumed_at` = NOW()
             WHERE `session_token` = :t AND `purpose` = :purpose AND `channel` = :channel AND `consumed_at` IS NULL',
            ['t' => $sessionToken, 'purpose' => $purpose, 'channel' => $channel]
        );
    }

    public function incrementAttempts(int $id): void
    {
        $this->db->query('UPDATE `registration_otps` SET `attempts` = `attempts` + 1 WHERE `id` = :id', ['id' => $id]);
    }

    /** Consumed WITHOUT being verified — expired or attempts exhausted; forces a fresh code next time. */
    public function markConsumed(int $id): void
    {
        $this->db->query('UPDATE `registration_otps` SET `consumed_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    /** Successful verification — sets both verified_at and consumed_at (a verified code can't be replayed). */
    public function markVerified(int $id): void
    {
        $this->db->query('UPDATE `registration_otps` SET `verified_at` = NOW(), `consumed_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    /** True if this channel's OTP for the given purpose has been successfully verified in this session. */
    public function isVerified(string $sessionToken, string $purpose, string $channel): bool
    {
        $row = $this->db->query(
            'SELECT id FROM `registration_otps`
             WHERE `session_token` = :t AND `purpose` = :purpose AND `channel` = :channel AND `verified_at` IS NOT NULL
             ORDER BY `id` DESC LIMIT 1',
            ['t' => $sessionToken, 'purpose' => $purpose, 'channel' => $channel]
        )->fetch();
        return (bool) $row;
    }
}
