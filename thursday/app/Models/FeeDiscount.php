<?php

namespace App\Models;

use App\Core\Model;

/** Full discount history per fee row — Fixed / Percentage / Scholarship / Sibling / Staff. */
class FeeDiscount extends Model
{
    protected string $table = 'fee_discounts';
    protected string $primaryKey = 'id';

    protected array $fillable = ['fee_id', 'type', 'value', 'amount', 'reason', 'given_by'];

    public const TYPES = [
        'fixed'       => 'Fixed Discount',
        'percentage'  => 'Percentage Discount',
        'scholarship' => 'Scholarship',
        'sibling'     => 'Sibling Discount',
        'staff'       => 'Staff Discount',
    ];

    public function forFee(int $feeId): array
    {
        return $this->raw(
            'SELECT d.*, u.full_name AS given_by_name FROM fee_discounts d
             LEFT JOIN users u ON u.id = d.given_by
             WHERE d.fee_id = :fid ORDER BY d.created_at DESC',
            ['fid' => $feeId]
        );
    }
}
