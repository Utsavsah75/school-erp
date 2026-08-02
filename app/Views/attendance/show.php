<?php
$statusMeta = [
    'present' => ['label' => 'P', 'class' => 'text-success', 'title' => 'Present'],
    'absent'  => ['label' => 'A', 'class' => 'text-danger',  'title' => 'Absent'],
    'late'    => ['label' => 'L', 'class' => 'text-warning', 'title' => 'Late'],
    'leave'   => ['label' => 'V', 'class' => 'text-primary', 'title' => 'Leave'],
    'holiday' => ['label' => 'H', 'class' => 'text-secondary', 'title' => 'Holiday'],
];
$monthLabel = date('F Y', strtotime($yearMonth . '-01'));
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="fw-bold mb-0">Individual Student Attendance</h5>
    <a href="<?= e(url('attendance')) ?>" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
</div>

<div id="printArea">
    <div class="text-center d-none d-print-block mb-3">
        <h4 class="fw-bold mb-0"><?= e(config('app.name', 'School ERP')) ?></h4>
        <div class="text-muted">Student Attendance Sheet — <?= e($monthLabel) ?></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($student['photo_path'])): ?>
                        <img src="<?= e(upload_url($student['photo_path'])) ?>" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;" alt="">
                    <?php else: ?>
                        <i class="bi bi-person-circle text-muted" style="font-size:64px;"></i>
                    <?php endif; ?>
                    <div>
                        <h5 class="fw-bold mb-1"><?= e($student['full_name']) ?></h5>
                        <div class="text-muted small">
                            Student ID: <strong><?= e($student['admission_number']) ?></strong> &nbsp;|&nbsp;
                            Class: <strong><?= e($className ?: '—') ?></strong> &nbsp;|&nbsp;
                            Section: <strong><?= e($sectionName ?: '—') ?></strong> &nbsp;|&nbsp;
                            Roll No.: <strong><?= e($student['roll_number']) ?></strong>
                        </div>
                    </div>
                </div>
                <div class="text-end no-print">
                    <form method="GET" action="<?= e(url('attendance/' . $student['id'])) ?>" class="d-flex gap-2">
                        <input type="month" name="month" class="form-control" value="<?= e($yearMonth) ?>">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Search</button>
                        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Print Attendance Sheet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Attendance Sheet — <?= e($monthLabel) ?></h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center align-middle mb-0" style="min-width:900px;">
                    <thead class="table-light">
                        <tr>
                            <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                                <th style="width:28px;"><?= $d ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                                <?php $st = $dayStatuses[$d] ?? null; ?>
                                <td class="<?= $st ? $statusMeta[$st]['class'] : 'text-muted' ?> fw-bold" title="<?= $st ? e($statusMeta[$st]['title']) : 'No record' ?>">
                                    <?= $st ? e($statusMeta[$st]['label']) : '-' ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                <span class="text-success fw-bold me-3">P</span>Present
                <span class="text-danger fw-bold ms-3 me-1">A</span>Absent
                <span class="text-warning fw-bold ms-3 me-1">L</span>Late
                <span class="text-primary fw-bold ms-3 me-1">V</span>Leave
                <span class="text-secondary fw-bold ms-3 me-1">H</span>Holiday
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0"><?= (int) $workingDays ?></div><div class="small text-muted">Working Days</div></div></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0 text-success"><?= (int) $summary['present'] ?></div><div class="small text-muted">Present</div></div></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0 text-danger"><?= (int) $summary['absent'] ?></div><div class="small text-muted">Absent</div></div></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0 text-primary"><?= (int) $summary['leave'] ?></div><div class="small text-muted">Leave</div></div></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0 text-secondary"><?= (int) $summary['holiday'] ?></div><div class="small text-muted">Holidays</div></div></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center"><div class="card-body py-3"><div class="h4 mb-0 fw-bold"><?= e($percentage) ?>%</div><div class="small text-muted">Attendance %</div></div></div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, .page-footer, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; }
    #printArea .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
