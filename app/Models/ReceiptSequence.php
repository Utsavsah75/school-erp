<?php

namespace App\Models;

use App\Core\Model;

/**
 * Generates the auto-incrementing payment Reference No. — 001, 002, 003, ...
 * one per receipt (i.e. per receipt_group / payment transaction, not per fee
 * head row within it). Never reset, never typed by a person, guaranteed
 * unique by the row-locking transaction below (same pattern as
 * ReceiptSequence) plus a DB-level UNIQUE key on payments.internal_ref_no as
 * a second line of defense.
 */
class ReferenceSequence extends Model
{
    protected string $table = 'payment_reference_sequences';
    protected string $primaryKey = 'id';

    /** Returns the next reference number, zero-padded to at least 3 digits (e.g. "001", "015", "100", "1000"). */
    public function next(): string
    {
        $this->db->beginTransaction();
        try {
            $row = $this->raw('SELECT last_number FROM payment_reference_sequences WHERE id = 1 FOR UPDATE');
            $next = (int) ($row[0]['last_number'] ?? 0) + 1;
            $this->db->query(
                'INSERT INTO payment_reference_sequences (id, last_number) VALUES (1, :n)
                 ON DUPLICATE KEY UPDATE last_number = :n2',
                ['n' => $next, 'n2' => $next]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}