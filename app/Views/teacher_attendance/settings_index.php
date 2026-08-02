<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Teacher Attendance Settings</h5>
</div>

<p class="text-muted">
    Configure biometric ID, device, shift and check-in/out rules for each teacher. This used to be
    step 7 of the teacher registration wizard — it now lives here so it can be set up (and revisited)
    independently of adding/editing the teacher's core record.
</p>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('teacher-attendance')) ?>" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, employee no, phone, email..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
            <p class="text-muted text-center py-4 mb-0">No teachers found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Employee No.</th>
                            <th>Designation</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $t): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($t['photo_path'])): ?>
                                        <img src="<?= e(upload_url($t['photo_path'])) ?>" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle text-muted" style="font-size:28px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($t['full_name']) ?></td>
                                <td><?= e($t['employee_number']) ?></td>
                                <td class="small"><?= e($t['designation'] ?? '—') ?></td>
                                <td><span class="badge <?= status_badge_class($t['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $t['status']))) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= e(url('teacher-attendance/' . $t['id'] . '/settings')) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-sliders me-1"></i>Configure
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= paginate_links($result, url('teacher-attendance'), ['search' => $search]) ?>
        <?php endif; ?>
    </div>
</div>
