<?php
/** Expects: $settings (flat key=>value array), $errors */
$s = fn(string $key, $default = '') => $settings[$key] ?? $default;
$checked = fn(string $key, bool $default = false) => in_array($s($key, $default ? '1' : '0'), ['1', 'true', 'on'], true) ? 'checked' : '';
$pageTitle = 'Registration & Security Settings';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill me-1"></i> Registration & Security Settings</h5>
    <a href="<?= e(url('settings')) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Settings</a>
</div>

<?= flash_alerts() ?>

<form method="POST" action="<?= e(url('settings/registration-security')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-toggle-on me-1"></i> Registration</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="registration_enabled" id="registration_enabled" <?= $checked('registration_enabled', true) ?>>
                        <label class="form-check-label" for="registration_enabled">Enable self-registration (Parent/Student/Teacher/Staff wizard)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="otp_enabled" id="otp_enabled" <?= $checked('otp_enabled', true) ?>>
                        <label class="form-check-label" for="otp_enabled">Enable OTP verification</label>
                        <div class="form-text">When off, existing OTP flows still run but this flag is available for custom gating logic.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="login_alert_email_enabled" id="login_alert_email_enabled" <?= $checked('login_alert_email_enabled', false) ?>>
                        <label class="form-check-label" for="login_alert_email_enabled">Send a "new sign-in" email alert on every login</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-key-fill me-1"></i> OTP Configuration</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Code Length</label>
                            <input type="number" name="otp_length" class="form-control" value="<?= e($s('otp_length', 6)) ?>" min="4" max="8" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Expiry (minutes)</label>
                            <input type="number" name="otp_ttl_minutes" class="form-control" value="<?= e($s('otp_ttl_minutes', 5)) ?>" min="1" max="60" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Max Attempts</label>
                            <input type="number" name="otp_max_attempts" class="form-control" value="<?= e($s('otp_max_attempts', 5)) ?>" min="1" max="20" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Resend Cooldown (seconds)</label>
                            <input type="number" name="otp_resend_cooldown_seconds" class="form-control" value="<?= e($s('otp_resend_cooldown_seconds', 60)) ?>" min="10" max="600" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-broadcast me-1"></i> SMS / Email Provider</h6>
                    <div class="mb-3">
                        <label class="form-label">SMS Provider</label>
                        <select name="sms_provider" class="form-select">
                            <?php foreach (['log' => 'Log only (dev/testing)', 'twilio' => 'Twilio', 'vonage' => 'Vonage'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $s('sms_provider', 'log') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">API credentials for the selected provider are configured in <code>.env</code>.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Email Provider</label>
                        <select name="email_provider" class="form-select">
                            <?php foreach (['log' => 'Log only (dev/testing)', 'smtp' => 'SMTP', 'mail' => 'PHP mail()'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $s('email_provider', 'smtp') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">SMTP host/credentials are managed on the main <a href="<?= e(url('settings')) ?>">Settings</a> page.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-google me-1"></i> Google reCAPTCHA</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled" <?= $checked('recaptcha_enabled', false) ?>>
                        <label class="form-check-label" for="recaptcha_enabled">Enable reCAPTCHA on login/registration</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site Key</label>
                        <input type="text" name="recaptcha_site_key" class="form-control" value="<?= e($s('recaptcha_site_key')) ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="recaptcha_secret_key" class="form-control" value="<?= e($s('recaptcha_secret_key')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-key me-1"></i> Password Policy</h6>
                    <div class="mb-3">
                        <label class="form-label">Minimum Length</label>
                        <input type="number" name="password_min_length" class="form-control" value="<?= e($s('password_min_length', 8)) ?>" min="6" max="64" required>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="password_require_upper" id="password_require_upper" <?= $checked('password_require_upper', true) ?>>
                        <label class="form-check-label" for="password_require_upper">Require uppercase letter</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="password_require_lower" id="password_require_lower" <?= $checked('password_require_lower', true) ?>>
                        <label class="form-check-label" for="password_require_lower">Require lowercase letter</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="password_require_number" id="password_require_number" <?= $checked('password_require_number', true) ?>>
                        <label class="form-check-label" for="password_require_number">Require number</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="password_require_symbol" id="password_require_symbol" <?= $checked('password_require_symbol', true) ?>>
                        <label class="form-check-label" for="password_require_symbol">Require special character</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lock-fill me-1"></i> Account Lockout</h6>
                    <div class="mb-3">
                        <label class="form-label">Lock after this many failed attempts</label>
                        <input type="number" name="max_failed_login_attempts" class="form-control" value="<?= e($s('max_failed_login_attempts', 5)) ?>" min="3" max="20" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Lock duration (minutes)</label>
                        <input type="number" name="account_lock_minutes" class="form-control" value="<?= e($s('account_lock_minutes', 15)) ?>" min="1" max="1440" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-check-circle-fill me-1"></i> Save Settings</button>
</form>
