<?php
$priorityBadge = match ($notice['priority']) {
    'high' => 'bg-danger', 'low' => 'bg-secondary', default => 'bg-warning text-dark',
};
$statusBadge = match ($notice['status']) {
    'published' => 'bg-success-subtle text-success', 'archived' => 'bg-secondary-subtle text-secondary',
    default => 'bg-info-subtle text-info',
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_notice_details')) ?></h5>
    <a href="<?= e(url('notices')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Notice Board</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <h4 class="fw-bold mb-0"><?= e($notice['title']) ?></h4>
            <div class="d-flex gap-2">
                <span class="badge <?= $priorityBadge ?>"><?= e(st($notice['priority'])) ?> Priority</span>
                <span class="badge <?= $statusBadge ?>"><?= e(st($notice['status'])) ?></span>
            </div>
        </div>
        <div class="small text-muted mb-3">
            <i class="bi bi-tag-fill me-1"></i><?= e($notice['category']) ?>
            &nbsp;|&nbsp; <i class="bi bi-people-fill me-1"></i><?= e(\App\Models\Notice::audienceLabel($notice['audience'])) ?>
            <?= $notice['class_name'] ? ' — ' . e($notice['class_name']) : '' ?><?= $notice['section_name'] ? ' ' . e($notice['section_name']) : '' ?><?= $notice['student_name'] ? ' (' . e($notice['student_name']) . ')' : '' ?>
            &nbsp;|&nbsp; <i class="bi bi-person-fill me-1"></i>Posted by <?= e($notice['posted_by_name'] ?? '—') ?>
            &nbsp;|&nbsp; <i class="bi bi-calendar-event me-1"></i><?= e(format_date($notice['publish_date'])) ?>
            <?php if ($notice['expiry_date']): ?>&nbsp;&rarr; <?= e(format_date($notice['expiry_date'])) ?><?php endif; ?>
        </div>

        <div class="notice-body mb-3" style="white-space:pre-wrap;"><?= e($notice['body']) ?></div>

        <?php if (!empty($notice['attachment_path'])): ?>
        <a href="<?= e(upload_url($notice['attachment_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-paperclip me-1"></i>Download Attachment<?= !empty($notice['attachment_name']) ? ': ' . e($notice['attachment_name']) : '' ?>
        </a>
        <?php endif; ?>
    </div>
</div>
