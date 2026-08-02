<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Schedule</title>
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
    <h1>School ERP — Exam Schedule</h1>
    <div class="meta">Printed on <?= e($printedAt) ?> &middot; <?= count($rows) ?> record(s)</div>

    <table>
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Type</th>
                <th>Class</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Time</th>
                <th>Room</th>
                <th>Max</th>
                <th>Pass</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="11" style="text-align:center;color:#888;">No records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['exam_name']) ?></td>
                <td><?= e($r['exam_type_name'] ?? '—') ?></td>
                <td><?= e($r['class_name'] ?? '—') ?></td>
                <td><?= e($r['section_name'] ?? '—') ?></td>
                <td><?= e($r['subject_name'] ?? '—') ?></td>
                <td><?= e(format_date($r['exam_date'])) ?></td>
                <td><?= e(substr($r['start_time'], 0, 5)) ?> - <?= e(substr($r['end_time'], 0, 5)) ?></td>
                <td><?= e($r['room_number'] ?: '—') ?></td>
                <td><?= e($r['max_marks']) ?></td>
                <td><?= e($r['passing_marks']) ?></td>
                <td><?= e(ucfirst($r['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
