<?php

namespace App\Models;

use App\Core\Model;

class StudentDocument extends Model
{
    protected string $table = 'student_documents';
    protected string $primaryKey = 'id';

    protected array $fillable = ['student_id', 'doc_type', 'file_path', 'original_name'];

    public function forStudent(int $studentId): array
    {
        return $this->where(['student_id' => $studentId], 'uploaded_at', 'DESC');
    }

    /**
     * Every uploaded-document row with its student's name/admission number
     * joined in, newest first — powers the storage health-check report
     * (which rows' file_path actually resolves to a real file on disk).
     */
    public function allWithStudent(): array
    {
        return $this->raw(
            "SELECT sd.*, s.full_name AS student_name, s.admission_number
             FROM student_documents sd
             LEFT JOIN students s ON s.id = sd.student_id
             ORDER BY sd.uploaded_at DESC"
        );
    }
}
