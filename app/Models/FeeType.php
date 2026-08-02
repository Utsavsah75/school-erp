<?php

namespace App\Models;

use App\Core\Model;

/**
 * Fee Types — the catalogue of chargeable fee heads (Admission, Tuition,
 * Monthly, Annual, Exam, Hostel, Transport, Library, Computer, Lab,
 * Sports, Misc, Fine), each with a recurrence pattern that Bulk Fee
 * Assignment and the business-rule checks in Fee::assign() rely on.
 */
class FeeType extends Model
{
    protected string $table = 'fee_types';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name', 'category', 'recurrence_type', 'default_amount', 'is_active', 'is_recurring',
    ];

    protected array $searchable = ['name'];

    public const CATEGORIES = [
        'admission' => 'Admission Fee',
        'tuition'   => 'Tuition Fee',
        'monthly'   => 'Monthly Fee',
        'annual'    => 'Annual Fee',
        'exam'      => 'Exam Fee',
        'hostel'    => 'Hostel Fee',
        'transport' => 'Transport Fee',
        'library'   => 'Library Fee',
        'computer'  => 'Computer Fee',
        'lab'       => 'Lab Fee',
        'sports'    => 'Sports Fee',
        'misc'      => 'Miscellaneous Fee',
        'fine'      => 'Fine',
    ];

    public const RECURRENCE_TYPES = [
        'one_time'    => 'One-Time',
        'monthly'     => 'Monthly',
        'quarterly'   => 'Quarterly',
        'half_yearly' => 'Half-Yearly',
        'yearly'      => 'Yearly',
    ];

    /** Business rule: these categories may only be charged once per student per academic session. */
    public const ONE_TIME_ONLY_CATEGORIES = ['admission', 'annual'];

    /** Business rule: these categories bill monthly and need a `month` on the fee row. */
    public const MONTHLY_CATEGORIES = ['tuition', 'monthly', 'hostel', 'transport'];

    public function active(): array
    {
        return $this->raw('SELECT * FROM fee_types WHERE is_active = 1 ORDER BY category ASC, name ASC');
    }

    public function duplicateName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM fee_types WHERE name = :name';
        $params = ['name' => $name];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        return !empty($this->raw($sql, $params));
    }

    /** Whether any fee has ever been assigned against this fee type (guards deletion). */
    public function isInUse(int $feeTypeId): bool
    {
        $row = $this->raw('SELECT COUNT(*) AS c FROM fees WHERE fee_type_id = :id', ['id' => $feeTypeId]);
        return (int) ($row[0]['c'] ?? 0) > 0;
    }
}
