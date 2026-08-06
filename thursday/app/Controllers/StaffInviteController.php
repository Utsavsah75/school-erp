<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\StaffInvite;

/**
 * Super Admin issues Employee Codes here so that the public "Staff/Employee
 * Registration" wizard step (RegistrationController::resolveRoleLink 'staff'
 * case) can't be used to self-grant a privileged role — registration only
 * succeeds against a matching, unused, unexpired invite issued from here.
 */
class StaffInviteController extends Controller
{
    public function index(): void
    {
        Auth::authorize('users');
        $this->view('staff-invites/index', [
            'pageTitle' => 'Staff Registration Invites',
            'invites'   => (new StaffInvite())->allWithInviter(),
            'roles'     => INVITABLE_STAFF_ROLES,
            'errors'    => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        Auth::authorize('users');

        $data = $this->validate([
            'employee_code' => 'required|min:3|max:50|unique:staff_invites,employee_code',
            'full_name'     => 'required|min:2|max:150',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|phone',
            'role'          => 'required|in:' . implode(',', INVITABLE_STAFF_ROLES),
            'expires_in_days' => 'nullable|integer',
        ]);

        $days = (int) ($data['expires_in_days'] ?? 30);
        $expiresAt = $days > 0 ? date('Y-m-d H:i:s', time() + $days * 86400) : null;

        (new StaffInvite())->insert([
            'employee_code' => strtoupper(trim($data['employee_code'])),
            'full_name'     => $data['full_name'],
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'role'          => $data['role'],
            'invited_by'    => Auth::id(),
            'expires_at'    => $expiresAt,
        ]);

        log_activity('staff_invite_create', "Issued Employee Code {$data['employee_code']} for {$data['full_name']} ({$data['role']})");
        $this->flashSuccess("Employee Code issued for {$data['full_name']}. Share it with them to complete registration.");
        $this->redirect(url('staff-invites'));
    }

    public function destroy(int $id): void
    {
        Auth::authorize('users');
        (new StaffInvite())->delete($id);
        $this->flashSuccess('Invite revoked.');
        $this->redirect(url('staff-invites'));
    }
}
