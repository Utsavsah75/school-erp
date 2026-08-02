<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Fee Collection</h5>
</div>

<?php if (!empty($justPostedGroup)): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center">
    <div><i class="bi bi-check-circle-fill me-2"></i>Payment recorded — Receipt <strong><?= e($justPostedGroup) ?></strong>.</div>
    <div>
        <a href="<?= e(url('payments/receipt/' . $justPostedGroup)) ?>" target="_blank" class="btn btn-sm btn-success me-1"><i class="bi bi-printer me-1"></i>Print Receipt</a>
        <a href="<?= e(url('payments/receipt/' . $justPostedGroup . '/pdf')) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- ==================== Student Search + Info ==================== -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Session</label>
                        <select id="filterAcademicYear" class="form-select form-select-sm">
                            <option value="">All</option>
                            <?php foreach ($academicYears as $ay): ?>
                            <option value="<?= e($ay['id']) ?>"><?= e($ay['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Class / Level</label>
                        <select id="filterClass" class="form-select form-select-sm">
                            <option value="">All</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Section</label>
                        <select id="filterSection" class="form-select form-select-sm">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <span class="small text-muted">Narrow the search below</span>
                    </div>
                </div>

                <label class="form-label small text-muted mb-1">Search Student</label>
                <div class="position-relative">
                    <input type="text" id="studentSearch" class="form-control" placeholder="Name, admission no., roll no. or mobile" autocomplete="off">
                    <div id="studentResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000; display:none; max-height:320px; overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        <?php if ($student): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><?= e($student['full_name']) ?></h6>
                <div class="small text-muted mb-1">Admission No: <?= e($student['admission_number'] ?? '—') ?> &nbsp;|&nbsp; Roll No: <?= e($student['roll_number'] ?? '—') ?></div>
                <div class="small text-muted mb-1">Session: <?= e($student['academic_year_label'] ?? '—') ?> &nbsp;|&nbsp; Class: <?= e($student['class_name'] ?? '—') ?> <?= e($student['section_name'] ?? '') ?></div>
                <div class="small text-muted mb-2">Father/Guardian: <?= e($student['father_name'] ?? $student['guardian_name'] ?? '—') ?></div>
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="small text-muted">TOTAL</div>
                            <div class="fw-bold"><?= e(format_currency($totals['total'])) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="small text-muted">PAID</div>
                            <div class="fw-bold text-success"><?= e(format_currency($totals['paid'])) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="small text-muted">BALANCE</div>
                            <div class="fw-bold text-danger"><?= e(format_currency($totals['balance'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Payment History</h6>
                <?php if (empty($paymentHistory)): ?>
                <p class="text-muted small mb-0">No payments recorded yet.</p>
                <?php else: ?>
                <div style="max-height:320px; overflow-y:auto;">
                    <?php foreach ($paymentHistory as $p): ?>
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2 small <?= $p['status'] === 'cancelled' ? 'opacity-50' : '' ?>">
                        <div class="d-flex align-items-start">
                            <div class="me-2">
                                <a href="<?= e(url('payments/receipt/' . $p['receipt_group'])) ?>" target="_blank" class="text-primary" title="Print"><i class="bi bi-printer"></i></a>
                            </div>
                            <div>
                                <div class="fw-semibold text-primary"><?= e($p['receipt_number']) ?> <?= $p['status'] === 'cancelled' ? '<span class="badge bg-danger-subtle text-danger">Voided</span>' : '' ?></div>
                                <div class="text-muted"><?= e($p['fee_type_name'] ?? '') ?> &middot; <?= e(PAYMENT_MODES[$p['payment_mode']] ?? ucfirst($p['payment_mode'])) ?></div>
                                <div class="text-muted"><?= e($p['paid_at_bs'] ?? substr($p['paid_at'], 0, 10)) ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?= e(format_currency($p['amount'])) ?></div>
                            <?php if ($p['status'] !== 'cancelled' && in_array(\App\Core\Auth::role(), ['super_admin', 'principal'], true)): ?>
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 void-btn" data-payment-id="<?= e($p['id']) ?>" data-receipt="<?= e($p['receipt_number']) ?>">Void</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-person-search fs-1"></i>
                <p class="mt-2 mb-0">Search for a student to view their fee ledger.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ==================== Fee Ledger ==================== -->
    <div class="col-lg-8">
        <?php if ($student): ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="<?= e(url('payments/apply-discount')) ?>" class="d-none" id="discountForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                    <input type="hidden" name="fee_id" id="discount_fee_id">
                    <input type="hidden" name="type" id="discount_type">
                    <input type="hidden" name="value" id="discount_value">
                    <input type="hidden" name="reason" id="discount_reason">
                </form>

                <form method="POST" action="<?= e(url('payments/apply-fine')) ?>" class="d-none" id="fineForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                    <input type="hidden" name="fee_id" id="fine_fee_id">
                    <input type="hidden" name="type" id="fine_type">
                    <input type="hidden" name="rate" id="fine_rate">
                    <input type="hidden" name="notes" id="fine_notes">
                </form>

                <form method="POST" action="<?= e(url('payments/collect')) ?>" id="paymentForm" class="row g-2 align-items-end mb-3"
                      data-confirm data-confirm-icon="question" data-confirm-title="Post this payment?"
                      data-confirm-message="Please confirm the payment details are correct before posting."
                      data-confirm-button="Yes, post payment" data-confirm-variant="primary"
                      data-loading-text="Processing payment...">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e($idempotencyKey) ?>">
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Pay By</label>
                        <select name="payment_mode" class="form-select form-select-sm">
                            <?php foreach ($paymentModes as $mv => $ml): ?>
                            <option value="<?= e($mv) ?>"><?= e($ml) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Ref No.</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Trans Date (BS)</label>
                        <input type="text" name="trans_date_bs" class="form-control form-control-sm" value="<?= e($today_bs) ?>">
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="submit" id="postPaymentBtn" class="btn btn-primary btn-sm w-100"><i class="bi bi-cash-coin me-1"></i>Post Payment</button>
                    </div>
                </form>

                <?php if (empty($ledger)): ?>
                <p class="text-muted text-center py-4 mb-0">No fees have been assigned to this student yet. Use <a href="<?= e(url('fees/bulk-assign')) ?>">Bulk Fee Assignment</a>.</p>
                <?php else: ?>
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" id="printSelectedBtn" class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="bi bi-printer me-1"></i>Print Selected
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:36px;">S.No</th>
                                <th>Fee Head</th>
                                <th>Month</th>
                                <th class="text-end">Fee</th>
                                <th class="text-end">Concession</th>
                                <th class="text-end">Fine</th>
                                <th class="text-end">Payable</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th style="width:120px;">Paying</th>
                                <th>Status</th>
                                <th></th>
                                <th class="text-center">Print</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sno = 0; foreach ($ledger as $row):
                                $sno++;
                                $balance = (float) $row['payable_amount'] - (float) $row['amount_paid'];
                                $period = $row['month'] ? date('M', mktime(0, 0, 0, (int) $row['month'], 1)) : ($row['term_name'] ?? 'Annual');
                            ?>
                            <tr>
                                <td class="text-muted"><?= $sno ?></td>
                                <td class="fw-semibold"><?= e($row['fee_type_name']) ?></td>
                                <td><?= e($period) ?></td>
                                <td class="text-end"><?= e(format_currency($row['amount_due'], '')) ?></td>
                                <td class="text-end"><?= e(format_currency($row['discount_amount'], '')) ?></td>
                                <td class="text-end"><?= e(format_currency($row['fine_amount'], '')) ?></td>
                                <td class="text-end"><?= e(format_currency($row['payable_amount'], '')) ?></td>
                                <td class="text-end"><?= e(format_currency($row['amount_paid'], '')) ?></td>
                                <td class="text-end fw-semibold"><?= e(format_currency($balance, '')) ?></td>
                                <td>
                                    <?php if ($balance > 0 && $row['status'] !== 'cancelled'): ?>
                                    <input type="number" step="0.01" min="0" max="<?= e($balance) ?>"
                                        name="paying[<?= e($row['id']) ?>]" form="paymentForm"
                                        class="form-control form-control-sm" placeholder="0">
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = ['unpaid' => 'secondary', 'partial' => 'warning', 'paid' => 'success', 'cancelled' => 'danger'][$row['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusBadge ?>-subtle text-<?= $statusBadge ?>"><?= e(ucfirst($row['status'])) ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($balance > 0 && $row['status'] !== 'cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning discount-btn"
                                        data-fee-id="<?= e($row['id']) ?>" data-amount-due="<?= e($row['amount_due']) ?>"
                                        title="Apply Discount"><i class="bi bi-percent"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fine-btn"
                                        data-fee-id="<?= e($row['id']) ?>"
                                        title="Apply Fine"><i class="bi bi-exclamation-triangle"></i></button>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input print-row-check" value="<?= e($row['id']) ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Discount modal -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Apply Discount</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Discount Type</label>
                    <select id="modalDiscountType" class="form-select form-select-sm">
                        <option value="fixed">Fixed Discount (Rs.)</option>
                        <option value="percentage">Percentage Discount (%)</option>
                        <option value="scholarship">Scholarship</option>
                        <option value="sibling">Sibling Discount</option>
                        <option value="staff">Staff Discount</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Value</label>
                    <input type="number" step="0.01" min="0.01" id="modalDiscountValue" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Reason (optional)</label>
                    <input type="text" id="modalDiscountReason" class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning" id="modalDiscountSubmit">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Fine modal -->
<div class="modal fade" id="fineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Apply Fine</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Fine Type</label>
                    <select id="modalFineType" class="form-select form-select-sm">
                        <option value="fixed">Fixed Fine (Rs.)</option>
                        <option value="daily">Daily Fine (Rs./day overdue)</option>
                        <option value="percentage">Percentage Fine (%)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Rate / Amount</label>
                    <input type="number" step="0.01" min="0.01" id="modalFineRate" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Notes (optional)</label>
                    <input type="text" id="modalFineNotes" class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="modalFineSubmit">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Void payment modal -->
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="voidForm" class="sa-loading-submit" data-loading-text="Voiding payment...">
                <?= csrf_field() ?>
                <input type="hidden" name="student_id" value="<?= e($student['id'] ?? '') ?>">
                <input type="hidden" name="reason" id="voidReasonHidden">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Void Payment <span id="voidReceiptLabel"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Reason for voiding (required)</label>
                    <input type="text" id="voidReasonInput" class="form-control form-control-sm" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Void Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const searchInput = document.getElementById('studentSearch');
    const resultsBox = document.getElementById('studentResults');
    const filterYear = document.getElementById('filterAcademicYear');
    const filterClass = document.getElementById('filterClass');
    const filterSection = document.getElementById('filterSection');
    let debounceTimer;

    function runSearch() {
        const term = searchInput.value.trim();
        const params = new URLSearchParams();
        if (term) params.set('term', term);
        if (filterYear.value) params.set('academic_year_id', filterYear.value);
        if (filterClass.value) params.set('class_id', filterClass.value);
        if (filterSection.value) params.set('section_id', filterSection.value);

        if (term.length < 2 && !filterClass.value) { resultsBox.style.display = 'none'; return; }

        fetch('<?= e(url('payments/search-student')) ?>?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                resultsBox.innerHTML = '';
                if (rows.length === 0) { resultsBox.style.display = 'none'; return; }
                rows.forEach(function (s) {
                    const a = document.createElement('a');
                    a.href = '<?= e(url('payments/collect')) ?>?student_id=' + s.id;
                    a.className = 'list-group-item list-group-item-action';
                    a.innerHTML = '<strong>' + s.full_name + '</strong> <span class="text-muted small">' + (s.admission_number || '') + ' &middot; ' + (s.class_name || '') + ' ' + (s.section_name || '') + '</span>';
                    resultsBox.appendChild(a);
                });
                resultsBox.style.display = '';
            });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 300);
        });
        document.addEventListener('click', function (e) {
            if (!resultsBox.contains(e.target) && e.target !== searchInput) resultsBox.style.display = 'none';
        });
    }

    if (filterClass) {
        filterClass.addEventListener('change', function () {
            filterSection.innerHTML = '<option value="">All</option>';
            if (!filterClass.value) { runSearch(); return; }
            fetch('<?= e(url('payments/sections-for-class')) ?>?class_id=' + filterClass.value)
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    rows.forEach(function (s) {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.name;
                        filterSection.appendChild(opt);
                    });
                    runSearch();
                });
        });
    }
    if (filterSection) { filterSection.addEventListener('change', runSearch); }
    if (filterYear) { filterYear.addEventListener('change', runSearch); }

    // Prevent a double-click / double-submit from posting the same payment twice.
    const paymentForm = document.getElementById('paymentForm');
    const postBtn = document.getElementById('postPaymentBtn');
    if (paymentForm && postBtn) {
        paymentForm.addEventListener('submit', function () {
            postBtn.disabled = true;
            postBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Posting…';
        });
    }

    // Discount modal
    let discountModalFeeId = null;
    document.querySelectorAll('.discount-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            discountModalFeeId = btn.getAttribute('data-fee-id');
            new bootstrap.Modal(document.getElementById('discountModal')).show();
        });
    });
    const discountSubmitBtn = document.getElementById('modalDiscountSubmit');
    if (discountSubmitBtn) {
        discountSubmitBtn.addEventListener('click', function () {
            const value = document.getElementById('modalDiscountValue').value;
            if (!value || parseFloat(value) <= 0) { return; }
            document.getElementById('discount_fee_id').value = discountModalFeeId;
            document.getElementById('discount_type').value = document.getElementById('modalDiscountType').value;
            document.getElementById('discount_value').value = value;
            document.getElementById('discount_reason').value = document.getElementById('modalDiscountReason').value;
            document.getElementById('discountForm').submit();
        });
    }

    // Fine modal
    let fineModalFeeId = null;
    document.querySelectorAll('.fine-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fineModalFeeId = btn.getAttribute('data-fee-id');
            new bootstrap.Modal(document.getElementById('fineModal')).show();
        });
    });
    const fineSubmitBtn = document.getElementById('modalFineSubmit');
    if (fineSubmitBtn) {
        fineSubmitBtn.addEventListener('click', function () {
            const rate = document.getElementById('modalFineRate').value;
            if (!rate || parseFloat(rate) <= 0) { return; }
            document.getElementById('fine_fee_id').value = fineModalFeeId;
            document.getElementById('fine_type').value = document.getElementById('modalFineType').value;
            document.getElementById('fine_rate').value = rate;
            document.getElementById('fine_notes').value = document.getElementById('modalFineNotes').value;
            document.getElementById('fineForm').submit();
        });
    }

    // Void modal
    document.querySelectorAll('.void-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('voidReceiptLabel').textContent = btn.getAttribute('data-receipt');
            document.getElementById('voidForm').action = '<?= e(url('payments')) ?>/' + btn.getAttribute('data-payment-id') + '/void';
            new bootstrap.Modal(document.getElementById('voidModal')).show();
        });
    });
    const voidForm = document.getElementById('voidForm');
    if (voidForm) {
        voidForm.addEventListener('submit', function () {
            document.getElementById('voidReasonHidden').value = document.getElementById('voidReasonInput').value;
        });
    }

    // Print-selected checkboxes
    const printBtn = document.getElementById('printSelectedBtn');
    function updatePrintBtn() {
        const checked = document.querySelectorAll('.print-row-check:checked');
        if (printBtn) printBtn.disabled = checked.length === 0;
    }
    document.querySelectorAll('.print-row-check').forEach(function (cb) {
        cb.addEventListener('change', updatePrintBtn);
    });
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            const ids = Array.from(document.querySelectorAll('.print-row-check:checked')).map(function (cb) { return cb.value; });
            if (!ids.length) return;
            const url = '<?= e(url('payments/print-ledger')) ?>?student_id=<?= e($student['id'] ?? '') ?>&fee_ids=' + ids.join(',');
            window.open(url, '_blank');
        });
    }
})();
</script>
