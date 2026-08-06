<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Search Results</h5>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(url('search')) ?>" class="row g-2">
            <div class="col-md-8 col-lg-6">
                <input type="search" name="q" class="form-control" autocomplete="off"
                    placeholder="Search students, teachers, classes, books, payments..."
                    value="<?= e($term) ?>" autofocus>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<?php if ($term === ''): ?>
    <p class="text-muted text-center py-4">Type something above to search across everything you have access to.</p>
<?php elseif ($totalCount === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-search text-muted" style="font-size:2rem;"></i>
            <p class="text-muted mt-2 mb-0">No results found for "<strong><?= e($term) ?></strong>".</p>
        </div>
    </div>
<?php else: ?>
    <p class="text-muted mb-3"><?= (int) $totalCount ?> result<?= $totalCount === 1 ? '' : 's' ?> for "<strong><?= e($term) ?></strong>"</p>

    <?php foreach ($results as $category): ?>
    <div class="card mb-3">
        <div class="card-header bg-transparent fw-semibold">
            <i class="bi <?= e($category['icon']) ?> me-2"></i><?= e($category['label']) ?>
            <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($category['results']) ?></span>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($category['results'] as $item): ?>
            <a href="<?= e($item['url']) ?>" class="list-group-item list-group-item-action">
                <div class="fw-semibold"><?= e($item['title']) ?></div>
                <?php if (!empty($item['subtitle'])): ?>
                <div class="text-muted small"><?= e($item['subtitle']) ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
