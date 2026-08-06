<?php
/**
 * Shared "entries per page" dropdown + "Showing X to Y of Z entries" +
 * numbered pager, matching the reference Library ERP screens (10 entries
 * per page dropdown, "Showing 1 to 10 of 27 entries", "1 2 3 » «").
 *
 * Expects in scope:
 *   $result      — ['data'=>..,'total'=>..,'page'=>..,'per_page'=>..,'last_page'=>..]
 *   $extraParams — array of other GET params to preserve (search, filters, etc.)
 *   $baseUrl     — url('library/...') for this screen
 */
$page = (int) $result['page'];
$lastPage = (int) $result['last_page'];
$perPage = (int) $result['per_page'];
$total = (int) $result['total'];
$shownFrom = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$shownTo = min($total, $page * $perPage);

$qs = function (array $overrides) use ($extraParams, $baseUrl, $perPage) {
    $params = array_merge($extraParams, ['per_page' => $perPage], $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $baseUrl . (empty($params) ? '' : '?' . http_build_query($params));
};

$pagerStart = max(1, $page - 2);
$pagerEnd = min($lastPage, $page + 2);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <form method="GET" action="<?= e($baseUrl) ?>" class="d-flex align-items-center gap-2" id="perPageForm">
        <?php foreach ($extraParams as $k => $v): if ($v === '' || $v === null) continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endforeach; ?>
        <label class="small text-muted mb-0" for="perPageSelect">entries per page</label>
        <select name="per_page" id="perPageSelect" class="form-select form-select-sm" style="width:auto;" onchange="document.getElementById('perPageForm').submit();">
            <?php foreach ([10, 25, 50, 100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <small class="text-muted">Showing <?= $shownFrom ?> to <?= $shownTo ?> of <?= $total ?> entries</small>

    <nav aria-label="Pagination">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($qs(['page' => max(1, $page - 1)])) ?>">&laquo;</a>
            </li>
            <?php for ($i = $pagerStart; $i <= $pagerEnd; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($qs(['page' => min($lastPage, $page + 1)])) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
</div>
