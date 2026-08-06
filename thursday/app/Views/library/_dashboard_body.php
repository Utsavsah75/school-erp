<?php if (!function_exists('lib_dash_export_buttons')): ?>
<?php function lib_dash_export_buttons(string $type): string {
    $base = e(url('library/export/' . $type));
    return '<div class="btn-group btn-group-sm ms-2" role="group">'
        . '<a href="' . $base . '?format=csv" class="btn btn-outline-secondary" title="Export to Excel"><i class="bi bi-file-earmark-excel"></i></a>'
        . '<a href="' . $base . '?format=pdf" class="btn btn-outline-secondary" title="Export to PDF"><i class="bi bi-file-earmark-pdf"></i></a>'
        . '<button type="button" class="btn btn-outline-secondary btn-print-table" title="Print"><i class="bi bi-printer"></i></button>'
        . '</div>';
} ?>
<?php endif; ?>

<div id="library-dashboard-body" data-generated-at="<?= e($generatedAt ?? '') ?>">

    <!-- ==================== Statistics Cards ==================== -->
    <div class="row g-3 mb-3">
        <?php
    $statCards = [
        ['icon' => 'bi-book-fill',            'color' => 'primary',   'value' => $counts['total_books'] ?? 0,        'label' => 'Total Books',        'url' => 'library/history/total-books'],
        ['icon' => 'bi-collection-fill',      'color' => 'info',      'value' => $counts['total_copies'] ?? 0,       'label' => 'Total Book Copies',   'url' => 'library/history/total-copies'],
        ['icon' => 'bi-check-circle-fill',    'color' => 'success',   'value' => $counts['total_available'] ?? 0,    'label' => 'Available Books',     'url' => 'library/history/available-books'],
        ['icon' => 'bi-arrow-left-right',     'color' => 'warning',   'value' => $counts['total_issued'] ?? 0,       'label' => 'Issued Books',        'url' => 'library/history/issued-books'],
        ['icon' => 'bi-box-arrow-in-left',    'color' => 'success',   'value' => $counts['returned_today'] ?? 0,     'label' => 'Returned Today',      'url' => 'library/history/returned-books'],
        ['icon' => 'bi-exclamation-triangle-fill', 'color' => 'danger', 'value' => $counts['total_overdue'] ?? 0,    'label' => 'Overdue Books',        'url' => 'library/history/overdue-books'],
        ['icon' => 'bi-cash-coin',            'color' => 'success',   'value' => format_currency($counts['fine_collected_today'] ?? 0), 'label' => 'Fine Collected Today', 'url' => 'library/history/fine-collected', 'is_currency' => true],
        ['icon' => 'bi-x-octagon-fill',       'color' => 'secondary', 'value' => $counts['total_lost'] ?? 0,         'label' => 'Lost Books',          'url' => 'library/history/lost-books'],
        ['icon' => 'bi-people-fill',          'color' => 'primary',   'value' => $counts['total_students'] ?? 0,     'label' => 'Total Students',      'url' => 'students'],
        ['icon' => 'bi-person-check-fill',    'color' => 'info',      'value' => $counts['active_members'] ?? 0,     'label' => 'Active Members',      'url' => 'library/transactions'],
        ['icon' => 'bi-tags-fill',            'color' => 'secondary', 'value' => $counts['total_categories'] ?? 0,   'label' => 'Categories',          'url' => 'library/categories'],
        ['icon' => 'bi-feather',              'color' => 'primary',   'value' => $counts['total_authors'] ?? 0,      'label' => 'Authors',             'url' => 'library/authors'],
        ['icon' => 'bi-building',             'color' => 'info',      'value' => $counts['total_publishers'] ?? 0,   'label' => 'Publishers',          'url' => 'library/publishers'],
        ['icon' => 'bi-layers-fill',          'color' => 'warning',   'value' => $counts['total_shelves'] ?? 0,      'label' => 'Shelves',             'url' => 'library/books'],
        ['icon' => 'bi-grid-3x3-gap-fill',    'color' => 'secondary', 'value' => $counts['total_racks'] ?? 0,        'label' => 'Racks',               'url' => 'library/books'],
    ];
    ?>
        <?php foreach ($statCards as $card): ?>
        <div class="col-lg-2 col-md-3 col-sm-4 col-6">
            <a href="<?= e(url($card['url'])) ?>" class="text-decoration-none">
                <div class="card text-center h-100 stat-card">
                    <div class="card-body py-3">
                        <i class="bi <?= e($card['icon']) ?> fs-3 text-<?= e($card['color']) ?>"></i>
                        <h4 class="mt-2 mb-0">
                            <?= !empty($card['is_currency']) ? e($card['value']) : (int) $card['value'] ?></h4>
                        <small class="text-muted"><?= e($card['label']) ?></small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ==================== Quick Actions ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-1"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php
            $quickActions = [
                ['label' => 'Add New Book', 'icon' => 'bi-plus-lg', 'url' => 'library/books/create'],
                ['label' => 'Issue Book', 'icon' => 'bi-box-arrow-right', 'url' => 'library/issue'],
                ['label' => 'Return Book', 'icon' => 'bi-box-arrow-in-left', 'url' => 'library/return'],
                ['label' => 'Add Student', 'icon' => 'bi-person-plus', 'url' => 'students/create'],
                ['label' => 'Manage Books', 'icon' => 'bi-book', 'url' => 'library/books'],
                ['label' => 'Manage Students', 'icon' => 'bi-people', 'url' => 'students'],
                ['label' => 'Book Categories', 'icon' => 'bi-tags', 'url' => 'library/categories'],
                ['label' => 'Authors', 'icon' => 'bi-feather', 'url' => 'library/authors'],
                ['label' => 'Publishers', 'icon' => 'bi-building', 'url' => 'library/publishers'],
                ['label' => 'Reports', 'icon' => 'bi-bar-chart', 'url' => 'library/transactions'],
            ];
            ?>
                <?php foreach ($quickActions as $qa): ?>
                <a href="<?= e(url($qa['url'])) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi <?= e($qa['icon']) ?> me-1"></i><?= e($qa['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- ==================== Recent Activities ==================== -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-activity me-1"></i>Recent Library Activities</h6>
                        <a href="<?= e(url('library/transactions')) ?>" class="btn btn-sm btn-link">View all</a>
                    </div>
                    <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                        <table class="table table-hover align-middle table-sm">
                            <thead>
                                <tr>
                                    <th><?= e(t('th_sn2')) ?></th>
                                    <th>Date &amp; Time</th>
                                    <th>Activity</th>
                                    <th><?= e(t('th_book')) ?></th>
                                    <th><?= e(t('th_isbn')) ?></th>
                                    <th><?= e(t('th_borrower')) ?></th>
                                    <th><?= e(t('th_status')) ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $i => $row): ?>
                                <tr>
                                    <td><?= (int) ($i + 1) ?></td>
                                    <td class="text-nowrap"><small><?= e(time_ago($row['activity_at'])) ?></small></td>
                                    <td><span class="badge <?= e($row['badge_class']) ?>"><i
                                                class="bi <?= e($row['icon']) ?> me-1"></i><?= e($row['label']) ?></span>
                                    </td>
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
                                    <td><?= e($row['borrower_name'] ?? '—') ?><?php if (!empty($row['admission_number'])): ?>
                                        <div class="text-muted"><small>#<?= e($row['admission_number']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span
                                            class="badge <?= status_badge_class((string) $row['status']) ?>"><?= e(st((string) $row['status'])) ?></span>
                                    </td>
                                    <td class="text-end"><a href="<?= e($row['view_url']) ?>"
                                            class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==================== Recent Notifications ==================== -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-bell-fill me-1"></i>Recent Notifications</h6>
                    <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
                    <?php else: ?>
                    <ul class="list-unstyled mb-0" style="max-height:420px;overflow-y:auto;">
                        <?php foreach ($recentActivity as $i => $row): ?>
                        <li class="d-flex align-items-start gap-2 py-2 border-bottom">
                            <span class="text-muted fw-semibold me-1"><?= (int) ($i + 1) ?>.</span>
                            <i class="bi <?= e($row['icon']) ?> mt-1"></i>
                            <div class="flex-grow-1">
                                <div><strong><?= e($row['display_user']) ?></strong> —
                                    <?= e($row['label']) ?><?php if (!empty($row['book_title'])): ?>:
                                    <em><?= e($row['book_title']) ?></em><?php endif; ?>
                                </div>
                                <div class="text-muted"><small><?= e($row['message']) ?></small></div>
                                <small class="text-muted"><?= e(format_datetime($row['activity_at'])) ?> ·
                                    <?= e(time_ago($row['activity_at'])) ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Recent Payments ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-cash-stack me-1"></i>Recent Payments</h6>
                <div><?= lib_dash_export_buttons('payments') ?></div>
            </div>
            <?php if (empty($recentPayments)): ?>
            <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-payments">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn2')) ?></th>
                            <th><?= e(t('th_student')) ?></th>
                            <th>Receipt #</th>
                            <th><?= e(t('th_amount')) ?></th>
                            <th><?= e(t('th_mode')) ?></th>
                            <th><?= e(t('th_type')) ?></th>
                            <th><?= e(t('th_paid_at')) ?></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $i => $row): ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td>
                                <?= e($row['student_display']) ?>
                                <?php if (!empty($row['student_id'])): ?>
                                <a href="<?= e(url('students/' . $row['student_id'])) ?>" class="text-muted ms-1"
                                    title="View full fee/payment history"><i class="bi bi-info-circle"></i></a>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['receipt_number'] ?? '—') ?></td>
                            <td><?= e(format_currency($row['paid_amount'])) ?></td>
                            <td><?= e(ucwords(str_replace('_', ' ', (string) $row['payment_mode']))) ?></td>
                            <td><span
                                    class="badge <?= $row['payment_kind'] === 'library_fine' ? 'bg-warning text-dark' : 'bg-primary' ?>"><?= $row['payment_kind'] === 'library_fine' ? 'Library Fine' : 'Fee Payment' ?></span>
                            </td>
                            <td><small><?= e(format_datetime($row['paid_at'])) ?></small></td>
                            <td class="text-end">
                                <?php if ($row['payment_kind'] === 'library_fine' && !empty($row['receipt_number'])): ?>
                                <a href="<?= e(url('library/fine-receipt/' . $row['receipt_number'])) ?>"
                                    target="_blank" class="btn btn-sm btn-light" title="View Receipt"><i
                                        class="bi bi-receipt"></i></a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (!empty($row['student_id'])): ?>
                                <a href="<?= e(url('students/' . $row['student_id'])) ?>"
                                    class="btn btn-sm btn-outline-primary">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== Recent Issued Books ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-arrow-right me-1"></i>Recent Issued Books</h6>
                <div><?= lib_dash_export_buttons('issued') ?><a href="<?= e(url('library/transactions')) ?>"
                        class="btn btn-sm btn-link">View all</a></div>
            </div>
            <?php if (empty($recentIssued)): ?>
            <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-issued">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn2')) ?></th>
                            <th><?= e(t('th_issue_date')) ?></th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th><?= e(t('th_student')) ?></th>
                            <th>Class/Sec</th>
                            <th><?= e(t('th_due_date')) ?></th>
                            <th>Issued By</th>
                            <th><?= e(t('th_status')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentIssued as $i => $row): ?>
                        <tr
                            class="<?= $row['status'] === 'issued' && $row['days_overdue'] > 0 ? 'table-danger' : '' ?>">
                            <td><?= (int) ($i + 1) ?></td>
                            <td><small><?= e(format_datetime($row['created_at'] ?? $row['issue_date'])) ?></small></td>
                            <td>
                                <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                    style="width:26px;height:36px;object-fit:cover;" class="me-1 rounded-1">
                                <?= e($row['book_title']) ?>
                            </td>
                            <td><?= e($row['isbn'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($row['student_name'])): ?>
                                <img src="<?= e(upload_url($row['student_photo'] ?? null)) ?>"
                                    style="width:26px;height:26px;object-fit:cover;" class="rounded-circle me-1">
                                <?php endif; ?>
                                <?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?>
                                <div class="text-muted">
                                    <small>#<?= e($row['admission_number'] ?? $row['employee_number'] ?? '') ?></small>
                                </div>
                            </td>
                            <td><?= e($row['class_name'] ?? '—') ?> <?= e($row['section_name'] ?? '') ?></td>
                            <td><?= e((string) $row['due_date']) ?></td>
                            <td><?= e($row['issued_by_name'] ?? '—') ?></td>
                            <td><span
                                    class="badge <?= status_badge_class($row['days_overdue'] > 0 ? 'overdue' : $row['status']) ?>"><?= $row['days_overdue'] > 0 ? 'Overdue' : st($row['status']) ?></span>
                            </td>
                            <td class="text-end"><a href="<?= e(url('library/transactions')) ?>"
                                    class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== Recent Returned Books ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-arrow-in-left me-1"></i>Recent Returned Books</h6>
                <div><?= lib_dash_export_buttons('returned') ?><a href="<?= e(url('library/transactions')) ?>"
                        class="btn btn-sm btn-link">View all</a></div>
            </div>
            <?php if (empty($recentReturned)): ?>
            <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-returned">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn2')) ?></th>
                            <th><?= e(t('th_return_date')) ?></th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th><?= e(t('th_borrower')) ?></th>
                            <th><?= e(t('th_status')) ?></th>
                            <th><?= e(t('th_fine')) ?></th>
                            <th>Returned By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReturned as $i => $row): ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td><small><?= e(format_datetime($row['returned_at'] ?? $row['return_date'])) ?></small>
                            </td>
                            <td>
                                <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                    style="width:26px;height:36px;object-fit:cover;" class="me-1 rounded-1">
                                <?= e($row['book_title']) ?>
                            </td>
                            <td><?= e($row['isbn'] ?? '—') ?></td>
                            <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?></td>
                            <td><span class="badge bg-success"><?= e(t('status_returned')) ?></span></td>
                            <td>
                                <?= (float) $row['fine_amount'] > 0 ? e(format_currency($row['fine_amount'])) : '—' ?>
                                <?php if ((float) $row['fine_amount'] > 0): ?>
                                <?php if (!empty($row['fine_receipt_number'])): ?>
                                <a href="<?= e(url('library/fine-receipt/' . $row['fine_receipt_number'])) ?>"
                                    target="_blank" class="badge bg-success ms-1 text-decoration-none"
                                    title="View Receipt">Paid</a>
                                <?php else: ?>
                                <form method="post"
                                    action="<?= e(url('library/transactions/' . $row['id'] . '/pay-fine')) ?>"
                                    class="d-inline fine-gate-form"
                                    data-fine-amount="<?= e((string) (float) $row['fine_amount']) ?>"
                                    data-days-overdue="0" data-fine-action-label="collect this fine">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="payment_mode" value="">
                                    <input type="hidden" name="reference_number" value="">
                                    <button type="submit" class="btn btn-sm btn-outline-warning ms-1">Collect
                                        Fine</button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['returned_by_name'] ?? '—') ?></td>
                            <td class="text-end"><a href="<?= e(url('library/transactions')) ?>"
                                    class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== Recent Reservations ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-bookmark-star-fill me-1"></i>Recent Reservations</h6>
                <div><?= lib_dash_export_buttons('reservations') ?><a href="<?= e(url('library/reservations')) ?>"
                        class="btn btn-sm btn-link">View all</a></div>
            </div>
            <?php if (empty($recentReservations)): ?>
            <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-reservations">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn2')) ?></th>
                            <th>Reserved On</th>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th><?= e(t('th_borrower')) ?></th>
                            <th>Reserved For</th>
                            <th><?= e(t('th_status')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReservations as $i => $row): ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td><small><?= e(format_datetime($row['created_at'] ?? $row['reserved_date'])) ?></small>
                            </td>
                            <td>
                                <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                    style="width:26px;height:36px;object-fit:cover;" class="me-1 rounded-1">
                                <?= e($row['book_title']) ?>
                            </td>
                            <td><small><?= e($row['isbn'] ?? '—') ?></small></td>
                            <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?><?php if (!empty($row['admission_number'])): ?>
                                <div class="text-muted"><small>#<?= e($row['admission_number']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['reserved_date']) ?></td>
                            <td><span
                                    class="badge <?= status_badge_class((string) $row['status']) ?>"><?= e(st((string) $row['status'])) ?></span>
                            </td>
                            <td class="text-end"><a href="<?= e(url('library/reservations')) ?>"
                                    class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- ==================== Recently Added Books ==================== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-journal-plus me-1"></i>Recently Added Books</h6>
                        <?= lib_dash_export_buttons('added') ?>
                    </div>
                    <?php if (empty($recentlyAdded)): ?>
                    <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm data-table" id="table-added">
                            <thead>
                                <tr>
                                    <th><?= e(t('th_sn2')) ?></th>
                                    <th>Added</th>
                                    <th><?= e(t('th_book')) ?></th>
                                    <th><?= e(t('th_category')) ?></th>
                                    <th>Copies</th>
                                    <th>Avail.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentlyAdded as $i => $row): ?>
                                <tr>
                                    <td><?= (int) ($i + 1) ?></td>
                                    <td><small><?= e(format_datetime($row['created_at'], 'd M Y')) ?></small></td>
                                    <td>
                                        <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                            style="width:24px;height:32px;object-fit:cover;" class="me-1 rounded-1">
                                        <?= e($row['title']) ?>
                                        <div class="text-muted"><small><?= e($row['isbn'] ?? '') ?> ·
                                                <?= e($row['author_name'] ?? '') ?></small></div>
                                    </td>
                                    <td><?= e($row['category_name'] ?? '—') ?></td>
                                    <td><?= (int) $row['total_copies'] ?></td>
                                    <td><?= (int) $row['available_copies'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==================== Recently Updated Books ==================== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square me-1"></i>Recently Updated Books</h6>
                        <?= lib_dash_export_buttons('updated') ?>
                    </div>
                    <?php if (empty($recentlyUpdated)): ?>
                    <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm data-table" id="table-updated">
                            <thead>
                                <tr>
                                    <th><?= e(t('th_sn2')) ?></th>
                                    <th>Updated</th>
                                    <th><?= e(t('th_book')) ?></th>
                                    <th>Updated By</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentlyUpdated as $i => $row): ?>
                                <tr>
                                    <td><?= (int) ($i + 1) ?></td>
                                    <td><small><?= e(time_ago($row['updated_at'])) ?></small></td>
                                    <td>
                                        <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                            style="width:24px;height:32px;object-fit:cover;" class="me-1 rounded-1">
                                        <?= e($row['title']) ?>
                                    </td>
                                    <td><?= e($row['updated_by_name'] ?? '—') ?></td>
                                    <td class="text-end"><a href="<?= e(url('library/books/' . $row['id'])) ?>"
                                            class="btn btn-sm btn-outline-primary">View</a></td>
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

    <!-- ==================== Recently Added Students ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-plus-fill me-1"></i>Recently Added Students</h6>
                <?= lib_dash_export_buttons('students') ?>
            </div>
            <?php if (empty($recentStudents)): ?>
            <p class="text-muted text-center py-4 mb-0">No Recent Activity</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-students">
                    <thead>
                        <tr>
                            <th><?= e(t('th_sn2')) ?></th>
                            <th>Admission Date</th>
                            <th><?= e(t('th_photo')) ?></th>
                            <th><?= e(t('th_student_id')) ?></th>
                            <th><?= e(t('th_name')) ?></th>
                            <th><?= e(t('th_class')) ?></th>
                            <th><?= e(t('th_section')) ?></th>
                            <th><?= e(t('th_phone')) ?></th>
                            <th><?= e(t('th_status')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStudents as $i => $row): ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td><small><?= e(format_datetime($row['admission_date'] ?? $row['created_at'] ?? null, 'd M Y')) ?></small>
                            </td>
                            <td><img src="<?= e(upload_url($row['photo_path'] ?? null)) ?>"
                                    style="width:28px;height:28px;object-fit:cover;" class="rounded-circle"></td>
                            <td><?= e($row['admission_number'] ?? '') ?></td>
                            <td><a href="<?= e(url('students/' . $row['id'])) ?>"><?= e($row['full_name']) ?></a></td>
                            <td><?= e($row['class_name'] ?? '—') ?></td>
                            <td><?= e($row['section_name'] ?? '—') ?></td>
                            <td><?= e($row['phone'] ?? '—') ?></td>
                            <td><span
                                    class="badge <?= status_badge_class((string) $row['status']) ?>"><?= e(st((string) $row['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== Overdue Books ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Overdue Books
                </h6>
                <?= lib_dash_export_buttons('overdue') ?>
            </div>
            <?php if (empty($overdue)): ?>
            <p class="text-muted text-center py-3 mb-0">No overdue books. 🎉</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-overdue">
                    <thead>
                        <tr>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_borrower')) ?></th>
                            <th><?= e(t('th_due_date')) ?></th>
                            <th>Days Overdue</th>
                            <th><?= e(t('th_fine')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue as $row): ?>
                        <tr class="table-danger">
                            <td><?= e($row['book_title']) ?></td>
                            <td><?= e($row['student_name'] ?? $row['teacher_name'] ?? '—') ?></td>
                            <td><?= e($row['due_date']) ?></td>
                            <td><span class="badge bg-danger"><?= (int) $row['days_overdue'] ?> day(s)</span></td>
                            <td><?= e(format_currency($row['fine_amount'] ?? 0)) ?></td>
                            <td class="text-end"><a href="<?= e(url('library/transactions')) ?>"
                                    class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== Low Stock Books ==================== -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-1"></i>Low Stock Books</h6>
                <?= lib_dash_export_buttons('lowstock') ?>
            </div>
            <?php if (empty($lowStock)): ?>
            <p class="text-muted text-center py-3 mb-0">No low-stock titles. 🎉</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle data-table" id="table-lowstock">
                    <thead>
                        <tr>
                            <th><?= e(t('th_book')) ?></th>
                            <th><?= e(t('th_isbn')) ?></th>
                            <th>Total Copies</th>
                            <th><?= e(t('status_available')) ?></th>
                            <th>Shelf</th>
                            <th>Rack</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $row): ?>
                        <tr class="<?= (int) $row['available_copies'] === 0 ? 'table-danger' : 'table-warning' ?>">
                            <td>
                                <img src="<?= e(upload_url($row['cover_image'] ?? null)) ?>"
                                    style="width:24px;height:32px;object-fit:cover;" class="me-1 rounded-1">
                                <?= e($row['title']) ?>
                            </td>
                            <td><?= e($row['isbn'] ?? '—') ?></td>
                            <td><?= (int) $row['total_copies'] ?></td>
                            <td><?= (int) $row['available_copies'] ?></td>
                            <td><?= e($row['shelf_number'] ?? '—') ?></td>
                            <td><?= e($row['rack_number'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- ==================== Categories / Authors / Publishers ==================== -->
        <?php
$mini = [
    ['title' => 'Recently Added Categories', 'icon' => 'bi-tags-fill', 'rows' => $recentCategories, 'type' => 'categories'],
    ['title' => 'Recently Added Authors', 'icon' => 'bi-feather', 'rows' => $recentAuthors, 'type' => 'authors'],
    ['title' => 'Recently Added Publishers', 'icon' => 'bi-building', 'rows' => $recentPublishers, 'type' => 'publishers'],
];
?>
        <?php foreach ($mini as $section): ?>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i
                                class="bi <?= $section['icon'] ?> me-1"></i><?= e($section['title']) ?></h6>
                        <?= lib_dash_export_buttons($section['type']) ?>
                    </div>
                    <?php if (empty($section['rows'])): ?>
                    <p class="text-muted text-center py-3 mb-0">No Recent Activity</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= e(t('th_sn2')) ?></th>
                                    <th><?= e(t('th_name')) ?></th>
                                    <th><?= e(t('th_books')) ?></th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['rows'] as $i => $row): ?>
                                <tr>
                                    <td><?= (int) ($i + 1) ?></td>
                                    <td><?= e($row['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= (int) $row['book_count'] ?></span></td>
                                    <td><small><?= e(format_datetime($row['created_at'], 'd M Y')) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>