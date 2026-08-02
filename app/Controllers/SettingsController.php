<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;

/**
 * Admin Settings — currently just Mail Setup (SMTP status + test email).
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
}
