<?php

namespace App\Models;

use App\Core\Model;

class LoginThrottle extends Model
{
    protected string $table = 'login_throttle';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'bucket', 'identifier', 'ip_address', 'attempts', 'window_started_at', 'blocked_until',
    ];

    public function find_row(string $bucket, string $identifier, string $ip): array|false
    {
        return $this->db->query(
            'SELECT * FROM `login_throttle` WHERE `bucket` = :b AND `identifier` = :i AND `ip_address` = :ip LIMIT 1',
            ['b' => $bucket, 'i' => $identifier, 'ip' => $ip]
        )->fetch();
    }

    public function upsertHit(string $bucket, string $identifier, string $ip, int $windowSeconds, int $maxAttempts, int $blockMinutes): array
    {
        $row = $this->find_row($bucket, $identifier, $ip);
        $now = time();

        if (!$row) {
            $this->insert([
                'bucket'            => $bucket,
                'identifier'        => $identifier,
                'ip_address'        => $ip,
                'attempts'          => 1,
                'window_started_at' => date('Y-m-d H:i:s', $now),
            ]);
            return ['blocked' => false, 'attempts' => 1];
        }

        // Blocked and still within the block window.
        if (!empty($row['blocked_until']) && strtotime($row['blocked_until']) > $now) {
            return ['blocked' => true, 'retry_after' => strtotime($row['blocked_until']) - $now];
        }

        // Window expired — start a fresh window.
        if ((strtotime($row['window_started_at']) + $windowSeconds) < $now) {
            $this->update((int) $row['id'], [
                'attempts'          => 1,
                'window_started_at' => date('Y-m-d H:i:s', $now),
                'blocked_until'     => null,
            ]);
            return ['blocked' => false, 'attempts' => 1];
        }

        $attempts = (int) $row['attempts'] + 1;
        $update = ['attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $blockedUntil = date('Y-m-d H:i:s', $now + $blockMinutes * 60);
            $update['blocked_until'] = $blockedUntil;
            $this->update((int) $row['id'], $update);
            return ['blocked' => true, 'retry_after' => $blockMinutes * 60];
        }

        $this->update((int) $row['id'], $update);
        return ['blocked' => false, 'attempts' => $attempts];
    }

    public function reset(string $bucket, string $identifier, string $ip): void
    {
        $this->db->query(
            'DELETE FROM `login_throttle` WHERE `bucket` = :b AND `identifier` = :i AND `ip_address` = :ip',
            ['b' => $bucket, 'i' => $identifier, 'ip' => $ip]
        );
    }
}
