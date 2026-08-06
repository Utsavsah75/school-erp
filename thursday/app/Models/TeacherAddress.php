<?php

namespace App\Models;

use App\Core\Model;

/** 1:many with teachers — one row per address_type (permanent/temporary). */
class TeacherAddress extends Model
{
    protected string $table = 'teacher_addresses';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'address_type', 'address_line', 'city', 'state_province', 'country', 'postal_code',
    ];

    public function forTeacher(int $teacherId): array
    {
        return $this->where(['teacher_id' => $teacherId]);
    }

    /** Insert or update the (teacher_id, address_type) row. */
    public function save(int $teacherId, string $type, array $data): void
    {
        $existing = $this->firstWhere(['teacher_id' => $teacherId, 'address_type' => $type]);
        $data['teacher_id'] = $teacherId;
        $data['address_type'] = $type;
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
