<?php

namespace App\Models;

use App\Core\Model;

class StudentLeaveRequest extends Model
{
    protected string $table = 'student_leave_requests';
    protected string $primaryKey = 'id';

    protected array $fillable = ['student_id', 'parent_id', 'start_date', 'end_date', 'reason', 'status'];

    /** All leave requests a parent has submitted, across all their children, newest first. */
    public function forParent(int $parentId, int $limit = 20): array
    {
        $sql = "SELECT slr.*, s.full_name AS student_name
                FROM student_leave_requests slr
                JOIN students s ON s.id = slr.student_id
                WHERE slr.parent_id = :pid
                ORDER BY slr.created_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['pid' => $parentId]);
    }
}
