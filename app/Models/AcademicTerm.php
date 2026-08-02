<?php

namespace App\Models;

use App\Core\Model;

/** Term 1 / Term 2 / Term 3 etc, scoped to one academic_years row. */
class AcademicTerm extends Model
{
    protected string $table = 'academic_terms';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'academic_year_id', 'name', 'start_date', 'end_date', 'display_order',
    ];

    protected array $searchable = ['name'];

    public function forYear(int $academicYearId): array
    {
        return $this->raw(
            'SELECT * FROM academic_terms WHERE academic_year_id = :ay ORDER BY display_order ASC, id ASC',
            ['ay' => $academicYearId]
        );
    }
}
