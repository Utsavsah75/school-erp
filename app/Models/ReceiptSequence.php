<?php

namespace App\Models;

use App\Core\Model;

/**
 * Generates the daily-reset Receipt Number — "RCPT-YYYYMMDD-0001",
 * "RCPT-YYYYMMDD-0002", ... — shared by every module that issues a
 * receipt (fee payments, library fine payments), so there's exactly one
 * counter and one format. Safe under concurrent cashiers because the
 * increment happens inside a row-locking transaction (SELECT ... FOR
 * UPDATE) against `receipt_sequences` (seq_date, last_number), the same
 * pattern BillSequence/ReferenceSequence use for their own counters.
 *
 * NOTE: this file previously had ReferenceSequence's class body pasted
 * into it by mistake, so App\Models\ReceiptSequence never actually
 * existed as a class — every `new ReceiptSequence()` (called from
 * Payment::nextReceiptNumber(), which both regular fee payments and
 * LibraryController::collectFine() rely on) was throwing a fatal "Class
 * not found" error the moment PHP tried to autoload it. In
 * collectFine()'s callers (returnBook()/renewBook()) that's caught by a
 * catch (\Throwable) block and shown as "The fine could not be collected
 * due to a server error" — this file is that fix.
 */
class ReceiptSequence extends Model
{
    protected string $table = 'receipt_sequences';
    protected string $primaryKey = 'seq_date';

    /** Returns the next receipt number for today, e.g. "RCPT-20260803-0001". */
    public function next(): string
    {
        $today = date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $row = $this->raw('SELECT last_number FROM receipt_sequences WHERE seq_date = :d FOR UPDATE', ['d' => $today]);

            if (empty($row)) {
                $this->db->query('INSERT INTO receipt_sequences (seq_date, last_number) VALUES (:d, 1)', ['d' => $today]);
                $next = 1;
            } else {
                $next = (int) $row[0]['last_number'] + 1;
                $this->db->query('UPDATE receipt_sequences SET last_number = :n WHERE seq_date = :d', ['n' => $next, 'd' => $today]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return 'RCPT-' . date('Ymd') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
