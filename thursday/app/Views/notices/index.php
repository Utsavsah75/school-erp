<?php
$qs = fn (array $extra = []) => http_build_query(array_merge([
    'search' => $search, 'sort' => $sort, 'direction' => $direction,
], $filters, $extra));
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-megaphone-fill me-1"></i> Notice Board</h5>
        <p class="text-muted small mb-0">School-wide and targeted notices — class, section, or individual students.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(url('notices/print') . '?' . $qs()) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer-fill me-1"></i>Print</a>
        <a href="<?= e(url('notices/export-excel') . '?' . $qs()) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel-fill me-1"></i>Excel</a>
        <a href="<?= e(url('notices/export-pdf') . '?' . $qs()) ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF</a>
        <?php if ($canManage): ?>
        <a href="<?= e(url('notices/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle-fill me-1"></i>Add Notice</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="<?= e(url('notices')) ?>" class="row g-2 align-items-end mb-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Search Title / Description</label>
                <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_category')) ?></label>
                <select name="category" id="filterCategory" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $filters['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select name="class_id" id="filterClass" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (string) $filters['class_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_section')) ?></label>
                <select name="section_id" id="filterSection" class="form-select form-select-sm">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (string) $filters['section_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1"><?= e(t('th_status')) ?></label>
                <select name="status" id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (\App\Models\Notice::statusOptions() as $sv => $sl): ?>
                    <option value="<?= e($sv) ?>" <?= $filters['status'] === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Publish Date</label>
                <input type="date" name="publish_date" id="filterPublishDate" class="form-control form-control-sm" value="<?= e($filters['publish_date']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Priority</label>
                <select name="priority" id="filterPriority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <?php foreach (\App\Models\Notice::priorityOptions() as $pv => $pl): ?>
                    <option value="<?= e($pv) ?>" <?= $filters['priority'] === $pv ? 'selected' : '' ?>><?= e($pl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="<?= e(url('notices')) ?>" class="btn btn-sm btn-light w-100">Clear Filters</a>
            </div>
        </form>

        <div id="noticesTableWrap">
            <?php require __DIR__ . '/_table.php'; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var sectionsUrl = <?= json_encode(url('notices/sections-for-class')) ?>;
    var listUrl = <?= json_encode(url('notices')) ?>;
    var wrap = document.getElementById('noticesTableWrap');
    var filterForm = document.getElementById('filterForm');

    function loadInto(select, url, placeholder, keepValue) {
        select.disabled = true;
        fetch(url).then(function (r) { return r.json(); }).then(function (rows) {
            select.innerHTML = '';
            select.appendChild(new Option(placeholder, ''));
            rows.forEach(function (row) {
                select.appendChild(new Option(row.name, row.id, false, String(row.id) === String(keepValue || '')));
            });
            select.disabled = false;
        }).catch(function () { select.disabled = false; });
    }

    var filterClass = document.getElementById('filterClass');
    if (filterClass) {
        filterClass.addEventListener('change', function () {
            if (!this.value) { return; }
            loadInto(document.getElementById('filterSection'), sectionsUrl + '/' + this.value, 'All Sections');
        });
    }

    // -------- AJAX search/filter (spec requirement) --------
    var debounceTimer = null;
    function ajaxFilter(extraParams) {
        var params = new URLSearchParams(new FormData(filterForm));
        if (extraParams) {
            Object.keys(extraParams).forEach(function (k) { params.set(k, extraParams[k]); });
        }
        params.set('ajax', '1');
        fetch(listUrl + '?' + params.toString())
            .then(function (r) { return r.text(); })
            .then(function (html) { wrap.innerHTML = html; bindTableEvents(); })
            .catch(function () {});
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxFilter();
    });
    filterForm.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', function () { ajaxFilter(); });
    });
    var searchInput = document.getElementById('filterSearch');
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { ajaxFilter(); }, 400);
    });
    document.getElementById('filterPublishDate').addEventListener('change', function () { ajaxFilter(); });

    function bindTableEvents() {
        wrap.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                ajaxFilter({ page: this.getAttribute('data-page') });
            });
        });
        wrap.querySelectorAll('a[data-sort]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var col = this.getAttribute('data-sort');
                var params = new URLSearchParams(new FormData(filterForm));
                var currentSort = params.get('sort');
                var currentDir = params.get('direction') || 'DESC';
                var nextDir = (currentSort === col && currentDir === 'ASC') ? 'DESC' : 'ASC';
                ajaxFilter({ sort: col, direction: nextDir });
            });
        });
    }
    bindTableEvents();
})();
</script>
