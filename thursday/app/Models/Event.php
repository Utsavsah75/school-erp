<?php

namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    protected string $table = 'events';
    protected string $primaryKey = 'id';

    protected array $fillable = ['title', 'description', 'event_type', 'start_date', 'end_date', 'created_by'];
    protected array $searchable = ['title'];

    public function upcoming(int $limit = 5): array
    {
        $sql = 'SELECT * FROM events WHERE start_date >= CURDATE() ORDER BY start_date ASC LIMIT ' . (int) $limit;
        return $this->raw($sql);
    }

    /** All events falling within a given month, for calendar rendering. */
    public function forMonth(int $year, int $month): array
    {
        $sql = 'SELECT * FROM events
                WHERE (YEAR(start_date) = :y AND MONTH(start_date) = :m)
                   OR (end_date IS NOT NULL AND start_date <= LAST_DAY(:d) AND end_date >= :d)
                ORDER BY start_date ASC';
        $firstOfMonth = sprintf('%04d-%02d-01', $year, $month);
        return $this->raw($sql, ['y' => $year, 'm' => $month, 'd' => $firstOfMonth]);
    }
}
