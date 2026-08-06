<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_add_teacher')) ?></h5>
    <a href="<?= e(url('teachers')) ?>" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <form id="teacherForm" data-wizard method="POST" action="<?= e(url('teachers')) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?php include __DIR__ . '/_form.php'; ?>
        </form>
    </div>
</div>
