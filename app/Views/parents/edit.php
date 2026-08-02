<?php
/** Expects: $parentRow, $errors (from Session::getErrors(), passed by the controller) */
$old = fn($key, $default = '') => e((string) old($key, $parentRow[$key] ?? $default));
$fieldError = fn($key) => field_error($errors, $key);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Edit Parent / Guardian</h5>
    <a href="<?= e(url('parents/' . $parentRow['id'])) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e(url('parents/' . $parentRow['id'])) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Father Name</label>
                    <input type="text" name="father_name" class="form-control" value="<?= $old('father_name') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mother Name</label>
                    <input type="text" name="mother_name" class="form-control" value="<?= $old('mother_name') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control" value="<?= $old('guardian_name') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="guardian_relationship" class="form-control" value="<?= $old('guardian_relationship') ?>" placeholder="e.g. Uncle">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control" value="<?= $old('occupation') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="phone_country_code" class="form-control" style="max-width:90px;" value="<?= $old('phone_country_code', '+977') ?>">
                        <input type="text" name="phone" class="form-control <?= $fieldError('phone') ? 'is-invalid' : '' ?>" value="<?= $old('phone') ?>" maxlength="15" required>
                    </div>
                    <div class="invalid-feedback d-block"><?= e($fieldError('phone')) ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control <?= $fieldError('email') ? 'is-invalid' : '' ?>" value="<?= $old('email') ?>">
                    <div class="invalid-feedback d-block"><?= e($fieldError('email')) ?></div>
                    <?php if (!empty($parentRow['user_id'])): ?>
                        <div class="form-text">This is the contact email shown on the profile. It also updates the portal login email below, unless you set a different Login Email explicitly.</div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($parentRow['user_id'])): ?>
                <div class="col-md-6">
                    <label class="form-label">Login Email <i class="bi bi-shield-lock text-muted" title="Used for the parent portal login and password reset emails"></i></label>
                    <input type="email" name="login_email" class="form-control <?= $fieldError('login_email') ? 'is-invalid' : '' ?>" value="<?= e((string) old('login_email', $login['email'] ?? '')) ?>">
                    <div class="invalid-feedback d-block"><?= e($fieldError('login_email')) ?></div>
                    <div class="form-text">This is the address that receives password reset links and portal sign-in. Leave as-is to keep it matched to Email above, or set it here directly if they need to differ.</div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= $old('address') ?>">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                <a href="<?= e(url('parents/' . $parentRow['id'])) ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
