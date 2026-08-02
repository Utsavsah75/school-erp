<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 9 — Payroll Information). */
class TeacherPayroll extends Model
{
    protected string $table = 'teacher_payroll';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'salary_structure', 'payment_type', 'tax_percentage', 'allowances',
        'deductions', 'overtime_rate', 'bonus', 'provident_fund', 'insurance', 'pension', 'net_salary',
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
