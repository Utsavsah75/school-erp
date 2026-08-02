<?php

namespace App\Models;

use App\Core\Model;

/**
 * 1:1 with teachers (spec section 6 — Permissions). Stores a JSON map of
 * module => 0/1 overrides layered on top of the role-based
 * MODULE_PERMISSIONS defaults. Two-factor and role/password/username/
 * account status live on `users`, not here — see TeacherController.
 */
class TeacherPermission extends Model
{
    protected string $table = 'teacher_permissions';
    protected string $primaryKey = 'id';

    protected array $fillable = ['teacher_id', 'permissions'];

    public function forTeacher(int $teacherId): array|false
    {
        return $this->firstWhere(['teacher_id' => $teacherId]);
    }

    /** @param array<string,bool> $permissionsMap */
    public function save(int $teacherId, array $permissionsMap): void
    {
        $existing = $this->forTeacher($teacherId);
        $data = ['teacher_id' => $teacherId, 'permissions' => json_encode($permissionsMap)];
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    /** @return array<string,bool> */
    public function permissionsFor(int $teacherId): array
    {
        $row = $this->forTeacher($teacherId);
        if (!$row || empty($row['permissions'])) {
            return [];
        }
        $decoded = json_decode($row['permissions'], true);
        return is_array($decoded) ? $decoded : [];
    }
}
