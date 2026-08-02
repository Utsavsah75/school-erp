<?php

namespace App\Models;

use App\Core\Model;

/**
 * Super Admin pre-provisions an Employee Code (+ role) here so that
 * Teacher / Staff / Librarian / Accountant / Receptionist self-registration
 * can't be used by an arbitrary visitor to grant themselves a privileged
 * role — the registration wizard requires a matching, unused, unexpired
 * invite before it will create the account.
 */
class StaffInvite extends Model
{
    protected string $table = 'staff_invites';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'employee_code', 'full_name', 'email', 'phone', 'role',
        'invited_by', 'expires_at', 'used_at', 'used_by_user_id',
    ];

    protected array $searchable = ['employee_code', 'full_name', 'email', 'phone'];

    public function findUsableByCode(string $employeeCode): array|false
    {
        $row = $this->findBy('employee_code', $employeeCode);
        if (!$row) {
            return false;
        }
        if (!empty($row['used_at'])) {
            return false;
        }
        if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
            return false;
        }
        return $row;
    }

    public function markUsed(int $id, int $usedByUserId): void
    {
        $this->update($id, [
            'used_at'         => date('Y-m-d H:i:s'),
            'used_by_user_id' => $usedByUserId,
        ]);
    }

    /** All invites, newest first, with inviter name joined. */
    public function allWithInviter(): array
    {
        return $this->raw(
            'SELECT si.*, u.full_name AS invited_by_name
             FROM staff_invites si
             LEFT JOIN users u ON u.id = si.invited_by
             ORDER BY si.id DESC'
        );
    }
}
