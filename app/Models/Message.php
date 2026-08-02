<?php

namespace App\Models;

use App\Core\Model;

class Message extends Model
{
    protected string $table = 'messages';
    protected string $primaryKey = 'id';

    protected array $fillable = ['sender_id', 'recipient_id', 'student_id', 'subject', 'body', 'is_read'];

    /** All messages sent to or by $userId (a simple flat inbox+sent view), newest first. */
    public function forUser(int $userId, int $limit = 50): array
    {
        $sql = "SELECT m.*,
                       su.full_name AS sender_name, ru.full_name AS recipient_name,
                       s.full_name AS student_name
                FROM messages m
                LEFT JOIN users su ON su.id = m.sender_id
                LEFT JOIN users ru ON ru.id = m.recipient_id
                LEFT JOIN students s ON s.id = m.student_id
                WHERE m.sender_id = :uid OR m.recipient_id = :uid2
                ORDER BY m.created_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['uid' => $userId, 'uid2' => $userId]);
    }

    public function unreadCount(int $userId): int
    {
        return $this->count(['recipient_id' => $userId, 'is_read' => 0]);
    }

    /** Marks a message read — only if $userId is actually the recipient. */
    public function markReadFor(int $messageId, int $userId): bool
    {
        $stmt = $this->db->query(
            'UPDATE messages SET is_read = 1 WHERE id = :id AND recipient_id = :uid',
            ['id' => $messageId, 'uid' => $userId]
        );
        return $stmt->rowCount() > 0;
    }

    /** Teachers who teach a given class+section — used to populate the "Message Teacher" recipient list. */
    public function teachersForClassSection(int $classId, int $sectionId): array
    {
        $sql = "SELECT DISTINCT t.id AS teacher_id, t.full_name, t.user_id, sub.name AS subject_name
                FROM class_subject_teacher cst
                JOIN teachers t ON t.id = cst.teacher_id
                JOIN subjects sub ON sub.id = cst.subject_id
                WHERE cst.class_id = :cid AND cst.section_id = :secid AND t.user_id IS NOT NULL
                ORDER BY t.full_name ASC";
        return $this->raw($sql, ['cid' => $classId, 'secid' => $sectionId]);
    }
}
