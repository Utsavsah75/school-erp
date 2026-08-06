<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Classes</title>
<!--
    Print-safe font stack only (NOT Inter): this view is rendered server-side
    via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
    Arial/Helvetica are Dompdf's built-in safe substitutes.
-->
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #222; padding: 24px; font-size: 12px; line-height: 1.5; }
    h1 { font-size: 20px; font-weight: 700; margin-bottom: 2px; }
    .meta { color: #666; font-size: 12px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f2f2f2; font-weight: 700; }
    .print-bar { text-align: right; margin-bottom: 12px; }
    @media print { .print-bar { display: none; } }
</style>
</head>
<body>
    <div class="print-bar"><button onclick="window.print()"><?= e(t('print')) ?></button></div>
    <h1>School ERP — All Classes</h1>
    <div class="meta">Printed on <?= e($printedAt) ?> &middot; <?= count($rows) ?> record(s)</div>

    <table>
        <thead>
            <tr>
                <th>Class ID</th>
                <th>Class Name</th>
                <th><?= e(t('th_code')) ?></th>
                <th><?= e(t('th_class_teacher')) ?></th>
                <th><?= e(t('th_room')) ?></th>
                <th><?= e(t('th_capacity')) ?></th>
                <th><?= e(t('th_shift')) ?></th>
                <th><?= e(t('th_academic_year')) ?></th>
                <th><?= e(t('th_status')) ?></th>
                <th><?= e(t('th_created')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" style="text-align:center;color:#888;">No records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>#<?= e($r['id']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['code']) ?></td>
                <td><?= e($r['teacher_name'] ?? '—') ?></td>
                <td><?= e($r['room_number'] ?: '—') ?></td>
                <td><?= e($r['capacity'] ?: '—') ?></td>
                <td><?= e(st($r['shift'])) ?></td>
                <td><?= e($r['academic_year_label'] ?? '—') ?></td>
                <td><?= e(st($r['status'])) ?></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
