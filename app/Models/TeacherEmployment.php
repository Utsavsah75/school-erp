<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 3 — Employment Information, minus bank details). */
class TeacherEmployment extends Model
{
    protected string $table = 'teacher_employment';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'employment_type', 'designation', 'department', 'specialization',
        'previous_school', 'previous_experience_details', 'reporting_manager_id',
        'salary_grade', 'basic_salary', 'allowances', 'employment_status',
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
