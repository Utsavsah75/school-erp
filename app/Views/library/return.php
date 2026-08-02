<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Return Book</h5>
    <a href="<?= e(url('library/transactions')) ?>" class="btn btn-outline-secondary btn-sm">View Issued Books</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">Find the loan on the <a href="<?= e(url('library/transactions')) ?>">Issued Books</a> list and click <strong>Return</strong> next to it — fines (if the book is overdue) are calculated automatically at the configured per-day rate.</p>
    </div>
</div>
