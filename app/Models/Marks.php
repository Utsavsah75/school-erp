<?php

namespace App\Models;

use App\Core\Model;

class Marks extends Model
{
    protected string $table = 'marks';
    protected string $primaryKey = 'id';

    protected array $fillable = ['exam_subject_id', 'student_id', 'marks_obtained', 'grade', 'remarks', 'entered_by'];

    /**
     * Full exam-result rows for one student: exam name, subject, marks, max/pass marks, grade, date.
     * This is what powers the "All Exam Result" table on the student dashboard.
     */
    public function forStudent(int $studentId, int $limit = 20): array
    {
        $sql = "SELECT m.*, e.name AS exam_name, e.type AS exam_type,
                       sub.name AS subject_name,
                       es.max_marks, es.pass_marks, es.exam_date
                FROM marks m
                JOIN exam_subjects es ON es.id = m.exam_subject_id
                JOIN exams e ON e.id = es.exam_id
                JOIN subjects sub ON sub.id = es.subject_id
                WHERE m.student_id = :sid
                ORDER BY es.exam_date DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['sid' => $studentId]);
    }

    /** All marks entered for a given exam+subject (for the teacher's marks-entry screen). */
    public function forExamSubject(int $examSubjectId): array
    {
        $sql = "SELECT m.*, s.full_name AS student_name, s.roll_number
                FROM marks m
                JOIN students s ON s.id = m.student_id
                WHERE m.exam_subject_id = :esid
                ORDER BY s.roll_number ASC";
        return $this->raw($sql, ['esid' => $examSubjectId]);
    }

    /** Upsert one student's mark for one exam subject, auto-computing a letter grade. */
    public function recordOne(int $examSubjectId, int $studentId, float $marksObtained, ?int $enteredBy, ?string $remarks = null): void
    {
        $existing = $this->raw(
            'SELECT id FROM marks WHERE exam_subject_id = :esid AND student_id = :sid',
            ['esid' => $examSubjectId, 'sid' => $studentId]
        );

        $data = [
            'exam_subject_id' => $examSubjectId,
            'student_id'      => $studentId,
            'marks_obtained'  => $marksObtained,
            'grade'           => $this->computeGrade($examSubjectId, $marksObtained),
            'remarks'         => $remarks,
            'entered_by'      => $enteredBy,
        ];

        if (!empty($existing)) {
            $this->update($existing[0]['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    private function computeGrade(int $examSubjectId, float $marksObtained): string
    {
        $row = $this->raw('SELECT max_marks FROM exam_subjects WHERE id = :id', ['id' => $examSubjectId]);
        $max = (float) ($row[0]['max_marks'] ?? 100);
        $percent = $max > 0 ? ($marksObtained / $max) * 100 : 0;

        return match (true) {
            $percent >= 90 => 'A+',
            $percent >= 80 => 'A',
            $percent >= 70 => 'B+',
            $percent >= 60 => 'B',
            $percent >= 50 => 'C',
            $percent >= 40 => 'D',
            default        => 'F',
        };
    }
}
