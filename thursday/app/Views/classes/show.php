<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_class_details')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('classes/' . $class['id'] . '/edit')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-fill me-1"></i>Edit Class</a>
        <a href="<?= e(url('classes')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to All Classes</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><?= e(t('h_class_information')) ?></h6>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Class Name</div><div class="fw-semibold"><?= e($class['name']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class Code</div><div class="fw-semibold"><?= e($class['code']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class Teacher</div><div class="fw-semibold"><?= e($class['teacher_name'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Room</div><div class="fw-semibold"><?= e($class['room_number'] ?: '—') ?></div></div>

            <div class="col-md-3"><div class="text-muted small">Capacity</div><div class="fw-semibold"><?= e($class['capacity'] ?: '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Students</div><div class="fw-semibold"><?= count($students) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Academic Year</div><div class="fw-semibold"><?= e($class['academic_year_label'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Shift</div><div class="fw-semibold"><?= e(tr_const(CLASS_SECTION_SHIFTS, $class['shift'])) ?></div></div>

            <div class="col-md-3">
                <div class="text-muted small">Status</div>
                <?php if ($class['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success"><?= e(t('status_active')) ?></span>
                <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary"><?= e(t('status_inactive')) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-md-3"><div class="text-muted small">Sections</div><div class="fw-semibold"><?= count($sections) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Subject Group</div><div class="fw-semibold"><?= e($class['subject_group'] ?: '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class Monitor</div><div class="fw-semibold"><?= e($class['class_monitor_name'] ?: '—') ?></div></div>

            <?php if (!empty($class['description'])): ?>
            <div class="col-12"><div class="text-muted small">Description</div><div><?= nl2br(e($class['description'])) ?></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <ul class="nav nav-tabs" id="classTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-students" type="button"><?= e(t('th_students')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sections" type="button">Sections</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-subjects" type="button"><?= e(t('th_subjects')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-routine" type="button"><?= e(t('th_routine')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button"><?= e(t('th_attendance')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exams" type="button"><?= e(t('th_exams')) ?></button></li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-students">
                <?php if (empty($students)): ?>
                <p class="text-muted text-center py-4 mb-0">No students enrolled in this class yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead><tr><th><?= e(t('th_roll_no2')) ?></th><th><?= e(t('th_name')) ?></th><th><?= e(t('th_section')) ?></th><th><?= e(t('th_status')) ?></th><th class="text-end"><?= e(t('th_actions')) ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= e($s['roll_number']) ?></td>
                                <td><?= e($s['full_name']) ?></td>
                                <td><?= e($s['section_name'] ?? '—') ?></td>
                                <td><span class="badge <?= status_badge_class($s['status']) ?>"><?= e(st($s['status'])) ?></span></td>
                                <td class="text-end"><a href="<?= e(url('students/' . $s['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye-fill"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="tab-sections">
                <?php if (empty($sections)): ?>
                <p class="text-muted text-center py-4 mb-0">No sections created for this class yet. <a href="<?= e(url('sections/create')) ?>">Add one</a>.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead><tr><th><?= e(t('th_section')) ?></th><th><?= e(t('th_code')) ?></th><th><?= e(t('th_room')) ?></th><th><?= e(t('th_capacity')) ?></th><th><?= e(t('th_status')) ?></th><th class="text-end"><?= e(t('th_actions')) ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($sections as $sec): ?>
                            <tr>
                                <td><?= e($sec['name']) ?></td>
                                <td><?= e($sec['code']) ?></td>
                                <td><?= e($sec['room_number'] ?: '—') ?></td>
                                <td><?= e($sec['capacity'] ?: '—') ?></td>
                                <td><span class="badge <?= status_badge_class($sec['status']) ?>"><?= e(st($sec['status'])) ?></span></td>
                                <td class="text-end"><a href="<?= e(url('sections/' . $sec['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye-fill"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="tab-subjects">
                <?php if (empty($subjects)): ?>
                <p class="text-muted text-center py-4 mb-0">No subjects assigned to this class yet. <a href="<?= e(url('subjects')) ?>">Add one</a>.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead><tr><th><?= e(t('th_code')) ?></th><th><?= e(t('th_subject')) ?></th><th><?= e(t('th_type')) ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($subjects as $sub): ?>
                            <tr><td><?= e($sub['code']) ?></td><td><?= e($sub['name']) ?></td><td><?= e(tr_const(SUBJECT_TYPES, $sub['subject_type'])) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="tab-routine">
                <p class="text-muted text-center py-4 mb-0">View this class's weekly routine in <a href="<?= e(url('timetable')) ?>">Timetable</a>.</p>
            </div>
            <div class="tab-pane fade" id="tab-attendance">
                <p class="text-muted text-center py-4 mb-0">View attendance for this class in <a href="<?= e(url('attendance')) ?>">Student Attendance</a>.</p>
            </div>
            <div class="tab-pane fade" id="tab-exams">
                <p class="text-muted text-center py-4 mb-0">View exams for this class in <a href="<?= e(url('exam-schedule')) ?>">Exam Schedule</a>.</p>
            </div>
        </div>
    </div>
</div>
