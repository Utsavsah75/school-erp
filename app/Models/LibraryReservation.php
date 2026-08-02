<?php

namespace App\Models;

use App\Core\Model;

class LibraryReservation extends Model
{
    protected string $table = 'library_reservations';
    protected string $primaryKey = 'id';

    protected array $fillable = ['book_id', 'borrower_type', 'student_id', 'teacher_id', 'reserved_date', 'status'];

    public function pendingList(): array
    {
        return $this->raw(
            "SELECT r.*, b.title AS book_title,
                    s.full_name AS student_name, te.full_name AS teacher_name
             FROM `library_reservations` r
             JOIN `books` b ON b.id = r.book_id
             LEFT JOIN `students` s ON s.id = r.student_id
             LEFT JOIN `teachers` te ON te.id = r.teacher_id
             WHERE r.status = 'pending'
             ORDER BY r.reserved_date ASC"
        );
    }

    /**
     * Latest reservation activity (created or cancelled), newest first —
     * powers the dashboard's "Recent Reservations" widget. Sorted by
     * created_at DESC so a reservation always appears at the top the
     * moment it's placed, regardless of its (much later) reserved_date.
     */
    public function recent(int $limit = 10): array
    {
        return $this->raw(
            "SELECT r.*, b.title AS book_title, b.isbn, b.cover_image,
                    s.full_name AS student_name, s.admission_number,
                    te.full_name AS teacher_name, te.employee_number
             FROM `library_reservations` r
             JOIN `books` b ON b.id = r.book_id
             LEFT JOIN `students` s ON s.id = r.student_id
             LEFT JOIN `teachers` te ON te.id = r.teacher_id
             ORDER BY r.created_at DESC
             LIMIT " . (int) $limit
        );
    }
}
