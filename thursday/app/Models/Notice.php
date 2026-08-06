<?php

namespace App\Models;

use App\Core\Model;

/**
 * Notice Board module. Builds on the pre-existing `notices` table (kept
 * 100% backward compatible — see database/notice_board_module_migration.sql)
 * with category/priority/audience targeting, a publish/expiry window,
 * an attachment, a draft/published/archived status, and soft delete.
 *
 * Audience targeting works two ways at once, same as the rest of this app's
 * "who can see it" story:
 *   - `visible_to`  (existing SET column) — role-based visibility, used by
 *     Notice::visibleTo() which the Dashboard/Parent/Student widgets already
 *     call. Kept in sync automatically whenever a notice is saved.
 *   - `audience` + `class_id` + `section_id` + `student_id` — the granular
 *     "All / Students / Teachers / Parents / Staff / Class / Section /
 *     Individual Student" targeting the Notice Board form exposes.
 */
class Notice extends Model
{
    protected string $table = 'notices';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'title', 'body', 'category', 'priority', 'scope', 'audience',
        'class_id', 'section_id', 'student_id', 'visible_to',
        'publish_date', 'expiry_date', 'published_at',
        'attachment_path', 'attachment_name', 'status',
        'published_by', 'created_by', 'updated_by',
    ];
    protected array $searchable = ['title', 'body', 'category'];

    private const JOIN_SELECT = "
        SELECT n.*, c.name AS class_name, sec.name AS section_name,
               s.full_name AS student_name, u.full_name AS posted_by_name
        FROM notices n
        LEFT JOIN classes c ON c.id = n.class_id
        LEFT JOIN sections sec ON sec.id = n.section_id
        LEFT JOIN students s ON s.id = n.student_id
        LEFT JOIN users u ON u.id = n.published_by
    ";

    /** Role => audience values that role should see (used to build `visible_to`). */
    private const AUDIENCE_ROLE_MAP = [
        'all'      => ['super_admin', 'principal', 'vice_principal', 'accountant', 'teacher', 'class_teacher', 'librarian', 'receptionist', 'parent', 'student'],
        'students'  => ['super_admin', 'principal', 'vice_principal', 'teacher', 'class_teacher', 'student'],
        'teachers'  => ['super_admin', 'principal', 'vice_principal', 'teacher', 'class_teacher'],
        'parents'   => ['super_admin', 'principal', 'vice_principal', 'parent'],
        'staff'     => ['super_admin', 'principal', 'vice_principal', 'accountant', 'teacher', 'class_teacher', 'librarian', 'receptionist'],
        'class'     => ['super_admin', 'principal', 'vice_principal', 'teacher', 'class_teacher', 'parent', 'student'],
        'section'   => ['super_admin', 'principal', 'vice_principal', 'teacher', 'class_teacher', 'parent', 'student'],
        'student'   => ['super_admin', 'principal', 'vice_principal', 'teacher', 'class_teacher', 'parent', 'student'],
    ];

    /** Human label for an audience value, for the list table / badges. */
    public static function audienceLabel(string $audience): string
    {
        return match ($audience) {
            'all' => 'All', 'students' => 'Students', 'teachers' => 'Teachers',
            'parents' => 'Parents', 'staff' => 'Staff', 'class' => 'Class',
            'section' => 'Section', 'student' => 'Individual Student',
            default => ucfirst($audience),
        };
    }

    public static function audienceOptions(): array
    {
        return [
            'all' => 'All', 'students' => 'Students', 'teachers' => 'Teachers',
            'parents' => 'Parents', 'staff' => 'Staff', 'class' => 'Class',
            'section' => 'Section', 'student' => 'Individual Student',
        ];
    }

    public static function categoryOptions(): array
    {
        return ['General', 'Academic', 'Exam', 'Fee', 'Event', 'Holiday', 'Sports', 'Emergency', 'Other'];
    }

    public static function priorityOptions(): array
    {
        return ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
    }

    public static function statusOptions(): array
    {
        return ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    }

    /** The `visible_to` SET string for a given audience (keeps the legacy role-based widgets in sync). */
    public static function visibleToForAudience(string $audience): string
    {
        return implode(',', self::AUDIENCE_ROLE_MAP[$audience] ?? self::AUDIENCE_ROLE_MAP['all']);
    }

    /** Fetch one row (joined) for the Notice Details / Edit page, ignoring soft-deleted rows. */
    public function findActive(int $id): array|false
    {
        $rows = $this->raw(self::JOIN_SELECT . ' WHERE n.id = :id AND n.deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /**
     * Filtered, searched, sorted, paginated listing with class/section/
     * student/poster names already joined in for the table.
     */
    public function paginateWithJoins(int $page, int $perPage, array $filters, string $search, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = ['n.deleted_at IS NULL'];
        $params = [];

        $map = ['category' => 'n.category', 'class_id' => 'n.class_id', 'section_id' => 'n.section_id', 'status' => 'n.status', 'priority' => 'n.priority'];
        foreach ($map as $key => $col) {
            if (!empty($filters[$key])) {
                $where[] = "{$col} = :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['publish_date'])) {
            $where[] = 'n.publish_date = :publish_date';
            $params['publish_date'] = $filters['publish_date'];
        }
        if (trim($search) !== '') {
            $where[] = '(n.title LIKE :search1 OR n.body LIKE :search2 OR n.category LIKE :search3)';
            $params['search1'] = $params['search2'] = $params['search3'] = '%' . trim($search) . '%';
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $allowedSort = [
            'title' => 'n.title', 'category' => 'n.category', 'priority' => 'n.priority',
            'publish_date' => 'n.publish_date', 'expiry_date' => 'n.expiry_date',
            'status' => 'n.status', 'posted_by' => 'u.full_name',
        ];
        $orderCol = $allowedSort[$sort] ?? 'n.publish_date';
        $orderDir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $countSql = "SELECT COUNT(*) AS c FROM notices n LEFT JOIN users u ON u.id = n.published_by" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = self::JOIN_SELECT . $whereSql . " ORDER BY {$orderCol} {$orderDir}, n.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql, $params);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Same filters as paginateWithJoins() but returns every matching row (for Print / Export). */
    public function allWithJoins(array $filters, string $search): array
    {
        $where = ['n.deleted_at IS NULL'];
        $params = [];
        $map = ['category' => 'n.category', 'class_id' => 'n.class_id', 'section_id' => 'n.section_id', 'status' => 'n.status', 'priority' => 'n.priority'];
        foreach ($map as $key => $col) {
            if (!empty($filters[$key])) {
                $where[] = "{$col} = :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['publish_date'])) {
            $where[] = 'n.publish_date = :publish_date';
            $params['publish_date'] = $filters['publish_date'];
        }
        if (trim($search) !== '') {
            $where[] = '(n.title LIKE :search1 OR n.body LIKE :search2)';
            $params['search1'] = $params['search2'] = '%' . trim($search) . '%';
        }
        $sql = self::JOIN_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY n.publish_date DESC, n.id DESC';
        return $this->raw($sql, $params);
    }

    /**
     * Notices that are currently live: published, publish_date has arrived,
     * and (no expiry_date OR expiry_date hasn't passed).
     */
    public function activeAndPublished(int $limit = 10, ?string $priority = null): array
    {
        $sql = self::JOIN_SELECT . "
            WHERE n.deleted_at IS NULL AND n.status = 'published'
              AND n.publish_date <= CURDATE()
              AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())";
        $params = [];
        if ($priority) {
            $sql .= ' AND n.priority = :priority';
            $params['priority'] = $priority;
        }
        $sql .= ' ORDER BY n.publish_date DESC, n.id DESC LIMIT ' . (int) $limit;
        return $this->raw($sql, $params);
    }

    /** Notices visible to a given role, most recent first — excludes soft-deleted/expired/unpublished rows. */
    public function visibleTo(string $role, int $limit = 10): array
    {
        $sql = "SELECT * FROM notices
                WHERE deleted_at IS NULL AND status = 'published'
                  AND publish_date <= CURDATE()
                  AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                  AND FIND_IN_SET(:role, visible_to) > 0
                ORDER BY published_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql, ['role' => $role]);
    }

    /**
     * Top matches for the topbar Global Search — title/body/category, restricted
     * to published notices this role is allowed to see (same rule as visibleTo()).
     */
    public function searchGlobal(string $term, string $role, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $t = '%' . $term . '%';
        $sql = self::JOIN_SELECT . " WHERE n.deleted_at IS NULL AND n.status = 'published'
                AND FIND_IN_SET(:role, n.visible_to) > 0
                AND (n.title LIKE :t1 OR n.body LIKE :t2 OR n.category LIKE :t3)
                ORDER BY n.published_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['role' => $role, 't1' => $t, 't2' => $t, 't3' => $t]);
    }

    public function recent(int $limit = 5): array
    {
        $sql = "SELECT * FROM notices
                WHERE deleted_at IS NULL AND status = 'published'
                  AND publish_date <= CURDATE()
                  AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                ORDER BY published_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE notices SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
