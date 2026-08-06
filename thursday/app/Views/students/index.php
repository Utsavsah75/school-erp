<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('th_students')) ?></h5>
    <a href="<?= e(url('students/create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Student</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('students')) ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1"><?= e(t('search')) ?></label>
                <input type="text" name="search" class="form-control" placeholder="Name, admission no, phone..."
                    value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_class')) ?></label>
                <select name="class_id" class="form-select">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (string) $classId === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_status')) ?></label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $sv => $sl): ?>
                    <option value="<?= e($sv) ?>" <?= $status === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No students found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= e(t('th_sn')) ?></th>
                        <th><?= e(t('th_photo')) ?></th>
                        <th><?= e(t('th_name')) ?></th>
                        <th><?= e(t('th_admission_no')) ?></th>
                        <th><?= e(t('th_class_section')) ?></th>
                        <th><?= e(t('th_phone')) ?></th>
                        <th><?= e(t('th_status')) ?></th>
                        <th class="text-end"><?= e(t('th_actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $start = ((int) ($result['page'] ?? 1) - 1) * ((int) ($result['per_page'] ?? count($result['data']))); ?>
                    <?php foreach ($result['data'] as $i => $s): ?>
                    <tr>
                        <td><?= e($start + $i + 1) ?></td>
                        <td>
                            <?php if (!empty($s['photo_path'])): ?>
                            <img src="<?= e(upload_url($s['photo_path'])) ?>" class="rounded-circle"
                                style="width:36px;height:36px;object-fit:cover;" alt="">
                            <?php else: ?>
                            <span class="avatar-circle"
                                style="width:36px;height:36px;"><?= e(strtoupper(substr($s['full_name'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= e(url('students/' . $s['id'])) ?>"
                                class="text-decoration-none fw-semibold"><?= e($s['full_name']) ?></a></td>
                        <td><?= e($s['admission_number']) ?></td>
                        <td><?= e($s['class_name']) ?> / <?= e($s['section_name']) ?></td>
                        <td><?= e($s['phone'] ?? '—') ?></td>
                        <td><span
                                class="badge <?= status_badge_class($s['status']) ?>"><?= e(st($s['status'])) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(url('students/' . $s['id'])) ?>" class="btn btn-sm btn-light" title="View"><i
                                    class="bi bi-eye"></i></a>
                            <a href="<?= e(url('students/' . $s['id'] . '/edit')) ?>" class="btn btn-sm btn-light"
                                title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="<?= e(url('students/' . $s['id'] . '/delete')) ?>"
                                class="d-inline confirm-delete"
                                data-confirm-message="Delete this student? This cannot be undone.">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i
                                        class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Showing <?= count($result['data']) ?> of <?= (int) $result['total'] ?>
                students</small>
            <?= paginate_links($result, url('students'), ['search' => $search, 'class_id' => $classId, 'status' => $status]) ?>
        </div>
        <?php endif; ?>
    </div>
</div>