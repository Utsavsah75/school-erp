<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Book Details</h5>
    <a href="<?= e(url('library/books')) ?>" class="btn btn-outline-secondary btn-sm">Back to Catalog</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-2 text-center">
                <?php if (!empty($book['cover_image'])): ?>
                    <img src="<?= e(url($book['cover_image'])) ?>" alt="" class="border rounded" style="width:100%;max-width:150px;aspect-ratio:3/4;object-fit:cover;">
                <?php else: ?>
                    <div class="bg-light border rounded d-flex align-items-center justify-content-center mx-auto" style="width:120px;height:160px;"><i class="bi bi-book fs-1 text-muted"></i></div>
                <?php endif; ?>
            </div>
            <div class="col-md-10">
                <h4 class="fw-bold"><?= e($book['title']) ?></h4>
                <div class="row small">
                    <div class="col-md-6">
                        <p><strong>ISBN:</strong> <?= e($book['isbn'] ?? '—') ?></p>
                        <p><strong>Accession No.:</strong> <?= e($book['accession_number']) ?></p>
                        <p><strong>Category:</strong> <?= e($book['category_name'] ?? '—') ?></p>
                        <p><strong>Author:</strong> <?= e($book['author_name'] ?? '—') ?></p>
                        <p><strong>Publisher:</strong> <?= e($book['publisher_name'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Edition:</strong> <?= e($book['edition'] ?? '—') ?></p>
                        <p><strong>Language:</strong> <?= e($book['language'] ?? '—') ?></p>
                        <p><strong>Rack / Shelf:</strong> <?= e(($book['rack_number'] ?? '—') . ' / ' . ($book['shelf_number'] ?? '—')) ?></p>
                        <p class="mb-1"><strong>Copies:</strong></p>
                        <p class="mb-0">
                            <span class="badge text-bg-light border me-1">Total: <?= (int) $copyCounts['total'] ?></span>
                            <span class="badge text-bg-success me-1">Available: <?= (int) $copyCounts['available'] ?></span>
                            <span class="badge text-bg-primary me-1">Issued: <?= (int) $copyCounts['issued'] ?></span>
                            <span class="badge text-bg-warning text-dark me-1">Reserved: <?= (int) $copyCounts['reserved'] ?></span>
                            <span class="badge text-bg-danger me-1">Lost: <?= (int) $copyCounts['lost'] ?></span>
                            <span class="badge text-bg-secondary">Damaged: <?= (int) $copyCounts['damaged'] ?></span>
                        </p>
                        <p><strong>Status:</strong> <span class="badge <?= $book['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($book['status'])) ?></span></p>
                    </div>
                </div>
                <?php if (!empty($book['description'])): ?>
                    <p class="text-muted"><?= nl2br(e($book['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-light">Copies (<?= count($copies) ?>)</div>
    <div class="card-body">
        <?php if (empty($copies)): ?>
            <p class="text-muted text-center py-3 mb-0">No physical copies recorded for this title yet.</p>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
                <?php
                $statusBadge = [
                    'available' => 'text-bg-success', 'issued' => 'text-bg-primary',
                    'reserved' => 'text-bg-warning text-dark', 'lost' => 'text-bg-danger',
                    'damaged' => 'text-bg-secondary', 'maintenance' => 'text-bg-dark',
                ];
                ?>
                <?php foreach ($copies as $c): ?>
                    <span class="badge <?= $statusBadge[$c['status']] ?? 'text-bg-light border' ?> fw-normal px-2 py-2"
                        title="<?= e(ucfirst($c['status'])) ?><?= $c['remarks'] ? ' — ' . e($c['remarks']) : '' ?>">
                        <?= e($c['copy_code']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light">Loan History</div>
    <div class="card-body">
        <?php if (empty($history)): ?>
            <p class="text-muted text-center py-3 mb-0">This book has never been issued.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Issued</th>
                            <th>Due</th>
                            <th>Returned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= e($h['student_name'] ?? $h['teacher_name'] ?? '—') ?></td>
                            <td><?= e($h['issue_date']) ?></td>
                            <td><?= e($h['due_date']) ?></td>
                            <td><?= e($h['return_date'] ?? 'Not Returned Yet') ?></td>
                            <td><span class="badge text-bg-light border"><?= e(ucfirst($h['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
