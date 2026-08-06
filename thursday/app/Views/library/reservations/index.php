<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_book_reservations')) ?></h5>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($reservations)): ?>
            <p class="text-muted text-center py-4 mb-0">No pending reservations.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn')) ?></th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_borrower')) ?></th>
                            <th>Reserved Date</th>
                            <th class="text-end"><?= e(t('th_actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($row['book_title']) ?></td>
                            <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?></td>
                            <td><?= e($row['reserved_date']) ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= e(url('library/reservations/' . $row['id'] . '/cancel')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= e(t('cancel')) ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
