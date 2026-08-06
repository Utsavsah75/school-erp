<?php

namespace App\Models;

use App\Core\Model;

/** 1:many with teachers — prior-employer work history (spec section 3). */
class TeacherExperience extends Model
{
    protected string $table = 'teacher_experience';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'institution_name', 'designation', 'from_date', 'to_date', 'description',
    ];

    public function forTeacher(int $teacherId): array
    {
        return $this->where(['teacher_id' => $teacherId], 'from_date', 'DESC');
    }

    public function replaceAll(int $teacherId, array $rows): void
    {
        $this->pdo->prepare('DELETE FROM teacher_experience WHERE teacher_id = ?')->execute([$teacherId]);
        foreach ($rows as $row) {
            $row['teacher_id'] = $teacherId;
            $this->insert($row);
        }
    }
}
