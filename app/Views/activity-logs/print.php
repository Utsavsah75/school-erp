<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Activity Log</title>
<!--
    Print-safe font stack only (NOT Inter): this view is rendered server-side
    via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
    Arial/Helvetica are Dompdf's built-in safe substitutes.
-->
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #222; padding: 24px; font-size: 12px; line-height: 1.5; }
    h1 { font-size: 20px; font-weight: 700; margin-bottom: 2px; }
    .meta { color: #666; font-size: 12px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f2f2f2; font-weight: 700; }
    .print-bar { text-align: right; margin-bottom: 12px; }
    @media print { .print-bar { display: none; } }
</style>
</head>
<body>
    <div class="print-bar"><button onclick="window.print()">Print</button></div>
    <h1>School ERP — Activity Log</h1>
    <div class="meta">Printed on <?= e($printedAt) ?> &middot; <?= count($rows) ?> record(s)</div>

    <table>
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Date &amp; Time</th>
                <th>User</th>
                <th>Role</th>
                <th>Module</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;color:#888;">No records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>#<?= e((string) $r['id']) ?></td>
                <td><?= e(format_datetime($r['created_at'])) ?></td>
                <td><?= e($r['user_name'] ?? 'System') ?></td>
                <td><?= e(role_label($r['user_role'] ?? null)) ?></td>
                <td><?= e($r['module'] ?? '—') ?></td>
                <td><?= e(\App\Models\ActivityLog::actionLabel($r['action'])) ?></td>
                <td><?= e($r['description'] ?? '') ?></td>
                <td><?= e($r['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
