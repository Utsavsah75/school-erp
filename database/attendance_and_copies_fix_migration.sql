-- ============================================================================
-- Student Attendance module + Library "100 vs 11" count fix — additive/
-- corrective migration. Safe to run against the live database: nothing
-- existing is dropped, renamed, or retyped.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Student Attendance — audit timestamps + hard duplicate-prevention.
--    `attendance` today has no created_at/updated_at at all, so there is no
--    way to show "recent attendance activity" or tell when a record was
--    last edited. Attendance::markOne() already does a find-then-update
--    upsert (so the app-level code never double-inserts), but there was no
--    DB-level guarantee — the UNIQUE key below closes that race condition
--    and gives INSERT ... ON DUPLICATE KEY UPDATE a real target to use.
-- ----------------------------------------------------------------------------
ALTER TABLE `attendance`
  ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `remarks`,
  ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

UPDATE `attendance` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL;

ALTER TABLE `attendance`
  ADD UNIQUE KEY `uq_attendance_student_date` (`student_id`, `date`),
  ADD INDEX `idx_attendance_class_section_date` (`class_id`, `section_id`, `date`),
  ADD INDEX `idx_attendance_created_at` (`created_at`);

-- ----------------------------------------------------------------------------
-- 2. Root cause of "Issue Book shows 100 but the system only has 11 issued":
--    two layers of drift, both fixed here.
--
--    Layer A (the real bug): 90 rows in `book_copies` are stuck at
--    status = 'issued' with no matching open loan in `book_issues` at all —
--    i.e. nothing is actually borrowing them, but they still count as
--    unavailable everywhere (Issue Book's copy dropdown, catalog
--    availability). This resets exactly those orphaned rows back to
--    'available'. Copies with a real matching `book_issues` row
--    (status = 'issued') are correctly left as 'issued'; 'lost',
--    'damaged', 'maintenance' and 'reserved' copies are untouched.
--
--    Layer B: `books.total_copies` / `books.available_copies` are
--    denormalized counters that were never kept in sync once per-copy
--    tracking (`book_copies`) was introduced. Once Layer A is fixed, this
--    recalculates both columns from the now-correct `book_copies` rows so
--    the numbers agree everywhere. Books with zero rows in `book_copies`
--    (never migrated to per-copy tracking) are left untouched.
-- ----------------------------------------------------------------------------
UPDATE `book_copies` bc
SET bc.`status` = 'available'
WHERE bc.`status` = 'issued'
  AND NOT EXISTS (
      SELECT 1 FROM `book_issues` bi WHERE bi.`book_copy_id` = bc.`id` AND bi.`status` = 'issued'
  );

UPDATE `books` b
SET
  b.`total_copies` = (SELECT COUNT(*) FROM `book_copies` bc WHERE bc.`book_id` = b.`id`),
  b.`available_copies` = (SELECT COUNT(*) FROM `book_copies` bc WHERE bc.`book_id` = b.`id` AND bc.`status` = 'available')
WHERE EXISTS (SELECT 1 FROM `book_copies` bc WHERE bc.`book_id` = b.`id`);
