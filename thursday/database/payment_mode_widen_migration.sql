-- Widens `payment_mode` on `payments` and `library_fine_payments` from a
-- (likely stale) ENUM to VARCHAR(30), so every mode the app's UI already
-- offers (Cash, UPI, Bank Transfer, Online, Cheque, Card — see
-- PAYMENT_MODES in config/constants.php) can actually be saved.
--
-- Why: the PHP/JS layers were updated to support all 6 modes some time
-- ago, but the live `payment_mode` columns may still be an ENUM from
-- before that (e.g. ENUM('cash') or ENUM('cash','cheque','card','online')
-- missing 'upi'/'bank_transfer'). Under MySQL/MariaDB, inserting a value
-- that isn't in an ENUM's list either raises a warning and silently
-- stores '' (non-strict mode) or errors outright (strict mode) — either
-- way, only 'cash' (or whichever modes were already in the list) actually
-- persist. Moving to VARCHAR(30) removes the mismatch entirely and means
-- this can never happen again if another mode is added later.
--
-- Idempotent: safe to run multiple times, and safe to run whether the
-- column is currently an ENUM, a VARCHAR, or missing.

SET @dbname = DATABASE();

-- payments.payment_mode
SET @col_type = (
    SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'payment_mode'
);
SET @sql = IF(
    @col_type IS NOT NULL AND @col_type NOT LIKE 'varchar%',
    'ALTER TABLE `payments` MODIFY `payment_mode` VARCHAR(30) NOT NULL DEFAULT \'cash\'',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- library_fine_payments.payment_mode
SET @col_type = (
    SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'library_fine_payments' AND COLUMN_NAME = 'payment_mode'
);
SET @sql = IF(
    @col_type IS NOT NULL AND @col_type NOT LIKE 'varchar%',
    'ALTER TABLE `library_fine_payments` MODIFY `payment_mode` VARCHAR(30) NOT NULL DEFAULT \'cash\'',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Sanity check — run after applying, to confirm both are now VARCHAR(30)
-- and see what values already exist in each table.
-- SELECT TABLE_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'payment_mode';
-- SELECT DISTINCT payment_mode, COUNT(*) FROM payments GROUP BY payment_mode;
-- SELECT DISTINCT payment_mode, COUNT(*) FROM library_fine_payments GROUP BY payment_mode;
