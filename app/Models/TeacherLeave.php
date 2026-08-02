<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 10 — Leave Management balances). */
class TeacherLeave extends Model
{
    protected string $table = 'teacher_leave';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'casual_leave_balance', 'sick_leave_balance', 'annual_leave_balance',
        'maternity_paternity_leave', 'leave_approver_id',
    ];

    public function forTeacher(int $teacherId): array|false
    {
        return $this->firstWhere(['teacher_id' => $teacherId]);
    }

    public function save(int $teacherId, array $data): void
    {
        $existing = $this->forTeacher($teacherId);
        $data['teacher_id'] = $teacherId;
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
