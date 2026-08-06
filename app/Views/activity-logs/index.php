<style>
.bg-purple { background-color: #6f42c1 !important; }
.bg-orange { background-color: #fd7e14 !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i><?= e(t('activity_logs')) ?></h5>
    <div>
        <a href="<?= e(url('activity-logs/export-csv?' . http_build_query($filters))) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel/CSV</a>
        <a href="<?= e(url('activity-logs/export-pdf?' . http_build_query($filters))) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="<?= e(url('activity-logs/print?' . http_build_query($filters))) ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#4e73df;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Activities Today</div><h4 class="mb-0"><?= number_format($stats['total']) ?></h4></div>
                <div class="stat-icon" style="background:#4e73df;"><i class="bi bi-activity"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#1cc88a;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Logins Today</div><h4 class="mb-0"><?= number_format($stats['logins']) ?></h4></div>
                <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-box-arrow-in-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#e74a3b;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Failed Logins</div><h4 class="mb-0"><?= number_format($stats['failed_logins']) ?></h4></div>
                <div class="stat-icon" style="background:#e74a3b;"><i class="bi bi-shield-exclamation"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#1cc88a;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Created Today</div><h4 class="mb-0"><?= number_format($stats['created']) ?></h4></div>
                <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-plus-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#36b9cc;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Updated Today</div><h4 class="mb-0"><?= number_format($stats['updated']) ?></h4></div>
                <div class="stat-icon" style="background:#36b9cc;"><i class="bi bi-pencil-square"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg">
        <div class="card stat-card h-100" style="border-left-color:#6f42c1;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div><div class="stat-label small text-muted">Payments Today</div><h4 class="mb-0"><?= number_format($stats['payments']) ?></h4></div>
                <div class="stat-icon" style="background:#6f42c1;"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(url('activity-logs')) ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Action, description, user, IP..." value="<?= e($filters['q'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">User</label>
                <select name="user_id" class="form-select">
                    <option value="0"><?= e(t('all')) ?></option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= e((string) $u['id']) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Role</label>
                <select name="role" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r) ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($modules)): ?>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Module</label>
                <select name="module" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= e($m) ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Action Type</label>
                <select name="category" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c) ?>" <?= ($filters['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value=""><?= e(t('all')) ?></option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Date Range</label>
                <select name="range" class="form-select" onchange="document.getElementById('customRangeWrap').style.display = this.value === 'custom' ? '' : 'none';">
                    <option value=""><?= e(t('all')) ?> Time</option>
                    <option value="today" <?= ($filters['range'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= ($filters['range'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="last_7" <?= ($filters['range'] ?? '') === 'last_7' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30" <?= ($filters['range'] ?? '') === 'last_30' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="this_month" <?= ($filters['range'] ?? '') === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="custom" <?= ($filters['range'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-4" id="customRangeWrap" style="<?= ($filters['range'] ?? '') === 'custom' ? '' : 'display:none;' ?>">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">From</label>
                        <input type="date" name="date_from" class="form-control" value="<?= e($filters['range'] === 'custom' ? ($filters['date_from'] ?? '') : '') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">To</label>
                        <input type="date" name="date_to" class="form-control" value="<?= e($filters['range'] === 'custom' ? ($filters['date_to'] ?? '') : '') ?>">
                    </div>
                </div>
                <input type="hidden" name="range" value="custom">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">IP Address</label>
                <input type="text" name="ip" class="form-control" placeholder="192.168..." value="<?= e($filters['ip'] ?? '') ?>">
            </div>

            <?php if (!empty($activeFilters)): ?>
            <div class="col-12">
                <a href="<?= e(url('activity-logs')) ?>" class="btn btn-sm btn-link text-decoration-none">Clear all filters</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($result['data'])): ?>
        <p class="text-muted text-center py-4 mb-0">No activity found for the selected filters.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>S.N.</th>
                        <th>Date &amp; Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn = ($result['page'] - 1) * $result['per_page'] + 1; ?>
                    <?php foreach ($result['data'] as $row): $cat = \App\Models\ActivityLog::categorize($row['action']); ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td class="text-nowrap"><?= e(format_datetime($row['created_at'])) ?></td>
                        <td><?= e($row['user_name'] ?? 'System') ?></td>
                        <td><?= e(role_label($row['user_role'] ?? null)) ?></td>
                        <td><?= e($row['module'] ?? '—') ?></td>
                        <td><span class="badge <?= e($cat['badge']) ?>"><?= e($cat['label']) ?></span></td>
                        <td class="text-truncate" style="max-width:320px;" title="<?= e($row['description']) ?>"><?= e($row['description']) ?></td>
                        <td><?= e($row['ip_address'] ?? '—') ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary activity-detail-btn" data-id="<?= e((string) $row['id']) ?>"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php
        $extraParams = $filters;
        $baseUrl = url('activity-logs');
        require __DIR__ . '/../library/_pagination.php';
        ?>
    </div>
</div>

<div class="modal fade" id="activityDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="activityDetailBody">
                <div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
// NOTE: bootstrap.bundle.min.js is loaded near the bottom of the main
// layout, AFTER this page's own content (this script included) — so at
// the point this <script> tag runs inline, `window.bootstrap` does not
// exist yet. Deferring setup to DOMContentLoaded runs it once every
// synchronous <script> tag earlier in the document (including bootstrap's)
// has already executed, so `bootstrap.Modal` is safely available.
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('activityDetailModal');
    var bodyEl = document.getElementById('activityDetailBody');
    var modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    document.querySelectorAll('.activity-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            bodyEl.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</div>';
            if (modal) {
                modal.show();
            } else {
                // Extremely defensive fallback — should never trigger in
                // practice now that setup runs on DOMContentLoaded, but
                // avoids a silent no-op if bootstrap somehow still isn't
                // ready.
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
            }
            fetch('<?= e(url('activity-logs/')) ?>' + btn.dataset.id)
                .then(function (res) { return res.text(); })
                .then(function (html) { bodyEl.innerHTML = html; })
                .catch(function () { bodyEl.innerHTML = '<p class="text-danger mb-0">Could not load this entry.</p>'; });
        });
    });
});
</script>
