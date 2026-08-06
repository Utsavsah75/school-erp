<?php

namespace App\Models;

use App\Core\Model;

class ExamSubject extends Model
{
    protected string $table = 'exam_subjects';
    protected string $primaryKey = 'id';

    protected array $fillable = ['exam_id', 'subject_id', 'exam_date', 'max_marks', 'pass_marks'];

    public function forExam(int $examId): array
    {
        $sql = "SELECT es.*, sub.name AS subject_name, sub.code AS subject_code
                FROM exam_subjects es
                LEFT JOIN subjects sub ON sub.id = es.subject_id
                WHERE es.exam_id = :eid
                ORDER BY es.exam_date ASC";
        return $this->raw($sql, ['eid' => $examId]);
    }
}
