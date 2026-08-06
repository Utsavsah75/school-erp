<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_payment_report')) ?></h5>
    <div>
        <a href="<?= e(url('payments/collect')) ?>" class="btn btn-primary"><i class="bi bi-cash-coin me-1"></i>Collect
            Payment</a>
        <a href="<?= e(url('payments/daily-collection')) ?>" class="btn btn-outline-secondary"><i
                class="bi bi-calendar-check me-1"></i>Daily Collection</a>
        <a href="<?= e(url('payments/class-wise')) ?>" class="btn btn-outline-secondary"><i
                class="bi bi-bar-chart me-1"></i>Class-wise Report</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('payments')) ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Student / Admission No.</label>
                <input type="text" name="q" class="form-control" placeholder="Name or admission no."
                    value="<?= e($filters['q'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select name="class_id" id="reportFilterClass" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>"
                        <?= (int) ($filters['class_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                <select name="section_id" id="reportFilterSection" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <?php foreach ($sections as $s): ?>
                    <option value="<?= e($s['id']) ?>"
                        <?= (int) ($filters['section_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_date')) ?></label>
                <input type="date" name="date" class="form-control" value="<?= e($filters['date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_receipt_no2')) ?></label>
                <input type="text" name="receipt_number" class="form-control" placeholder="Receipt #"
                    value="<?= e($filters['receipt_number'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_status')) ?></label>
                <select name="status" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>
                        Completed</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Voided
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
            <?php if (!empty($activeFilters)): ?>
            <div class="col-md-2">
                <a href="<?= e(url('payments')) ?>" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No payment records found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= e(t('th_sn')) ?></th>
                        <th><?= e(t('th_receipt_no')) ?></th>
                        <th><?= e(t('th_date')) ?></th>
                        <th><?= e(t('th_student')) ?></th>
                        <th><?= e(t('th_admission_no')) ?></th>
                        <th><?= e(t('th_class_section')) ?></th>
                        <th><?= e(t('th_amount')) ?></th>
                        <th><?= e(t('th_method')) ?></th>
                        <th><?= e(t('th_status')) ?></th>
                        <th class="text-end"><?= e(t('th_action')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn = ($result['page'] - 1) * $result['per_page'] + 1; ?>
                    <?php foreach ($result['data'] as $p): ?>
                    <?php $profileUrl = !empty($p['student_id']) ? url('students/' . $p['student_id']) : null; ?>
                    <tr class="payment-row"
                        <?= $profileUrl ? 'style="cursor:pointer;" onclick="window.location=\'' . e($profileUrl) . '\'"' : '' ?>>
                        <td><?= $sn++ ?></td>
                        <td><?= e($p['receipt_number']) ?></td>
                        <td><?= e(format_date($p['paid_at'])) ?></td>
                        <td>
                            <?php if ($profileUrl): ?>
                            <a href="<?= e($profileUrl) ?>" class="text-decoration-none fw-semibold"
                                onclick="event.stopPropagation();"
                                title="Open this student's profile"><?= e($p['student_name'] ?? '—') ?></a>
                            <?php else: ?>
                            <?= e($p['student_name'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['admission_number'] ?? '—') ?></td>
                        <td><?= e(trim(($p['class_name'] ?? '') . ' ' . ($p['section_name'] ?? '')) ?: '—') ?></td>
                        <td><?= e(format_currency($p['amount'])) ?></td>
                        <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_mode'])) ?></td>
                        <td><span
                                class="badge <?= status_badge_class($p['status'] === 'cancelled' ? 'inactive' : 'active') ?>"><?= $p['status'] === 'cancelled' ? 'Voided' : 'Completed' ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(url('payments/receipt/' . $p['receipt_group'])) ?>"
                                class="btn btn-sm btn-light" title="View Receipt" onclick="event.stopPropagation();"><i
                                    class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Showing <?= count($result['data']) ?> of <?= (int) $result['total'] ?>
                payments</small>
            <?= paginate_links($result, url('payments'), $filters) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const filterClass = document.getElementById('reportFilterClass');
    const filterSection = document.getElementById('reportFilterSection');
    if (filterClass) {
        filterClass.addEventListener('change', function() {
            // Class changed — the previously selected section no longer applies, so clear it
            // and let the form submit fetch the right section list server-side on reload.
            filterSection.innerHTML = '<option value=""><?= e(t('all')) ?></option>';
            filterClass.form.submit();
        });
    }
})();
</script>