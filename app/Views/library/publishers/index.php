<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Publishers</h5>
    <a href="<?= e(url('library/publishers/create')) ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Publisher</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto ms-auto">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>S.N.</th>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Website</th>
                        <th>Books</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['data'])): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No publishers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($result['data'] as $i => $pub): ?>
                        <tr>
                            <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                            <td>
                                <?php if (!empty($pub['logo'])): ?>
                                    <img src="<?= e(url($pub['logo'])) ?>" alt="" style="width:36px;height:36px;object-fit:contain;">
                                <?php else: ?>
                                    <i class="bi bi-building text-muted fs-5"></i>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= e($pub['name']) ?></td>
                            <td class="small text-muted"><?= e($pub['email'] ?? '') ?></td>
                            <td class="small text-muted"><?= e($pub['phone'] ?? '') ?></td>
                            <td class="small"><?php if (!empty($pub['website'])): ?><a href="<?= e($pub['website']) ?>" target="_blank" rel="noopener"><?= e($pub['website']) ?></a><?php endif; ?></td>
                            <td><span class="badge text-bg-light border"><?= (int) $pub['book_count'] ?></span></td>
                            <td>
                                <a href="<?= e(url('library/publishers/' . $pub['id'] . '/edit')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="POST" action="<?= e(url('library/publishers/' . $pub['id'] . '/delete')) ?>" class="d-inline confirm-delete" data-confirm-message="Delete this publisher? This action cannot be undone.">
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

        <?php $extraParams = ['search' => $search]; $baseUrl = url('library/publishers'); require __DIR__ . '/../_pagination.php'; ?>
    </div>
</div>
