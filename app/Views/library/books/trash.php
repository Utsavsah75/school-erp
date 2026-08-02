<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Deleted Books</h5>
    <a href="<?= e(url('library/books')) ?>" class="btn btn-outline-secondary btn-sm">Back to Catalog</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
            <p class="text-muted text-center py-4 mb-0">Trash is empty.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Book Name</th>
                            <th>ISBN</th>
                            <th>Author</th>
                            <th>Deleted</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $row): ?>
                        <tr>
                            <td><?= e($row['title']) ?></td>
                            <td><code><?= e($row['isbn'] ?? '—') ?></code></td>
                            <td><?= e($row['author_name'] ?? '—') ?></td>
                            <td class="small text-muted"><?= e($row['deleted_at']) ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= e(url('library/books/' . $row['id'] . '/restore')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php $extraParams = []; $baseUrl = url('library/books/trash'); require __DIR__ . '/../_pagination.php'; ?>
    </div>
</div>
