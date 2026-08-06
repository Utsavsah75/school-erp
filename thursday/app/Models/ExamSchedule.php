<?php

namespace App\Models;

use App\Core\Model;

/**
 * One row per Class + Section + Subject exam sitting (Exam Schedule
 * module). Soft-deletable via `deleted_at`; every read query below
 * explicitly filters `deleted_at IS NULL` since the base Model's
 * generic find()/all()/where() know nothing about soft deletes.
 */
class ExamSchedule extends Model
{
    protected string $table = 'exam_schedule';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'exam_name', 'academic_year_id', 'exam_type_id', 'class_id', 'section_id', 'subject_id',
        'exam_date', 'start_time', 'end_time', 'room_number', 'max_marks', 'passing_marks',
        'status', 'description', 'created_by', 'updated_by',
    ];
    protected array $searchable = ['exam_name'];

    private const JOIN_SELECT = "
        SELECT es.*, c.name AS class_name, sec.name AS section_name, sub.name AS subject_name,
               sub.code AS subject_code, et.name AS exam_type_name, ay.label AS academic_year_label
        FROM exam_schedule es
        LEFT JOIN classes c ON c.id = es.class_id
        LEFT JOIN sections sec ON sec.id = es.section_id
        LEFT JOIN subjects sub ON sub.id = es.subject_id
        LEFT JOIN exam_types et ON et.id = es.exam_type_id
        LEFT JOIN academic_years ay ON ay.id = es.academic_year_id
    ";

    /** Fetch one row (joined) for the View action / edit prefill, ignoring soft-deleted rows. */
    public function findActive(int $id): array|false
    {
        $rows = $this->raw(self::JOIN_SELECT . ' WHERE es.id = :id AND es.deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /**
     * Filtered, searched, sorted, paginated listing with class/section/
     * subject/exam-type names already joined in for the table.
     */
    public function paginateWithJoins(int $page, int $perPage, array $filters, string $search, string $sort, string $direction): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = ['es.deleted_at IS NULL'];
        $params = [];

        $map = [
            'class_id' => 'es.class_id', 'section_id' => 'es.section_id',
            'subject_id' => 'es.subject_id', 'exam_type_id' => 'es.exam_type_id',
            'academic_year_id' => 'es.academic_year_id', 'status' => 'es.status',
        ];
        foreach ($map as $key => $col) {
            if (!empty($filters[$key])) {
                $where[] = "{$col} = :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['date'])) {
            $where[] = 'es.exam_date = :date';
            $params['date'] = $filters['date'];
        }
        if (trim($search) !== '') {
            $where[] = '(es.exam_name LIKE :search1 OR sub.name LIKE :search2 OR es.room_number LIKE :search3)';
            $params['search1'] = $params['search2'] = $params['search3'] = '%' . trim($search) . '%';
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $allowedSort = [
            'exam_name' => 'es.exam_name', 'exam_date' => 'es.exam_date',
            'class' => 'c.display_order', 'subject' => 'sub.name', 'status' => 'es.status',
        ];
        $orderCol = $allowedSort[$sort] ?? 'es.exam_date';
        $orderDir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $countSql = "SELECT COUNT(*) AS c FROM exam_schedule es
                     LEFT JOIN classes c ON c.id = es.class_id
                     LEFT JOIN subjects sub ON sub.id = es.subject_id" . $whereSql;
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $sql = self::JOIN_SELECT . $whereSql . " ORDER BY {$orderCol} {$orderDir}, es.start_time ASC LIMIT {$perPage} OFFSET {$offset}";
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
        $where = ['es.deleted_at IS NULL'];
        $params = [];
        $map = [
            'class_id' => 'es.class_id', 'section_id' => 'es.section_id',
            'subject_id' => 'es.subject_id', 'exam_type_id' => 'es.exam_type_id',
            'academic_year_id' => 'es.academic_year_id', 'status' => 'es.status',
        ];
        foreach ($map as $key => $col) {
            if (!empty($filters[$key])) {
                $where[] = "{$col} = :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['date'])) {
            $where[] = 'es.exam_date = :date';
            $params['date'] = $filters['date'];
        }
        if (trim($search) !== '') {
            $where[] = '(es.exam_name LIKE :search1 OR sub.name LIKE :search2)';
            $params['search1'] = $params['search2'] = '%' . trim($search) . '%';
        }
        $sql = self::JOIN_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY es.exam_date ASC, es.start_time ASC';
        return $this->raw($sql, $params);
    }

    /**
     * Duplicate guard: same class + section + subject + exam type on the
     * same date already scheduled.
     */
    public function duplicateExists(array $d, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM exam_schedule
                WHERE deleted_at IS NULL AND class_id = :class_id AND section_id = :section_id
                  AND subject_id = :subject_id AND exam_type_id = :exam_type_id AND exam_date = :exam_date';
        $params = [
            'class_id' => $d['class_id'], 'section_id' => $d['section_id'],
            'subject_id' => $d['subject_id'], 'exam_type_id' => $d['exam_type_id'], 'exam_date' => $d['exam_date'],
        ];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /**
     * Overlap guard: same class + section already has another exam whose
     * time range overlaps this one on the same date (a class can't sit two
     * different subject exams at once).
     */
    public function overlappingExists(array $d, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM exam_schedule
                WHERE deleted_at IS NULL AND class_id = :class_id AND section_id = :section_id
                  AND exam_date = :exam_date AND start_time < :end_time AND end_time > :start_time';
        $params = [
            'class_id' => $d['class_id'], 'section_id' => $d['section_id'], 'exam_date' => $d['exam_date'],
            'start_time' => $d['start_time'], 'end_time' => $d['end_time'],
        ];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE exam_schedule SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
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
        $stmt = $this->pdo->prepare("UPDATE exam_schedule SET deleted_at = NOW() WHERE id IN ({$placeholders})");
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
