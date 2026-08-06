<?php

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';

    protected array $fillable = ['user_id', 'channel', 'event_key', 'message', 'is_read', 'sent_at'];

    public function forUser(int $userId, int $limit = 20): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid ORDER BY sent_at DESC LIMIT ' . (int) $limit;
        return $this->raw($sql, ['uid' => $userId]);
    }

    public function unreadCount(int $userId): int
    {
        return $this->count(['user_id' => $userId, 'is_read' => 0]);
    }

    public function markRead(int $id): bool
    {
        return $this->update($id, ['is_read' => 1]);
    }

    public function markAllRead(int $userId): void
    {
        $this->db->query('UPDATE notifications SET is_read = 1 WHERE user_id = :uid', ['uid' => $userId]);
    }

    public function push(int $userId, string $channel, string $eventKey, string $message): int
    {
        return $this->insert([
            'user_id'   => $userId,
            'channel'   => $channel,
            'event_key' => $eventKey,
            'message'   => $message,
            'is_read'   => 0,
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
