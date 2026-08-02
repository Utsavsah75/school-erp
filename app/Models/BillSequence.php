<?php

namespace App\Models;

use App\Core\Model;
use App\Core\NepaliDate;

/**
 * Generates institutional bill / reference numbers like "2083/001" —
 * sequential and gap-free per Bikram Sambat year, safe under concurrent
 * cashiers because the increment happens inside a row-locking transaction
 * (SELECT ... FOR UPDATE), the same pattern ReceiptSequence uses for the
 * day-based receipt number.
 */
class BillSequence extends Model
{
    protected string $table = 'bill_sequences';
    protected string $primaryKey = 'bs_year';

    /** Returns the next bill number for the given (or current) BS year, e.g. "2083/001". */
    public function next(?int $bsYear = null): string
    {
        $bsYear ??= (int) explode('-', NepaliDate::todayBsString())[0];

        $this->db->beginTransaction();
        try {
            $row = $this->raw('SELECT last_number FROM bill_sequences WHERE bs_year = :y FOR UPDATE', ['y' => $bsYear]);

            if (empty($row)) {
                $this->db->query('INSERT INTO bill_sequences (bs_year, last_number) VALUES (:y, 1)', ['y' => $bsYear]);
                $next = 1;
            } else {
                $next = (int) $row[0]['last_number'] + 1;
                $this->db->query('UPDATE bill_sequences SET last_number = :n WHERE bs_year = :y', ['n' => $next, 'y' => $bsYear]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $bsYear . '/' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
