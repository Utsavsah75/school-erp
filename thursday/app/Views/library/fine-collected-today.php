<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-1"></i>Fine Collected Today</h5>
    <a href="<?= e(url('library')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($payments)): ?>
        <p class="text-muted text-center py-4 mb-0">No fines have been collected today.</p>
        <?php else: ?>
        <div class="d-flex justify-content-end mb-2">
            <span class="fw-bold text-success">
                Total: <?= e(format_currency(array_sum(array_column($payments, 'amount')))) ?>
                <span class="text-muted fw-normal">(<?= count($payments) ?> payment<?= count($payments) === 1 ? '' : 's' ?>)</span>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= e(t('th_sn2')) ?></th>
                        <th>Receipt #</th>
                        <th>Student / Teacher</th>
                        <th><?= e(t('th_book')) ?></th>
                        <th>Collected For</th>
                        <th><?= e(t('th_amount')) ?></th>
                        <th><?= e(t('th_mode')) ?></th>
                        <th><?= e(t('th_paid_at')) ?></th>
                        <th><?= e(t('th_collected_by')) ?></th>
                        <th class="text-end"><?= e(t('th_receipt')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $i => $row): ?>
                    <tr>
                        <td><?= (int) ($i + 1) ?></td>
                        <td><code><?= e($row['receipt_number']) ?></code></td>
                        <td>
                            <?php if (!empty($row['student_name'])): ?>
                                <?= e($row['student_name']) ?>
                                <br><small class="text-muted">#<?= e($row['admission_number'] ?? '') ?></small>
                            <?php elseif (!empty($row['teacher_name'])): ?>
                                <?= e($row['teacher_name']) ?>
                                <br><small class="text-muted">#<?= e($row['employee_number'] ?? '') ?></small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($row['book_title']) ?>
                            <?php if (!empty($row['isbn'])): ?>
                                <br><small class="text-muted"><?= e($row['isbn']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $context = $row['notes'] ?: 'Fine payment';
                                $badgeClass = match ($context) {
                                    'Book Return'  => 'bg-success',
                                    'Book Renewal' => 'bg-info text-dark',
                                    default        => 'bg-secondary',
                                };
                            ?>
                            <span class="badge <?= e($badgeClass) ?>"><?= e($context) ?></span>
                        </td>
                        <td class="fw-bold"><?= e(format_currency($row['amount'])) ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', (string) $row['payment_mode']))) ?></td>
                        <td><small><?= e(format_datetime($row['paid_at'])) ?></small></td>
                        <td><?= e($row['received_by_name'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('library/fine-receipt/' . $row['receipt_number'])) ?>" target="_blank" class="btn btn-sm btn-light" title="View Receipt">
                                <i class="bi bi-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
