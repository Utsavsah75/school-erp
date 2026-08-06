<?php $isEdit = !empty($author); $errors = form_errors(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= $isEdit ? 'Edit Author' : 'Add Author' ?></h5>
    <a href="<?= e(url('library/authors')) ?>" class="btn btn-outline-secondary btn-sm">Back to Authors</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $isEdit ? e(url('library/authors/' . $author['id'])) : e(url('library/authors')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3 text-center">
                    <?php if (!empty($author['photo'])): ?>
                        <img src="<?= e(url($author['photo'])) ?>" alt="" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:100px;height:100px;"><i class="bi bi-person fs-1 text-muted"></i></div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control form-control-sm <?= field_error($errors, 'photo') ? 'is-invalid' : '' ?>" accept=".jpg,.jpeg,.png,.webp">
                    <div class="invalid-feedback"><?= e(field_error($errors, 'photo')) ?></div>
                </div>
                <div class="col-md-9">
                    <div class="mb-3">
                        <label class="form-label">Author Name *</label>
                        <input type="text" name="name" class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>" required
                               value="<?= e(old('name', $author['name'] ?? '')) ?>">
                        <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(t('th_country')) ?></label>
                        <input type="text" name="country" class="form-control" value="<?= e(old('country', $author['country'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biography</label>
                        <textarea name="bio" class="form-control" rows="4"><?= e(old('bio', $author['bio'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Submit' ?></button>
        </form>
    </div>
</div>
