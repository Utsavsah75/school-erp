<?php

namespace App\Models;

use App\Core\Model;

/** 1:many with teachers (spec section 4 — multiple qualifications allowed). */
class TeacherQualification extends Model
{
    protected string $table = 'teacher_qualifications';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'degree', 'university', 'board', 'passing_year', 'percentage_gpa',
        'certifications', 'professional_training', 'teaching_license_number',
        'license_expiry_date', 'is_highest',
    ];

    public function forTeacher(int $teacherId): array
    {
        return $this->where(['teacher_id' => $teacherId], 'is_highest', 'DESC');
    }

    /** Replaces every qualification row for this teacher with the given set (used by the wizard's dynamic rows). */
    public function replaceAll(int $teacherId, array $rows): void
    {
        $this->pdo->prepare('DELETE FROM teacher_qualifications WHERE teacher_id = ?')->execute([$teacherId]);
        foreach ($rows as $row) {
            $row['teacher_id'] = $teacherId;
            $this->insert($row);
        }
    }
}
