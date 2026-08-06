<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_attendance_reports')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('attendance')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people me-1"></i>Find Student</a>
        <a href="<?= e(url('attendance/mark')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
    </div>
</div>

<?php
$panelTitle = 'Recent Attendance Activity';
$panelIcon = 'bi-clock-history';
$panelHeaders = ['Date & Time', 'Student', 'Class / Section', 'Status', 'Marked By'];
$panelViewAllUrl = null;
$statusClasses = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'leave' => 'info', 'holiday' => 'secondary'];
$panelRows = array_map(static function ($r) use ($statusClasses) {
    $cls = $statusClasses[$r['status']] ?? 'secondary';
    return [
        'cells' => [
            e(time_ago($r['updated_at'] ?? $r['created_at'])),
            e($r['student_name'] ?? '—') . ($r['roll_number'] ? ' <span class="text-muted small">(Roll ' . e($r['roll_number']) . ')</span>' : ''),
            e(trim(($r['class_name'] ?? '') . ' ' . ($r['section_name'] ?? ''))),
            '<span class="badge bg-' . $cls . '">' . e(st($r['status'])) . '</span>',
            e($r['marked_by_name'] ?? '—'),
        ],
    ];
}, $recentActivity);
include dirname(__DIR__) . '/partials/_recent_activity_panel.php';
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('attendance/reports')) ?>" class="row g-2 align-items-end" id="reportFilterForm">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Report Type</label>
                <select name="range" class="form-select">
                    <option value="monthly" <?= $range === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $range === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </div>
            <div class="col-md-2 range-monthly" <?= $range === 'yearly' ? 'style="display:none;"' : '' ?>>
                <label class="form-label small text-muted mb-1"><?= e(t('th_month')) ?></label>
                <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
            </div>
            <div class="col-md-2 range-yearly" <?= $range === 'monthly' ? 'style="display:none;"' : '' ?>>
                <label class="form-label small text-muted mb-1"><?= e(t('th_year')) ?></label>
                <input type="number" name="year" class="form-control" value="<?= e($year) ?>" min="2000" max="2100">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select name="class_id" class="form-select">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= (string) $classId === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                <select name="section_id" class="form-select">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= e($s['id']) ?>" <?= (string) $sectionId === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Student ID <span class="text-muted">(optional)</span></label>
                <input type="number" name="student_id" class="form-control" value="<?= $studentId ?: '' ?>" placeholder="From Find Student">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i> Apply Filters</button>
                <div class="btn-group btn-group-sm ms-2">
                    <a href="<?= e(url('attendance/reports/export/csv') . '?' . http_build_query(['class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $studentId, 'from' => $from, 'to' => $to])) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel"></i> Excel/CSV</a>
                    <a href="<?= e(url('attendance/reports/export/pdf') . '?' . http_build_query(['class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $studentId, 'from' => $from, 'to' => $to])) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($range === 'yearly' && $studentId && $yearlySummary): ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Yearly Summary — <?= e($year) ?></h6>
            <span class="badge bg-<?= $yearlySummary['percentage'] >= 75 ? 'success' : 'danger' ?> fs-6">
                <?= e($yearlySummary['percentage']) ?>% Present
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th><?= e(t('th_month')) ?></th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th><?= e(date('M', mktime(0, 0, 0, $m, 1))) ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'] as $key => $label): ?>
                        <tr>
                            <td class="fw-semibold text-start"><?= e($label) ?></td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td><?= (int) $yearlySummary['months'][$m][$key] ?></td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><?= $range === 'yearly' ? e($year) : e(format_date($from, 'F Y')) ?> — <?= count($rows) ?> record(s)</h6>
        </div>
        <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-4 mb-0">No attendance records match these filters.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                    <thead>
                        <tr>
                            <th><?= e(t('th_date')) ?></th>
                            <th><?= e(t('th_roll_no')) ?></th>
                            <th><?= e(t('th_student')) ?></th>
                            <th><?= e(t('th_class_section')) ?></th>
                            <th><?= e(t('th_status')) ?></th>
                            <th><?= e(t('th_remarks')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= e(format_date($r['date'])) ?></td>
                                <td><?= e($r['roll_number'] ?? '') ?></td>
                                <td class="fw-semibold"><?= e($r['student_name'] ?? '') ?></td>
                                <td><?= e(trim(($r['class_name'] ?? '') . ' ' . ($r['section_name'] ?? ''))) ?></td>
                                <td><span class="badge bg-<?= e($statusClasses[$r['status']] ?? 'secondary') ?>"><?= e(st($r['status'])) ?></span></td>
                                <td class="text-muted small"><?= e($r['remarks'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelector('select[name="range"]').addEventListener('change', function () {
    document.querySelectorAll('.range-monthly').forEach(function (el) { el.style.display = this.value === 'yearly' ? 'none' : ''; }.bind(this));
    document.querySelectorAll('.range-yearly').forEach(function (el) { el.style.display = this.value === 'yearly' ? '' : 'none'; }.bind(this));
});
</script>
