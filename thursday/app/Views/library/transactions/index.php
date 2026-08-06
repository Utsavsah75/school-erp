<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_issued_books')) ?></h5>
    <a href="<?= e(url('library/issue')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Issue Book</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('library/transactions')) ?>" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1"><?= e(t('search')) ?></label>
                <input type="text" name="search" class="form-control" placeholder="Book title, accession no, borrower name, admission/employee no..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-danger mb-1"><?= e(t('th_from_date')) ?></label>
                <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-danger mb-1"><?= e(t('th_to_date')) ?></label>
                <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100" title="Apply Filter"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-muted text-center py-4 mb-0">No books currently issued.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn')) ?></th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th>Accession No.</th>
                            <th>Book Code</th>
                            <th><?= e(t('th_borrower')) ?></th>
                            <th><?= e(t('th_issue_date')) ?></th>
                            <th><?= e(t('th_due_date')) ?></th>
                            <th><?= e(t('th_status')) ?></th>
                            <th><?= e(t('th_fine')) ?></th>
                            <th class="text-end"><?= e(t('th_actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $i => $row): ?>
                        <tr>
                            <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                            <td><?= e($row['book_title']) ?></td>
                            <td><code><?= e($row['isbn'] ?: '—') ?></code></td>
                            <td><code><?= e($row['accession_number']) ?></code></td>
                            <td><code><?= e($row['copy_code'] ?: '—') ?></code></td>
                            <td>
                                <?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?>
                                <br><small class="text-muted"><?= e($row['admission_number'] ?? $row['employee_number'] ?? '') ?></small>
                            </td>
                            <td><?= e($row['issue_date']) ?></td>
                            <td><?= e($row['due_date']) ?></td>
                            <td>
                                <?php if ((int) $row['days_overdue'] > 0): ?>
                                    <span class="badge bg-danger"><?= (int) $row['days_overdue'] ?> day(s) overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-success">On time</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((float) ($row['fine_amount'] ?? 0) > 0): ?>
                                    <span class="text-danger fw-semibold"><?= e(format_currency($row['fine_amount'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <?php $isOverdue = (int) $row['days_overdue'] > 0 && (int) $row['fine_paid'] !== 1; ?>
                            <td class="text-end">
                                <?php if ($isOverdue): ?>
                                <form method="POST" action="<?= e(url('library/transactions/' . $row['id'] . '/renew')) ?>" class="d-inline fine-gate-form" data-fine-amount="<?= e((string) (float) ($row['fine_amount'] ?? 0)) ?>" data-days-overdue="<?= (int) $row['days_overdue'] ?>" data-fine-action-label="renew this book">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="payment_mode" value="">
                                    <input type="hidden" name="reference_number" value="">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" <?= (int) $row['renewed_count'] > 0 ? 'title="Already renewed"' : '' ?>>Renew</button>
                                </form>
                                <form method="POST" action="<?= e(url('library/transactions/' . $row['id'] . '/return')) ?>" class="d-inline fine-gate-form" data-fine-amount="<?= e((string) (float) ($row['fine_amount'] ?? 0)) ?>" data-days-overdue="<?= (int) $row['days_overdue'] ?>" data-fine-action-label="return this book">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="payment_mode" value="">
                                    <input type="hidden" name="reference_number" value="">
                                    <button type="submit" class="btn btn-sm btn-primary"><?= e(t('return')) ?></button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="<?= e(url('library/transactions/' . $row['id'] . '/renew')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" <?= (int) $row['renewed_count'] > 0 ? 'title="Already renewed"' : '' ?>>Renew</button>
                                </form>
                                <form method="POST" action="<?= e(url('library/transactions/' . $row['id'] . '/return')) ?>" class="d-inline" data-confirm data-confirm-icon="question" data-confirm-title="Confirm Return" data-confirm-message="Confirm this book has been returned?" data-confirm-button="Yes, returned" data-confirm-variant="success">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary"><?= e(t('return')) ?></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= e(url('library/transactions/' . $row['id'] . '/lost')) ?>" class="d-inline" data-confirm data-confirm-icon="warning" data-confirm-title="Mark as Lost?" data-confirm-message="This book will be marked as lost and removed from the catalog's total copies." data-confirm-button="Yes, mark lost" data-confirm-variant="danger">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= e(t('status_lost')) ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php
        $extraParams = ['search' => $search, 'from_date' => $fromDate, 'to_date' => $toDate];
        $baseUrl = url('library/transactions');
        require __DIR__ . '/../_pagination.php';
        ?>
    </div>
</div>

<script>
(function () {
    // Overdue loans always carry an outstanding fine, and Renew/Return
    // must collect it before the loan can be extended or closed out (see
    // LibraryController::renewBook()/returnBook()). This intercepts the
    // submit, asks for how the fine was paid, then fills the form's
    // hidden payment_mode/reference_number fields and submits for real.
    var PAYMENT_MODES = <?= json_encode(PAYMENT_MODES) ?>;

    document.querySelectorAll('form.fine-gate-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.getAttribute('data-fine-collected') === '1') {
                return; // already confirmed once — let it submit for real
            }
            e.preventDefault();

            var amount = parseFloat(form.dataset.fineAmount || '0') || 0;
            var days = form.dataset.daysOverdue || '0';
            var actionLabel = form.dataset.fineActionLabel || 'continue';

            var modeOptions = Object.keys(PAYMENT_MODES).map(function (key) {
                return '<option value="' + key + '">' + PAYMENT_MODES[key] + '</option>';
            }).join('');

            (window.SA ? SA.fire({
                icon: 'warning',
                title: 'Collect Overdue Fine',
                html:
                    '<p class="mb-3">This book is ' + days + ' day(s) overdue. ' +
                    'A fine of <strong>' + amount.toFixed(2) + '</strong> must be collected before you can ' + actionLabel + '.</p>' +
                    '<div class="mb-2 text-start">' +
                    '<label class="form-label small text-muted mb-1">Payment Mode</label>' +
                    '<select id="fine-gate-mode" class="form-select">' + modeOptions + '</select>' +
                    '</div>' +
                    '<div class="text-start">' +
                    '<label class="form-label small text-muted mb-1">Reference No. (optional)</label>' +
                    '<input id="fine-gate-reference" type="text" class="form-control" placeholder="Cheque/UPI/txn ref">' +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: 'Collect Fine & Continue',
                cancelButtonText: 'Cancel',
                focusConfirm: false,
                preConfirm: function () {
                    var mode = document.getElementById('fine-gate-mode').value;
                    var reference = document.getElementById('fine-gate-reference').value;
                    return { mode: mode, reference: reference };
                },
            }) : Promise.resolve({ isConfirmed: true, value: { mode: 'cash', reference: '' } }))
                .then(function (result) {
                    if (!result || !result.isConfirmed) {
                        return;
                    }
                    var value = result.value || { mode: 'cash', reference: '' };
                    form.querySelector('input[name="payment_mode"]').value = value.mode;
                    form.querySelector('input[name="reference_number"]').value = value.reference || '';
                    form.setAttribute('data-fine-collected', '1');
                    form.submit();
                });
        });
    });
})();
</script>
