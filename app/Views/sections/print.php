<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Sections</title>
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
    <div class="print-bar"><button onclick="window.print()">Print</button></div>
    <h1>School ERP — All Sections</h1>
    <div class="meta">Printed on <?= e($printedAt) ?> &middot; <?= count($rows) ?> record(s)</div>

    <table>
        <thead>
            <tr>
                <th>Section ID</th>
                <th>Section Name</th>
                <th>Code</th>
                <th>Class</th>
                <th>Teacher</th>
                <th>Room</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="9" style="text-align:center;color:#888;">No records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>#<?= e($r['id']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['code']) ?></td>
                <td><?= e($r['class_name'] ?? '—') ?></td>
                <td><?= e($r['teacher_name'] ?? '—') ?></td>
                <td><?= e($r['room_number'] ?: '—') ?></td>
                <td><?= e($r['capacity'] ?: '—') ?></td>
                <td><?= e(ucfirst($r['status'])) ?></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
