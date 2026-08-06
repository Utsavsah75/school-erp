<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 3 — bank/tax identifiers). */
class TeacherBankDetail extends Model
{
    protected string $table = 'teacher_bank_details';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'bank_account_number', 'bank_name', 'branch', 'pan_number', 'pf_number', 'insurance_number',
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
