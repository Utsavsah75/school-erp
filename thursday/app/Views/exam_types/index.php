<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_exam_types')) ?></h5>
    <div class="d-none d-md-flex gap-2">
        <a href="<?= e(url('exam-schedule')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-calendar-week me-1"></i>Exam Schedule</a>
        <a href="<?= e(url('exam-grades')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-award me-1"></i>Exam Grades</a>
    </div>
</div>
<p class="text-muted">Manage the exam type list (Terminal, Mid Term, Final, Weekly, Monthly, Half Yearly, Annual, Practical, ...) used by the Exam Schedule form.</p>

<div class="row g-3">
    <!-- ==================== Create New Exam Type ==================== -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><?= e(t('h_create_new_exam_type')) ?></h6>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('exam-types')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Exam Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="60"
                            class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('name')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(t('th_description')) ?></label>
                        <textarea name="description" rows="2" maxlength="255"
                            class="form-control <?= field_error($errors, 'description') ? 'is-invalid' : '' ?>"><?= e(old('description')) ?></textarea>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'description')) ?></div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active"><?= e(t('status_active')) ?></label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Submit</button>
                    <a href="<?= e(url('exam-types')) ?>" class="btn btn-light">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== All Exam Types ==================== -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><?= e(t('h_all_exam_types')) ?></h6>

                <form method="GET" action="<?= e(url('exam-types')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-md-8">
                        <label class="form-label small text-muted mb-1">Search Name</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>

                <?php if (empty($types)): ?>
                <p class="text-muted text-center py-4 mb-0">No exam types found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead>
                            <tr>
                                <th><?= e(t('th_name')) ?></th>
                                <th><?= e(t('th_description')) ?></th>
                                <th><?= e(t('th_status')) ?></th>
                                <th class="text-end"><?= e(t('th_actions')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $t): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($t['name']) ?></td>
                                <td class="text-muted small"><?= e($t['description'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($t['is_active'])): ?>
                                    <span class="badge bg-success-subtle text-success"><?= e(t('status_active')) ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e(t('status_inactive')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"
                                        data-bs-toggle="collapse" data-bs-target="#editType<?= e($t['id']) ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button type="submit" form="delete-type-<?= e($t['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                                        ><i class="bi bi-trash-fill"></i></button>
                                    <form id="delete-type-<?= e($t['id']) ?>" method="POST" action="<?= e(url('exam-types/' . $t['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this exam type? This action cannot be undone.">
                                        <?= csrf_field() ?></form>
                                </td>
                            </tr>
                            <tr class="collapse" id="editType<?= e($t['id']) ?>">
                                <td colspan="4" class="bg-light">
                                    <form method="POST" action="<?= e(url('exam-types/' . $t['id'])) ?>" class="row g-2 align-items-end py-2">
                                        <?= csrf_field() ?>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1"><?= e(t('th_name')) ?></label>
                                            <input type="text" name="name" class="form-control form-control-sm" value="<?= e($t['name']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1"><?= e(t('th_description')) ?></label>
                                            <input type="text" name="description" class="form-control form-control-sm" value="<?= e($t['description'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2 form-check">
                                            <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= !empty($t['is_active']) ? 'checked' : '' ?>>
                                            <label class="form-check-label small"><?= e(t('status_active')) ?></label>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100"><?= e(t('save')) ?></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
