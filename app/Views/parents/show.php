<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Parent / Guardian Profile</h5>
    <div>
        <a href="<?= e(url('parents/' . $parentRow['id'] . '/edit')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="<?= e(url('parents')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-hearts me-1"></i>Contact Details</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Father</td><td class="fw-semibold"><?= e($parentRow['father_name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Mother</td><td class="fw-semibold"><?= e($parentRow['mother_name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Guardian</td><td><?= e($parentRow['guardian_name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Relationship</td><td><?= e($parentRow['guardian_relationship'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Occupation</td><td><?= e($parentRow['occupation'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= e(($parentRow['phone_country_code'] ?? '') . ' ' . $parentRow['phone']) ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= e($parentRow['email'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Address</td><td><?= e($parentRow['address'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-1"></i>Parent Portal Access</h6>
                <?php if ($login): ?>
                    <p class="mb-2">
                        <span class="badge <?= (int) $login['is_active'] === 1 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= (int) $login['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                        <?php if (empty($login['email_verified_at'])): ?><span class="badge bg-secondary">Email unverified</span><?php endif; ?>
                    </p>
                    <p class="small text-muted mb-3">Login email: <?= e($login['email']) ?></p>
                    <?php if (\App\Core\Auth::role() === ROLE_SUPER_ADMIN): ?>
                    <form method="POST" action="<?= e(url('admin/users/' . $login['id'] . '/send-reset')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-envelope-fill me-1"></i>Send Password Reset Email</button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-3">This parent doesn't have a portal login yet. Granting access creates their account and emails them a link to set a password.</p>
                    <form method="POST" action="<?= e(url('parents/' . $parentRow['id'] . '/grant-portal-access')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-primary w-100" <?= empty($parentRow['email']) ? 'disabled' : '' ?>><i class="bi bi-person-plus-fill me-1"></i>Grant Portal Access</button>
                    </form>
                    <?php if (empty($parentRow['email'])): ?>
                        <p class="small text-danger mt-2 mb-0">Add an email address first (Edit) — it's required to send the account setup link.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-people-fill me-1"></i>Children (<?= count($children) ?>)</h6>
                <?php if (empty($children)): ?>
                    <p class="text-muted mb-0">No children linked to this parent record yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Photo</th><th>Name</th><th>Admission No.</th><th>Class / Section</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($children as $c): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($c['photo_path'])): ?>
                                                <img src="<?= e(upload_url($c['photo_path'])) ?>" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person-circle text-muted" style="font-size:28px;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($c['full_name']) ?></td>
                                        <td>#<?= e($c['admission_number']) ?></td>
                                        <td><?= e($c['class_name'] ?? '—') ?> / <?= e($c['section_name'] ?? '—') ?></td>
                                        <td class="text-end"><a href="<?= e(url('students/' . $c['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye-fill"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
