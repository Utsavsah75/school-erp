<?php $cat = \App\Models\ActivityLog::categorize($row['action']); ?>
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
    <div class="col-6">
        <div class="small text-muted">Date &amp; Time</div>
        <div><?= e(format_datetime($row['created_at'])) ?></div>
    </div>
    <div class="col-6">
        <div class="small text-muted">IP Address</div>
        <div><?= e($row['ip_address'] ?? '—') ?></div>
    </div>
    <div class="col-12">
        <div class="small text-muted">Description</div>
        <div class="border rounded p-2 bg-light"><?= e($row['description'] ?? '') ?></div>
    </div>
</div>
