<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-megaphone-fill me-1"></i> Add Notice</h5>
    <a href="<?= e(url('notices')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Notice Board</a>
</div>
<div class="card">
    <div class="card-body">
        <?php
        $formAction = url('notices');
        $notice = null;
        $submitLabel = 'Publish Notice';
        require __DIR__ . '/_form.php';
        ?>
    </div>
</div>
