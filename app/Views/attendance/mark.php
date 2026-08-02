<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Mark Attendance</h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('attendance')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people me-1"></i>Find Student</a>
        <a href="<?= e(url('attendance/reports')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart me-1"></i>Reports</a>
    </div>
</div>

<?php
$panelTitle = 'Recent Attendance Activity';
$panelIcon = 'bi-clock-history';
$panelHeaders = ['Date & Time', 'Student', 'Class / Section', 'Status', 'Marked By'];
$panelViewAllUrl = 'attendance/reports';
$panelEmptyText = 'No attendance has been marked yet.';
$statusClasses = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'leave' => 'info', 'holiday' => 'secondary'];
$panelRows = array_map(static function ($r) use ($statusClasses) {
    $cls = $statusClasses[$r['status']] ?? 'secondary';
    return [
        'cells' => [
            e(time_ago($r['updated_at'] ?? $r['created_at'])),
            e($r['student_name'] ?? '—') . ($r['roll_number'] ? ' <span class="text-muted small">(Roll ' . e($r['roll_number']) . ')</span>' : ''),
            e(trim(($r['class_name'] ?? '') . ' ' . ($r['section_name'] ?? ''))),
            '<span class="badge bg-' . $cls . '">' . e(ucfirst($r['status'])) . '</span>',
            e($r['marked_by_name'] ?? '—'),
        ],
    ];
}, $recentActivity);
include dirname(__DIR__) . '/partials/_recent_activity_panel.php';
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('attendance/mark')) ?>" class="row g-2 align-items-end" id="markPickerForm">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Class</label>
                <select name="class_id" id="mkClass" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= (string) $classId === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Section</label>
                <select name="section_id" id="mkSection" class="form-select" required <?= $classId ? '' : 'disabled' ?>>
                    <option value=""><?= $classId ? '-- Select --' : '-- Select Class First --' ?></option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= e($s['id']) ?>" data-class="<?= e($s['class_id']) ?>" <?= (string) $sectionId === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($date) ?>" max="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-arrow-repeat"></i> Load Students</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$classId || !$sectionId): ?>
    <div class="card"><div class="card-body">
        <p class="text-muted text-center py-4 mb-0">Select a Class and Section above to mark attendance.</p>
    </div></div>
<?php elseif (empty($students)): ?>
    <div class="card"><div class="card-body">
        <p class="text-muted text-center py-4 mb-0">No active students found for this class/section.</p>
    </div></div>
<?php else: ?>
    <form method="POST" action="<?= e(url('attendance/mark')) ?>" id="markForm">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= e($classId) ?>">
        <input type="hidden" name="section_id" value="<?= e($sectionId) ?>">
        <input type="hidden" name="date" value="<?= e($date) ?>">

        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong><?= count($students) ?></strong> student(s) —
                    marking attendance for <strong><?= e(format_date($date)) ?></strong>
                    <?php if (!empty($existing)): ?>
                        <span class="badge bg-info ms-2">Already marked — editing</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-success" data-mark-all="present"><i class="bi bi-check-all"></i> Mark All Present</button>
                    <button type="button" class="btn btn-outline-danger" data-mark-all="absent"><i class="bi bi-x-lg"></i> Mark All Absent</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Roll No.</th>
                                <th>Name</th>
                                <th class="text-center" style="min-width:340px;">Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s):
                                $currentStatus = $existing[$s['id']]['status'] ?? 'present';
                                $currentRemarks = $existing[$s['id']]['remarks'] ?? '';
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($s['photo_path'])): ?>
                                            <img src="<?= e(upload_url($s['photo_path'])) ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" alt="">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle text-muted" style="font-size:26px;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($s['roll_number']) ?></td>
                                    <td class="fw-semibold"><?= e($s['full_name']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm status-group" role="group" data-student="<?= e($s['id']) ?>">
                                            <?php foreach (['present' => ['Present', 'success'], 'absent' => ['Absent', 'danger'], 'late' => ['Late', 'warning'], 'leave' => ['Leave', 'info']] as $val => [$label, $color]): ?>
                                                <input type="radio" class="btn-check" name="status[<?= e($s['id']) ?>]" id="st_<?= e($s['id']) ?>_<?= $val ?>" value="<?= $val ?>" autocomplete="off" <?= $currentStatus === $val ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-<?= $color ?>" for="st_<?= e($s['id']) ?>_<?= $val ?>"><?= $label ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?= e($s['id']) ?>]" class="form-control form-control-sm" value="<?= e($currentRemarks) ?>" placeholder="Optional">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Attendance</button>
            </div>
        </div>
    </form>
<?php endif; ?>

<script>
(function () {
    var classSelect = document.getElementById('mkClass');
    var sectionSelect = document.getElementById('mkSection');
    if (classSelect && sectionSelect) {
        var sectionsForClassUrl = <?= json_encode(url('students/sections-for-class')) ?>;
        classSelect.addEventListener('change', function () {
            if (!this.value) {
                sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
                sectionSelect.disabled = true;
                return;
            }
            sectionSelect.disabled = true;
            fetch(sectionsForClassUrl + '/' + this.value)
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    sectionSelect.innerHTML = '';
                    sectionSelect.appendChild(new Option('-- Select --', ''));
                    rows.forEach(function (row) { sectionSelect.appendChild(new Option(row.name, row.id)); });
                    sectionSelect.disabled = false;
                })
                .catch(function () { sectionSelect.disabled = false; });
        });
    }

    document.querySelectorAll('[data-mark-all]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var status = this.getAttribute('data-mark-all');
            document.querySelectorAll('.status-group').forEach(function (group) {
                var input = group.querySelector('input[value="' + status + '"]');
                if (input) input.checked = true;
            });
        });
    });
})();
</script>
