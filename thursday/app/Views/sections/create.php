<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_add_new_section')) ?></h5>
    <a href="<?= e(url('sections')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to All Sections</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('sections')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Section Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>" value="<?= e(old('name')) ?>" required>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Section Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control <?= field_error($errors, 'code') ? 'is-invalid' : '' ?>" value="<?= e(old('code', $nextSectionCode)) ?>" maxlength="20">
                    <div class="form-text">Leave as suggested, or type your own.</div>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'code')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select <?= field_error($errors, 'class_id') ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= (string) old('class_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'class_id')) ?></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= e(t('th_teacher')) ?></label>
                    <select name="class_teacher_id" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= e($t['id']) ?>" <?= (string) old('class_teacher_id') === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(t('th_room_number')) ?></label>
                    <input type="text" name="room_number" class="form-control" value="<?= e(old('room_number')) ?>" maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(t('th_capacity')) ?></label>
                    <input type="number" name="capacity" min="0" class="form-control <?= field_error($errors, 'capacity') ? 'is-invalid' : '' ?>" value="<?= e(old('capacity')) ?>">
                    <div class="invalid-feedback"><?= e(field_error($errors, 'capacity')) ?></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= e(t('th_shift')) ?></label>
                    <select name="shift" class="form-select">
                        <?php foreach (CLASS_SECTION_SHIFTS as $sv => $sl): ?>
                        <option value="<?= e($sv) ?>" <?= old('shift', 'morning') === $sv ? 'selected' : '' ?>><?= e(tr_const(CLASS_SECTION_SHIFTS, $sv)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label"><?= e(t('th_status')) ?></label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?= old('status', 'active') === 'active' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="statusActive"><?= e(t('status_active')) ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?= old('status') === 'inactive' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="statusInactive"><?= e(t('status_inactive')) ?></label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label"><?= e(t('th_description')) ?></label>
                    <textarea name="description" class="form-control" rows="3"><?= e(old('description')) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Save</button>
                <button type="submit" name="save_and_add_another" value="1" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Save &amp; Add Another</button>
                <button type="reset" class="btn btn-light"><?= e(t('reset')) ?></button>
                <a href="<?= e(url('sections')) ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
