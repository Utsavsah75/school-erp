<?php
$qs = fn (array $extra = []) => http_build_query(array_merge([
    'search' => $search, 'search_point' => $pointSearch, 'status' => $statusFilter,
    'sort' => $sort, 'direction' => $direction,
], $extra));
$sortLink = function (string $col) use ($sort, $direction, $qs) {
    $nextDir = ($sort === $col && $direction === 'ASC') ? 'DESC' : 'ASC';
    return e(url('exam-grades') . '?' . $qs(['sort' => $col, 'direction' => $nextDir]));
};
$sortIcon = function (string $col) use ($sort, $direction) {
    if ($sort !== $col) return '<i class="bi bi-arrow-down-up text-muted small"></i>';
    return $direction === 'ASC' ? '<i class="bi bi-sort-numeric-down"></i>' : '<i class="bi bi-sort-numeric-up-alt"></i>';
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_exam_grades')) ?></h5>
    <div class="d-none d-md-flex gap-2">
        <a href="<?= e(url('exam-schedule')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-calendar-week me-1"></i>Exam Schedule</a>
        <a href="<?= e(url('exam-types')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags me-1"></i>Exam Types</a>
    </div>
</div>

<div class="row g-3">
    <!-- ==================== Add New Grades ==================== -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><?= e(t('h_add_new_grades')) ?></h6>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('exam-grades')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Grade Name <span class="text-danger">*</span></label>
                        <input type="text" name="grade_name" maxlength="20" placeholder="e.g. A+"
                            class="form-control <?= field_error($errors, 'grade_name') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('grade_name')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'grade_name')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade Point <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="10" name="grade_point"
                            class="form-control <?= field_error($errors, 'grade_point') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('grade_point')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'grade_point')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percentage From <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="percent_from"
                            class="form-control <?= field_error($errors, 'percent_from') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('percent_from')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'percent_from')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percent Upto <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="percent_upto"
                            class="form-control <?= field_error($errors, 'percent_upto') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('percent_upto')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'percent_upto')) ?></div>
                        <div class="form-text">Ranges can't overlap an existing grade band.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea name="remarks" rows="2" maxlength="255" placeholder="e.g. Good Result"
                            class="form-control"><?= e(old('remarks')) ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active"><?= e(t('status_active')) ?></label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Submit</button>
                    <a href="<?= e(url('exam-grades')) ?>" class="btn btn-light">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== Exam Grade Lists ==================== -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><?= e(t('h_exam_grade_lists')) ?></h6>

                <form method="GET" action="<?= e(url('exam-grades')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Search by Grade Name</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Search by Point</label>
                        <input type="text" name="search_point" class="form-control form-control-sm" value="<?= e($pointSearch) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_status')) ?></label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <?php foreach (EXAM_RECORD_STATUSES as $sv => $sl): ?>
                            <option value="<?= e($sv) ?>" <?= $statusFilter === $sv ? 'selected' : '' ?>><?= e(tr_const(EXAM_RECORD_STATUSES, $sv)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>

                <?php if (empty($result['data'])): ?>
                <p class="text-muted text-center py-4 mb-0">No grades found.</p>
                <?php else: ?>
                <form method="POST" action="<?= e(url('exam-grades/bulk-delete')) ?>" class="confirm-delete" data-confirm-message="Delete the selected grades? This action cannot be undone." id="bulkDeleteGradesForm">
                    <?= csrf_field() ?>
                    <div class="d-flex justify-content-end mb-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill me-1"></i>Delete Selected</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllGrades"></th>
                                    <th><a href="<?= $sortLink('grade_name') ?>" class="text-decoration-none text-reset">Grade Name <?= $sortIcon('grade_name') ?></a></th>
                                    <th><a href="<?= $sortLink('grade_point') ?>" class="text-decoration-none text-reset">Grade Point <?= $sortIcon('grade_point') ?></a></th>
                                    <th><a href="<?= $sortLink('percent_from') ?>" class="text-decoration-none text-reset">Percent From <?= $sortIcon('percent_from') ?></a></th>
                                    <th>Percent Upto</th>
                                    <th>Comment</th>
                                    <th><?= e(t('th_status')) ?></th>
                                    <th class="text-end"><?= e(t('th_action')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data'] as $g): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?= e($g['id']) ?>" class="grade-checkbox"></td>
                                    <td class="fw-semibold"><?= e($g['grade_name']) ?></td>
                                    <td><?= e(number_format((float) $g['grade_point'], 2)) ?></td>
                                    <td><?= e(number_format((float) $g['percent_from'], 2)) ?></td>
                                    <td><?= e(number_format((float) $g['percent_upto'], 2)) ?></td>
                                    <td class="text-muted small"><?= e($g['remarks'] ?: '—') ?></td>
                                    <td>
                                        <form method="POST" action="<?= e(url('exam-grades/' . $g['id'] . '/toggle-status')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Toggle status">
                                                <span class="badge <?= $g['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= e(st($g['status'])) ?></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="View" data-bs-toggle="collapse" data-bs-target="#viewGrade<?= e($g['id']) ?>"><i class="bi bi-eye-fill"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="collapse" data-bs-target="#editGrade<?= e($g['id']) ?>"><i class="bi bi-pencil-fill"></i></button>
                                        <button type="submit" form="delete-grade-<?= e($g['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                        <form id="delete-grade-<?= e($g['id']) ?>" method="POST" action="<?= e(url('exam-grades/' . $g['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this grade? This action cannot be undone."><?= csrf_field() ?></form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="viewGrade<?= e($g['id']) ?>">
                                    <td colspan="8" class="bg-light small">
                                        <strong>Range:</strong> <?= e($g['percent_from']) ?>% – <?= e($g['percent_upto']) ?>% &nbsp;|&nbsp;
                                        <strong>Remarks:</strong> <?= e($g['remarks'] ?: '—') ?>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editGrade<?= e($g['id']) ?>">
                                    <td colspan="8" class="bg-light">
                                        <form method="POST" action="<?= e(url('exam-grades/' . $g['id'])) ?>" class="row g-2 align-items-end py-2">
                                            <?= csrf_field() ?>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_name')) ?></label>
                                                <input type="text" name="grade_name" class="form-control form-control-sm" value="<?= e($g['grade_name']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Point</label>
                                                <input type="number" step="0.01" name="grade_point" class="form-control form-control-sm" value="<?= e($g['grade_point']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">From %</label>
                                                <input type="number" step="0.01" name="percent_from" class="form-control form-control-sm" value="<?= e($g['percent_from']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Upto %</label>
                                                <input type="number" step="0.01" name="percent_upto" class="form-control form-control-sm" value="<?= e($g['percent_upto']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_remarks')) ?></label>
                                                <input type="text" name="remarks" class="form-control form-control-sm" value="<?= e($g['remarks']) ?>">
                                            </div>
                                            <div class="col-md-1 form-check">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $g['status'] === 'active' ? 'checked' : '' ?>>
                                                <label class="form-check-label small"><?= e(t('status_active')) ?></label>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-sm btn-primary w-100"><?= e(t('save')) ?></button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <?= paginate_links($result, url('exam-grades'), ['search' => $search, 'search_point' => $pointSearch, 'status' => $statusFilter, 'sort' => $sort, 'direction' => $direction]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAllGrades')?.addEventListener('change', function () {
    document.querySelectorAll('.grade-checkbox').forEach(function (cb) { cb.checked = this.checked; }.bind(this));
});
</script>
