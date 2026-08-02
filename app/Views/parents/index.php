<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Parents</h5>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('parents')) ?>" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Father/mother/guardian name, phone, email..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No parent records found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Children</th>
                        <th>Portal Access</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $start = ((int) ($result['page'] ?? 1) - 1) * ((int) ($result['per_page'] ?? count($result['data']))); ?>
                    <?php foreach ($result['data'] as $i => $s): ?>
                    <tr>
                        <td><?= e($start + $i + 1) ?></td>


                        <?php foreach ($result['data'] as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('parents/' . $p['id'])) ?>" class="fw-semibold text-decoration-none">
                                <?= e($p['father_name'] ?? $p['guardian_name'] ?? $p['mother_name'] ?? 'Guardian') ?>
                            </a>
                            <?php if (!empty($p['mother_name']) && !empty($p['father_name'])): ?>
                            <div class="small text-muted">& <?= e($p['mother_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(($p['phone_country_code'] ?? '') . ' ' . $p['phone']) ?></td>
                        <td><?= e($p['email'] ?? '—') ?></td>
                        <td><span class="badge bg-secondary"><?= (int) $p['children_count'] ?></span></td>
                        <td>
                            <?php if (!empty($p['login_user_id'])): ?>
                            <?php if ((int) $p['login_is_active'] === 1): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Inactive</span>
                            <?php endif; ?>
                            <?php if (empty($p['login_verified_at'])): ?>
                            <span class="badge bg-secondary">Unverified</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="badge bg-light text-dark border">No login</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(url('parents/' . $p['id'])) ?>" class="btn btn-sm btn-outline-primary"
                                title="View"><i class="bi bi-eye-fill"></i></a>
                            <a href="<?= e(url('parents/' . $p['id'] . '/edit')) ?>"
                                class="btn btn-sm btn-outline-secondary" title="Edit"><i
                                    class="bi bi-pencil-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= paginate_links($result, url('parents'), ['search' => $search]) ?>
        <?php endif; ?>
    </div>
</div>