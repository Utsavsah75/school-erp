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

    /** Every fine payment collected today, with student/teacher + book title joined in — for the "Fine Collected Today" dashboard card. */
    public function today(): array
    {
        $sql = "SELECT fp.*, b.title AS book_title, b.isbn,
                       s.full_name AS student_name, s.admission_number,
                       te.full_name AS teacher_name, te.employee_number,
                       u.full_name AS received_by_name
                FROM `library_fine_payments` fp
                JOIN `book_issues` bi ON bi.id = fp.book_issue_id
                JOIN `books` b ON b.id = bi.book_id
                LEFT JOIN `students` s ON s.id = fp.student_id
                LEFT JOIN `teachers` te ON te.id = fp.teacher_id
                LEFT JOIN `users` u ON u.id = fp.received_by
                WHERE DATE(fp.paid_at) = CURDATE()
                ORDER BY fp.paid_at DESC";
        return $this->raw($sql);
    }

    public function totalCollectedToday(): float
    {
        $rows = $this->raw("SELECT COALESCE(SUM(amount),0) AS total FROM `library_fine_payments` WHERE DATE(paid_at) = CURDATE()");
        return (float) ($rows[0]['total'] ?? 0);
    }

    /**
     * Paginated, searchable, date-filtered fine payment history — powers
     * the "Fine Collected" dashboard card's History page. today() stays
     * as-is (still used for the plain "today only" today() call sites);
     * this is the browsable, date-range version.
     */
    public function paginateHistory(int $page, int $perPage, string $search = '', string $fromDate = '', string $toDate = ''): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(b.title LIKE :search1 OR s.full_name LIKE :search2 OR s.admission_number LIKE :search3
                         OR te.full_name LIKE :search4 OR te.employee_number LIKE :search5 OR fp.receipt_number LIKE :search6)";
            $like = "%{$search}%";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
        }
        if ($fromDate !== '') {
            $where[] = 'fp.paid_at >= :from_date';
            $params['from_date'] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $where[] = 'fp.paid_at <= :to_date';
            $params['to_date'] = $toDate . ' 23:59:59';
        }

        $whereSql = $where === [] ? '1=1' : implode(' AND ', $where);
        $joinSql = "FROM `library_fine_payments` fp
                     JOIN `book_issues` bi ON bi.id = fp.book_issue_id
                     JOIN `books` b ON b.id = bi.book_id
                     LEFT JOIN `students` s ON s.id = fp.student_id
                     LEFT JOIN `teachers` te ON te.id = fp.teacher_id
                     LEFT JOIN `users` u ON u.id = fp.received_by
                     WHERE {$whereSql}";

        $total = (int) ($this->raw("SELECT COUNT(*) AS c {$joinSql}", $params)[0]['c'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT fp.*, b.title AS book_title, b.isbn,
                       s.full_name AS student_name, s.admission_number,
                       te.full_name AS teacher_name, te.employee_number,
                       u.full_name AS received_by_name
                {$joinSql}
                ORDER BY fp.paid_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->raw($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
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