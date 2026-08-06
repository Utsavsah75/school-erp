<?php

namespace App\Models;

use App\Core\Model;

/** Full fine history per fee row — Fixed / Daily / Percentage, with manual-override support. */
class FeeFine extends Model
{
    protected string $table = 'fee_fines';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'fee_id', 'type', 'rate', 'amount', 'applied_on', 'is_manual',
        'waived_amount', 'waived_by', 'notes',
    ];

    public const TYPES = [
        'fixed'      => 'Fixed Fine',
        'daily'      => 'Daily Fine',
        'percentage' => 'Percentage Fine',
    ];

    public function forFee(int $feeId): array
    {
        return $this->raw(
            'SELECT f.*, u.full_name AS waived_by_name FROM fee_fines f
             LEFT JOIN users u ON u.id = f.waived_by
             WHERE f.fee_id = :fid ORDER BY f.created_at DESC',
            ['fid' => $feeId]
        );
    }

    public function waive(int $fineId, float $waivedAmount, int $waivedBy): void
    {
        $this->update($fineId, ['waived_amount' => $waivedAmount, 'waived_by' => $waivedBy]);
    }
}
