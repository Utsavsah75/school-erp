<?php

namespace App\Controllers;

use App\Controllers\AuthController;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\ParentModel;
use App\Models\PasswordReset;
use App\Models\User;

/**
 * Admin-facing "Parents" module — the counterpart to StudentController but
 * for guardian/parent records. Lets Super Admin / Principal / Receptionist
 * (see config/constants.php MODULE_PERMISSIONS['parents']) browse parent
 * records, see which children are linked to each, edit contact details,
 * and grant a parent-portal login (username/password account in `users`
 * with role=parent) so they can access the Parent Dashboard.
 *
 * This does NOT touch the parent-portal self-service side (ParentController)
 * or the `parents`/`users` schema — it only adds admin CRUD on data that
 * already exists.
 */
class ParentsController extends Controller
{
    public function index(): void
    {
        $this->authorizeModule('parents');

        $parentModel = new ParentModel();
        $search = trim((string) $this->input('search', ''));
        $result = $parentModel->paginateWithStats($this->currentPage(), 20, $search);

        $this->view('parents/index', [
            'pageTitle' => 'Parents',
            'result'    => $result,
            'search'    => $search,
        ]);
    }

    public function show(int $id): void
    {
        $this->authorizeModule('parents');

        $parentModel = new ParentModel();
        $parent = $parentModel->find($id);
        if (!$parent) {
            $this->flashError('Parent record not found.');
            $this->redirect(url('parents'));
            return;
        }

        $login = $parent['user_id'] ? (new User())->find((int) $parent['user_id']) : null;

        $this->view('parents/show', [
            'pageTitle' => $parentModel->displayName($parent),
            'parentRow' => $parent,
            'children'  => $parentModel->children($id),
            'login'     => $login ?: null,
        ]);
    }

    public function edit(int $id): void
    {
        $this->authorizeModule('parents');

        $parentModel = new ParentModel();
        $parent = $parentModel->find($id);
        if (!$parent) {
            $this->flashError('Parent record not found.');
            $this->redirect(url('parents'));
            return;
        }

        $login = $parent['user_id'] ? (new User())->find((int) $parent['user_id']) : null;

        $this->view('parents/edit', [
            'pageTitle' => 'Edit ' . $parentModel->displayName($parent),
            'parentRow' => $parent,
            'login'     => $login ?: null,
            'errors'    => Session::getErrors(),
        ]);
    }

    public function update(int $id): void
    {
        $this->authorizeModule('parents');

        $parentModel = new ParentModel();
        $parent = $parentModel->find($id);
        if (!$parent) {
            $this->flashError('Parent record not found.');
            $this->redirect(url('parents'));
            return;
        }

        $data = $this->validate([
            'father_name'           => 'max:150',
            'mother_name'           => 'max:150',
            'guardian_name'         => 'max:150',
            'guardian_relationship' => 'max:100',
            'occupation'            => 'max:150',
            'phone_country_code'    => 'max:6',
            'phone'                 => 'required|max:15',
            'email'                 => 'nullable|email|max:190',
            'address'               => 'max:255',
            'login_email'           => 'nullable|email|max:190',
        ]);

        $loginEmailInput = trim((string) ($data['login_email'] ?? ''));
        unset($data['login_email']); // not a `parents` table column — handled separately below

        foreach (['father_name', 'mother_name', 'guardian_name', 'guardian_relationship', 'occupation', 'email', 'address'] as $optional) {
            $data[$optional] = trim((string) ($data[$optional] ?? '')) ?: null;
        }

        $parentModel->update($id, $data);

        // Keep the linked portal login in sync. The "Login Email" field on
        // the form is authoritative when the admin filled it in (lets them
        // fix a login email directly without touching the contact email);
        // otherwise fall back to keeping it matched to the contact email as
        // before. Either way, a conflict is now reported instead of silently
        // dropped, so "I changed the email but resets still go to the old
        // one" can't happen without the admin being told why.
        if (!empty($parent['user_id'])) {
            $userModel = new User();
            $desiredLoginEmail = $loginEmailInput ?: $data['email'];

            if (!empty($desiredLoginEmail)) {
                $currentLogin = $userModel->find((int) $parent['user_id']);
                $loginEmailChanged = !$currentLogin || strcasecmp((string) $currentLogin['email'], $desiredLoginEmail) !== 0;

                if ($loginEmailChanged) {
                    if ($userModel->emailExists($desiredLoginEmail, (int) $parent['user_id'])) {
                        Session::flash('error', 'Parent details updated, but the login email was NOT changed to "' . $desiredLoginEmail . '" because another account already uses it.');
                        $this->redirect(url('parents/' . $id));
                        return;
                    }
                    $userModel->update((int) $parent['user_id'], ['email' => $desiredLoginEmail, 'phone' => $data['phone']]);
                } else {
                    $userModel->update((int) $parent['user_id'], ['phone' => $data['phone']]);
                }
            }
        }

        $this->flashSuccess('Parent details updated.');
        $this->redirect(url('parents/' . $id));
    }

