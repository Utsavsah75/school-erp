<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Authors</h5>
    <a href="<?= e(url('library/authors/create')) ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Author</a>
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
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Books</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['data'])): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No authors found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($result['data'] as $i => $author): ?>
                        <tr>
                            <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                            <td>
                                <?php if (!empty($author['photo'])): ?>
                                    <img src="<?= e(url($author['photo'])) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%">
                                <?php else: ?>
                                    <span class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width:40px;height:40px;"><i class="bi bi-person"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= e($author['name']) ?></td>
                            <td class="text-muted small"><?= e($author['country'] ?? '') ?></td>
                            <td><span class="badge <?= $author['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $author['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td><span class="badge text-bg-light border"><?= (int) $author['book_count'] ?></span></td>
                            <td>
                                <a href="<?= e(url('library/authors/' . $author['id'] . '/edit')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="POST" action="<?= e(url('library/authors/' . $author['id'] . '/delete')) ?>" class="d-inline confirm-delete" data-confirm-message="Delete this author? This action cannot be undone.">
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

        <?php $extraParams = ['search' => $search]; $baseUrl = url('library/authors'); require __DIR__ . '/../_pagination.php'; ?>
    </div>
</div>
