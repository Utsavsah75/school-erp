<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Daily Collection Report</h5>
    <a href="<?= e(url('payments')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Payment Report</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('payments/daily-collection')) ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($date) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> View</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted">Total Collected</div>
                <div class="fs-4 fw-bold text-success"><?= e(format_currency($report['total'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted">Transactions</div>
                <div class="fs-4 fw-bold"><?= (int) $report['count'] ?></div>
            </div>
        </div>
    </div>
    <?php foreach ($report['by_mode'] as $mode => $amount): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted text-capitalize"><?= e($paymentModes[$mode] ?? str_replace('_', ' ', $mode)) ?></div>
                <div class="fs-5 fw-bold"><?= e(format_currency($amount)) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($report['rows'])): ?>
            <p class="text-muted text-center py-4 mb-0">No payments collected on <?= e(format_date($date)) ?>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Time</th>
                            <th>Receipt No</th>
                            <th>Student</th>
                            <th>Admission No.</th>
                            <th>Class / Section</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Collected By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; ?>
                        <?php foreach ($report['rows'] as $p): ?>
                            <?php $profileUrl = !empty($p['student_id']) ? url('students/' . $p['student_id']) : null; ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td><?= e(date('h:i A', strtotime($p['paid_at']))) ?></td>
                                <td><a href="<?= e(url('payments/receipt/' . $p['receipt_group'])) ?>"><?= e($p['receipt_number']) ?></a></td>
                                <td>
                                    <?php if ($profileUrl): ?>
                                        <a href="<?= e($profileUrl) ?>" class="text-decoration-none fw-semibold" title="Open this student's profile"><?= e($p['student_name'] ?? '—') ?></a>
                                    <?php else: ?>
                                        <?= e($p['student_name'] ?? '—') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($p['admission_number'] ?? '—') ?></td>
                                <td><?= e(trim(($p['class_name'] ?? '') . ' ' . ($p['section_name'] ?? '')) ?: '—') ?></td>
                                <td><?= e($p['fee_type_name'] ?? '—') ?></td>
                                <td class="fw-bold"><?= e(format_currency($p['amount'])) ?></td>
                                <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_mode'])) ?></td>
                                <td><?= e($p['received_by_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="7" class="text-end">Total</td>
                            <td><?= e(format_currency($report['total'])) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
