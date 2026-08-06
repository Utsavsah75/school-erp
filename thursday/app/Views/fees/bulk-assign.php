<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_bulk_fee_assignment')) ?></h5>
    <a href="<?= e(url('fee-types')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags-fill me-1"></i>Manage Fee Types</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Academic Session</label>
                <select id="academic_year_id" class="form-select form-select-sm">
                    <?php foreach ($academicYears as $ay): ?>
                    <option value="<?= e($ay['id']) ?>"><?= e($ay['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select id="class_id" class="form-select form-select-sm">
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                <select id="section_id" class="form-select form-select-sm">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_fee_type')) ?></label>
                <select id="fee_type_id" class="form-select form-select-sm">
                    <option value="">-- Select --</option>
                    <?php foreach ($feeTypes as $ft): ?>
                    <option value="<?= e($ft['id']) ?>" data-category="<?= e($ft['category']) ?>"
                        data-recurrence="<?= e($ft['recurrence_type']) ?>"><?= e($ft['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2" id="monthField" style="display:none;">
                <label class="form-label small text-muted mb-1"><?= e(t('th_month')) ?></label>
                <select id="month" class="form-select form-select-sm">
                    <option value="">-- Select --</option>
                    <?php
                    $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>"><?= e($mn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2" id="termField" style="display:none;">
                <label class="form-label small text-muted mb-1">Term</label>
                <select id="term_id" class="form-select form-select-sm">
                    <option value="">-- None --</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Fee Date (BS)</label>
                <input type="text" id="fee_date_bs" class="form-control form-control-sm" value="<?= e($today_bs) ?>" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Due Date (BS)</label>
                <input type="text" id="due_date_bs" class="form-control form-control-sm" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="button" id="fetchStudentsBtn" class="btn btn-secondary btn-sm"><i class="bi bi-search me-1"></i>Fetch Students</button>
                <button type="button" id="assignSelectedBtn" class="btn btn-primary btn-sm" disabled><i class="bi bi-check2-square me-1"></i>Assign to Selected</button>
                <button type="button" id="assignAllBtn" class="btn btn-success btn-sm" disabled><i class="bi bi-check2-all me-1"></i>Assign to All</button>
                <button type="button" id="cancelBtn" class="btn btn-light btn-sm"><i class="bi bi-x-circle me-1"></i>Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><?= e(t('h_preview')) ?></h6>
        <div id="loadingIndicator" class="text-center text-muted py-4" style="display:none;">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>Fetching students…
        </div>
        <div id="emptyState" class="text-center text-muted py-4">
            Select a session, class, and fee type, then click <strong>Fetch Students</strong>.
        </div>
        <div id="previewTableWrap" style="display:none;">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllStudents"></th>
                            <th><?= e(t('th_roll_no')) ?></th>
                            <th><?= e(t('th_admission_no')) ?></th>
                            <th>Student Name</th>
                            <th style="width:160px;">Amount (Rs.)</th>
                            <th><?= e(t('th_status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="previewTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="assignForm" method="POST" action="<?= e(url('fees/bulk-assign')) ?>" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="fee_type_id" id="hidden_fee_type_id">
    <input type="hidden" name="academic_year_id" id="hidden_academic_year_id">
    <input type="hidden" name="term_id" id="hidden_term_id">
    <input type="hidden" name="month" id="hidden_month">
    <input type="hidden" name="fee_date_bs" id="hidden_fee_date_bs">
    <input type="hidden" name="due_date_bs" id="hidden_due_date_bs">
    <div id="hiddenAmounts"></div>
</form>

<div id="toastHost" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
(function () {
    const feeTypeSelect = document.getElementById('fee_type_id');
    const monthField = document.getElementById('monthField');
    const termField = document.getElementById('termField');
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const yearSelect = document.getElementById('academic_year_id');
    const termSelect = document.getElementById('term_id');
    const fetchBtn = document.getElementById('fetchStudentsBtn');
    const assignSelectedBtn = document.getElementById('assignSelectedBtn');
    const assignAllBtn = document.getElementById('assignAllBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const previewTbody = document.getElementById('previewTbody');
    const previewWrap = document.getElementById('previewTableWrap');
    const emptyState = document.getElementById('emptyState');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const selectAll = document.getElementById('selectAllStudents');

    let currentStudents = [];

    function toast(message, variant) {
        // Delegates to the shared SweetAlert2 toast helper (see
        // sweetalert-helpers.js) so this page matches the rest of the app.
        const icon = variant === 'danger' ? 'error' : (variant === 'success' ? 'success' : (variant === 'warning' ? 'warning' : 'info'));
        if (window.SA) {
            window.SA.toast(icon, message);
        }
    }

    // ---- Fee Type change: toggle Month / Term fields based on recurrence ----
    feeTypeSelect.addEventListener('change', function () {
        const opt = feeTypeSelect.options[feeTypeSelect.selectedIndex];
        const recurrence = opt ? opt.getAttribute('data-recurrence') : '';
        monthField.style.display = recurrence === 'monthly' ? '' : 'none';
        termField.style.display = (recurrence === 'quarterly' || recurrence === 'half_yearly' || recurrence === 'yearly') ? '' : 'none';
        resetPreview();
    });

    // ---- Class change: load sections ----
    classSelect.addEventListener('change', function () {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        if (!classSelect.value) { resetPreview(); return; }
        fetch('<?= e(url('fees/sections-for-class')) ?>?class_id=' + classSelect.value)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                rows.forEach(function (s) {
                    const o = document.createElement('option');
                    o.value = s.id; o.textContent = s.name;
                    sectionSelect.appendChild(o);
                });
            });
        resetPreview();
    });

    // ---- Academic year change: load terms ----
    yearSelect.addEventListener('change', function () {
        termSelect.innerHTML = '<option value="">-- None --</option>';
        fetch('<?= e(url('fees/terms-for-year')) ?>?academic_year_id=' + yearSelect.value)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                rows.forEach(function (t) {
                    const o = document.createElement('option');
                    o.value = t.id; o.textContent = t.name;
                    termSelect.appendChild(o);
                });
            });
        resetPreview();
    });

    function resetPreview() {
        currentStudents = [];
        previewWrap.style.display = 'none';
        emptyState.style.display = '';
        assignSelectedBtn.disabled = true;
        assignAllBtn.disabled = true;
    }

    cancelBtn.addEventListener('click', function () {
        document.getElementById('filterForm').reset();
        monthField.style.display = 'none';
        termField.style.display = 'none';
        resetPreview();
    });

    // ---- Fetch Students ----
    fetchBtn.addEventListener('click', function () {
        const feeTypeId = feeTypeSelect.value;
        const classId = classSelect.value;
        const academicYearId = yearSelect.value;
        if (!feeTypeId || !classId || !academicYearId) {
            toast('Select Academic Session, Class, and Fee Type first.', 'danger');
            return;
        }

        loadingIndicator.style.display = '';
        emptyState.style.display = 'none';
        previewWrap.style.display = 'none';

        const params = new URLSearchParams({
            class_id: classId,
            section_id: sectionSelect.value,
            fee_type_id: feeTypeId,
            academic_year_id: academicYearId,
            term_id: termSelect.value,
            month: document.getElementById('month').value,
        });

        fetch('<?= e(url('fees/fetch-students')) ?>?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                loadingIndicator.style.display = 'none';
                if (data.error) { toast(data.error, 'danger'); emptyState.style.display = ''; return; }
                currentStudents = data.students;
                renderPreview();
            })
            .catch(function () {
                loadingIndicator.style.display = 'none';
                toast('Something went wrong fetching students.', 'danger');
                emptyState.style.display = '';
            });
    });

    function renderPreview() {
        previewTbody.innerHTML = '';
        if (currentStudents.length === 0) {
            emptyState.textContent = 'No active students found for that class/section.';
            emptyState.style.display = '';
            previewWrap.style.display = 'none';
            return;
        }
        currentStudents.forEach(function (s, idx) {
            const tr = document.createElement('tr');
            if (s.already_assigned) tr.classList.add('table-secondary');
            tr.innerHTML =
                '<td><input type="checkbox" class="student-row-check" data-idx="' + idx + '" ' + (s.already_assigned ? 'disabled' : '') + '></td>' +
                '<td>' + (s.roll_number || '—') + '</td>' +
                '<td>' + (s.admission_number || '—') + '</td>' +
                '<td class="fw-semibold">' + s.full_name + '</td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm student-amount" data-idx="' + idx + '" value="' + s.amount + '" ' + (s.already_assigned ? 'disabled' : '') + '></td>' +
                '<td>' + (s.already_assigned ? '<span class="badge bg-secondary-subtle text-secondary">Already Assigned</span>' : '<span class="badge bg-success-subtle text-success">Ready</span>') + '</td>';
            previewTbody.appendChild(tr);
        });
        previewWrap.style.display = '';
        const anyAssignable = currentStudents.some(function (s) { return !s.already_assigned; });
        assignSelectedBtn.disabled = !anyAssignable;
        assignAllBtn.disabled = !anyAssignable;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.student-row-check:not(:disabled)').forEach(function (cb) { cb.checked = selectAll.checked; });
    });

    function submitAssignment(onlySelected) {
        const hiddenAmounts = document.getElementById('hiddenAmounts');
        hiddenAmounts.innerHTML = '';
        let anyAdded = false;

        currentStudents.forEach(function (s, idx) {
            if (s.already_assigned) return;
            const checkbox = document.querySelector('.student-row-check[data-idx="' + idx + '"]');
            if (onlySelected && !(checkbox && checkbox.checked)) return;
            const amountInput = document.querySelector('.student-amount[data-idx="' + idx + '"]');
            const amount = amountInput ? amountInput.value : s.amount;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'amounts[' + s.id + ']';
            input.value = amount;
            hiddenAmounts.appendChild(input);
            anyAdded = true;
        });

        if (!anyAdded) {
            toast(onlySelected ? 'Select at least one student first.' : 'No assignable students to process.', 'danger');
            return;
        }

        document.getElementById('hidden_fee_type_id').value = feeTypeSelect.value;
        document.getElementById('hidden_academic_year_id').value = yearSelect.value;
        document.getElementById('hidden_term_id').value = termSelect.value;
        document.getElementById('hidden_month').value = document.getElementById('month').value;
        document.getElementById('hidden_fee_date_bs').value = document.getElementById('fee_date_bs').value;
        document.getElementById('hidden_due_date_bs').value = document.getElementById('due_date_bs').value;

        const confirmFire = window.SA ? window.SA.confirmAction({
            icon: 'question',
            title: 'Assign Fees?',
            text: onlySelected ? 'Assign this fee to the selected students?' : 'Assign this fee to all assignable students?',
            confirmButtonText: 'Yes, assign',
        }) : Promise.resolve(true);

        confirmFire.then(function (confirmed) {
            if (!confirmed) { return; }
            if (window.SA) { window.SA.loading('Assigning fees...'); }
            document.getElementById('assignForm').submit();
        });
    }

    assignSelectedBtn.addEventListener('click', function () { submitAssignment(true); });
    assignAllBtn.addEventListener('click', function () { submitAssignment(false); });
})();
</script>
