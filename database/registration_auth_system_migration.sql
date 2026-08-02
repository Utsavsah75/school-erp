-- ============================================================================
-- Registration & Authentication System — additive migration.
-- Safe to run against the live database: nothing existing is dropped,
-- renamed, or retyped. Run this once via phpMyAdmin / mysql CLI:
--   mysql -u root -p school_erp < database/registration_auth_system_migration.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. `users` — new columns for multi-method login, account lockout, 2FA,
--    and staff-style Employee ID login (students/teachers already have
--    admission_number / employee_number on their own tables — see
--    Auth::attemptByIdentifier() which checks those first).
-- ----------------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN `employee_code` VARCHAR(50) NULL DEFAULT NULL AFTER `username`,
  ADD COLUMN `phone_verified_at` DATETIME NULL DEFAULT NULL AFTER `email_verified_at`,
  ADD COLUMN `failed_login_attempts` INT NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_login_attempts`,
  ADD COLUMN `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `two_factor_enabled`,
  ADD COLUMN `two_factor_recovery_codes` TEXT NULL DEFAULT NULL AFTER `two_factor_secret`,
  ADD COLUMN `password_changed_at` DATETIME NULL DEFAULT NULL AFTER `password`,
  ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_changed_at`,
  ADD COLUMN `registered_via` VARCHAR(40) NULL DEFAULT NULL AFTER `must_change_password`,
  ADD COLUMN `registration_ip` VARCHAR(45) NULL DEFAULT NULL AFTER `registered_via`,
  ADD UNIQUE KEY `uq_users_employee_code` (`employee_code`);

-- ----------------------------------------------------------------------------
-- 2. `registration_otps` — OTP codes issued during self-registration, i.e.
--    BEFORE a `users` row exists. Keyed by a random per-attempt session
--    token (not user_id, since there's no user yet) so it cannot reuse
--    the existing `otps` table without weakening its NOT NULL user_id FK.
--    Mirrors the shape/semantics of `otps` (see App\Core\Otp) exactly so
--    App\Core\RegistrationOtp can reuse the same generate/verify logic.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_otps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` CHAR(64) NOT NULL,
  `channel` ENUM('sms','email') NOT NULL,
  `purpose` VARCHAR(60) NOT NULL,
  `destination` VARCHAR(190) NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `max_attempts` INT NOT NULL DEFAULT 5,
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME NULL DEFAULT NULL,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reg_otp_lookup` (`session_token`, `purpose`, `channel`, `consumed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. `staff_invites` — pre-provisioned by a Super Admin so that Teacher /
--    Staff / Librarian / Accountant / Receptionist self-registration
--    ("Staff/Employee Registration" in the spec) cannot be used by a
--    random visitor to grant themselves a privileged role. An admin
--    issues an Employee Code + email/phone; the invitee then completes
--    the public registration wizard using that code, which is consumed
--    on success.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_invites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_code` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `role` VARCHAR(40) NOT NULL,
  `invited_by` INT UNSIGNED NULL DEFAULT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `used_at` DATETIME NULL DEFAULT NULL,
  `used_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_invite_code` (`employee_code`),
  KEY `idx_staff_invite_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. `admin_settings` — runtime-configurable Registration & Security
--    settings (spec "Admin Settings" section), editable from
--    Settings > Registration & Security without touching .env or
--    redeploying. App\Core\Settings::get() checks this table first and
--    falls back to config()/​.env when a key isn't set yet.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. `login_throttle` — fixed-window rate limiting for login/registration/
--    password-reset POSTs, keyed by identifier (email/username/etc, or
--    "ip" when there's no identifier yet) + client IP. See
--    App\Core\RateLimitMiddleware.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_throttle` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bucket` VARCHAR(60) NOT NULL,
  `identifier` VARCHAR(190) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `window_started_at` DATETIME NOT NULL,
  `blocked_until` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_throttle_bucket_id_ip` (`bucket`, `identifier`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Seed sane defaults into admin_settings so the Settings screen has
--    something to display immediately (all overridable in the UI).
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `admin_settings` (`setting_key`, `setting_value`) VALUES
  ('registration_enabled', '1'),
  ('otp_enabled', '1'),
  ('otp_length', '6'),
  ('otp_ttl_minutes', '5'),
  ('otp_max_attempts', '5'),
  ('otp_resend_cooldown_seconds', '60'),
  ('sms_provider', 'log'),
  ('email_provider', 'smtp'),
  ('password_min_length', '8'),
  ('password_require_upper', '1'),
  ('password_require_lower', '1'),
  ('password_require_number', '1'),
  ('password_require_symbol', '1'),
  ('max_failed_login_attempts', '5'),
  ('account_lock_minutes', '15'),
  ('recaptcha_enabled', '0'),
  ('recaptcha_site_key', ''),
  ('recaptcha_secret_key', ''),
  ('login_alert_email_enabled', '0');
