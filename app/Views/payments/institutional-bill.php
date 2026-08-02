<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill <?= e($billNumber) ?></title>
<!--
    Print-safe font stack only (NOT Inter): this view is rendered server-side
    via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
    Arial/Helvetica are Dompdf's built-in safe substitutes. Standardized to
    match every other print/PDF view in the app (previously used a serif
    Times New Roman stack unique to this page).
-->
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111; padding: 24px; font-size: 13px; line-height: 1.5; background: #f4f4f4; }
    .bill-box { max-width: 700px; margin: 0 auto; border: 2px solid #222; padding: 22px 28px; background: #fff; }
    .bill-header { text-align: center; position: relative; padding-bottom: 8px; margin-bottom: 6px; }
    .bill-header .council { font-size: 11px; letter-spacing: .3px; margin-bottom: 2px; }
    .bill-header .inst-name { font-size: 19px; font-weight: 700; text-transform: uppercase; margin: 2px 0; }
    .bill-header .addr { font-size: 12px; margin: 1px 0; }
    .bill-header .logo { position: absolute; left: 0; top: 0; width: 56px; height: 56px; border: 2px solid #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #555; text-align: center; }
    .bill-title { text-align: center; font-weight: 700; font-size: 15px; letter-spacing: .5px; margin: 12px 0 14px; text-decoration: underline; color: #1a3c8f; }
    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .meta-table td { padding: 3px 4px; font-size: 12.5px; vertical-align: top; }
    .meta-table td.label { color: #333; font-weight: 700; width: 110px; white-space: nowrap; }
    .meta-table td.colon { width: 10px; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #333; }
    table.items th, table.items td { border: 1px solid #333; padding: 6px 10px; font-size: 12.5px; }
    table.items th { background: #eef0f5; text-align: left; font-weight: 700; }
    table.items td.sn, table.items th.sn { text-align: center; width: 36px; }
    table.items td.amount, table.items th.amount { text-align: right; width: 110px; }
    table.items tr.blank td { height: 22px; border-left: 1px solid #333; border-right: 1px solid #333; border-bottom: none; border-top: none; }
    .totals-row td { font-weight: 700; background: #f7f7f7; }
    .words { font-style: italic; font-size: 12.5px; margin: 10px 0 0; border-top: 1px dashed #999; padding-top: 8px; }
    .signatures { display: flex; justify-content: space-between; margin-top: 46px; font-size: 12.5px; }
    .signatures div { text-align: center; width: 42%; border-top: 1px solid #444; padding-top: 4px; }
    .footer-email { margin-top: 18px; font-size: 11.5px; }
    .print-bar { text-align: right; margin-bottom: 12px; max-width: 700px; margin-left: auto; margin-right: auto; }
    @media print { .print-bar { display: none; } body { padding: 0; background: #fff; } .bill-box { border: 2px solid #222; } }
</style>
</head>
<body>
<?php
    // Fee category of the first row, used for the "Category" line (e.g. Full Fee / Tuition Fee / Admission Fee).
    $categoryLabel = 'Full Fee';
    if (!empty($rows[0]['fee_type_name'])) {
        $categoryLabel = $rows[0]['fee_type_name'];
    }
    $addressParts = array_filter([
        $student['address'] ?? null,
        $student['address_district'] ?? null,
    ]);
    $addressLine = !empty($addressParts) ? implode(', ', array_unique($addressParts)) : '—';
?>
    <?php if (empty($forPdf)): ?>
    <div class="print-bar">
        <button onclick="window.print()">Print</button>
        <a href="<?= e(url('payments/bill/' . $receiptGroup . '/pdf')) ?>"><button type="button">Download PDF</button></a>
    </div>
    <?php endif; ?>

    <div class="bill-box">
        <div class="bill-header">
            <?php if (!empty(config('institute.council'))): ?>
                <div class="council"><?= e(config('institute.council')) ?></div>
            <?php endif; ?>
            <div class="inst-name"><?= e(config('institute.name', config('app.name', 'School ERP'))) ?></div>
            <?php if (!empty(config('institute.address_line'))): ?>
                <div class="addr"><?= e(config('institute.address_line')) ?></div>
            <?php endif; ?>
            <?php if (!empty(config('institute.phone'))): ?>
                <div class="addr">Phone : <?= e(config('institute.phone')) ?></div>
            <?php endif; ?>
        </div>

        <div class="bill-title">INCOME RECEIPT</div>

        <table class="meta-table">
            <tr>
                <td class="label">Bill No.</td><td class="colon">:</td><td><strong><?= e($billNumber) ?></strong></td>
                <td class="label">Bill Miti</td><td class="colon">:</td><td><?= e($billDateBs ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Student Name</td><td class="colon">:</td><td colspan="4"><?= e($student['full_name'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Program</td><td class="colon">:</td><td colspan="4"><?= e($student['class_name'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Category</td><td class="colon">:</td><td><?= e($categoryLabel) ?></td>
                <td class="label">Address</td><td class="colon">:</td><td><?= e($addressLine) ?></td>
            </tr>
            <tr>
                <td class="label">Level</td><td class="colon">:</td><td><?= e($student['section_name'] ?? '—') ?></td>
                <td class="label">Admission Year</td><td class="colon">:</td><td><?= e($student['admission_date'] ? substr($student['admission_date'], 0, 4) : '—') ?></td>
            </tr>
            <tr>
                <td class="label">Semester</td><td class="colon">:</td><td><?= e($student['academic_year_label'] ?? '—') ?></td>
                <td class="label">REG. No.</td><td class="colon">:</td><td><?= e($student['admission_number'] ?? '—') ?></td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="sn">S.N.</th>
                    <th>Particulars</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="sn"><?= $i + 1 ?></td>
                    <td><?= e($r['fee_type_name'] ?? '—') ?></td>
                    <td class="amount"><?= e(format_currency($r['amount'], '')) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="totals-row">
                    <td colspan="2" style="text-align:right;">Total</td>
                    <td class="amount"><?= e(format_currency($total, '')) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="words">In Words : <?= e(amount_in_words((float) $total)) ?> Only</div>

        <div class="signatures">
            <div>&nbsp;</div>
            <div>Received by</div>
        </div>

        <div class="footer-email">
            <?php if (!empty(config('institute.email'))): ?>
                E-mail:- <?= e(config('institute.email')) ?>
            <?php endif; ?>
        </div>

        <p style="text-align:center; color:#777; font-size:10px; margin-top:14px;">
            This is a computer-generated bill. Printed on <?= e($printedAt) ?>.
        </p>
    </div>
</body>
</html>
