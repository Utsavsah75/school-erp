<?php

namespace App\Models;

use App\Core\Model;

class BookIssue extends Model
{
    protected string $table = 'book_issues';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'book_id', 'book_copy_id', 'copy_code', 'student_id', 'teacher_id', 'issue_date', 'due_date',
        'return_date', 'returned_at', 'fine_amount', 'fine_paid', 'status',
        'renewed_count', 'issued_by', 'returned_by', 'remarks',
    ];

    /**
     * Currently-issued (not yet returned) books, with borrower + book
     * title joined in. "Overdue" is computed on the fly (status stays
     * 'issued' — the schema's status enum is issued/returned/lost, so we
     * don't invent a stored 'overdue' value).
     */
    public function activeList(string $search = ''): array
    {
        $where = "bi.status = 'issued'";
        $params = [];
        if ($search !== '') {
            // Unique placeholder per occurrence — PDO::ATTR_EMULATE_PREPARES is off,
            // so the real MySQL driver rejects binding one named param more than once.
            $where .= " AND (b.title LIKE :search1 OR b.accession_number LIKE :search2
                         OR s.full_name LIKE :search3 OR s.admission_number LIKE :search4
                         OR te.full_name LIKE :search5 OR te.employee_number LIKE :search6)";
            $like = "%{$search}%";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
        }

        return $this->raw(
            "SELECT bi.*, b.title AS book_title, b.accession_number,
                    s.full_name AS student_name, s.admission_number,
                    te.full_name AS teacher_name, te.employee_number,
                    CASE WHEN bi.due_date < CURDATE() THEN DATEDIFF(CURDATE(), bi.due_date) ELSE 0 END AS days_overdue
             FROM `book_issues` bi
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             WHERE {$where}
             ORDER BY bi.due_date ASC",
            $params
        );
    }

    /**
     * Paginated + date-filtered version of activeList(), for the Manage
     * Issued Books screen's "entries per page" / "from date"–"to date"
     * controls. Same currently-issued scope as activeList(); kept as a
     * separate method so activeList() (used elsewhere) is untouched.
     */
    public function paginateActive(int $page, int $perPage, string $search = '', string $fromDate = '', string $toDate = ''): array
    {
        $where = ["bi.status = 'issued'"];
        $params = [];

        if ($search !== '') {
            $where[] = "(b.title LIKE :search1 OR b.accession_number LIKE :search2
                         OR s.full_name LIKE :search3 OR s.admission_number LIKE :search4
                         OR te.full_name LIKE :search5 OR te.employee_number LIKE :search6)";
            $like = "%{$search}%";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
        }
        if ($fromDate !== '') {
            $where[] = 'bi.issue_date >= :from_date';
            $params['from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $where[] = 'bi.issue_date <= :to_date';
            $params['to_date'] = $toDate;
        }

        $whereSql = implode(' AND ', $where);
        $joinSql = "FROM `book_issues` bi
                     JOIN `books` b ON b.id = bi.book_id
                     LEFT JOIN `students` s ON s.id = bi.student_id
                     LEFT JOIN `teachers` te ON te.id = bi.teacher_id
                     WHERE {$whereSql}";

        $total = (int) ($this->raw("SELECT COUNT(*) AS c {$joinSql}", $params)[0]['c'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT bi.*, b.title AS book_title, b.accession_number, b.isbn,
                        s.full_name AS student_name, s.admission_number,
                        te.full_name AS teacher_name, te.employee_number,
                        CASE WHEN bi.due_date < CURDATE() THEN DATEDIFF(CURDATE(), bi.due_date) ELSE 0 END AS days_overdue
                 {$joinSql}
                 ORDER BY bi.due_date ASC
                 LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->raw($sql, $params);

        // Same reasoning as overdueList(): `fine_amount` on `book_issues`
        // is only finalized when a book is actually returned, so for books
        // still on loan it's always 0.00 in the database. Project it live
        // for any row that's currently overdue.
        if (!empty($data)) {
            $finePerDay = (float) ((new LibrarySetting())->current()['fine_per_day'] ?? 0);
            foreach ($data as &$row) {
                if ((int) $row['days_overdue'] > 0) {
                    $row['fine_amount'] = round((int) $row['days_overdue'] * $finePerDay, 2);
                }
            }
            unset($row);
        }

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function historyForStudent(int $studentId): array
    {
        return $this->raw(
            "SELECT bi.*, b.title AS book_title
             FROM `book_issues` bi JOIN `books` b ON b.id = bi.book_id
             WHERE bi.student_id = :sid ORDER BY bi.issue_date DESC",
            ['sid' => $studentId]
        );
    }

    public function historyForTeacher(int $teacherId): array
    {
        return $this->raw(
            "SELECT bi.*, b.title AS book_title
             FROM `book_issues` bi JOIN `books` b ON b.id = bi.book_id
             WHERE bi.teacher_id = :tid ORDER BY bi.issue_date DESC",
            ['tid' => $teacherId]
        );
    }

    /** How many books a borrower currently has out — enforces library_settings.max_books_*. */
    public function activeCountForBorrower(string $borrowerType, int $borrowerId): int
    {
        $column = $borrowerType === 'teacher' ? 'teacher_id' : 'student_id';
        $rows = $this->raw(
            "SELECT COUNT(*) AS c FROM `book_issues` WHERE `{$column}` = :id AND `status` = 'issued'",
            ['id' => $borrowerId]
        );
        return (int) ($rows[0]['c'] ?? 0);
    }

    /** Full loan history (issued, returned, lost) for one book — powers the Book Details screen. */
    public function historyForBook(int $bookId): array
    {
        return $this->raw(
            "SELECT bi.*, s.full_name AS student_name, s.admission_number, te.full_name AS teacher_name, te.employee_number
             FROM `book_issues` bi
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             WHERE bi.book_id = :bid
             ORDER BY bi.issue_date DESC",
            ['bid' => $bookId]
        );
    }

    public function overdueList(): array
    {
        $rows = $this->raw(
            "SELECT bi.*, b.title AS book_title, b.isbn,
                    s.full_name AS student_name, s.admission_number, c.name AS class_name, sec.name AS section_name,
                    te.full_name AS teacher_name, te.employee_number,
                    DATEDIFF(CURDATE(), bi.due_date) AS days_overdue
             FROM `book_issues` bi
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `classes` c ON c.id = s.class_id
             LEFT JOIN `sections` sec ON sec.id = s.section_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             WHERE bi.status = 'issued' AND bi.due_date < CURDATE()
             ORDER BY bi.due_date ASC"
        );

        // `fine_amount` on `book_issues` is only ever finalized when the
        // book is actually returned (see LibraryController::returnBook()),
        // so for books still on loan it's always 0.00 in the database.
        // For this report we want the fine the borrower would owe *right
        // now* if they returned it today, so project it live using the
        // same days-overdue x fine-per-day formula.
        if (!empty($rows)) {
            $finePerDay = (float) ((new LibrarySetting())->current()['fine_per_day'] ?? 0);
            foreach ($rows as &$row) {
                $row['fine_amount'] = round((int) $row['days_overdue'] * $finePerDay, 2);
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * Every library fine still owed, school-wide, right now — combines
     * unpaid fines on already-returned loans (fine_amount is finalized)
     * with unpaid fines on loans still out but overdue (fine_amount is
     * projected live the same way overdueList() does, since it's only
     * finalized in the DB once the book actually comes back). Feeds the
     * school dashboard's "Outstanding Fees" card.
     */
    public function totalOutstandingFines(): float
    {
        $finePerDay = (float) ((new LibrarySetting())->current()['fine_per_day'] ?? 0);
        $rows = $this->raw(
            "SELECT
                COALESCE(SUM(
                    CASE
                        WHEN status = 'returned' THEN fine_amount
                        WHEN status = 'issued' AND due_date < CURDATE() THEN DATEDIFF(CURDATE(), due_date) * :fine_per_day
                        ELSE 0
                    END
                ), 0) AS total
             FROM `book_issues`
             WHERE fine_paid = 0 AND status IN ('issued', 'returned')",
            ['fine_per_day' => $finePerDay]
        );
        return (float) ($rows[0]['total'] ?? 0);
    }

    // ------------------------------------------------------------------
    // Dashboard — Recent Activities feed
    // ------------------------------------------------------------------

    private const RECENT_ISSUE_SELECT = "
        SELECT bi.*, b.title AS book_title, b.isbn, b.cover_image, b.accession_number,
               s.full_name AS student_name, s.admission_number, s.photo_path AS student_photo,
               s.class_id, cl.name AS class_name, sec.name AS section_name,
               te.full_name AS teacher_name, te.employee_number,
               u.full_name AS issued_by_name,
               CASE WHEN bi.status = 'issued' AND bi.due_date < CURDATE()
                    THEN DATEDIFF(CURDATE(), bi.due_date) ELSE 0 END AS days_overdue
        FROM `book_issues` bi
        JOIN `books` b ON b.id = bi.book_id
        LEFT JOIN `students` s ON s.id = bi.student_id
        LEFT JOIN `classes` cl ON cl.id = s.class_id
        LEFT JOIN `sections` sec ON sec.id = s.section_id
        LEFT JOIN `teachers` te ON te.id = bi.teacher_id
        LEFT JOIN `users` u ON u.id = bi.issued_by
    ";

    /** Latest issues, newest first — powers "Recent Issued Books". */
    public function recentIssued(int $limit = 10): array
    {
        return $this->raw(self::RECENT_ISSUE_SELECT . " ORDER BY bi.created_at DESC LIMIT " . (int) $limit);
    }

    /** Latest returns, newest first — powers "Recent Returned Books". */
    public function recentReturned(int $limit = 10): array
    {
        $sql = "SELECT bi.*, b.title AS book_title, b.isbn, b.cover_image,
                       s.full_name AS student_name, s.admission_number,
                       te.full_name AS teacher_name, te.employee_number,
                       u.full_name AS returned_by_name,
                       fp.receipt_number AS fine_receipt_number
                FROM `book_issues` bi
                JOIN `books` b ON b.id = bi.book_id
                LEFT JOIN `students` s ON s.id = bi.student_id
                LEFT JOIN `teachers` te ON te.id = bi.teacher_id
                LEFT JOIN `users` u ON u.id = bi.returned_by
                LEFT JOIN `library_fine_payments` fp ON fp.book_issue_id = bi.id
                WHERE bi.status = 'returned'
                ORDER BY COALESCE(bi.returned_at, bi.return_date) DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /** How many books were returned today — dashboard stat card. */
    public function returnedTodayCount(): int
    {
        $rows = $this->raw("SELECT COUNT(*) AS c FROM `book_issues` WHERE status = 'returned' AND return_date = CURDATE()");
        return (int) ($rows[0]['c'] ?? 0);
    }

    /** Distinct borrowers (student or teacher) with issue activity in the last N days — "Active Members". */
    public function activeMembersCount(int $days = 90): int
    {
        $rows = $this->raw(
            "SELECT COUNT(DISTINCT COALESCE(CONCAT('s', student_id), CONCAT('t', teacher_id))) AS c
             FROM `book_issues`
             WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)",
            ['days' => $days]
        );
        return (int) ($rows[0]['c'] ?? 0);
    }

    /** Merged, timestamped feed of issue/return/lost events — powers "Recent Activities" and "Recent Notifications". */
    public function recentActivity(int $limit = 15): array
    {
        $sql = "SELECT bi.id, b.title AS book_title, b.isbn, b.cover_image,
                       s.full_name AS student_name, s.admission_number,
                       te.full_name AS teacher_name,
                       ui.full_name AS issued_by_name, ur.full_name AS returned_by_name,
                       bi.status, bi.created_at AS issue_ts, bi.returned_at,
                       CASE
                           WHEN bi.status = 'lost' THEN 'Lost'
                           WHEN bi.status = 'returned' THEN 'Returned'
                           ELSE 'Issued'
                       END AS activity_type,
                       COALESCE(bi.returned_at, bi.created_at) AS activity_at
                FROM `book_issues` bi
                JOIN `books` b ON b.id = bi.book_id
                LEFT JOIN `students` s ON s.id = bi.student_id
                LEFT JOIN `teachers` te ON te.id = bi.teacher_id
                LEFT JOIN `users` ui ON ui.id = bi.issued_by
                LEFT JOIN `users` ur ON ur.id = bi.returned_by
                ORDER BY activity_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }
}