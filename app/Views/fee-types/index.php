<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Fee Types</h5>
</div>

<div class="row g-3">
    <!-- ==================== Create New Fee Type ==================== -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Create A New Fee Type</h6>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('fee-types')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Fee Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                            class="form-control <?= field_error($errors, 'name') ? 'is-invalid' : '' ?>"
                            value="<?= e(old('name')) ?>" placeholder="e.g. Term 1 Tuition Fee" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'name')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category"
                            class="form-select <?= field_error($errors, 'category') ? 'is-invalid' : '' ?>" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cv => $cl): ?>
                            <option value="<?= e($cv) ?>" <?= old('category') === $cv ? 'selected' : '' ?>><?= e($cl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'category')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recurrence <span class="text-danger">*</span></label>
                        <select name="recurrence_type"
                            class="form-select <?= field_error($errors, 'recurrence_type') ? 'is-invalid' : '' ?>" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($recurrences as $rv => $rl): ?>
                            <option value="<?= e($rv) ?>" <?= old('recurrence_type') === $rv ? 'selected' : '' ?>><?= e($rl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'recurrence_type')) ?></div>
                        <div class="form-text">Monthly = billed once per calendar month (Tuition, Hostel, Transport). One-Time = billed once per session (Admission, Annual).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Amount (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="default_amount" class="form-control"
                            value="<?= e(old('default_amount', '0')) ?>">
                        <div class="form-text">Used to prefill Bulk Fee Assignment when no class-specific fee structure exists.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Submit</button>
                    <a href="<?= e(url('fee-types')) ?>" class="btn btn-light">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== All Fee Types ==================== -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">All Fee Types</h6>

                <form method="GET" action="<?= e(url('fee-types')) ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cv => $cl): ?>
                            <option value="<?= e($cv) ?>" <?= $categoryFilter === $cv ? 'selected' : '' ?>><?= e($cl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>

                <?php if (empty($result['data'])): ?>
                <p class="text-muted text-center py-4 mb-0">No fee types found. Add your first one on the left.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Recurrence</th>
                                <th class="text-end">Default Amount</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['data'] as $ft): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($ft['name']) ?></td>
                                <td><?= e($categories[$ft['category']] ?? ucfirst($ft['category'])) ?></td>
                                <td><?= e($recurrences[$ft['recurrence_type']] ?? ucfirst($ft['recurrence_type'])) ?></td>
                                <td class="text-end"><?= e(format_currency($ft['default_amount'])) ?></td>
                                <td>
                                    <?php if (!empty($ft['is_active'])): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"
                                        data-bs-toggle="collapse" data-bs-target="#editFeeType<?= e($ft['id']) ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button type="submit" form="delete-feetype-<?= e($ft['id']) ?>"
                                        class="btn btn-sm btn-outline-danger" title="Delete"
                                        ><i class="bi bi-trash-fill"></i></button>
                                    <form id="delete-feetype-<?= e($ft['id']) ?>" method="POST"
                                        action="<?= e(url('fee-types/' . $ft['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this fee type? This action cannot be undone.">
                                        <?= csrf_field() ?></form>
                                </td>
                            </tr>
                            <tr class="collapse" id="editFeeType<?= e($ft['id']) ?>">
                                <td colspan="6" class="bg-light">
                                    <form method="POST" action="<?= e(url('fee-types/' . $ft['id'])) ?>" class="row g-2 align-items-end py-2">
                                        <?= csrf_field() ?>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-1">Name</label>
                                            <input type="text" name="name" class="form-control form-control-sm" value="<?= e($ft['name']) ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Category</label>
                                            <select name="category" class="form-select form-select-sm">
                                                <?php foreach ($categories as $cv => $cl): ?>
                                                <option value="<?= e($cv) ?>" <?= $ft['category'] === $cv ? 'selected' : '' ?>><?= e($cl) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Recurrence</label>
                                            <select name="recurrence_type" class="form-select form-select-sm">
                                                <?php foreach ($recurrences as $rv => $rl): ?>
                                                <option value="<?= e($rv) ?>" <?= $ft['recurrence_type'] === $rv ? 'selected' : '' ?>><?= e($rl) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Default Amount</label>
                                            <input type="number" step="0.01" min="0" name="default_amount" class="form-control form-control-sm" value="<?= e($ft['default_amount']) ?>">
                                        </div>
                                        <div class="col-md-1 form-check">
                                            <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= !empty($ft['is_active']) ? 'checked' : '' ?>>
                                            <label class="form-check-label small">Active</label>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= paginate_links($result, url('fee-types'), ['search' => $search, 'category' => $categoryFilter]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
