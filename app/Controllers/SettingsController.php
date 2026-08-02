<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\Settings;

/**
 * Admin Settings — Mail Setup (SMTP status + test email) and Registration &
 * Security (OTP/password policy/lockout/reCAPTCHA/registration toggle).
 * Gated to `settings` module permission (super_admin only, see
 * config/constants.php MODULE_PERMISSIONS).
 */
class SettingsController extends Controller
{
    public function index(): void
    {
        $this->authorizeModule('settings');

        $this->view('settings/index', [
            'pageTitle'  => 'Settings',
            'mailStatus' => Mailer::status(),
        ]);
    }

    public function sendTestEmail(): void
    {
        $this->authorizeModule('settings');

        $data = $this->validate(['test_email' => 'required|email']);

        $result = Mailer::sendTest($data['test_email'], Auth::user()['full_name'] ?? 'Administrator');

        if ($result['success']) {
            $this->flashSuccess("Test email sent successfully to {$data['test_email']}. Check the inbox (and spam folder) to confirm.");
        } else {
            error_log('[MAIL TEST] Failed to send test email to ' . $data['test_email'] . ': ' . $result['error']);
            $this->flashError('Test email failed: ' . $result['error']);
        }

        $this->redirect(url('settings'));
    }

    // ------------------------------------------------------------
    // Registration & Security
    // ------------------------------------------------------------

    public function registrationSecurity(): void
    {
        $this->authorizeModule('settings');

        $this->view('settings/registration-security', [
            'pageTitle' => 'Registration & Security',
            'settings'  => Settings::all(),
            'errors'    => Session::getErrors(),
        ]);
    }

    public function updateRegistrationSecurity(): void
    {
        $this->authorizeModule('settings');

        $data = $this->validate([
            'otp_length'                  => 'required|integer|min:4|max:8',
            'otp_ttl_minutes'             => 'required|integer|min:1|max:60',
            'otp_max_attempts'            => 'required|integer|min:1|max:20',
            'otp_resend_cooldown_seconds' => 'required|integer|min:10|max:600',
            'sms_provider'                => 'required|in:log,twilio,vonage',
            'email_provider'              => 'required|in:log,smtp,mail',
            'password_min_length'         => 'required|integer|min:6|max:64',
            'max_failed_login_attempts'   => 'required|integer|min:3|max:20',
            'account_lock_minutes'        => 'required|integer|min:1|max:1440',
            'recaptcha_site_key'          => 'nullable|max:255',
            'recaptcha_secret_key'        => 'nullable|max:255',
        ]);

        $booleans = [
            'registration_enabled', 'otp_enabled', 'password_require_upper',
            'password_require_lower', 'password_require_number', 'password_require_symbol',
            'recaptcha_enabled', 'login_alert_email_enabled',
        ];
        $textFields = [
            'otp_length', 'otp_ttl_minutes', 'otp_max_attempts', 'otp_resend_cooldown_seconds',
            'sms_provider', 'email_provider', 'password_min_length',
            'max_failed_login_attempts', 'account_lock_minutes',
            'recaptcha_site_key', 'recaptcha_secret_key',
        ];

        $toSave = [];
        foreach ($textFields as $key) {
            $toSave[$key] = (string) ($data[$key] ?? '');
        }
        foreach ($booleans as $key) {
            $toSave[$key] = !empty($_POST[$key]) ? '1' : '0';
        }

        Settings::setMany($toSave, Auth::id());

        log_activity('settings_update', 'Updated Registration & Security settings.');
        $this->flashSuccess('Registration & Security settings saved.');
        $this->redirect(url('settings/registration-security'));
    }
}
