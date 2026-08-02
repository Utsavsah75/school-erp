<?php
/**
 * Generic "Recent Activity" panel. Include this at the top of a module page
 * with:
 *
 *   <?php include dirname(__DIR__) . '/partials/_recent_activity_panel.php'; ?>
 *
 * after setting these locals in the calling view:
 *   $panelTitle   string
 *   $panelIcon    string   bootstrap-icon class, e.g. 'bi-activity'
 *   $panelHeaders string[] column labels
 *   $panelRows    array<int, array{cells: string[], status: ?string, statusClass: ?string, viewUrl: ?string}>
 *   $panelViewAllUrl ?string
 *   $panelEmptyText  ?string
 */
$panelIcon = $panelIcon ?? 'bi-activity';
$panelEmptyText = $panelEmptyText ?? 'No recent activity yet.';
?>
<div class="card mb-3 recent-activity-panel">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0"><i class="bi <?= e($panelIcon) ?> me-1"></i><?= e($panelTitle) ?></h6>
            <?php if (!empty($panelViewAllUrl)): ?>
                <a href="<?= e(url($panelViewAllUrl)) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
            <?php endif; ?>
        </div>
        <?php if (empty($panelRows)): ?>
            <p class="text-muted text-center py-3 mb-0 small"><?= e($panelEmptyText) ?></p>
        <?php else: ?>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <?php foreach ($panelHeaders as $h): ?>
                                <th class="small text-muted text-uppercase"><?= e($h) ?></th>
                            <?php endforeach; ?>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($panelRows as $row): ?>
                            <tr>
                                <?php foreach ($row['cells'] as $i => $cell): ?>
                                    <?php if ($i === 0): ?>
                                        <td class="fw-semibold"><?= $cell ?></td>
                                    <?php else: ?>
                                        <td><?= $cell ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <td class="text-end">
                                    <?php if (!empty($row['viewUrl'])): ?>
                                        <a href="<?= e(url($row['viewUrl'])) ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">
                                            <i class="bi bi-eye"></i> View
                                        </a>
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
