<?php
/** Expects: $roles, $errors — rendered inside the main app layout (logged-in super_admin only) */
$pageTitle = 'Register New Admin/Staff Account';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-person-plus-fill me-1"></i>Register New Account</h5>
    <a href="<?= e(url('staff-invites')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-ticket-perforated me-1"></i> Staff Invites
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Creates a login directly — no OTP verification is needed since you're vouching for this person.
                    A temporary password is set below and emailed to them if SMTP is configured; they'll be required
                    to change it on first login.
                </p>

                <form method="POST" action="<?= e(url('admin/register')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control <?= field_error($errors, 'full_name') ? 'is-invalid' : '' ?>" value="<?= e(old('full_name')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'full_name')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select <?= field_error($errors, 'role') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= e($r) ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'role')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
                            <input type="tel" name="phone" class="form-control <?= field_error($errors, 'phone') ? 'is-invalid' : '' ?>" value="<?= e(old('phone')) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'phone')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-muted">(optional)</span></label>
                            <input type="text" name="username" class="form-control <?= field_error($errors, 'username') ? 'is-invalid' : '' ?>" value="<?= e(old('username')) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'username')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Temporary Password</label>
                            <input type="text" name="password" id="pwInput" class="form-control <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                            <div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="pwBar"></div></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="text" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-check-circle-fill me-1"></i> Create Account</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-1"></i>About this screen</h6>
                <p class="text-muted small mb-0">
                    Use this only for accounts a Super Admin is creating directly (Admin, Principal, Vice Principal,
                    Accountant, Librarian, Receptionist, Staff). Teachers, Parents and Students should use the public
                    self-registration wizard at <a href="<?= e(url('register')) ?>"><?= e(url('register')) ?></a> instead,
                    which verifies their identity via OTP against existing school records.
                </p>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/auth-common.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initPasswordStrengthMeter(document.getElementById('pwInput'), document.getElementById('pwBar'), null);
});
</script>
