<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Statement — <?= e($student['full_name']) ?></title>
<!--
    Print-safe font stack only (NOT Inter): this view is rendered server-side
    via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
    Arial/Helvetica are Dompdf's built-in safe substitutes.
-->
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #222; padding: 24px; font-size: 13px; line-height: 1.5; }
    h1 { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
    .meta { color: #555; font-size: 12px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f2f2f2; font-weight: 700; }
    td.amount, th.amount { text-align: right; }
    .print-bar { text-align: right; margin-bottom: 12px; }
    @media print { .print-bar { display: none; } }
</style>
</head>
<body>
    <div class="print-bar"><button onclick="window.print()">Print</button></div>

    <h1><?= e(config('app.name', 'School ERP')) ?> — Fee Statement</h1>
    <div class="meta">
        <?= e($student['full_name']) ?> &middot; Admission No: <?= e($student['admission_number'] ?? '—') ?>
        &middot; Class: <?= e(($student['class_name'] ?? '—') . ' ' . ($student['section_name'] ?? '')) ?>
        &middot; Printed on <?= e($printedAt) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fee Head</th>
                <th>Period</th>
                <th class="amount">Fee</th>
                <th class="amount">Concession</th>
                <th class="amount">Fine</th>
                <th class="amount">Payable</th>
                <th class="amount">Paid</th>
                <th class="amount">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" style="text-align:center;color:#888;">No matching fee rows.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r):
                $balance = (float) $r['payable_amount'] - (float) $r['amount_paid'];
                $period = $r['month'] ? date('M', mktime(0, 0, 0, (int) $r['month'], 1)) : ($r['term_name'] ?? 'Annual');
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($r['fee_type_name']) ?></td>
                <td><?= e($period) ?></td>
                <td class="amount"><?= e(format_currency($r['amount_due'], '')) ?></td>
                <td class="amount"><?= e(format_currency($r['discount_amount'], '')) ?></td>
                <td class="amount"><?= e(format_currency($r['fine_amount'], '')) ?></td>
                <td class="amount"><?= e(format_currency($r['payable_amount'], '')) ?></td>
                <td class="amount"><?= e(format_currency($r['amount_paid'], '')) ?></td>
                <td class="amount"><?= e(format_currency($balance, '')) ?></td>
                <td><?= e(ucfirst($r['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
