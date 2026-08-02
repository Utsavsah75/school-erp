<?php $isEdit = !empty($category); $errors = form_errors(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= $isEdit ? 'Edit Category' : 'Add Category' ?></h5>
    <a href="<?= e(url('library/categories')) ?>" class="btn btn-outline-secondary btn-sm">Back to Categories</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $isEdit ? e(url('library/categories/' . $category['id'])) : e(url('library/categories')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>" required
                       value="<?= e(old('name', $category['name'] ?? '')) ?>">
                <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e(old('description', $category['description'] ?? '')) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Submit' ?></button>
        </form>
    </div>
</div>
