<?php

namespace App\Models;

use App\Core\Model;

/**
 * Percentage-band grading scale (A+, A, B+, ...) used to translate a
 * student's marks percentage into a letter grade. Soft-deletable via
 * `deleted_at`.
 */
class ExamGrade extends Model
{
    protected string $table = 'exam_grades';
    protected string $primaryKey = 'id';

    protected array $fillable = ['grade_name', 'grade_point', 'percent_from', 'percent_upto', 'remarks', 'status'];
    protected array $searchable = ['grade_name'];

    public function findActive(int $id): array|false
    {
        $rows = $this->raw('SELECT * FROM exam_grades WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /** Searched/paginated listing of non-deleted grades, highest band first. */
    public function paginateActive(int $page, int $perPage, string $search, string $statusFilter, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = ['deleted_at IS NULL'];
        $params = [];
        if (trim($search) !== '') {
            $where[] = '(grade_name LIKE :search1 OR remarks LIKE :search2)';
            $params['search1'] = $params['search2'] = '%' . trim($search) . '%';
        }
        if ($statusFilter !== '') {
            $where[] = 'status = :status';
            $params['status'] = $statusFilter;
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $allowedSort = ['grade_name' => 'grade_name', 'grade_point' => 'grade_point', 'percent_from' => 'percent_from'];
        $orderCol = $allowedSort[$sort] ?? 'percent_from';
        $orderDir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $countSql = "SELECT COUNT(*) AS c FROM exam_grades" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = "SELECT * FROM exam_grades" . $whereSql . " ORDER BY {$orderCol} {$orderDir} LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql, $params);

        return [
            'data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /**
     * True if [percent_from, percent_upto] overlaps any other non-deleted
     * grade band. Ranges are treated as inclusive on both ends.
     */
    public function rangeOverlaps(float $from, float $upto, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM exam_grades
                WHERE deleted_at IS NULL AND percent_from <= :upto AND percent_upto >= :from';
        $params = ['from' => $from, 'upto' => $upto];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE exam_grades SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteMany(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("UPDATE exam_grades SET deleted_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    public function toggleStatus(int $id): bool
    {
        $row = $this->findActive($id);
        if (!$row) {
            return false;
        }
        $next = $row['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $next]);
    }
}
