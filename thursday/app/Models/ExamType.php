<?php

namespace App\Models;

use App\Core\Model;

/**
 * Exam Types lookup (Terminal, Mid Term, Final, Weekly, Monthly, Half
 * Yearly, Annual, Practical, ...) — feeds the Exam Schedule form's
 * "Exam Type" dropdown. Soft-deletable via `deleted_at`.
 */
class ExamType extends Model
{
    protected string $table = 'exam_types';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'description', 'is_active'];
    protected array $searchable = ['name'];

    /** All non-deleted types, active first, alphabetical within each group. */
    public function allActive(): array
    {
        return $this->raw(
            "SELECT * FROM exam_types WHERE deleted_at IS NULL ORDER BY is_active DESC, name ASC"
        );
    }

    /** Only active types — used to populate the Exam Schedule create form. */
    public function forDropdown(): array
    {
        return $this->raw(
            "SELECT * FROM exam_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC"
        );
    }

    public function findActive(int $id): array|false
    {
        $rows = $this->raw('SELECT * FROM exam_types WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
        return $rows[0] ?? false;
    }

    /** Case-insensitive duplicate name check among non-deleted rows. */
    public function duplicateName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM exam_types WHERE deleted_at IS NULL AND LOWER(name) = LOWER(:name)';
        $params = ['name' => trim($name)];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** True if any exam_schedule row (soft-deleted or not) still references this type. */
    public function isInUse(int $id): bool
    {
        $rows = $this->raw('SELECT COUNT(*) AS c FROM exam_schedule WHERE exam_type_id = :id', ['id' => $id]);
        return (int) ($rows[0]['c'] ?? 0) > 0;
    }

    /** Soft delete — overrides the base hard-delete. */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->query('UPDATE exam_types SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
