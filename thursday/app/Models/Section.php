<?php

namespace App\Models;

use App\Core\Model;

/**
 * Classes & Sections module (MODULE_PERMISSIONS['sections']). Soft-deletable
 * via `deleted_at` — every read query below explicitly filters
 * `deleted_at IS NULL` since the base Model's generic find()/all()/where()
 * know nothing about soft deletes.
 */
class Section extends Model
{
    protected string $table = 'sections';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'class_id', 'name', 'code', 'class_teacher_id', 'room_number', 'capacity',
        'shift', 'description', 'status', 'created_by', 'updated_by',
    ];
    protected array $searchable = ['name', 'code', 'room_number'];

    private const JOIN_SELECT = "
        SELECT sec.*, c.name AS class_name, c.academic_year_id AS academic_year_id,
               t.full_name AS teacher_name, ay.label AS academic_year_label
        FROM sections sec
        LEFT JOIN classes c ON c.id = sec.class_id
        LEFT JOIN teachers t ON t.id = sec.class_teacher_id
        LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
    ";

    /** Top matches for the topbar Global Search — name/code/room number, class/teacher joined, soft-deleted rows excluded. */
    public function searchGlobal(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $t = '%' . $term . '%';
        $sql = self::JOIN_SELECT . " WHERE sec.deleted_at IS NULL
                AND (sec.name LIKE :t1 OR sec.code LIKE :t2 OR sec.room_number LIKE :t3)
                ORDER BY sec.name ASC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['t1' => $t, 't2' => $t, 't3' => $t]);
    }

    public function forClass(int $classId): array
    {
        return $this->raw(
            'SELECT * FROM sections WHERE class_id = :cid AND deleted_at IS NULL ORDER BY name ASC',
            ['cid' => $classId]
        );
    }

    /** Fetch one row (joined) for the View/Edit pages, ignoring soft-deleted rows. */
    public function findActive(int $id): array|false
    {
        $rows = $this->raw(self::JOIN_SELECT . ' WHERE sec.id = :id AND sec.deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /** Every non-deleted section, joined — for dropdowns and quick lists. */
    public function activeList(): array
    {
        return $this->raw(self::JOIN_SELECT . ' WHERE sec.deleted_at IS NULL ORDER BY c.display_order ASC, sec.name ASC');
    }

    /** True if another (non-deleted) section already uses this code. */
    public function duplicateCode(string $code, ?int $excludeId = null): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS c FROM sections WHERE LOWER(code) = LOWER(:code) AND deleted_at IS NULL';
        $params = ['code' => $code];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /**
     * Filtered, searched, sorted, paginated listing with class/teacher
     * names already joined in for the "All Sections" table.
     */
    public function paginateWithJoins(int $page, int $perPage, array $filters, string $search, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilterWhere($filters, $search);

        $allowedSort = [
            'name' => 'sec.name', 'code' => 'sec.code', 'class' => 'c.display_order',
            'room_number' => 'sec.room_number', 'capacity' => 'sec.capacity', 'status' => 'sec.status',
            'created_at' => 'sec.created_at',
        ];
        $orderCol = $allowedSort[$sort] ?? 'c.display_order';
        $orderDir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $countSql = "SELECT COUNT(*) AS c FROM sections sec
                     LEFT JOIN classes c ON c.id = sec.class_id
                     LEFT JOIN teachers t ON t.id = sec.class_teacher_id" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = self::JOIN_SELECT . $whereSql . " ORDER BY {$orderCol} {$orderDir}, sec.name ASC LIMIT {$perPage} OFFSET {$offset}";
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
        [$whereSql, $params] = $this->buildFilterWhere($filters, $search);
        $sql = self::JOIN_SELECT . $whereSql . ' ORDER BY c.display_order ASC, sec.name ASC';
        return $this->raw($sql, $params);
    }

    private function buildFilterWhere(array $filters, string $search): array
    {
        $where = ['sec.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['class_id'])) {
            $where[] = 'sec.class_id = :class_id';
            $params['class_id'] = $filters['class_id'];
        }
        if (!empty($filters['teacher_id'])) {
            $where[] = 'sec.class_teacher_id = :teacher_id';
            $params['teacher_id'] = $filters['teacher_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'sec.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['academic_year_id'])) {
            $where[] = 'c.academic_year_id = :academic_year_id';
            $params['academic_year_id'] = $filters['academic_year_id'];
        }
        if (trim($search) !== '') {
            $where[] = '(sec.name LIKE :search1 OR sec.code LIKE :search2 OR c.name LIKE :search3)';
            $params['search1'] = $params['search2'] = $params['search3'] = '%' . trim($search) . '%';
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE sections SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Bulk soft delete by id list. */
    public function deleteMany(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("UPDATE sections SET deleted_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /** Count of non-deleted sections — for the module's summary card. */
    public function activeCount(): int
    {
        return (int) ($this->raw('SELECT COUNT(*) AS c FROM sections WHERE deleted_at IS NULL')[0]['c'] ?? 0);
    }
}
