<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <div>
        <h5 class="fw-bold mb-0"><?= e($pageTitle) ?></h5>
        <p class="text-muted small mb-0"><?= e($description) ?></p>
    </div>
    <a href="<?= e(url('library')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
    </a>
</div>

<div class="card my-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('library/history/' . $historyType)) ?>" class="row g-2 align-items-end">
            <?php if ($mode !== 'activity'): ?>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1"><?= e(t('search')) ?></label>
                <input type="text" name="search" class="form-control"
                    placeholder="Book title, borrower name, admission/employee no..." value="<?= e($search) ?>">
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_from_date')) ?></label>
                <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1"><?= e(t('th_to_date')) ?></label>
                <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary w-100" title="Apply Filter"><i
                        class="bi bi-search"></i></button>
                <?php if ($fromDate !== '' || $toDate !== '' || $search !== ''): ?>
                <a href="<?= e(url('library/history/' . $historyType)) ?>" class="btn btn-outline-secondary"
                    title="Clear filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">
                <?php if ($mode === 'fine'): ?>
                Total collected in this range:
                <strong class="text-success"><?= e(format_currency(array_sum(array_column($rows, 'amount')))) ?></strong>
                <?php else: ?>
                <?= (int) $result['total'] ?> record<?= (int) $result['total'] === 1 ? '' : 's' ?> found
                <?php endif; ?>
            </span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="<?= e(url('library/export/' . $historyType) . '?format=csv') ?>"
                    class="btn btn-outline-secondary" title="Export to Excel"><i
                        class="bi bi-file-earmark-excel"></i></a>
                <a href="<?= e(url('library/export/' . $historyType) . '?format=pdf') ?>"
                    class="btn btn-outline-secondary" title="Export to PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()" title="Print"><i
                        class="bi bi-printer"></i></button>
            </div>
        </div>

        <?php if (empty($rows)): ?>
        <p class="text-muted text-center py-4 mb-0">No records found for this range.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <?php if ($mode === 'activity'): ?>
                <thead>
                    <tr>
                        <th><?= e(t('th_sn')) ?></th>
                        <th>Date &amp; Time</th>
                        <th>Event</th>
                        <th><?= e(t('th_book')) ?></th>
                        <th><?= e(t('th_isbn')) ?></th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                        <td class="text-nowrap"><?= e(format_datetime($row['activity_at'])) ?>
                            <div class="text-muted"><small><?= e(time_ago($row['activity_at'])) ?></small></div>
                        </td>
                        <td><span class="badge <?= e($row['badge_class']) ?>"><i
                                    class="bi <?= e($row['icon']) ?> me-1"></i><?= e($row['label']) ?></span></td>
                        <td>
                            <?php if (!empty($row['book_title'])): ?>
                            <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                style="width:26px;height:36px;object-fit:cover;" class="me-1 rounded-1">
                            <?= e($row['book_title']) ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= e($row['isbn'] ?? '—') ?></small></td>
                        <td><?= e($row['display_user']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <?php elseif ($mode === 'fine'): ?>
                <thead>
                    <tr>
                        <th><?= e(t('th_sn')) ?></th>
                        <th><?= e(t('th_receipt_no2')) ?></th>
                        <th><?= e(t('th_paid_at')) ?></th>
                        <th><?= e(t('th_book')) ?></th>
                        <th><?= e(t('th_borrower')) ?></th>
                        <th><?= e(t('th_amount')) ?></th>
                        <th><?= e(t('th_mode')) ?></th>
                        <th>Received By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                        <td><code><?= e($row['receipt_number']) ?></code></td>
                        <td class="text-nowrap"><?= e(format_datetime($row['paid_at'])) ?></td>
                        <td><?= e($row['book_title']) ?><br><small class="text-muted"><?= e($row['isbn'] ?? '') ?></small>
                        </td>
                        <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?>
                            <?php if (!empty($row['admission_number']) || !empty($row['employee_number'])): ?>
                            <div class="text-muted">
                                <small>#<?= e($row['admission_number'] ?? $row['employee_number']) ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold text-success"><?= e(format_currency($row['amount'])) ?></td>
                        <td><?= e(tr_const(PAYMENT_MODES, $row['payment_mode'])) ?></td>
                        <td><?= e($row['received_by_name'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('library/fine-receipt/' . $row['receipt_number'])) ?>" target="_blank"
                                class="btn btn-sm btn-outline-primary" title="View Receipt"><i
                                    class="bi bi-receipt"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <?php else: /* mode === 'issue' */ ?>
                <thead>
                    <tr>
                        <th><?= e(t('th_sn')) ?></th>
                        <th><?= e(t('th_book')) ?></th>
                        <th><?= e(t('th_isbn')) ?></th>
                        <th><?= e(t('th_borrower')) ?></th>
                        <th><?= e(t('th_issue_date')) ?></th>
                        <th><?= e(t('th_due_date')) ?></th>
                        <th><?= e(t('th_return_date')) ?></th>
                        <th><?= e(t('th_status')) ?></th>
                        <th><?= e(t('th_fine')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                    <?php
                        $isLateReturn = $row['status'] === 'returned' && !empty($row['return_date']) && $row['return_date'] > $row['due_date'];
                        $isCurrentlyOverdue = (int) $row['days_overdue'] > 0;
                        $displayStatus = match (true) {
                            $row['status'] === 'lost' => 'Lost',
                            $isCurrentlyOverdue => 'Overdue',
                            $isLateReturn => 'Returned late',
                            $row['status'] === 'returned' => 'Returned',
                            default => 'Issued',
                        };
                        $badgeStatus = match (true) {
                            $row['status'] === 'lost' => 'lost',
                            $isCurrentlyOverdue || $isLateReturn => 'overdue',
                            $row['status'] === 'returned' => 'returned',
                            default => 'issued',
                        };
                    ?>
                    <tr>
                        <td><?= (($result['page'] - 1) * $result['per_page']) + $i + 1 ?></td>
                        <td>
                            <?php if (!empty($row['cover_image'])): ?>
                            <img src="<?= e(upload_url($row['cover_image'])) ?>"
                                style="width:26px;height:36px;object-fit:cover;" class="me-1 rounded-1">
                            <?php endif; ?>
                            <?= e($row['book_title']) ?>
                        </td>
                        <td><small><?= e($row['isbn'] ?? '—') ?></small></td>
                        <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?>
                            <?php if (!empty($row['admission_number']) || !empty($row['employee_number'])): ?>
                            <div class="text-muted">
                                <small>#<?= e($row['admission_number'] ?? $row['employee_number']) ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['issue_date']) ?></td>
                        <td><?= e($row['due_date']) ?></td>
                        <td><?= e($row['return_date'] ?? '—') ?></td>
                        <td><span class="badge <?= status_badge_class($badgeStatus) ?>"><?= e($displayStatus) ?></span>
                            <?php if ($isCurrentlyOverdue): ?>
                            <div class="text-danger"><small><?= (int) $row['days_overdue'] ?> day(s) overdue</small>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((float) ($row['fine_amount'] ?? 0) > 0): ?>
                            <span class="text-danger fw-semibold"><?= e(format_currency($row['fine_amount'])) ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php
        $extraParams = ['search' => $search, 'from_date' => $fromDate, 'to_date' => $toDate];
        $baseUrl = url('library/history/' . $historyType);
        require __DIR__ . '/_pagination.php';
        ?>
    </div>
</div>
