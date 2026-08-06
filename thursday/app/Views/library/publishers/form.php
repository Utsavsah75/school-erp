<?php $isEdit = !empty($publisher); $errors = form_errors(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= $isEdit ? 'Edit Publisher' : 'Add Publisher' ?></h5>
    <a href="<?= e(url('library/publishers')) ?>" class="btn btn-outline-secondary btn-sm">Back to Publishers</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $isEdit ? e(url('library/publishers/' . $publisher['id'])) : e(url('library/publishers')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3 text-center">
                    <?php if (!empty($publisher['logo'])): ?>
                        <img src="<?= e(url($publisher['logo'])) ?>" alt="" class="mb-2" style="width:100px;height:100px;object-fit:contain;">
                    <?php else: ?>
                        <div class="bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:100px;height:100px;"><i class="bi bi-building fs-1 text-muted"></i></div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control form-control-sm <?= field_error($errors, 'logo') ? 'is-invalid' : '' ?>" accept=".jpg,.jpeg,.png,.webp">
                    <div class="invalid-feedback"><?= e(field_error($errors, 'logo')) ?></div>
                </div>
                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Publisher Name *</label>
                            <input type="text" name="name" class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>" required
                                   value="<?= e(old('name', $publisher['name'] ?? '')) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(t('th_email')) ?></label>
                            <input type="email" name="email" class="form-control" value="<?= e(old('email', $publisher['email'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= e(t('th_phone')) ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= e(old('phone', $publisher['phone'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" class="form-control" placeholder="https://" value="<?= e(old('website', $publisher['website'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(t('th_address')) ?></label>
                            <textarea name="address" class="form-control" rows="2"><?= e(old('address', $publisher['address'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><?= $isEdit ? 'Update' : 'Submit' ?></button>
        </form>
    </div>
</div>
