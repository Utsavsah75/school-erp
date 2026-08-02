<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">All Teachers</h5>
    <a href="<?= e(url('teachers/create')) ?>" class="btn btn-primary"><i class="bi bi-person-plus-fill me-1"></i>Add
        Teacher</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('teachers')) ?>" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, employee no, phone, email..."
                    value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (TEACHER_STATUSES as $sv => $sl): ?>
                    <option value="<?= e($sv) ?>" <?= $status === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
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
                        <th>S.No.</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Employee No.</th>
                        <th>Gender</th>
                        <th>Subjects</th>
                        <th>Class / Section</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['data'] as $t): ?>
                    <tr>
                        <td>
                            <?php if (!empty($t['photo_path'])): ?>
                            <img src="<?= e(upload_url($t['photo_path'])) ?>" class="rounded-circle"
                                style="width:40px;height:40px;object-fit:cover;" alt="">
                            <?php else: ?>
                            <i class="bi bi-person-circle text-muted" style="font-size:32px;"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= e(url('teachers/' . $t['id'])) ?>"
                                class="fw-semibold text-decoration-none"><?= e($t['full_name']) ?></a>
                        </td>
                        <td><?= e($t['employee_number']) ?></td>
                        <td><?= e($t['gender'] ? ucfirst($t['gender']) : '—') ?></td>
                        <td class="small"><?= e($t['subjects_summary'] ?: '—') ?></td>
                        <td class="small"><?= e($t['classes_summary'] ?: '—') ?></td>
                        <td><?= e($t['phone'] ?? '—') ?></td>
                        <td><?= e($t['email'] ?? '—') ?></td>
                        <td><span
                                class="badge <?= status_badge_class($t['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $t['status']))) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(url('teachers/' . $t['id'])) ?>" class="btn btn-sm btn-outline-primary"
                                title="View"><i class="bi bi-eye-fill"></i></a>
                            <a href="<?= e(url('teachers/' . $t['id'] . '/edit')) ?>"
                                class="btn btn-sm btn-outline-secondary" title="Edit"><i
                                    class="bi bi-pencil-fill"></i></a>
                            <form method="POST" action="<?= e(url('teachers/' . $t['id'] . '/delete')) ?>"
                                class="d-inline confirm-delete" data-confirm-title="Remove this teacher?"
                                data-confirm-message="It can still be restored from the database if needed.">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i
                                        class="bi bi-trash-fill"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= paginate_links($result, url('teachers'), ['search' => $search, 'status' => $status]) ?>
        <?php endif; ?>
    </div>
</div>