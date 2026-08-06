<?php

namespace App\Models;

use App\Core\Model;

/**
 * Merged, newest-first feed of every event that should show up in the
 * Library Dashboard's "Recent Library Activities" and "Recent
 * Notifications" widgets: book issued/returned/lost, reservations
 * created/cancelled, books added/updated/deleted, fines collected, and
 * students added/updated.
 *
 * Each branch of the UNION is normalized to the same column list so the
 * whole feed can be sorted with a single `ORDER BY activity_at DESC` —
 * i.e. newest event first, no matter which table it came from. This is
 * the single source of truth both dashboard widgets read from, so they
 * always stay in sync with each other.
 */
class LibraryActivity extends Model
{
    protected string $table = 'book_issues';
    protected string $primaryKey = 'id';

    /**
     * @return array<int,array{
     *   activity_at:string, activity_type:string, entity_type:string, entity_id:int,
     *   book_title:?string, isbn:?string, cover_image:?string,
     *   borrower_name:?string, admission_number:?string,
     *   actor_name:?string, status:string
     * }>
     */
    public function recent(int $limit = 10): array
    {
        return $this->raw(self::feedSql() . " ORDER BY activity_at DESC LIMIT " . (int) $limit);
    }

    /**
     * Paginated, date-filtered, activity_type-filtered version of recent() —
     * powers the dashboard stat cards' "History" pages (e.g. Total Books,
     * Total Book Copies) via LibraryController::history(). Wraps the same
     * feed SQL in a subquery so it can be filtered/paginated without
     * duplicating the 11-way UNION.
     *
     * @param string[] $types activity_type values to include (e.g. ['book_added','book_updated','book_deleted'])
     * @return array{data:array<int,array>,total:int,page:int,per_page:int,last_page:int}
     */
    public function history(array $types, int $page, int $perPage, string $fromDate = '', string $toDate = ''): array
    {
        $typeParams = [];
        $typePlaceholders = [];
        foreach (array_values($types) as $i => $type) {
            $key = "type{$i}";
            $typePlaceholders[] = ":{$key}";
            $typeParams[$key] = $type;
        }
        $where = ['t.activity_type IN (' . implode(',', $typePlaceholders) . ')'];
        $params = $typeParams;

        if ($fromDate !== '') {
            $where[] = 't.activity_at >= :from_date';
            $params['from_date'] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $where[] = 't.activity_at <= :to_date';
            $params['to_date'] = $toDate . ' 23:59:59';
        }
        $whereSql = implode(' AND ', $where);

        $feedSql = self::feedSql();
        $total = (int) ($this->raw("SELECT COUNT(*) AS c FROM ({$feedSql}) t WHERE {$whereSql}", $params)[0]['c'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $data = $this->raw(
            "SELECT * FROM ({$feedSql}) t WHERE {$whereSql} ORDER BY t.activity_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /** The 11-way UNION that both recent() and history() select from (no ORDER BY/LIMIT — callers add their own). */
    private static function feedSql(): string
    {
        return "
            (SELECT bi.created_at AS activity_at, 'issued' AS activity_type, 'book_issue' AS entity_type, bi.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    ui.full_name AS actor_name, 'issued' AS status
             FROM `book_issues` bi
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             LEFT JOIN `users` ui ON ui.id = bi.issued_by)

            UNION ALL

            (SELECT COALESCE(bi.returned_at, TIMESTAMP(bi.return_date)) AS activity_at, 'returned' AS activity_type, 'book_issue' AS entity_type, bi.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    ur.full_name AS actor_name, 'returned' AS status
             FROM `book_issues` bi
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             LEFT JOIN `users` ur ON ur.id = bi.returned_by
             WHERE bi.status = 'returned')

            UNION ALL

            (SELECT COALESCE(bi.returned_at, bi.created_at) AS activity_at, 'lost' AS activity_type, 'book_issue' AS entity_type, bi.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    ur.full_name AS actor_name, 'lost' AS status
             FROM `book_issues` bi
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = bi.student_id
             LEFT JOIN `teachers` te ON te.id = bi.teacher_id
             LEFT JOIN `users` ur ON ur.id = bi.returned_by
             WHERE bi.status = 'lost')

            UNION ALL

            (SELECT r.created_at AS activity_at, 'reserved' AS activity_type, 'reservation' AS entity_type, r.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    NULL AS actor_name, r.status
             FROM `library_reservations` r
             JOIN `books` b ON b.id = r.book_id
             LEFT JOIN `students` s ON s.id = r.student_id
             LEFT JOIN `teachers` te ON te.id = r.teacher_id)

            UNION ALL

            (SELECT r.updated_at AS activity_at, 'reservation_cancelled' AS activity_type, 'reservation' AS entity_type, r.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    NULL AS actor_name, r.status
             FROM `library_reservations` r
             JOIN `books` b ON b.id = r.book_id
             LEFT JOIN `students` s ON s.id = r.student_id
             LEFT JOIN `teachers` te ON te.id = r.teacher_id
             WHERE r.status = 'cancelled')

            UNION ALL

            (SELECT b.created_at AS activity_at, 'book_added' AS activity_type, 'book' AS entity_type, b.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    NULL AS borrower_name, NULL AS admission_number,
                    NULL AS actor_name, 'active' AS status
             FROM `books` b)

            UNION ALL

            (SELECT b.updated_at AS activity_at, 'book_updated' AS activity_type, 'book' AS entity_type, b.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    NULL AS borrower_name, NULL AS admission_number,
                    ub.full_name AS actor_name, 'active' AS status
             FROM `books` b
             LEFT JOIN `users` ub ON ub.id = b.updated_by
             WHERE b.updated_at IS NOT NULL AND b.updated_at > b.created_at)

            UNION ALL

            (SELECT b.deleted_at AS activity_at, 'book_deleted' AS activity_type, 'book' AS entity_type, b.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    NULL AS borrower_name, NULL AS admission_number,
                    NULL AS actor_name, 'deleted' AS status
             FROM `books` b
             WHERE b.deleted_at IS NOT NULL)

            UNION ALL

            (SELECT fp.paid_at AS activity_at, 'fine_collected' AS activity_type, 'fine_payment' AS entity_type, fp.id AS entity_id,
                    b.title AS book_title, b.isbn, b.cover_image,
                    COALESCE(s.full_name, te.full_name) AS borrower_name, s.admission_number,
                    ur.full_name AS actor_name, 'paid' AS status
             FROM `library_fine_payments` fp
             JOIN `book_issues` bi ON bi.id = fp.book_issue_id
             JOIN `books` b ON b.id = bi.book_id
             LEFT JOIN `students` s ON s.id = fp.student_id
             LEFT JOIN `teachers` te ON te.id = fp.teacher_id
             LEFT JOIN `users` ur ON ur.id = fp.received_by)

            UNION ALL

            (SELECT s.created_at AS activity_at, 'student_added' AS activity_type, 'student' AS entity_type, s.id AS entity_id,
                    NULL AS book_title, NULL AS isbn, NULL AS cover_image,
                    s.full_name AS borrower_name, s.admission_number,
                    cu.full_name AS actor_name, s.status
             FROM `students` s
             LEFT JOIN `users` cu ON cu.id = s.created_by)

            UNION ALL

            (SELECT s.updated_at AS activity_at, 'student_updated' AS activity_type, 'student' AS entity_type, s.id AS entity_id,
                    NULL AS book_title, NULL AS isbn, NULL AS cover_image,
                    s.full_name AS borrower_name, s.admission_number,
                    uu.full_name AS actor_name, s.status
             FROM `students` s
             LEFT JOIN `users` uu ON uu.id = s.updated_by
             WHERE s.updated_at IS NOT NULL AND s.updated_at > s.created_at)
        ";
    }
}
