<?php $pageTitle = 'Change Password'; ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-key-fill me-2"></i>Change Password</h5>
                <form method="POST" action="<?= e(url('change-password')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control <?= field_error($errors, 'current_password') ? 'is-invalid' : '' ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'current_password')) ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= e(t('th_new_password')) ?></label>
                        <input type="password" name="password" class="form-control <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" required minlength="8">
                        <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                        <div class="form-text">At least 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?= e(t('th_confirm_new_password')) ?></label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
