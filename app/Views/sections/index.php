<?php
$sortLink = function (string $col) use ($sort, $direction, $search, $filters) {
    $nextDir = ($sort === $col && $direction === 'ASC') ? 'DESC' : 'ASC';
    return e(url('sections') . '?' . http_build_query(array_merge($filters, [
        'search' => $search, 'sort' => $col, 'direction' => $nextDir,
    ])));
};
$sortIcon = function (string $col) use ($sort, $direction) {
    if ($sort !== $col) {
        return '<i class="bi bi-arrow-down-up text-muted small"></i>';
    }
    return $direction === 'ASC' ? '<i class="bi bi-sort-alpha-down"></i>' : '<i class="bi bi-sort-alpha-up-alt"></i>';
};
$queryParams = array_merge($filters, ['search' => $search, 'sort' => $sort, 'direction' => $direction]);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Sections Management</h5>
    <a href="<?= e(url('sections/create')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle-fill me-1"></i>Add New Section</a>
</div>

<!-- ==================== Summary Cards ==================== -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('classes')) ?>" class="text-decoration-none text-reset">
        <div class="card stat-card stat-card-clickable">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Classes</div>
                    <h3><?= number_format($stats['total_classes']) ?></h3>
                </div>
                <div class="stat-icon" style="background:#4e73df;"><i class="bi bi-diagram-3-fill"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('sections')) ?>" class="text-decoration-none text-reset">
        <div class="card stat-card stat-card-clickable" style="border-left-color:#1cc88a;">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Sections</div>
                    <h3><?= number_format($stats['total_sections']) ?></h3>
                </div>
                <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-columns-gap"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('students')) ?>" class="text-decoration-none text-reset">
        <div class="card stat-card stat-card-clickable" style="border-left-color:#36b9cc;">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Students</div>
                    <h3><?= number_format($stats['total_students']) ?></h3>
                </div>
                <div class="stat-icon" style="background:#36b9cc;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('teachers')) ?>" class="text-decoration-none text-reset">
        <div class="card stat-card stat-card-clickable" style="border-left-color:#f6c23e;">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Teachers</div>
                    <h3><?= number_format($stats['total_teachers']) ?></h3>
                </div>
                <div class="stat-icon" style="background:#f6c23e;"><i class="bi bi-person-workspace"></i></div>
            </div>
        </div>
        </a>
    </div>
</div>

<!-- ==================== All Sections ==================== -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">All Sections</h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= e(url('sections') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-secondary" title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
                <a href="<?= e(url('sections/export-excel') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-success" title="Export Excel"><i class="bi bi-file-earmark-excel-fill"></i> Excel</a>
                <a href="<?= e(url('sections/export-pdf') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-danger" title="Export PDF"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                <a href="<?= e(url('sections/print') . '?' . http_build_query($queryParams)) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print"><i class="bi bi-printer-fill"></i> Print</a>
            </div>
        </div>

        <form method="GET" action="<?= e(url('sections')) ?>" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Section name, code, class">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (string) $filters['class_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Teacher</label>
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">All Teachers</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= e($t['id']) ?>" <?= (string) $filters['teacher_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (CLASS_SECTION_STATUSES as $sv => $sl): ?>
                    <option value="<?= e($sv) ?>" <?= $filters['status'] === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Academic Year</label>
                <select name="academic_year_id" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    <?php foreach ($academicYears as $ay): ?>
                    <option value="<?= e($ay['id']) ?>" <?= (string) $filters['academic_year_id'] === (string) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                <a href="<?= e(url('sections')) ?>" class="btn btn-light btn-sm" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>

        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No sections found.</p>
        <?php else: ?>
        <form method="POST" action="<?= e(url('sections/bulk-delete')) ?>" class="confirm-delete" data-confirm-message="Delete the selected sections? This action cannot be undone." id="bulkDeleteForm">
            <?= csrf_field() ?>
            <div class="d-flex justify-content-end mb-2">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th><input type="checkbox" id="selectAllSections"></th>
                            <th>Section ID</th>
                            <th><a href="<?= $sortLink('name') ?>" class="text-decoration-none text-reset">Section Name <?= $sortIcon('name') ?></a></th>
                            <th><a href="<?= $sortLink('code') ?>" class="text-decoration-none text-reset">Section Code <?= $sortIcon('code') ?></a></th>
                            <th><a href="<?= $sortLink('class') ?>" class="text-decoration-none text-reset">Class <?= $sortIcon('class') ?></a></th>
                            <th>Teacher</th>
                            <th><a href="<?= $sortLink('room_number') ?>" class="text-decoration-none text-reset">Room <?= $sortIcon('room_number') ?></a></th>
                            <th><a href="<?= $sortLink('capacity') ?>" class="text-decoration-none text-reset">Capacity <?= $sortIcon('capacity') ?></a></th>
                            <th>Students</th>
                            <th><a href="<?= $sortLink('status') ?>" class="text-decoration-none text-reset">Status <?= $sortIcon('status') ?></a></th>
                            <th><a href="<?= $sortLink('created_at') ?>" class="text-decoration-none text-reset">Created <?= $sortIcon('created_at') ?></a></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $sec): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= e($sec['id']) ?>" class="section-checkbox"></td>
                            <td>#<?= e($sec['id']) ?></td>
                            <td class="fw-semibold"><?= e($sec['name']) ?></td>
                            <td><?= e($sec['code']) ?></td>
                            <td><?= e($sec['class_name'] ?? '—') ?></td>
                            <td><?= e($sec['teacher_name'] ?? '—') ?></td>
                            <td><?= e($sec['room_number'] ?: '—') ?></td>
                            <td><?= e($sec['capacity'] ?: '—') ?></td>
                            <td><?= (int) $sec['students_count'] ?></td>
                            <td>
                                <?php if ($sec['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_date($sec['created_at'])) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('sections/' . $sec['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye-fill"></i></a>
                                <a href="<?= e(url('sections/' . $sec['id'] . '/edit')) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                <button type="submit" form="delete-section-<?= e($sec['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                <form id="delete-section-<?= e($sec['id']) ?>" method="POST" action="<?= e(url('sections/' . $sec['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this section? This action cannot be undone."><?= csrf_field() ?></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?= paginate_links($result, url('sections'), $queryParams) ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('selectAllSections')?.addEventListener('change', function() {
    document.querySelectorAll('.section-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }.bind(this));
});
</script>
