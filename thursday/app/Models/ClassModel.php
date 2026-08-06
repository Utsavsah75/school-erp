<?php

namespace App\Models;

use App\Core\Model;

/**
 * Maps to the `classes` table. Named ClassModel (not Class) because
 * "class" is a reserved word and cannot be used as a PHP class name.
 *
 * Classes & Sections module (MODULE_PERMISSIONS['classes']). Soft-deletable
 * via `deleted_at` — every read query below explicitly filters
 * `deleted_at IS NULL` since the base Model's generic find()/all()/where()
 * know nothing about soft deletes.
 */
class ClassModel extends Model
{
    protected string $table = 'classes';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name', 'code', 'display_order', 'academic_year_id', 'class_teacher_id',
        'room_number', 'capacity', 'shift', 'subject_group', 'class_monitor_name',
        'start_time', 'end_time', 'description', 'status', 'created_by', 'updated_by',
    ];
    protected array $searchable = ['name', 'code'];

    private const JOIN_SELECT = "
        SELECT cl.*, t.full_name AS teacher_name, ay.label AS academic_year_label
        FROM classes cl
        LEFT JOIN teachers t ON t.id = cl.class_teacher_id
        LEFT JOIN academic_years ay ON ay.id = cl.academic_year_id
    ";

    /** Top matches for the topbar Global Search — name/code, teacher/academic-year joined, soft-deleted rows excluded. */
    public function searchGlobal(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $t = '%' . $term . '%';
        $sql = self::JOIN_SELECT . " WHERE cl.deleted_at IS NULL AND (cl.name LIKE :t1 OR cl.code LIKE :t2)
                ORDER BY cl.display_order ASC, cl.name ASC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['t1' => $t, 't2' => $t]);
    }

    public function forAcademicYear(int $academicYearId): array
    {
        return $this->raw(
            'SELECT * FROM classes WHERE academic_year_id = :ay AND deleted_at IS NULL ORDER BY display_order ASC, name ASC',
            ['ay' => $academicYearId]
        );
    }

    /** Every non-deleted class, joined with teacher/academic-year names — for dropdowns and quick lists. */
    public function activeList(): array
    {
        return $this->raw(self::JOIN_SELECT . ' WHERE cl.deleted_at IS NULL ORDER BY cl.display_order ASC, cl.name ASC');
    }

    /** Fetch one row (joined) for the View/Edit pages, ignoring soft-deleted rows. */
    public function findActive(int $id): array|false
    {
        $rows = $this->raw(self::JOIN_SELECT . ' WHERE cl.id = :id AND cl.deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /** True if another (non-deleted) class already uses this code. */
    public function duplicateCode(string $code, ?int $excludeId = null): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS c FROM classes WHERE LOWER(code) = LOWER(:code) AND deleted_at IS NULL';
        $params = ['code' => $code];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /**
     * Filtered, searched, sorted, paginated listing with teacher/academic
     * year names already joined in for the "All Classes" table.
     */
    public function paginateWithJoins(int $page, int $perPage, array $filters, string $search, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilterWhere($filters, $search);

        $allowedSort = [
            'name' => 'cl.name', 'code' => 'cl.code', 'room_number' => 'cl.room_number',
            'capacity' => 'cl.capacity', 'shift' => 'cl.shift', 'status' => 'cl.status',
            'created_at' => 'cl.created_at',
        ];
        $orderCol = $allowedSort[$sort] ?? 'cl.display_order';
        $orderDir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $countSql = "SELECT COUNT(*) AS c FROM classes cl
                     LEFT JOIN teachers t ON t.id = cl.class_teacher_id" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = self::JOIN_SELECT . $whereSql . " ORDER BY {$orderCol} {$orderDir}, cl.name ASC LIMIT {$perPage} OFFSET {$offset}";
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
        $sql = self::JOIN_SELECT . $whereSql . ' ORDER BY cl.display_order ASC, cl.name ASC';
        return $this->raw($sql, $params);
    }

    private function buildFilterWhere(array $filters, string $search): array
    {
        $where = ['cl.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = 'cl.name LIKE :fname';
            $params['fname'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['teacher_id'])) {
            $where[] = 'cl.class_teacher_id = :teacher_id';
            $params['teacher_id'] = $filters['teacher_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'cl.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['academic_year_id'])) {
            $where[] = 'cl.academic_year_id = :academic_year_id';
            $params['academic_year_id'] = $filters['academic_year_id'];
        }
        if (!empty($filters['shift'])) {
            $where[] = 'cl.shift = :shift';
            $params['shift'] = $filters['shift'];
        }
        if (trim($search) !== '') {
            $where[] = '(cl.name LIKE :search1 OR cl.code LIKE :search2 OR t.full_name LIKE :search3)';
            $params['search1'] = $params['search2'] = $params['search3'] = '%' . trim($search) . '%';
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE classes SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
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
        $stmt = $this->pdo->prepare("UPDATE classes SET deleted_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /** Count of non-deleted classes — for the module's summary card. */
    public function activeCount(): int
    {
        return (int) ($this->raw('SELECT COUNT(*) AS c FROM classes WHERE deleted_at IS NULL')[0]['c'] ?? 0);
    }
}
