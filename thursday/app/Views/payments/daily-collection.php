<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_daily_collection_report')) ?></h5>
    <a href="<?= e(url('payments')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Payment Report</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('payments/daily-collection')) ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_date')) ?></label>
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
                <div class="small text-muted">Grand Total <span class="d-block" style="font-size:.7rem;">(fees + library fines)</span></div>
                <div class="fs-4 fw-bold text-success"><?= e(format_currency($grandTotal)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted">Fee Payments</div>
                <div class="fs-4 fw-bold"><?= e(format_currency($report['total'])) ?></div>
                <div class="small text-muted"><?= (int) $report['count'] ?> transaction<?= $report['count'] === 1 ? '' : 's' ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted">Library Fines</div>
                <div class="fs-4 fw-bold"><?= e(format_currency($fineTotal)) ?></div>
                <div class="small text-muted"><?= count($fineRows) ?> transaction<?= count($fineRows) === 1 ? '' : 's' ?></div>
            </div>
        </div>
    </div>
    <?php foreach ($report['by_mode'] as $mode => $amount): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="small text-muted text-capitalize"><?= e($paymentModes[$mode] ?? str_replace('_', ' ', $mode)) ?> <span class="d-block" style="font-size:.65rem;">(fees only)</span></div>
                <div class="fs-5 fw-bold"><?= e(format_currency($amount)) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-1"></i>Fee Payments</h6>
        <?php if (empty($report['rows'])): ?>
            <p class="text-muted text-center py-4 mb-0">No fee payments collected on <?= e(format_date($date)) ?>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn')) ?></th>
                            <th><?= e(t('th_time')) ?></th>
                            <th><?= e(t('th_receipt_no')) ?></th>
                            <th><?= e(t('th_student')) ?></th>
                            <th><?= e(t('th_admission_no')) ?></th>
                            <th><?= e(t('th_class_section')) ?></th>
                            <th><?= e(t('th_fee_type')) ?></th>
                            <th><?= e(t('th_amount')) ?></th>
                            <th><?= e(t('th_method')) ?></th>
                            <th><?= e(t('th_collected_by')) ?></th>
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

<div class="card">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-book-half me-1"></i>Library Fine Payments</h6>
        <?php if (empty($fineRows)): ?>
            <p class="text-muted text-center py-4 mb-0">No library fines collected on <?= e(format_date($date)) ?>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn')) ?></th>
                            <th><?= e(t('th_time')) ?></th>
                            <th>Receipt #</th>
                            <th>Student / Teacher</th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_amount')) ?></th>
                            <th><?= e(t('th_method')) ?></th>
                            <th><?= e(t('th_collected_by')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; ?>
                        <?php foreach ($fineRows as $r): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td><?= e(date('h:i A', strtotime($r['paid_at']))) ?></td>
                                <td><a href="<?= e(url('library/fine-receipt/' . $r['receipt_number'])) ?>" target="_blank"><?= e($r['receipt_number']) ?></a></td>
                                <td>
                                    <?php if (!empty($r['student_name'])): ?>
                                        <?= e($r['student_name']) ?> <span class="text-muted small">#<?= e($r['admission_number'] ?? '') ?></span>
                                    <?php elseif (!empty($r['teacher_name'])): ?>
                                        <?= e($r['teacher_name']) ?> <span class="text-muted small">#<?= e($r['employee_number'] ?? '') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($r['book_title'] ?? '—') ?></td>
                                <td class="fw-bold"><?= e(format_currency($r['amount'])) ?></td>
                                <td class="text-capitalize"><?= e(str_replace('_', ' ', (string) $r['payment_mode'])) ?></td>
                                <td><?= e($r['received_by_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">Total</td>
                            <td><?= e(format_currency($fineTotal)) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($report['rows']) && empty($fineRows)): ?>
<p class="text-center text-muted mt-3 mb-0">Nothing at all was collected on <?= e(format_date($date)) ?> — Grand Total is <?= e(format_currency(0)) ?>.</p>
<?php endif; ?>
