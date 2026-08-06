<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_student_attendance')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= e(url('attendance/mark')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
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
            '<span class="badge bg-' . $cls . '">' . e(st($r['status'])) . '</span>',
            e($r['marked_by_name'] ?? '—'),
        ],
    ];
}, $recentActivity ?? []);
include dirname(__DIR__) . '/partials/_recent_activity_panel.php';
?>

<p class="text-muted">Pick a class and section to find a student, then open their individual monthly attendance sheet.</p>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('attendance')) ?>" class="row g-2 align-items-end" id="studentSelectorForm">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select name="class_id" id="ssClass" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= (string) $classId === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                <select name="section_id" id="ssSection" class="form-select" required <?= $classId ? '' : 'disabled' ?>>
                    <option value=""><?= $classId ? '-- Select --' : '-- Select Class First --' ?></option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= e($s['id']) ?>" data-class="<?= e($s['class_id']) ?>" <?= (string) $sectionId === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 position-relative">
                <label class="form-label small text-muted mb-1">Search by Name / Roll No.</label>
                <input type="text" name="search" id="ssSearch" class="form-control" autocomplete="off"
                    value="<?= e($search) ?>" placeholder="Start typing a name or roll number..."
                    role="combobox" aria-expanded="false" aria-controls="ssResults" aria-autocomplete="list"
                    <?= ($classId && $sectionId) ? '' : 'disabled' ?>>
                <div id="ssResults" class="student-selector-dropdown shadow-sm" role="listbox" hidden></div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!$classId || !$sectionId): ?>
            <p class="text-muted text-center py-4 mb-0">Select a Class and Section above to list students.</p>
        <?php elseif (empty($students)): ?>
            <p class="text-muted text-center py-4 mb-0">No students found for this class/section.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_photo')) ?></th>
                            <th><?= e(t('th_roll_no')) ?></th>
                            <th><?= e(t('th_name')) ?></th>
                            <th><?= e(t('th_admission_no')) ?></th>
                            <th class="text-end"><?= e(t('th_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($s['photo_path'])): ?>
                                        <img src="<?= e(upload_url($s['photo_path'])) ?>" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle text-muted" style="font-size:28px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($s['roll_number']) ?></td>
                                <td class="fw-semibold"><?= e($s['full_name']) ?></td>
                                <td><?= e($s['admission_number']) ?></td>
                                <td class="text-end">
                                    <a href="<?= e(url('attendance/' . $s['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-calendar-check me-1"></i>View Attendance
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.student-selector-dropdown {
    position: absolute;
    top: 100%;
    left: 12px;
    right: 12px;
    z-index: 1050;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-top: 4px;
    max-height: 320px;
    overflow-y: auto;
}
.student-selector-dropdown .ss-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    cursor: pointer;
}
.student-selector-dropdown .ss-option:hover,
.student-selector-dropdown .ss-option.ss-active {
    background: #f1f5fb;
}
.student-selector-dropdown .ss-option img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}
.student-selector-dropdown .ss-empty,
.student-selector-dropdown .ss-loading {
    padding: 10px 12px;
    color: #888;
    font-size: 0.875rem;
}
</style>

<script>
/**
 * Student Selector — reusable searchable/keyboard-friendly autocomplete.
 * Class -> Section are AJAX dependent dropdowns; once both are set, the
 * search box queries /attendance/search-students/{classId}/{sectionId}
 * as the person types and lets them jump straight to a student's
 * attendance sheet without a full page reload.
 */
(function () {
    var classSelect = document.getElementById('ssClass');
    var sectionSelect = document.getElementById('ssSection');
    var searchInput = document.getElementById('ssSearch');
    var resultsBox = document.getElementById('ssResults');
    if (!classSelect || !sectionSelect || !searchInput || !resultsBox) return;

    var sectionsForClassUrl = <?= json_encode(url('students/sections-for-class')) ?>;
    var searchStudentsUrlBase = <?= json_encode(url('attendance/search-students')) ?>;
    var activeIndex = -1;
    var currentItems = [];
    var debounceTimer = null;

    function setSectionDisabled(disabled) {
        sectionSelect.disabled = disabled;
        searchInput.disabled = disabled || !sectionSelect.value;
    }

    classSelect.addEventListener('change', function () {
        closeDropdown();
        if (!this.value) {
            sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            setSectionDisabled(true);
            searchInput.disabled = true;
            return;
        }
        sectionSelect.disabled = true;
        fetch(sectionsForClassUrl + '/' + this.value)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                sectionSelect.innerHTML = '';
                sectionSelect.appendChild(new Option('-- Select --', ''));
                rows.forEach(function (row) {
                    sectionSelect.appendChild(new Option(row.name, row.id));
                });
                sectionSelect.disabled = false;
                searchInput.disabled = true;
            })
            .catch(function () { sectionSelect.disabled = false; });
    });

    sectionSelect.addEventListener('change', function () {
        searchInput.disabled = !this.value;
        searchInput.value = '';
        closeDropdown();
        if (this.value) searchInput.focus();
    });

    function currentEndpoint() {
        return searchStudentsUrlBase + '/' + classSelect.value + '/' + sectionSelect.value;
    }

    function renderResults(students) {
        currentItems = students;
        activeIndex = -1;
        if (!students.length) {
            resultsBox.innerHTML = '<div class="ss-empty">No matching students.</div>';
            openDropdown();
            return;
        }
        resultsBox.innerHTML = students.map(function (s, i) {
            return '<div class="ss-option" role="option" id="ss-opt-' + i + '" data-id="' + s.id + '">'
                + '<img src="' + s.photo_url + '" alt="">'
                + '<span><strong>' + escapeHtml(s.full_name) + '</strong>'
                + '<span class="text-muted small ms-2">Roll ' + escapeHtml(String(s.roll_number)) + '</span></span>'
                + '</div>';
        }).join('');
        openDropdown();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function openDropdown() {
        resultsBox.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
    }
    function closeDropdown() {
        resultsBox.hidden = true;
        resultsBox.innerHTML = '';
        searchInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        currentItems = [];
    }

    function selectStudent(id) {
        window.location.href = <?= json_encode(url('attendance')) ?> + '/' + id;
    }

    searchInput.addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        if (!classSelect.value || !sectionSelect.value) return;
        if (q === '') { closeDropdown(); return; }
        debounceTimer = setTimeout(function () {
            resultsBox.innerHTML = '<div class="ss-loading">Searching...</div>';
            openDropdown();
            fetch(currentEndpoint() + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(renderResults)
                .catch(function () { resultsBox.innerHTML = '<div class="ss-empty">Search failed. Try again.</div>'; });
        }, 200);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (resultsBox.hidden || !currentItems.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, currentItems.length - 1);
            highlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlight();
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0) {
                e.preventDefault();
                selectStudent(currentItems[activeIndex].id);
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    function highlight() {
        resultsBox.querySelectorAll('.ss-option').forEach(function (el, i) {
            el.classList.toggle('ss-active', i === activeIndex);
            if (i === activeIndex) el.scrollIntoView({ block: 'nearest' });
        });
    }

    resultsBox.addEventListener('click', function (e) {
        var opt = e.target.closest('.ss-option');
        if (opt && opt.dataset.id) selectStudent(opt.dataset.id);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#ssResults') && e.target !== searchInput) closeDropdown();
    });

    // Initial state on page load (server-rendered GET with class+section already chosen).
    if (classSelect.value && sectionSelect.value) {
        setSectionDisabled(false);
        searchInput.disabled = false;
    } else if (!classSelect.value) {
        setSectionDisabled(true);
    }
})();
</script>
