<?php
$cat = \App\Models\ActivityLog::categorize($row['action']);
$old = $row['old_value'] ? json_decode((string) $row['old_value'], true) : null;
$new = $row['new_value'] ? json_decode((string) $row['new_value'], true) : null;
?>
<div class="row g-3">
    <div class="col-6">
        <div class="small text-muted">Log ID</div>
        <div class="fw-semibold">#<?= e((string) $row['id']) ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">Action</div>
        <div><span class="badge <?= e($cat['badge']) ?>"><?= e($cat['label']) ?></span> <?= e(\App\Models\ActivityLog::actionLabel($row['action'])) ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">User</div>
        <div class="fw-semibold"><?= e($row['user_name'] ?? 'System') ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">Role</div>
        <div><?= e(role_label($row['user_role'] ?? null)) ?></div>
    </div>
    <?php if (!empty($row['module']) || !empty($row['record_name'])): ?>
    <div class="col-6">
        <div class="small text-muted">Module</div>
        <div><?= e($row['module'] ?? '—') ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">Record</div>
        <div><?= e($row['record_name'] ?? '—') ?><?= !empty($row['record_id']) ? ' (#' . e((string) $row['record_id']) . ')' : '' ?></div>
    </div>
    <?php endif; ?>
    <div class="col-6">
        <div class="small text-muted">Date &amp; Time</div>
        <div><?= e(format_datetime($row['created_at'])) ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">IP Address</div>
        <div><?= e($row['ip_address'] ?? '—') ?></div>
    </div>
    <div class="col-4">
        <div class="small text-muted">Browser</div>
        <div><?= e($row['browser'] ?? '—') ?></div>
    </div>
    <div class="col-4">
        <div class="small text-muted">Operating System</div>
        <div><?= e($row['os'] ?? '—') ?></div>
    </div>
    <div class="col-4">
        <div class="small text-muted">Device</div>
        <div><?= e($row['device_type'] ?? '—') ?></div>
    </div>
    <div class="col-12">
        <div class="small text-muted">Description</div>
        <div class="border rounded p-2 bg-light"><?= e($row['description'] ?? '') ?></div>
    </div>
    <?php if ($old !== null || $new !== null): ?>
    <div class="col-6">
        <div class="small text-muted mb-1">Old Value</div>
        <pre class="border rounded p-2 bg-light small mb-0" style="white-space:pre-wrap;"><?= e($old !== null ? json_encode($old, JSON_PRETTY_PRINT) : '—') ?></pre>
    </div>
    <div class="col-6">
        <div class="small text-muted mb-1">New Value</div>
        <pre class="border rounded p-2 bg-light small mb-0" style="white-space:pre-wrap;"><?= e($new !== null ? json_encode($new, JSON_PRETTY_PRINT) : '—') ?></pre>
    </div>
    <?php endif; ?>
</div>
