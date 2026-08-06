<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_section_details')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('sections/' . $section['id'] . '/edit')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-fill me-1"></i>Edit Section</a>
        <a href="<?= e(url('sections')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to All Sections</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><?= e(t('h_section_information')) ?></h6>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Section Name</div><div class="fw-semibold"><?= e($section['name']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Section Code</div><div class="fw-semibold"><?= e($section['code']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Class</div><div class="fw-semibold"><a href="<?= e(url('classes/' . $section['class_id'])) ?>"><?= e($section['class_name'] ?? '—') ?></a></div></div>
            <div class="col-md-3"><div class="text-muted small">Teacher</div><div class="fw-semibold"><?= e($section['teacher_name'] ?? '—') ?></div></div>

            <div class="col-md-3"><div class="text-muted small">Room</div><div class="fw-semibold"><?= e($section['room_number'] ?: '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Capacity</div><div class="fw-semibold"><?= e($section['capacity'] ?: '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Students</div><div class="fw-semibold"><?= count($students) ?></div></div>
            <div class="col-md-3">
                <div class="text-muted small">Status</div>
                <?php if ($section['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success"><?= e(t('status_active')) ?></span>
                <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary"><?= e(t('status_inactive')) ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($section['description'])): ?>
            <div class="col-12"><div class="text-muted small">Description</div><div><?= nl2br(e($section['description'])) ?></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <ul class="nav nav-tabs" id="sectionTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-students" type="button"><?= e(t('th_students')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-routine" type="button"><?= e(t('th_routine')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button"><?= e(t('th_attendance')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-results" type="button"><?= e(t('th_results')) ?></button></li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-students">
                <?php if (empty($students)): ?>
                <p class="text-muted text-center py-4 mb-0">No students enrolled in this section yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead><tr><th><?= e(t('th_roll_no2')) ?></th><th><?= e(t('th_name')) ?></th><th><?= e(t('th_status')) ?></th><th class="text-end"><?= e(t('th_actions')) ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= e($s['roll_number']) ?></td>
                                <td><?= e($s['full_name']) ?></td>
                                <td><span class="badge <?= status_badge_class($s['status']) ?>"><?= e(st($s['status'])) ?></span></td>
                                <td class="text-end"><a href="<?= e(url('students/' . $s['id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye-fill"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="tab-routine">
                <p class="text-muted text-center py-4 mb-0">View this section's weekly routine in <a href="<?= e(url('timetable')) ?>">Timetable</a>.</p>
            </div>
            <div class="tab-pane fade" id="tab-attendance">
                <p class="text-muted text-center py-4 mb-0">View attendance for this section in <a href="<?= e(url('attendance')) ?>">Student Attendance</a>.</p>
            </div>
            <div class="tab-pane fade" id="tab-results">
                <p class="text-muted text-center py-4 mb-0">View exam results for this section in <a href="<?= e(url('marks')) ?>">Marks &amp; Grades</a>.</p>
            </div>
        </div>
    </div>
</div>
