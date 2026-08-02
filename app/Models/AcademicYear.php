<?php

namespace App\Models;

use App\Core\Model;

class AcademicYear extends Model
{
    protected string $table = 'academic_years';
    protected string $primaryKey = 'id';

    protected array $fillable = ['label', 'start_date', 'end_date', 'is_current'];
    protected array $searchable = ['label'];

    public function current(): array|false
    {
        return $this->firstWhere(['is_current' => 1]);
    }

    /** Sets $id as the current academic year and un-sets all others. */
    public function setCurrent(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('UPDATE academic_years SET is_current = 0');
            $this->update($id, ['is_current' => 1]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
