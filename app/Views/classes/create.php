<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Add New Class</h5>
    <a href="<?= e(url('classes')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to All Classes</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('classes')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Class Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>" value="<?= e(old('name')) ?>" required>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Class Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control <?= field_error($errors, 'code') ? 'is-invalid' : '' ?>" value="<?= e(old('code', $nextClassCode)) ?>" maxlength="20">
                    <div class="form-text">Leave as suggested, or type your own.</div>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'code')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Class Teacher <span class="text-danger">*</span></label>
                    <select name="class_teacher_id" class="form-select <?= field_error($errors, 'class_teacher_id') ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= e($t['id']) ?>" <?= (string) old('class_teacher_id') === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'class_teacher_id')) ?></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" value="<?= e(old('room_number')) ?>" maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" min="0" class="form-control <?= field_error($errors, 'capacity') ? 'is-invalid' : '' ?>" value="<?= e(old('capacity')) ?>">
                    <div class="invalid-feedback"><?= e(field_error($errors, 'capacity')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select">
                        <?php foreach (CLASS_SECTION_SHIFTS as $sv => $sl): ?>
                        <option value="<?= e($sv) ?>" <?= old('shift', 'morning') === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="form-select <?= field_error($errors, 'academic_year_id') ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($academicYears as $ay): ?>
                        <option value="<?= e($ay['id']) ?>" <?= (string) old('academic_year_id') === (string) $ay['id'] || (!old('academic_year_id') && !empty($ay['is_current'])) ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= e(field_error($errors, 'academic_year_id')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject Group</label>
                    <input type="text" name="subject_group" class="form-control" value="<?= e(old('subject_group')) ?>" placeholder="e.g. Science, Commerce">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Class Monitor</label>
                    <input type="text" name="class_monitor_name" class="form-control" value="<?= e(old('class_monitor_name')) ?>" placeholder="Student name">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" value="<?= e(old('start_time')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" value="<?= e(old('end_time')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?= old('status', 'active') === 'active' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="statusActive">Active</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?= old('status') === 'inactive' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="statusInactive">Inactive</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e(old('description')) ?></textarea>
                </div>
            </div>

            <div class="form-text mt-2 mb-3"><i class="bi bi-info-circle me-1"></i>Sections for this class can be added afterwards from <a href="<?= e(url('sections/create')) ?>">Sections &raquo; Add New Section</a>.</div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Save</button>
                <button type="submit" name="save_and_add_another" value="1" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Save &amp; Add Another</button>
                <button type="reset" class="btn btn-light">Reset</button>
                <a href="<?= e(url('classes')) ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
