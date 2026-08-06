<?php
$qs = fn (array $extra = []) => http_build_query(array_merge([
    'search' => $search, 'sort' => $sort, 'direction' => $direction,
], $filters, $extra));
$sortLink = function (string $col) use ($sort, $direction, $qs) {
    $nextDir = ($sort === $col && $direction === 'ASC') ? 'DESC' : 'ASC';
    return e(url('exam-schedule') . '?' . $qs(['sort' => $col, 'direction' => $nextDir]));
};
$sortIcon = function (string $col) use ($sort, $direction) {
    if ($sort !== $col) return '<i class="bi bi-arrow-down-up text-muted small"></i>';
    return $direction === 'ASC' ? '<i class="bi bi-sort-alpha-down"></i>' : '<i class="bi bi-sort-alpha-up-alt"></i>';
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_exam_schedule')) ?></h5>
    <div class="d-none d-md-flex gap-2">
        <a href="<?= e(url('exam-types')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags me-1"></i>Exam Types</a>
        <a href="<?= e(url('exam-grades')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-award me-1"></i>Exam Grades</a>
    </div>
</div>

<div class="row g-3">
    <!-- ==================== Add New Exam ==================== -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><?= e(t('h_add_new_exam')) ?></h6>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('exam-schedule')) ?>" id="examScheduleForm" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Exam Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" maxlength="150"
                            class="form-control <?= field_error($errors, 'exam_name') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('exam_name')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'exam_name')) ?></div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-select <?= field_error($errors, 'academic_year_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($academicYears as $ay): ?>
                                <option value="<?= e($ay['id']) ?>" <?= (string) old('academic_year_id', $ay['is_current'] ? $ay['id'] : '') === (string) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'academic_year_id')) ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Exam Type <span class="text-danger">*</span></label>
                            <select name="exam_type_id" class="form-select <?= field_error($errors, 'exam_type_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($examTypes as $et): ?>
                                <option value="<?= e($et['id']) ?>" <?= (string) old('exam_type_id') === (string) $et['id'] ? 'selected' : '' ?>><?= e($et['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'exam_type_id')) ?></div>
                            <div class="form-text">No type? <a href="<?= e(url('exam-types')) ?>">Add one</a>.</div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-4 mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="class_id" id="createClass" class="form-select <?= field_error($errors, 'class_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= (string) old('class_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'class_id')) ?></div>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section_id" id="createSection" class="form-select <?= field_error($errors, 'section_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select Class First --</option>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'section_id')) ?></div>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" id="createSubject" class="form-select <?= field_error($errors, 'subject_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select Class First --</option>
                            </select>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'subject_id')) ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" name="exam_date" class="form-control <?= field_error($errors, 'exam_date') ? 'is-invalid' : '' ?>" value="<?= e(old('exam_date')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'exam_date')) ?></div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control <?= field_error($errors, 'start_time') ? 'is-invalid' : '' ?>" value="<?= e(old('start_time')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'start_time')) ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control <?= field_error($errors, 'end_time') ? 'is-invalid' : '' ?>" value="<?= e(old('end_time')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'end_time')) ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= e(t('th_room_number')) ?></label>
                        <input type="text" name="room_number" maxlength="30" class="form-control" value="<?= e(old('room_number')) ?>">
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Maximum Marks <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="max_marks" class="form-control <?= field_error($errors, 'max_marks') ? 'is-invalid' : '' ?>" value="<?= e(old('max_marks', '100')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'max_marks')) ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Passing Marks <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="passing_marks" class="form-control <?= field_error($errors, 'passing_marks') ? 'is-invalid' : '' ?>" value="<?= e(old('passing_marks', '40')) ?>" required>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'passing_marks')) ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= e(t('th_description')) ?></label>
                        <textarea name="description" rows="2" maxlength="1000" class="form-control"><?= e(old('description')) ?></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active"><?= e(t('status_active')) ?></label>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Submit</button>
                    <a href="<?= e(url('exam-schedule')) ?>" class="btn btn-light">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== All Exam Schedule ==================== -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><?= e(t('h_all_exam_schedule')) ?></h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= e(url('exam-schedule/print') . '?' . $qs()) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer-fill me-1"></i>Print</a>
                        <a href="<?= e(url('exam-schedule/export-excel') . '?' . $qs()) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel-fill me-1"></i>Excel</a>
                        <a href="<?= e(url('exam-schedule/export-pdf') . '?' . $qs()) ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF</a>
                        <a href="<?= e(url('exam-schedule/import')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload me-1"></i>Import</a>
                    </div>
                </div>

                <form method="GET" action="<?= e(url('exam-schedule')) ?>" class="row g-2 align-items-end mb-3" id="filterForm">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Search Exam / Subject</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                        <select name="class_id" id="filterClass" class="form-select form-select-sm">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= (string) $filters['class_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                        <select name="section_id" id="filterSection" class="form-select form-select-sm">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?= e($s['id']) ?>" <?= (string) $filters['section_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_subject')) ?></label>
                        <select name="subject_id" id="filterSubject" class="form-select form-select-sm">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $sub): ?>
                            <option value="<?= e($sub['id']) ?>" <?= (string) $filters['subject_id'] === (string) $sub['id'] ? 'selected' : '' ?>><?= e($sub['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_exam_type')) ?></label>
                        <select name="exam_type_id" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <?php foreach ($examTypes as $et): ?>
                            <option value="<?= e($et['id']) ?>" <?= (string) $filters['exam_type_id'] === (string) $et['id'] ? 'selected' : '' ?>><?= e($et['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_date')) ?></label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filters['date']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_status')) ?></label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <?php foreach (EXAM_RECORD_STATUSES as $sv => $sl): ?>
                            <option value="<?= e($sv) ?>" <?= $filters['status'] === $sv ? 'selected' : '' ?>><?= e(tr_const(EXAM_RECORD_STATUSES, $sv)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1"><?= e(t('th_academic_year')) ?></label>
                        <select name="academic_year_id" class="form-select form-select-sm">
                            <option value="">All Years</option>
                            <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= e($ay['id']) ?>" <?= (string) $filters['academic_year_id'] === (string) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="<?= e(url('exam-schedule')) ?>" class="btn btn-sm btn-light w-100">Clear Filters</a>
                    </div>
                </form>

                <?php if (empty($result['data'])): ?>
                <p class="text-muted text-center py-4 mb-0">No exam schedule found.</p>
                <?php else: ?>
                <form method="POST" action="<?= e(url('exam-schedule/bulk-delete')) ?>" class="confirm-delete" data-confirm-message="Delete the selected exam schedule rows? This action cannot be undone." id="bulkDeleteForm">
                    <?= csrf_field() ?>
                    <div class="d-flex justify-content-end mb-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill me-1"></i>Delete Selected</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllSchedule"></th>
                                    <th><a href="<?= $sortLink('exam_name') ?>" class="text-decoration-none text-reset">Exam Name <?= $sortIcon('exam_name') ?></a></th>
                                    <th><?= e(t('th_subject')) ?></th>
                                    <th><a href="<?= $sortLink('class') ?>" class="text-decoration-none text-reset">Class <?= $sortIcon('class') ?></a></th>
                                    <th><?= e(t('th_section')) ?></th>
                                    <th><?= e(t('th_time')) ?></th>
                                    <th><a href="<?= $sortLink('exam_date') ?>" class="text-decoration-none text-reset">Date <?= $sortIcon('exam_date') ?></a></th>
                                    <th><?= e(t('th_status')) ?></th>
                                    <th class="text-end"><?= e(t('th_action')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data'] as $s): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?= e($s['id']) ?>" class="schedule-checkbox"></td>
                                    <td class="fw-semibold"><?= e($s['exam_name']) ?><div class="small text-muted"><?= e($s['exam_type_name'] ?? '—') ?></div></td>
                                    <td><?= e($s['subject_name'] ?? '—') ?></td>
                                    <td><?= e($s['class_name'] ?? '—') ?></td>
                                    <td><?= e($s['section_name'] ?? '—') ?></td>
                                    <td><?= e(date('h.i a', strtotime($s['start_time']))) ?> - <?= e(date('h.i a', strtotime($s['end_time']))) ?></td>
                                    <td><?= e(format_date($s['exam_date'])) ?></td>
                                    <td>
                                        <form method="POST" action="<?= e(url('exam-schedule/' . $s['id'] . '/toggle-status')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Toggle status">
                                                <span class="badge <?= $s['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= e(st($s['status'])) ?></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="View" data-bs-toggle="collapse" data-bs-target="#viewSchedule<?= e($s['id']) ?>"><i class="bi bi-eye-fill"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="collapse" data-bs-target="#editSchedule<?= e($s['id']) ?>"><i class="bi bi-pencil-fill"></i></button>
                                        <button type="submit" form="delete-schedule-<?= e($s['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                        <form id="delete-schedule-<?= e($s['id']) ?>" method="POST" action="<?= e(url('exam-schedule/' . $s['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this exam schedule? This action cannot be undone."><?= csrf_field() ?></form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="viewSchedule<?= e($s['id']) ?>">
                                    <td colspan="9" class="bg-light small">
                                        <strong>Room:</strong> <?= e($s['room_number'] ?: '—') ?> &nbsp;|&nbsp;
                                        <strong>Max Marks:</strong> <?= e($s['max_marks']) ?> &nbsp;|&nbsp;
                                        <strong>Passing Marks:</strong> <?= e($s['passing_marks']) ?> &nbsp;|&nbsp;
                                        <strong>Academic Year:</strong> <?= e($s['academic_year_label'] ?? '—') ?> &nbsp;|&nbsp;
                                        <strong>Description:</strong> <?= e($s['description'] ?: '—') ?>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editSchedule<?= e($s['id']) ?>">
                                    <td colspan="9" class="bg-light">
                                        <form method="POST" action="<?= e(url('exam-schedule/' . $s['id'])) ?>" class="row g-2 align-items-end py-2 edit-schedule-form">
                                            <?= csrf_field() ?>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Exam Name</label>
                                                <input type="text" name="exam_name" class="form-control form-control-sm" value="<?= e($s['exam_name']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_academic_year')) ?></label>
                                                <select name="academic_year_id" class="form-select form-select-sm">
                                                    <?php foreach ($academicYears as $ay): ?>
                                                    <option value="<?= e($ay['id']) ?>" <?= (string) $s['academic_year_id'] === (string) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_exam_type')) ?></label>
                                                <select name="exam_type_id" class="form-select form-select-sm">
                                                    <?php foreach ($examTypes as $et): ?>
                                                    <option value="<?= e($et['id']) ?>" <?= (string) $s['exam_type_id'] === (string) $et['id'] ? 'selected' : '' ?>><?= e($et['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_class')) ?></label>
                                                <select name="class_id" class="form-select form-select-sm edit-class-select" data-current-section="<?= e($s['section_id']) ?>" data-current-subject="<?= e($s['subject_id']) ?>">
                                                    <?php foreach ($classes as $c): ?>
                                                    <option value="<?= e($c['id']) ?>" <?= (string) $s['class_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_section')) ?></label>
                                                <select name="section_id" class="form-select form-select-sm edit-section-select">
                                                    <option value="<?= e($s['section_id']) ?>" selected><?= e($s['section_name']) ?></option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_subject')) ?></label>
                                                <select name="subject_id" class="form-select form-select-sm edit-subject-select">
                                                    <option value="<?= e($s['subject_id']) ?>" selected><?= e($s['subject_name']) ?></option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_date')) ?></label>
                                                <input type="date" name="exam_date" class="form-control form-control-sm" value="<?= e($s['exam_date']) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Start</label>
                                                <input type="time" name="start_time" class="form-control form-control-sm" value="<?= e(substr($s['start_time'], 0, 5)) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">End</label>
                                                <input type="time" name="end_time" class="form-control form-control-sm" value="<?= e(substr($s['end_time'], 0, 5)) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1"><?= e(t('th_room')) ?></label>
                                                <input type="text" name="room_number" class="form-control form-control-sm" value="<?= e($s['room_number']) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-1">Max</label>
                                                <input type="number" step="0.01" name="max_marks" class="form-control form-control-sm" value="<?= e($s['max_marks']) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-1">Pass</label>
                                                <input type="number" step="0.01" name="passing_marks" class="form-control form-control-sm" value="<?= e($s['passing_marks']) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1"><?= e(t('th_description')) ?></label>
                                                <input type="text" name="description" class="form-control form-control-sm" value="<?= e($s['description']) ?>">
                                            </div>
                                            <div class="col-md-1 form-check">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $s['status'] === 'active' ? 'checked' : '' ?>>
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
                </form>
                <?= paginate_links($result, url('exam-schedule'), array_merge(['search' => $search, 'sort' => $sort, 'direction' => $direction], $filters)) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var sectionsUrl = <?= json_encode(url('exam-schedule/sections-for-class')) ?>;
    var subjectsUrl = <?= json_encode(url('exam-schedule/subjects-for-class')) ?>;

    function loadInto(select, url, placeholder, keepValue) {
        select.disabled = true;
        fetch(url).then(function (r) { return r.json(); }).then(function (rows) {
            select.innerHTML = '';
            select.appendChild(new Option(placeholder, ''));
            rows.forEach(function (row) {
                select.appendChild(new Option(row.name, row.id, false, String(row.id) === String(keepValue || '')));
            });
            select.disabled = false;
        }).catch(function () { select.disabled = false; });
    }

    // -------- Create form: Class -> Section + Subject --------
    var createClass = document.getElementById('createClass');
    var createSection = document.getElementById('createSection');
    var createSubject = document.getElementById('createSubject');
    if (createClass) {
        createClass.addEventListener('change', function () {
            if (!this.value) {
                createSection.innerHTML = '<option value="">-- Select Class First --</option>';
                createSubject.innerHTML = '<option value="">-- Select Class First --</option>';
                return;
            }
            loadInto(createSection, sectionsUrl + '/' + this.value, '-- Select --', <?= json_encode(old('section_id')) ?>);
            loadInto(createSubject, subjectsUrl + '/' + this.value, '-- Select --', <?= json_encode(old('subject_id')) ?>);
        });
        if (createClass.value) createClass.dispatchEvent(new Event('change'));
    }

    // -------- Filter bar: Class -> Section + Subject --------
    var filterClass = document.getElementById('filterClass');
    var filterSection = document.getElementById('filterSection');
    var filterSubject = document.getElementById('filterSubject');
    if (filterClass) {
        filterClass.addEventListener('change', function () {
            if (!this.value) return;
            loadInto(filterSection, sectionsUrl + '/' + this.value, 'All Sections');
            loadInto(filterSubject, subjectsUrl + '/' + this.value, 'All Subjects');
        });
    }

    // -------- Edit rows: Class -> Section + Subject (per row) --------
    document.querySelectorAll('.edit-class-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var row = this.closest('form');
            var sectionSel = row.querySelector('.edit-section-select');
            var subjectSel = row.querySelector('.edit-subject-select');
            loadInto(sectionSel, sectionsUrl + '/' + this.value, '-- Select --');
            loadInto(subjectSel, subjectsUrl + '/' + this.value, '-- Select --');
        });
    });

    // -------- Bulk select-all --------
    var selectAll = document.getElementById('selectAllSchedule');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.schedule-checkbox').forEach(function (cb) { cb.checked = selectAll.checked; });
        });
    }
})();
</script>
