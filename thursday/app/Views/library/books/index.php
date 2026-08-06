<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_manage_books')) ?></h5>
    <div>
        <a href="<?= e(url('library/books/trash')) ?>" class="btn btn-outline-secondary btn-sm me-1"><i class="bi bi-trash me-1"></i>Trash</a>
        <a href="<?= e(url('library/books/create')) ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Book</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('library/books')) ?>" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1"><?= e(t('search')) ?></label>
                <input type="text" name="search" class="form-control" placeholder="Title, ISBN, accession no, author..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_category')) ?></label>
                <select name="category" id="categoryFilterSelect" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) $category === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Availability</label>
                <select name="availability" class="form-select">
                    <option value="">Any</option>
                    <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= $availability === 'unavailable' ? 'selected' : '' ?>>None available</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light">Categories Listing</div>
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
            <p class="text-muted text-center py-4 mb-0">No books found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Book Name</th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th><?= e(t('th_category')) ?></th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Available / Total</th>
                            <th><?= e(t('th_status')) ?></th>
                            <th class="text-end"><?= e(t('th_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $i => $row): ?>
                        <tr>
                            <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                            <td>
                                <?php if (!empty($row['cover_image'])): ?>
                                    <img src="<?= e(url($row['cover_image'])) ?>" alt="" style="width:32px;height:44px;object-fit:cover;">
                                <?php else: ?>
                                    <i class="bi bi-book text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><a href="<?= e(url('library/books/' . $row['id'])) ?>"><?= e($row['title']) ?></a></td>
                            <td><code><?= e($row['isbn'] ?? '—') ?></code></td>
                            <td><?= e($row['category_name'] ?? '—') ?></td>
                            <td><?= e($row['author_name'] ?? '—') ?></td>
                            <td><?= e($row['publisher_name'] ?? '—') ?></td>
                            <td>
                                <span class="badge bg-<?= (int) $row['available_copies'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= (int) $row['available_copies'] ?> / <?= (int) $row['total_copies'] ?>
                                </span>
                            </td>
                            <td><span class="badge <?= $row['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(st($row['status'])) ?></span></td>
                            <td class="text-end">
                                <a href="<?= e(url('library/books/' . $row['id'] . '/edit')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                <?php if ((int) $row['available_copies'] > 0): ?>
                                <form method="POST" action="<?= e(url('library/books/' . $row['id'] . '/damaged')) ?>" class="d-inline" data-confirm data-confirm-icon="warning" data-confirm-title="Mark Copy as Damaged?" data-confirm-message="One on-shelf copy of this book will be marked damaged and removed from circulation." data-confirm-button="Yes, mark damaged" data-confirm-variant="danger">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Mark a copy damaged"><i class="bi bi-exclamation-triangle"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= e(url('library/books/' . $row['id'] . '/delete')) ?>" class="d-inline confirm-delete" data-confirm-title="Remove this title?" data-confirm-message="It will be removed from the catalog, but can be restored from Trash.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php
        $extraParams = ['search' => $search, 'category' => $category, 'availability' => $availability];
        $baseUrl = url('library/books');
        require __DIR__ . '/../_pagination.php';
        ?>
    </div>
</div>

<link href="<?= e(asset('css/searchable-select.css')) ?>" rel="stylesheet">
<script src="<?= e(asset('js/searchable-select.js')) ?>"></script>
<script>
if (window.SearchableSelect) {
    SearchableSelect.enhance('#categoryFilterSelect', { placeholder: 'All Categories', searchPlaceholder: 'Search categories…' });
}
</script>
