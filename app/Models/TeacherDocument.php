<?php

namespace App\Models;

use App\Core\Model;

/** 1:many with teachers (spec section 7 — Documents). */
class TeacherDocument extends Model
{
    protected string $table = 'teacher_documents';
    protected string $primaryKey = 'id';

    protected array $fillable = ['teacher_id', 'doc_name', 'file_path', 'remarks', 'uploaded_by'];

    public function forTeacher(int $teacherId): array
    {
        return $this->where(['teacher_id' => $teacherId], 'uploaded_at', 'DESC');
    }
}
