<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notice Board — Print</title>
<!--
    Print-safe font stack only (NOT Inter): this view is rendered server-side
    via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
    Arial/Helvetica are Dompdf's built-in safe substitutes.
-->
<style>
body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.5; color: #222; margin: 24px; }
h2 { font-weight: 700; margin-bottom: 2px; }
.meta { color: #666; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
th { background: #f2f2f2; font-weight: 700; }
</style>
</head>
<body>
<h2><?= e(t('h_notice_board')) ?></h2>
<div class="meta">Printed on <?= e($printedAt) ?></div>
<table>
    <thead>
        <tr><th><?= e(t('th_title')) ?></th><th><?= e(t('th_category')) ?></th><th>Priority</th><th>Audience</th><th><?= e(t('th_posted_by')) ?></th><th>Publish Date</th><th>Expiry Date</th><th><?= e(t('th_status')) ?></th></tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['title']) ?></td>
            <td><?= e($r['category']) ?></td>
            <td><?= e(st($r['priority'])) ?></td>
            <td><?= e(\App\Models\Notice::audienceLabel($r['audience'])) ?><?= $r['class_name'] ? ' - ' . e($r['class_name']) : '' ?></td>
            <td><?= e($r['posted_by_name'] ?? '-') ?></td>
            <td><?= e(format_date($r['publish_date'])) ?></td>
            <td><?= $r['expiry_date'] ? e(format_date($r['expiry_date'])) : '-' ?></td>
            <td><?= e(st($r['status'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>window.onload = function () { window.print(); };</script>
</body>
</html>
