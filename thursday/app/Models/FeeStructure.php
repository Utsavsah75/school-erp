<?php

namespace App\Models;

use App\Core\Model;

/**
 * Default fee amount per (academic year, class, fee type[, term]).
 * Bulk Fee Assignment looks these up to prefill the "Amount" column;
 * the assigning user can still override per student before saving.
 */
class FeeStructure extends Model
{
    protected string $table = 'fee_structures';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'academic_year_id', 'class_id', 'fee_type_id', 'term_id', 'amount',
    ];

    /** The configured amount for a class + fee type (+ term), or null if none is set. */
    public function lookup(int $academicYearId, int $classId, int $feeTypeId, ?int $termId = null): ?float
    {
        $sql = 'SELECT amount FROM fee_structures
                WHERE academic_year_id = :ay AND class_id = :cid AND fee_type_id = :ftid
                AND ' . ($termId === null ? 'term_id IS NULL' : 'term_id = :tid');
        $params = ['ay' => $academicYearId, 'cid' => $classId, 'ftid' => $feeTypeId];
        if ($termId !== null) {
            $params['tid'] = $termId;
        }
        $row = $this->raw($sql, $params);
        return isset($row[0]) ? (float) $row[0]['amount'] : null;
    }

    public function forClass(int $academicYearId, int $classId): array
    {
        $sql = "SELECT fs.*, ft.name AS fee_type_name, ft.category, t.name AS term_name
                FROM fee_structures fs
                JOIN fee_types ft ON ft.id = fs.fee_type_id
                LEFT JOIN academic_terms t ON t.id = fs.term_id
                WHERE fs.academic_year_id = :ay AND fs.class_id = :cid
                ORDER BY ft.category ASC";
        return $this->raw($sql, ['ay' => $academicYearId, 'cid' => $classId]);
    }

    /** Insert or update the amount for a given combination (used by the Fee Structure editor). */
    public function upsert(int $academicYearId, int $classId, int $feeTypeId, ?int $termId, float $amount): void
    {
        $existing = $this->raw(
            'SELECT id FROM fee_structures WHERE academic_year_id = :ay AND class_id = :cid AND fee_type_id = :ftid AND '
            . ($termId === null ? 'term_id IS NULL' : 'term_id = :tid'),
            $termId === null
                ? ['ay' => $academicYearId, 'cid' => $classId, 'ftid' => $feeTypeId]
                : ['ay' => $academicYearId, 'cid' => $classId, 'ftid' => $feeTypeId, 'tid' => $termId]
        );

        if (!empty($existing)) {
            $this->update((int) $existing[0]['id'], ['amount' => $amount]);
            return;
        }

        $this->insert([
            'academic_year_id' => $academicYearId,
            'class_id'         => $classId,
            'fee_type_id'      => $feeTypeId,
            'term_id'          => $termId,
            'amount'           => $amount,
        ]);
    }
}