    /**
     * Creates a parent-portal login (users row, role=parent) for a parent
     * record that doesn't have one yet, then emails them a set-password
     * link — same mechanism as AuthController::adminSendReset.
     */
    public function grantPortalAccess(int $id): void
    {
        Auth::authorize('parents');

        $parentModel = new ParentModel();
        $parent = $parentModel->find($id);
        if (!$parent) {
            $this->flashError('Parent record not found.');
            $this->redirect(url('parents'));
            return;
        }

        if (!empty($parent['user_id'])) {
            $this->flashError('This parent already has a portal login.');
            $this->redirect(url('parents/' . $id));
            return;
        }

        if (empty($parent['email'])) {
            $this->flashError('Add an email address for this parent before granting portal access.');
            $this->redirect(url('parents/' . $id . '/edit'));
            return;
        }

        $userModel = new User();
        if ($userModel->emailExists($parent['email'])) {
            $this->flashError('A login already exists for that email address. Link it manually if it belongs to this parent.');
            $this->redirect(url('parents/' . $id));
            return;
        }

        if (!\App\Core\Mailer::isConfigured()) {
            $this->flashError('Cannot grant portal access — SMTP is not configured yet, so the account-setup email could not be sent. Visit Settings > Mail Setup to configure it, then try again.');
            $this->redirect(url('parents/' . $id));
            return;
        }

        $userId = $userModel->insert([
            'full_name'          => $parentModel->displayName($parent),
            'email'              => $parent['email'],
            'password'           => Auth::hashPassword(bin2hex(random_bytes(16))), // random — parent sets their own via the emailed link
            'role'               => ROLE_PARENT,
            'phone'              => $parent['phone'],
            'is_active'          => 1,
            'email_verified_at'  => date('Y-m-d H:i:s'), // admin already verified this is a real guardian's email on file
        ]);

        $parentModel->update($id, ['user_id' => $userId]);

        $rawToken = (new PasswordReset())->createToken($userId);
        $resetUrl = url('reset-password/' . $rawToken);

        $html = '<p>Hi ' . e($parentModel->displayName($parent)) . ',</p>'
            . '<p>A School ERP parent portal account has been created for you. Click the link below to set your password and log in.</p>'
            . '<p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>'
            . '<p>This link expires in 1 hour. If you weren\'t expecting this, please contact the school office.</p>';

        $result = \App\Core\Mailer::send($parent['email'], $parentModel->displayName($parent), 'Your School ERP parent portal account', $html);

        if ($result['success']) {
            $this->flashSuccess('Portal access granted — a set-password email has been sent to ' . $parent['email'] . '.');
        } else {
            error_log('[GRANT PORTAL ACCESS] Failed to send set-password email to ' . $parent['email'] . ': ' . $result['error']);
            $this->flashError('Portal login was created, but the set-password email failed to send: ' . $result['error'] . ' Use "Send Password Reset Email" from this page to retry once mail is fixed.');
        }
        $this->redirect(url('parents/' . $id));
    }
}
