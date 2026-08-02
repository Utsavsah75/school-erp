<?php

namespace App\Models;

use App\Core\Model;

class ExamTimetable extends Model
{
    protected string $table = 'exam_timetable';
    protected string $primaryKey = 'id';

    protected array $fillable = ['exam_subject_id', 'room_number', 'start_time', 'end_time'];

    /** Exam schedule (subject, room, timing) for one class's upcoming/all exams. */
    public function forClass(int $classId, int $limit = 20): array
    {
        $sql = "SELECT et.*, e.name AS exam_name, sub.name AS subject_name, es.exam_date, es.max_marks
                FROM exam_timetable et
                JOIN exam_subjects es ON es.id = et.exam_subject_id
                JOIN exams e ON e.id = es.exam_id
                JOIN subjects sub ON sub.id = es.subject_id
                WHERE e.class_id = :cid
                ORDER BY es.exam_date ASC, et.start_time ASC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['cid' => $classId]);
    }
}
