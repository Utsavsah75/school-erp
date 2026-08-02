<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Outstanding Fees</h5>
    <a href="<?= e(url('fees/bulk-assign')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign Fees</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
            <p class="text-muted text-center py-4 mb-0">No outstanding fees — every student is fully paid up.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Student</th>
                            <th>Admission No.</th>
                            <th>Class / Section</th>
                            <th>Balance Due</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = ($result['page'] - 1) * $result['per_page'] + 1; ?>
                        <?php foreach ($result['data'] as $row): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td>
                                    <a href="<?= e(url('students/' . $row['student_id'])) ?>" class="text-decoration-none fw-semibold" title="Database ID: <?= e($row['student_id']) ?>"><?= e($row['full_name']) ?></a>
                                </td>
                                <td><?= e($row['admission_number']) ?></td>
                                <td><?= e(trim(($row['class_name'] ?? '-') . ' ' . ($row['section_name'] ?? ''))) ?></td>
                                <td class="text-danger fw-semibold"><?= e(format_currency($row['balance_due'])) ?></td>
                                <td class="text-end">
                                    <a href="<?= e(url('payments/collect') . '?student_id=' . $row['student_id']) ?>" class="btn btn-sm btn-outline-primary" title="Collect Payment"><i class="bi bi-cash-coin"></i> Collect</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Showing <?= count($result['data']) ?> of <?= (int) $result['total'] ?> students with dues</small>
                <?= paginate_links($result, url('fees/due'), []) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
