<?php
/** Expects: $student, $class, $section */
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($student['photo_path'])): ?>
                    <img src="<?= e(upload_url($student['photo_path'])) ?>" class="rounded mb-3" style="width:160px;height:160px;object-fit:cover;" alt="Photo">
                <?php else: ?>
                    <div class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width:160px;height:160px;">
                        <i class="bi bi-person-fill" style="font-size:64px;color:#ccc;"></i>
                    </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-0"><?= e($student['full_name']) ?></h5>
                <div class="text-muted">Roll #<?= e($student['roll_number'] ?? '—') ?> · <?= e($class['name'] ?? '—') ?> <?= e($section['name'] ?? '') ?></div>
                <span class="badge <?= status_badge_class($student['status']) ?> mt-2"><?= e(STUDENT_STATUSES[$student['status']] ?? ucfirst($student['status'])) ?></span>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="<?= e(url('parent/children/' . $student['id'] . '/report-card')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Report Card</a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Student Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:220px;">Admission Number</td><td class="fw-semibold">#<?= e($student['admission_number']) ?></td></tr>
                    <tr><td class="text-muted">Admission Date</td><td><?= e(format_date($student['admission_date'])) ?></td></tr>
                    <tr><td class="text-muted">Gender</td><td><?= e(GENDER_OPTIONS[$student['gender']] ?? $student['gender']) ?></td></tr>
                    <tr><td class="text-muted">Date of Birth</td><td><?= e(format_date($student['dob'])) ?> (<?= e(student_age_display($student['dob'])) ?>)</td></tr>
                    <tr><td class="text-muted">Blood Group</td><td><?= e($student['blood_group'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Class / Section</td><td><?= e($class['name'] ?? '—') ?> / <?= e($section['name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= e($student['phone'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= e($student['email'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Address</td><td><?= e($student['address'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Emergency Contact</td><td><?= e($student['emergency_contact_name'] ?? '—') ?> <?= !empty($student['emergency_contact_phone']) ? '(' . e($student['emergency_contact_phone']) . ')' : '' ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
