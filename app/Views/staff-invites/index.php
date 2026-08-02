<?php
/** Expects: $invites, $roles, $errors */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-ticket-perforated me-1"></i>Staff Registration Invites</h5>
    <a href="<?= e(url('admin/register')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person-plus-fill me-1"></i> Create account directly instead
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Issue a new Employee Code</h6>
                <p class="text-muted small">
                    Give this code to a Librarian, Accountant, Receptionist, Staff member, or Admin so they can
                    complete their own registration (with OTP verification) at
                    <a href="<?= e(url('register/wizard?role=staff')) ?>"><?= e(url('register/wizard?role=staff')) ?></a>.
                </p>
                <form method="POST" action="<?= e(url('staff-invites')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Employee Code</label>
                        <input type="text" name="employee_code" class="form-control <?= field_error($errors, 'employee_code') ? 'is-invalid' : '' ?>" value="<?= e(old('employee_code')) ?>" placeholder="e.g. STAFF-2026-001" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'employee_code')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control <?= field_error($errors, 'full_name') ? 'is-invalid' : '' ?>" value="<?= e(old('full_name')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'full_name')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select <?= field_error($errors, 'role') ? 'is-invalid' : '' ?>" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r) ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'role')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-muted">(optional)</span></label>
                        <input type="email" name="email" class="form-control <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>">
                        <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
                        <input type="tel" name="phone" class="form-control <?= field_error($errors, 'phone') ? 'is-invalid' : '' ?>" value="<?= e(old('phone')) ?>">
                        <div class="invalid-feedback"><?= e(field_error($errors, 'phone')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expires in (days)</label>
                        <input type="number" name="expires_in_days" class="form-control" value="30" min="0">
                        <small class="text-muted">0 = never expires</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send-fill me-1"></i> Issue Code</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Issued Codes</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Employee Code</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Issued By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($invites)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No invites issued yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($invites as $inv): ?>
                            <?php
                                $isUsed = !empty($inv['used_at']);
                                $isExpired = !empty($inv['expires_at']) && strtotime($inv['expires_at']) < time();
                                $status = $isUsed ? 'Used' : ($isExpired ? 'Expired' : 'Active');
                                $badge = $isUsed ? 'bg-secondary' : ($isExpired ? 'bg-danger' : 'bg-success');
                            ?>
                            <tr>
                                <td><code><?= e($inv['employee_code']) ?></code></td>
                                <td><?= e($inv['full_name']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= e(role_label($inv['role'])) ?></span></td>
                                <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                                <td><?= $inv['expires_at'] ? e(format_date($inv['expires_at'])) : 'Never' ?></td>
                                <td class="small text-muted"><?= e($inv['invited_by_name'] ?? '—') ?></td>
                                <td>
                                    <?php if (!$isUsed): ?>
                                    <form method="POST" action="<?= e(url('staff-invites/' . $inv['id'] . '/delete')) ?>" onsubmit="return confirm('Revoke this invite?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
