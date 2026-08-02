<?php

namespace App\Models;

use App\Core\Model;

/**
 * A real receipt ledger for library fines (see database/library_dashboard_migration.sql).
 * Previously a fine was only ever `fine_amount` + a `fine_paid` boolean on
 * book_issues, with no record of who paid, how, or when. This model gives
 * the dashboard's "Recent Payments" section actual rows to show — the same
 * way the school's general Fee/[[Payment]] ledger already works.
 */
class LibraryFinePayment extends Model
{
    protected string $table = 'library_fine_payments';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'book_issue_id', 'student_id', 'teacher_id', 'amount', 'payment_mode',
        'reference_number', 'receipt_number', 'received_by', 'notes', 'paid_at',
    ];

    public const PAYMENT_MODES = Payment::PAYMENT_MODES;

    /** Most recent fine payments, with student/teacher + book title joined in. */
    public function recent(int $limit = 10): array
    {
        $sql = "SELECT fp.*, b.title AS book_title,
                       s.full_name AS student_name, s.admission_number,
                       te.full_name AS teacher_name, te.employee_number,
                       u.full_name AS received_by_name
                FROM `library_fine_payments` fp
                JOIN `book_issues` bi ON bi.id = fp.book_issue_id
                JOIN `books` b ON b.id = bi.book_id
                LEFT JOIN `students` s ON s.id = fp.student_id
                LEFT JOIN `teachers` te ON te.id = fp.teacher_id
                LEFT JOIN `users` u ON u.id = fp.received_by
                ORDER BY fp.paid_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /** Every fine payment a student has made — linked from the dashboard's "full fee history" action. */
    public function forStudent(int $studentId): array
    {
        return $this->raw(
            "SELECT fp.*, b.title AS book_title
             FROM `library_fine_payments` fp
             JOIN `book_issues` bi ON bi.id = fp.book_issue_id
             JOIN `books` b ON b.id = bi.book_id
             WHERE fp.student_id = :sid
             ORDER BY fp.paid_at DESC",
            ['sid' => $studentId]
        );
    }

    public function totalCollectedToday(): float
    {
        $rows = $this->raw("SELECT COALESCE(SUM(amount),0) AS total FROM `library_fine_payments` WHERE DATE(paid_at) = CURDATE()");
        return (float) ($rows[0]['total'] ?? 0);
    }

    /** One fine payment by its receipt number, with everything a printable receipt needs. */
    public function byReceiptNumber(string $receiptNumber): ?array
    {
        $rows = $this->raw(
            "SELECT fp.*, b.title AS book_title, b.isbn, bi.due_date, bi.issue_date,
                    s.full_name AS student_name, s.admission_number,
                    te.full_name AS teacher_name, te.employee_number,
                    u.full_name AS received_by_name
             FROM `library_fine_payments` fp
             JOIN `book_issues` bi ON bi.id = fp.book_issue_id
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = fp.student_id
             LEFT JOIN `teachers` te ON te.id = fp.teacher_id
             LEFT JOIN `users` u ON u.id = fp.received_by
             WHERE fp.receipt_number = :r
             LIMIT 1",
            ['r' => $receiptNumber]
        );
        return $rows[0] ?? null;
    }
}