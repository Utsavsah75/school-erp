<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_class_wise_fee_report')) ?></h5>
    <a href="<?= e(url('payments')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Payment Report</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('payments/class-wise')) ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_session')) ?></label>
                <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Sessions</option>
                    <?php foreach ($academicYears as $ay): ?>
                    <option value="<?= e($ay['id']) ?>" <?= $academicYearId === (int) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php
            $grandDue = array_sum(array_column($summary, 'total_due'));
            $grandPaid = array_sum(array_column($summary, 'total_paid'));
            $grandBalance = array_sum(array_column($summary, 'total_balance'));
        ?>
        <?php if (empty($summary)): ?>
            <p class="text-muted text-center py-4 mb-0">No classes found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_class')) ?></th>
                            <th class="text-end"><?= e(t('th_students')) ?></th>
                            <th class="text-end">Total Assigned</th>
                            <th class="text-end">Collected</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">% Collected</th>
                            <th class="text-end"><?= e(t('th_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $row): ?>
                            <?php $pct = (float) $row['total_due'] > 0 ? round(((float) $row['total_paid'] / (float) $row['total_due']) * 100, 1) : 0; ?>
                            <tr>
                                <td class="fw-semibold"><?= e($row['class_name']) ?></td>
                                <td class="text-end"><?= (int) $row['student_count'] ?></td>
                                <td class="text-end"><?= e(format_currency($row['total_due'])) ?></td>
                                <td class="text-end text-success"><?= e(format_currency($row['total_paid'])) ?></td>
                                <td class="text-end text-danger"><?= e(format_currency($row['total_balance'])) ?></td>
                                <td class="text-end">
                                    <div class="progress" style="height:18px;min-width:100px;">
                                        <div class="progress-bar bg-success" style="width:<?= min(100, $pct) ?>%"><?= $pct ?>%</div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(url('payments') . '?class_id=' . $row['class_id']) ?>" class="btn btn-sm btn-light" title="View payments for this class"><i class="bi bi-list-ul"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end"><?= (int) array_sum(array_column($summary, 'student_count')) ?></td>
                            <td class="text-end"><?= e(format_currency($grandDue)) ?></td>
                            <td class="text-end text-success"><?= e(format_currency($grandPaid)) ?></td>
                            <td class="text-end text-danger"><?= e(format_currency($grandBalance)) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
