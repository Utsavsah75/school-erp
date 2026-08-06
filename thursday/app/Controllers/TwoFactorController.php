<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\TwoFactor;
use App\Models\User;

/**
 * Two-Factor Authentication: setup (enable, with QR + recovery codes),
 * disable, and the login-time verification step that Auth::attemptByIdentifier()
 * / Auth::verifyPhoneOtpLogin() hand off to when a user has 2FA enabled.
 */
class TwoFactorController extends Controller
{
    // ------------------------------------------------------------
    // Login-time verification (user already passed primary credentials —
    // see Auth::hasPending2fa()).
    // ------------------------------------------------------------

    public function showVerify(): void
    {
        if (!Auth::hasPending2fa()) {
            $this->redirect(url('login'));
            return;
        }
        $this->view('auth/2fa-verify', ['errors' => Session::getErrors()], null);
    }

    public function verify(): void
    {
        if (!Auth::hasPending2fa()) {
            $this->redirect(url('login'));
            return;
        }
        $data = $this->validate(['code' => 'required']);

        $result = Auth::completePending2fa($data['code']);
        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            $this->redirect(url('2fa/verify'));
            return;
        }

        $this->redirect(url('dashboard'));
    }

    // ------------------------------------------------------------
    // Setup / enable (for a logged-in user, from Account Settings).
    // ------------------------------------------------------------

    public function showSetup(): void
    {
        $user = Auth::user();
        if (!empty($user['two_factor_enabled'])) {
            $recoveryCodes = Session::get('2fa_recovery_codes_once');
            Session::remove('2fa_recovery_codes_once');
            $this->view('auth/2fa-setup', [
                'alreadyEnabled' => true,
                'recoveryCodes'  => $recoveryCodes,
                'errors'         => Session::getErrors(),
            ]);
            return;
        }

        // A fresh secret per GET so a stale one from an abandoned setup
        // attempt can't be reused; stored in session until confirmed.
        $secret = TwoFactor::generateSecret();
        Session::set('2fa_setup_secret', $secret);

        $this->view('auth/2fa-setup', [
            'alreadyEnabled' => false,
            'secret'         => $secret,
            'otpauthUri'     => TwoFactor::provisioningUri($secret, $user['email'] ?? $user['username'] ?? ('user' . $user['id'])),
            'errors'         => Session::getErrors(),
        ]);
    }

    public function enable(): void
    {
        $data = $this->validate(['code' => 'required']);
        $secret = Session::get('2fa_setup_secret');

        if (!$secret || !TwoFactor::verify($secret, $data['code'])) {
            Session::flash('error', 'That code did not match. Please scan the QR code again and try once more.');
            $this->redirect(url('2fa/setup'));
            return;
        }

        $recoveryCodes = TwoFactor::generateRecoveryCodes();
        $hashedJson = TwoFactor::hashRecoveryCodes($recoveryCodes);

        $user = Auth::user();
        (new User())->enableTwoFactor((int) $user['id'], $secret, $hashedJson);
        Session::remove('2fa_setup_secret');

        // Recovery codes are shown exactly once — the hashed versions in
        // the DB can't be turned back into plaintext.
        Session::set('2fa_recovery_codes_once', $recoveryCodes);
        Session::flash('success', 'Two-Factor Authentication has been enabled.');
        $this->redirect(url('2fa/setup'));
    }

    public function disable(): void
    {
        $data = $this->validate(['password' => 'required']);
        $user = Auth::user();

        if (!password_verify($data['password'], $user['password'])) {
            Session::flash('error', 'Incorrect password.');
            $this->redirect(url('2fa/setup'));
            return;
        }

        (new User())->disableTwoFactor((int) $user['id']);
        Session::flash('success', 'Two-Factor Authentication has been disabled.');
        $this->redirect(url('2fa/setup'));
    }
}
