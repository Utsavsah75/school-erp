<?php
/**
 * Student dashboard. Expects: $student, $class, $section, $parent, $stats, $notices, $examResults
 */
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100" style="border-left-color:#1cc88a;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <h3><?= (int) $stats['upcoming_exams'] ?></h3>
                    <div class="stat-label">Upcoming Exams</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100" style="border-left-color:#e74a3b;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#e74a3b;"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <h3><?= e(format_currency($stats['due_fees'])) ?></h3>
                    <div class="stat-label">Due Fees</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100" style="border-left-color:#36b9cc;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#36b9cc;"><i class="bi bi-flag-fill"></i></div>
                <div>
                    <h3><?= (int) $stats['upcoming_events'] ?></h3>
                    <div class="stat-label">Upcoming Events</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100" style="border-left-color:#f6c23e;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f6c23e;"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <h3><?= e($stats['attendance_pct']) ?>%</h3>
                    <div class="stat-label">Attendance (This Month)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- My Information -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-vcard-fill me-2"></i>My Information</h5>
                <div class="row">
                    <div class="col-4 text-center">
                        <?php if (!empty($student['photo_path'])): ?>
                            <img src="<?= e(upload_url($student['photo_path'])) ?>" class="rounded mb-2" style="width:100%;max-width:150px;aspect-ratio:1;object-fit:cover;" alt="Photo">
                        <?php else: ?>
                            <div class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width:120px;height:120px;">
                                <i class="bi bi-person-fill" style="font-size:56px;color:#ccc;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-8">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e($student['full_name']) ?></td></tr>
                            <tr><td class="text-muted">Gender</td><td><?= e(GENDER_OPTIONS[$student['gender']] ?? $student['gender']) ?></td></tr>
                            <?php if ($parent): ?>
                            <tr><td class="text-muted">Father's Name</td><td><?= e($parent['father_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Mother's Name</td><td><?= e($parent['mother_name'] ?? '—') ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="text-muted">Date of Birth</td><td><?= e(format_date($student['dob'])) ?></td></tr>
                            <tr><td class="text-muted">Religion</td><td><?= e($student['religion'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Email</td><td><?= e($student['email'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Admission Date</td><td><?= e(format_date($student['admission_date'])) ?></td></tr>
                            <tr><td class="text-muted">Class</td><td><?= e($class['name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Section</td><td><?= e($section['name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Roll</td><td><?= e($student['roll_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Address</td><td><?= e($student['address'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Phone</td><td><?= e($student['phone'] ?? '—') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notice Board -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-megaphone-fill me-2"></i>Notice Board</h5>
                <?php if (empty($notices)): ?>
                    <p class="text-muted mb-0">No notices published yet.</p>
                <?php else: ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= e($notice['title']) ?></span>
                                <small class="text-muted"><?= e(format_datetime($notice['published_at'])) ?></small>
                            </div>
                            <p class="mb-0 small text-muted"><?= e(mb_strimwidth($notice['body'], 0, 140, '…')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- All Exam Results -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-award-fill me-2"></i>All Exam Results</h5>
                <?php if (empty($examResults)): ?>
                    <p class="text-muted mb-0">No exam results published yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th><?= e(t('th_subject')) ?></th>
                                    <th>Marks Obtained</th>
                                    <th>Max Marks</th>
                                    <th><?= e(t('th_grade')) ?></th>
                                    <th><?= e(t('th_date')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examResults as $r): ?>
                                    <tr>
                                        <td><?= e($r['exam_name']) ?></td>
                                        <td><?= e($r['subject_name']) ?></td>
                                        <td><?= e($r['marks_obtained']) ?></td>
                                        <td><?= e($r['max_marks']) ?></td>
                                        <td><span class="badge bg-primary"><?= e($r['grade']) ?></span></td>
                                        <td><?= e(format_date($r['exam_date'])) ?></td>
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
