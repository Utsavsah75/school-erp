# Registration & Authentication System — Setup Guide

This adds a full multi-method registration + authentication system on top
of the existing School ERP codebase. Nothing existing was removed or
renamed — only extended.

## 1. Run the migration

```bash
mysql -u root -p school_erp < database/registration_auth_system_migration.sql
```

This adds:
- New columns on `users` (lockout, 2FA, employee_code, phone_verified_at, etc.)
- `registration_otps` — OTP codes for the public registration wizard (before a user exists)
- `staff_invites` — Super-Admin-issued Employee Codes for Staff/Employee self-registration
- `admin_settings` — runtime-configurable Registration & Security settings
- `login_throttle` — rate limiting for login/registration/password-reset

It's additive and idempotent-ish (`ADD COLUMN`/`CREATE TABLE IF NOT EXISTS`),
safe to run once against your existing database.

## 2. What's new

### Registration (`/register`)
A role-select screen, then a 3-step wizard (details → OTP verify → done):

| Role | How it's verified |
|---|---|
| **Parent** | Child's Admission Number + Mobile OTP / Email Code / Phone+Email (your choice) |
| **Student** | Admission Number only — OTP is sent to the **parent's** phone/email on file |
| **Teacher** | Employee Number (must exist in `teachers`) + your choice of OTP method |
| **Staff/Employee** | Employee Code from an admin-issued invite (see below) + OTP |
| **Admin** | Not public — see `/admin/register`, Super Admin only, direct creation |

### Login (`/login`)
Five tabs: **Email**, **Username**, **Student ID**, **Employee ID** (all
password-based), and **Phone + OTP** (passwordless). All go through the
same account-lockout and rate-limiting rules.

### Two-Factor Authentication
Any logged-in user can enable TOTP 2FA from the account menu →
**Two-Factor Authentication** (`/2fa/setup`) — works with Google
Authenticator, Authy, Microsoft Authenticator, 1Password, etc. Login
automatically detours through `/2fa/verify` for accounts with it enabled.
Recovery codes are issued once at setup time.

### Staff Invites (`/staff-invites`, Super Admin only)
Issue an Employee Code for Librarian/Accountant/Receptionist/Staff/Admin
roles so they can self-register (with OTP verification) instead of you
creating their account by hand.

### Admin Settings → Registration & Security (`/settings/registration-security`)
Runtime-configurable (no `.env` edit or redeploy needed):
- Enable/disable self-registration and OTP
- OTP length / expiry / max attempts / resend cooldown
- SMS & Email provider selection
- Password policy (length + character requirements)
- Account lockout (max attempts + lock duration)
- Google reCAPTCHA (site/secret key + on/off)
- Login alert emails on/off

## 3. Optional `.env` additions

Everything above works with sane defaults out of the box (registration
enabled, OTP required, reCAPTCHA off, `log` SMS/mail drivers). To wire up
real providers, the existing `.env` keys already cover it:

```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...

SMS_DRIVER=twilio        # or vonage, or log for dev
TWILIO_SID=...
TWILIO_TOKEN=...
TWILIO_FROM=...
```

reCAPTCHA site/secret keys are configured in the UI
(`/settings/registration-security`), not `.env`.

## 4. Security notes

- Passwords are hashed with bcrypt (`password_hash(..., PASSWORD_BCRYPT)`)
  the moment they're collected — a raw password is never written to the
  session or database.
- Parent/Teacher/Staff self-registration always cross-checks the
  phone/email the registrant supplies against what the school already has
  on file (or against a Super-Admin-issued invite for Staff) — Employee
  Codes and Admission Numbers alone are never sufficient to create a login.
- All sensitive POST endpoints (login, registration OTP requests,
  password reset) are rate-limited per identifier+IP via the `throttle:`
  route middleware, backed by `login_throttle`.
- CSRF protection (existing `csrf` middleware) applies to every new POST
  route.
- reCAPTCHA fails open (treated as passed) if Google's endpoint is
  unreachable, so a network hiccup never locks out legitimate users —
  but fails closed if reCAPTCHA is enabled and no token was submitted.

## 5. Files changed/added

See the accompanying summary in the chat response, or `git status` /
`git diff` if you've imported this into a repo — every change is additive
except the rewritten `login()`/`showLogin()` in `AuthController.php` and
the `login.php` view (both replaced to support multiple login methods).
