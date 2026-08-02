<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Class Details</h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('classes/' . $class['id'] . '/edit')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-fill me-1"></i>Edit Class</a>
        <a href="<?= e(url('classes')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to All Classes</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Class Information</h6>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Class Name</div><div class="fw-semibold"><?= e($class['name']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class Code</div><div class="fw-semibold"><?= e($class['code']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class Teacher</div><div class="fw-semibold"><?= e($class['teacher_name'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Room</div><div class="fw-semibold"><?= e($class['room_number'] ?: '—') ?></div></div>

            <div class="col-md-3"><div class="text-muted small">Capacity</div><div class="fw-semibold"><?= e($class['capacity'] ?: '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Students</div><div class="fw-semibold"><?= count($students) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Academic Year</div><div class="fw-semibold"><?= e($class['academic_year_label'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Shift</div><div class="fw-semibold"><?= e(CLASS_SECTION_SHIFTS[$class['shift']] ?? ucfirst($class['shift'])) ?></div></div>

            <div class="col-md-3">
                <div class="text-muted small">Status</div>
                <?php if ($class['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success">Active</span>
                <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
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
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-students" type="button">Students</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sections" type="button">Sections</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-subjects" type="button">Subjects</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-routine" type="button">Routine</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button">Attendance</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exams" type="button">Exams</button></li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-students">
                <?php if (empty($students)): ?>
                <p class="text-muted text-center py-4 mb-0">No students enrolled in this class yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead><tr><th>Roll No</th><th>Name</th><th>Section</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= e($s['roll_number']) ?></td>
                                <td><?= e($s['full_name']) ?></td>
                                <td><?= e($s['section_name'] ?? '—') ?></td>
                                <td><span class="badge <?= status_badge_class($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
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
                        <thead><tr><th>Section</th><th>Code</th><th>Room</th><th>Capacity</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($sections as $sec): ?>
                            <tr>
                                <td><?= e($sec['name']) ?></td>
                                <td><?= e($sec['code']) ?></td>
                                <td><?= e($sec['room_number'] ?: '—') ?></td>
                                <td><?= e($sec['capacity'] ?: '—') ?></td>
                                <td><span class="badge <?= status_badge_class($sec['status']) ?>"><?= e(ucfirst($sec['status'])) ?></span></td>
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
                        <thead><tr><th>Code</th><th>Subject</th><th>Type</th></tr></thead>
                        <tbody>
                        <?php foreach ($subjects as $sub): ?>
                            <tr><td><?= e($sub['code']) ?></td><td><?= e($sub['name']) ?></td><td><?= e(SUBJECT_TYPES[$sub['subject_type']] ?? ucfirst($sub['subject_type'])) ?></td></tr>
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
