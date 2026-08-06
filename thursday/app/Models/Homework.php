<?php

namespace App\Models;

use App\Core\Model;

class Homework extends Model
{
    protected string $table = 'homework';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'class_id', 'section_id', 'subject_id', 'teacher_id',
        'title', 'description', 'file_path', 'due_date',
    ];

    /**
     * All homework assigned to a class+section, most recent due date first,
     * with each student's submission status for $studentId joined in
     * (submission fields are NULL if nothing was submitted yet).
     */
    public function forStudent(int $classId, int $sectionId, int $studentId, int $limit = 50): array
    {
        $sql = "SELECT h.*, sub.name AS subject_name, t.full_name AS teacher_name,
                       hs.id AS submission_id, hs.file_path AS submission_file_path,
                       hs.submitted_at, hs.status AS submission_status, hs.grade, hs.feedback
                FROM homework h
                LEFT JOIN subjects sub ON sub.id = h.subject_id
                LEFT JOIN teachers t ON t.id = h.teacher_id
                LEFT JOIN homework_submissions hs ON hs.homework_id = h.id AND hs.student_id = :sid
                WHERE h.class_id = :cid AND h.section_id = :secid
                ORDER BY h.due_date DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['sid' => $studentId, 'cid' => $classId, 'secid' => $sectionId]);
    }
}
