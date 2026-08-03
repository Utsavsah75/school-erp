<?php
$sortLink = function (string $col) use ($sort, $direction, $search, $filters) {
    $nextDir = ($sort === $col && $direction === 'ASC') ? 'DESC' : 'ASC';
    return e(url('classes') . '?' . http_build_query(array_merge($filters, [
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
    <h5 class="fw-bold mb-0">Classes Management</h5>
    <a href="<?= e(url('classes/create')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle-fill me-1"></i>Add New Class</a>
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

<!-- ==================== All Classes ==================== -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">All Classes</h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= e(url('classes') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-secondary" title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
                <a href="<?= e(url('classes/export-excel') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-success" title="Export Excel"><i class="bi bi-file-earmark-excel-fill"></i> Excel</a>
                <a href="<?= e(url('classes/export-pdf') . '?' . http_build_query($queryParams)) ?>" class="btn btn-sm btn-outline-danger" title="Export PDF"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                <a href="<?= e(url('classes/print') . '?' . http_build_query($queryParams)) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print"><i class="bi bi-printer-fill"></i> Print</a>
            </div>
        </div>

        <form method="GET" action="<?= e(url('classes')) ?>" class="row g-2 align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Class Name</label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= e($filters['name']) ?>" placeholder="Search by class name">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Search Code / Teacher</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Search by teacher">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Shift</label>
                <select name="shift" class="form-select form-select-sm">
                    <option value="">All Shifts</option>
                    <?php foreach (CLASS_SECTION_SHIFTS as $sv => $sl): ?>
                    <option value="<?= e($sv) ?>" <?= $filters['shift'] === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
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
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> Search</button>
                <a href="<?= e(url('classes')) ?>" class="btn btn-light btn-sm" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>

        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No classes found.</p>
        <?php else: ?>
        <form method="POST" action="<?= e(url('classes/bulk-delete')) ?>" class="confirm-delete" data-confirm-message="Delete the selected classes? This action cannot be undone." id="bulkDeleteForm">
            <?= csrf_field() ?>
            <div class="d-flex justify-content-end mb-2">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th><input type="checkbox" id="selectAllClasses"></th>
                            <th>S.N.</th>
                            <th>Class ID</th>
                            <th><a href="<?= $sortLink('name') ?>" class="text-decoration-none text-reset">Class Name <?= $sortIcon('name') ?></a></th>
                            <th><a href="<?= $sortLink('code') ?>" class="text-decoration-none text-reset">Class Code <?= $sortIcon('code') ?></a></th>
                            <th>Sections</th>
                            <th>Class Teacher</th>
                            <th><a href="<?= $sortLink('room_number') ?>" class="text-decoration-none text-reset">Room <?= $sortIcon('room_number') ?></a></th>
                            <th><a href="<?= $sortLink('capacity') ?>" class="text-decoration-none text-reset">Capacity <?= $sortIcon('capacity') ?></a></th>
                            <th>Students</th>
                            <th><a href="<?= $sortLink('shift') ?>" class="text-decoration-none text-reset">Shift <?= $sortIcon('shift') ?></a></th>
                            <th>Academic Year</th>
                            <th><a href="<?= $sortLink('status') ?>" class="text-decoration-none text-reset">Status <?= $sortIcon('status') ?></a></th>
                            <th><a href="<?= $sortLink('created_at') ?>" class="text-decoration-none text-reset">Created <?= $sortIcon('created_at') ?></a></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $start = ((int) ($result['page'] ?? 1) - 1) * ((int) ($result['per_page'] ?? count($result['data']))); ?>
                        <?php foreach ($result['data'] as $i => $c): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= e($c['id']) ?>" class="class-checkbox"></td>
                            <td><?= e($start + $i + 1) ?></td>
                            <td>#<?= e($c['id']) ?></td>
                            <td class="fw-semibold"><?= e($c['name']) ?></td>
                            <td><?= e($c['code']) ?></td>
                            <td><?= (int) $c['sections_count'] ?></td>
                            <td><?= e($c['teacher_name'] ?? '—') ?></td>
                            <td><?= e($c['room_number'] ?: '—') ?></td>
                            <td><?= e($c['capacity'] ?: '—') ?></td>
                            <td><?= (int) $c['students_count'] ?></td>
                            <td><?= e(CLASS_SECTION_SHIFTS[$c['shift']] ?? ucfirst($c['shift'])) ?></td>
                            <td><?= e($c['academic_year_label'] ?? '—') ?></td>
                            <td>
                                <?php if ($c['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_date($c['created_at'])) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('classes/' . $c['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye-fill"></i></a>
                                <a href="<?= e(url('classes/' . $c['id'] . '/edit')) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                <button type="submit" form="delete-class-<?= e($c['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                <form id="delete-class-<?= e($c['id']) ?>" method="POST" action="<?= e(url('classes/' . $c['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this class? This action cannot be undone."><?= csrf_field() ?></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?= paginate_links($result, url('classes'), $queryParams) ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('selectAllClasses')?.addEventListener('change', function() {
    document.querySelectorAll('.class-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }.bind(this));
});
</script>
