<?php

namespace App\Models;

use App\Core\Model;

/**
 * 1:many with teachers (spec section 5 — Teaching Assignments).
 * One teacher can hold many rows: multiple classes, sections and subjects
 * without duplicating the teacher record itself.
 */
class TeacherAssignment extends Model
{
    protected string $table = 'teacher_assignments';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'academic_year_id', 'campus', 'class_id', 'section_id', 'subject_id',
        'weekly_classes', 'classroom', 'room_number', 'effective_from', 'effective_to',
        'is_class_teacher', 'is_subject_coordinator', 'is_active',
    ];

    public function forTeacher(int $teacherId): array
    {
        return $this->raw(
            'SELECT ta.*, c.name AS class_name, sec.name AS section_name, sub.name AS subject_name, ay.label AS year_label
             FROM teacher_assignments ta
             JOIN classes c ON c.id = ta.class_id
             JOIN sections sec ON sec.id = ta.section_id
             JOIN subjects sub ON sub.id = ta.subject_id
             JOIN academic_years ay ON ay.id = ta.academic_year_id
             WHERE ta.teacher_id = :t
             ORDER BY ay.start_date DESC, c.display_order ASC, sub.name ASC',
            ['t' => $teacherId]
        );
    }

    public function replaceAll(int $teacherId, array $rows): void
    {
        $this->pdo->prepare('DELETE FROM teacher_assignments WHERE teacher_id = ?')->execute([$teacherId]);
        foreach ($rows as $row) {
            $row['teacher_id'] = $teacherId;
            $this->insert($row);
        }
    }

    /**
     * True if a DIFFERENT teacher already teaches this exact
     * Class+Section+Subject+Year combination (helps catch accidental
     * double-booking while still letting one teacher hold the row).
     */
    public function isSlotTaken(int $classId, int $sectionId, int $subjectId, int $academicYearId, int $teacherId, ?int $excludeTeacherId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM teacher_assignments
                WHERE class_id = :c AND section_id = :s AND subject_id = :sub AND academic_year_id = :y
                  AND is_active = 1 AND teacher_id != :t';
        $params = ['c' => $classId, 's' => $sectionId, 'sub' => $subjectId, 'y' => $academicYearId, 't' => $teacherId];
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }
}
