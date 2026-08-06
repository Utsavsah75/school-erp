-- ============================================================================
-- Remember Me — persistent login.
-- Additive migration. Safe to run against the live database: nothing
-- existing is dropped, renamed, or retyped.
--
-- Context: App\Core\Auth already implements the full "Remember Me" flow
-- (see setRememberCookie()/loginViaRememberCookie() in app/Core/Auth.php) —
-- it reads/writes `users.remember_token`, but that column was never actually
-- added to the database (it's absent from
-- database/registration_auth_system_migration.sql and every other migration
-- in this folder). So checking "Remember me" logs you in as normal, but the
-- follow-up `UPDATE users SET remember_token = ...` silently fails against a
-- column that doesn't exist, no cookie-matching token ever gets stored, and
-- the session ends the moment the browser session cookie expires/closes —
-- exactly like the checkbox was never checked at all.
-- ============================================================================

ALTER TABLE `users`
  ADD COLUMN `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  ADD KEY `idx_users_remember_token` (`remember_token`);
