<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Book Reservations</h5>
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
                            <th>Book</th>
                            <th>Borrower</th>
                            <th>Reserved Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $row): ?>
                        <tr>
                            <td><?= e($row['book_title']) ?></td>
                            <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?></td>
                            <td><?= e($row['reserved_date']) ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= e(url('library/reservations/' . $row['id'] . '/cancel')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
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
