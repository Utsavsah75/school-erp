<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_book_categories')) ?></h5>
    <a href="<?= e(url('library/categories/create')) ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
</div>

<div class="card">
    <div class="card-header bg-light">Categories Listing</div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto ms-auto">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= e(t('search')) ?></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= e(t('th_category')) ?></th>
                        <th><?= e(t('th_description')) ?></th>
                        <th><?= e(t('th_status')) ?></th>
                        <th><?= e(t('th_created')) ?></th>
                        <th><?= e(t('th_books')) ?></th>
                        <th><?= e(t('th_action')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['data'])): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($result['data'] as $i => $cat): ?>
                        <tr>
                            <td><?= ($result['per_page'] * ($result['page'] - 1)) + $i + 1 ?></td>
                            <td class="fw-semibold"><?= e($cat['name']) ?></td>
                            <td class="text-muted small"><?= e($cat['description'] ?? '') ?></td>
                            <td>
                                <form method="POST" action="<?= e(url('library/categories/' . $cat['id'] . '/toggle-status')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= $cat['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="small text-muted"><?= e(date('Y-m-d', strtotime($cat['created_at']))) ?></td>
                            <td><span class="badge text-bg-light border"><?= (int) $cat['book_count'] ?></span></td>
                            <td>
                                <a href="<?= e(url('library/categories/' . $cat['id'] . '/edit')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="POST" action="<?= e(url('library/categories/' . $cat['id'] . '/delete')) ?>" class="d-inline confirm-delete" data-confirm-message="Delete this category? This action cannot be undone.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $extraParams = ['search' => $search]; $baseUrl = url('library/categories'); require __DIR__ . '/../_pagination.php'; ?>
    </div>
</div>
