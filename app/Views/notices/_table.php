<?php
/**
 * Notice list table + pagination. Rendered both by notices/index.php (first
 * page load) and directly (viewRaw) by NoticeController::index() for the
 * AJAX search/filter requests — must not assume a wrapping layout.
 */
$sortLink = function (string $col, string $label) use ($sort, $direction) {
    $nextDir = ($sort === $col && $direction === 'ASC') ? 'DESC' : 'ASC';
    $icon = $sort !== $col ? '<i class="bi bi-arrow-down-up text-muted small"></i>'
        : ($direction === 'ASC' ? '<i class="bi bi-sort-alpha-down"></i>' : '<i class="bi bi-sort-alpha-up-alt"></i>');
    return '<a href="#" data-sort="' . e($col) . '" class="text-decoration-none text-reset">' . e($label) . ' ' . $icon . '</a>';
};
$priorityBadge = fn (string $p) => match ($p) {
    'high' => 'bg-danger', 'low' => 'bg-secondary', default => 'bg-warning text-dark',
};
$statusBadge = fn (string $s) => match ($s) {
    'published' => 'bg-success-subtle text-success', 'archived' => 'bg-secondary-subtle text-secondary',
    default => 'bg-info-subtle text-info',
};
?>
<?php if (empty($result['data'])): ?>
<p class="text-muted text-center py-4 mb-0">No notices found.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle table-sm">
        <thead>
            <tr>
                <th><?= $sortLink('title', 'Title') ?></th>
                <th><?= $sortLink('category', 'Category') ?></th>
                <th><?= $sortLink('priority', 'Priority') ?></th>
                <th>Posted By</th>
                <th><?= $sortLink('publish_date', 'Publish Date') ?></th>
                <th>Expiry Date</th>
                <th><?= $sortLink('status', 'Status') ?></th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['data'] as $n): ?>
            <tr>
                <td class="fw-semibold">
                    <a href="<?= e(url('notices/' . $n['id'])) ?>" class="text-decoration-none"><?= e($n['title']) ?></a>
                    <div class="small text-muted"><?= e(\App\Models\Notice::audienceLabel($n['audience'])) ?><?= $n['class_name'] ? ' — ' . e($n['class_name']) : '' ?><?= $n['section_name'] ? ' ' . e($n['section_name']) : '' ?><?= $n['student_name'] ? ' (' . e($n['student_name']) . ')' : '' ?></div>
                </td>
                <td><?= e($n['category']) ?></td>
                <td><span class="badge <?= $priorityBadge($n['priority']) ?>"><?= e(ucfirst($n['priority'])) ?></span></td>
                <td><?= e($n['posted_by_name'] ?? '—') ?></td>
                <td><?= e(format_date($n['publish_date'])) ?></td>
                <td><?= $n['expiry_date'] ? e(format_date($n['expiry_date'])) : '—' ?></td>
                <td><span class="badge <?= $statusBadge($n['status']) ?>"><?= e(ucfirst($n['status'])) ?></span></td>
                <td class="text-end text-nowrap">
                    <a href="<?= e(url('notices/' . $n['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye-fill"></i></a>
                    <?php if ($canManage): ?>
                    <a href="<?= e(url('notices/' . $n['id'] . '/edit')) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                    <button type="submit" form="delete-notice-<?= e($n['id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                    <form id="delete-notice-<?= e($n['id']) ?>" method="POST" action="<?= e(url('notices/' . $n['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this notice? This action cannot be undone."><?= csrf_field() ?></form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$page = $result['page']; $lastPage = $result['last_page'];
if ($lastPage > 1):
    $start = max(1, $page - 2); $end = min($lastPage, $page + 2);
?>
<nav aria-label="Pagination">
    <ul class="pagination">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="#" data-page="<?= max(1, $page - 1) ?>">&laquo;</a></li>
        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>"><a class="page-link" href="#" data-page="<?= min($lastPage, $page + 1) ?>">&raquo;</a></li>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
