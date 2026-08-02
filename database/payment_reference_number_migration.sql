-- ============================================================================
-- Fee Payment Receipt — auto-generated Reference No. (001, 002, 003, ...).
-- Additive/corrective migration. Safe to run against the live database:
-- nothing existing is dropped, renamed, or retyped.
--
-- Context: `payments.reference_number` already exists and is a free-text
-- field the cashier types in for a cheque/UPI/bank transaction reference —
-- that stays exactly as it is, since it's needed for reconciling non-cash
-- payments against the bank/gateway statement.
--
-- This migration adds a SEPARATE, system-generated running reference number
-- (`internal_ref_no`) that is never typed by anyone: it starts at 001,
-- increments by 1 for every new receipt (one per receipt_group, i.e. per
-- payment transaction — not per fee head), and is guaranteed unique. It is
-- generated the same safe way `receipt_sequences` already generates Receipt
-- Numbers (row-locking transaction, see App\Models\ReceiptSequence), just
-- without the daily reset.
-- ============================================================================

-- 1. Global counter table — one row, locked+incremented per payment.
CREATE TABLE IF NOT EXISTS `payment_reference_sequences` (
  `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  `last_number` INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payment_reference_sequences` (`id`, `last_number`)
VALUES (1, 0)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- 2. The column payments.internal_ref_no is stamped onto every row of a
--    receipt_group with the SAME value (one reference number per payment
--    transaction, matching the one Receipt No. / one PDF for that group).
ALTER TABLE `payments`
  ADD COLUMN `internal_ref_no` VARCHAR(10) NULL DEFAULT NULL AFTER `reference_number`,
  ADD UNIQUE KEY `uniq_internal_ref_no` (`internal_ref_no`);
