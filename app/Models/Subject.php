<?php

namespace App\Models;

use App\Core\Model;

class Subject extends Model
{
    protected string $table = 'subjects';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'code', 'class_id', 'subject_type', 'is_elective', 'is_active'];
    protected array $searchable = ['name', 'code'];

    public function forClass(int $classId): array
    {
        return $this->where(['class_id' => $classId], 'name', 'ASC');
    }

    /** True if another subject with this name already exists in this class (case-insensitive). */
    public function duplicateInClass(string $name, int $classId, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM subjects WHERE class_id = :cid AND LOWER(name) = LOWER(:name)';
        $params = ['cid' => $classId, 'name' => trim($name)];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** Paginated subject list joined with the class name, with optional class/type filters + code/name search. */
    public function paginateWithClass(int $page, int $perPage, array $filters, string $search, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if (!empty($filters['class_id'])) {
            $where[] = 's.class_id = :class_id';
            $params['class_id'] = $filters['class_id'];
        }
        if (!empty($filters['subject_type'])) {
            $where[] = 's.subject_type = :subject_type';
            $params['subject_type'] = $filters['subject_type'];
        }
        if (trim($search) !== '') {
            $where[] = '(s.code LIKE :search OR s.name LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $allowedSort = ['code' => 's.code', 'name' => 's.name', 'class' => 'c.display_order'];
        $orderCol = $allowedSort[$sort] ?? 's.code';
        $orderDir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $countSql = "SELECT COUNT(*) AS c FROM subjects s LEFT JOIN classes c ON c.id = s.class_id" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = "SELECT s.*, c.name AS class_name FROM subjects s LEFT JOIN classes c ON c.id = s.class_id"
            . $whereSql . " ORDER BY {$orderCol} {$orderDir}, s.name ASC LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql, $params);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Bulk delete by id list (used by the checkbox "bulk delete" action). */
    public function deleteMany(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM subjects WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
}
