<?php

namespace App\Models;

use App\Core\Model;

class TimetableSlot extends Model
{
    protected string $table = 'timetable_slots';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'class_id', 'section_id', 'subject_id', 'teacher_id',
        'day_of_week', 'start_time', 'end_time', 'room_number',
    ];

    /** Full weekly timetable for a class+section, ordered for display. */
    public function forClassSection(int $classId, int $sectionId): array
    {
        $sql = "SELECT ts.*, sub.name AS subject_name, t.full_name AS teacher_name
                FROM timetable_slots ts
                LEFT JOIN subjects sub ON sub.id = ts.subject_id
                LEFT JOIN teachers t ON t.id = ts.teacher_id
                WHERE ts.class_id = :cid AND ts.section_id = :secid
                ORDER BY FIELD(ts.day_of_week,'mon','tue','wed','thu','fri','sat'), ts.start_time ASC";
        return $this->raw($sql, ['cid' => $classId, 'secid' => $sectionId]);
    }
}
