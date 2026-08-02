<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 2 — Contact Information). */
class TeacherContact extends Model
{
    protected string $table = 'teacher_contacts';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'mobile_number', 'alternate_mobile_number', 'personal_email',
        'official_email', 'emergency_contact_name', 'emergency_contact_number',
        'emergency_contact_relationship',
    ];

    public function forTeacher(int $teacherId): array|false
    {
        return $this->firstWhere(['teacher_id' => $teacherId]);
    }

    /** Insert or update the single row for this teacher. */
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
