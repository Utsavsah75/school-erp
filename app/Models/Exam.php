<?php

namespace App\Models;

use App\Core\Model;

class Exam extends Model
{
    protected string $table = 'exams';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'type', 'academic_year_id', 'class_id', 'start_date', 'end_date'];
    protected array $searchable = ['name'];

    /** Exams with a start_date in the future, soonest first. */
    public function upcoming(int $limit = 5): array
    {
        $sql = "SELECT e.*, c.name AS class_name
                FROM exams e
                LEFT JOIN classes c ON c.id = e.class_id
                WHERE e.start_date >= CURDATE()
                ORDER BY e.start_date ASC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /** Upcoming exams for one specific class only — used on the student dashboard. */
    public function upcomingForClass(int $classId, int $limit = 5): array
    {
        $sql = "SELECT e.*, c.name AS class_name
                FROM exams e
                LEFT JOIN classes c ON c.id = e.class_id
                WHERE e.start_date >= CURDATE() AND e.class_id = :cid
                ORDER BY e.start_date ASC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['cid' => $classId]);
    }
}
