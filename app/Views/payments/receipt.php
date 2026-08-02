<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt <?= e($receiptGroup) ?></title>
    <!--
        Print-safe font stack only (NOT Inter): this view is rendered server-side
        via Dompdf with remote fetching disabled, so it cannot load Google Fonts.
        Arial/Helvetica are Dompdf's built-in safe substitutes.
    -->
    <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #222;
        padding: 24px;
        font-size: 13px;
        line-height: 1.5;
    }

    .receipt-box {
        max-width: 620px;
        margin: 0 auto;
        border: 1px solid #999;
        padding: 20px;
    }

    .school-header {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }

    .school-header h1 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 2px;
    }

    .school-header .addr {
        color: #555;
        font-size: 11px;
    }

    .receipt-title {
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        margin: 10px 0;
        text-decoration: underline;
    }

    .meta-table {
        width: 100%;
        margin-bottom: 12px;
    }

    .meta-table td {
        padding: 2px 0;
        font-size: 12px;
    }

    .meta-table td.label {
        color: #555;
        width: 110px;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    table.items th,
    table.items td {
        border: 1px solid #ccc;
        padding: 6px 8px;
        font-size: 12px;
    }

    table.items th {
        background: #f2f2f2;
        text-align: left;
    }

    table.items td.amount,
    table.items th.amount {
        text-align: right;
    }

    .totals-row td {
        font-weight: bold;
        background: #fafafa;
    }

    .words {
        font-style: italic;
        font-size: 12px;
        margin: 8px 0;
    }

    .signatures {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
        font-size: 12px;
    }

    .signatures div {
        text-align: center;
        width: 40%;
        border-top: 1px solid #666;
        padding-top: 4px;
    }

    .print-bar {
        text-align: right;
        margin-bottom: 12px;
    }

    @media print {
        .print-bar {
            display: none;
        }

        body {
            padding: 0;
        }

        .receipt-box {
            border: none;
        }
    }
    </style>
</head>

<body>
    <?php if (empty($forPdf)): ?>
    <div class="print-bar">
        <button onclick="window.print()">Print</button>
        <a href="<?= e(url('payments/receipt/' . $receiptGroup . '/pdf')) ?>"><button type="button">Download
                PDF</button></a>
        <a href="<?= e(url('payments/bill/' . $receiptGroup)) ?>"><button type="button">Institutional Bill
                Format</button></a>
    </div>
    <?php endif; ?>

    <div class="receipt-box">
        <div class="school-header">
            <h1><?= e(config('app.name', 'School ERP')) ?></h1>
            <div class="addr">Fee Payment Receipt</div>
        </div>

        <div class="receipt-title">FEE RECEIPT</div>

        <table class="meta-table">
            <tr>
                <td class="label">Receipt No.</td>
                <td><strong><?= e($receiptGroup) ?></strong></td>
                <td class="label">Date</td>
                <td><?= e($rows[0]['paid_at_bs'] ?? substr($rows[0]['paid_at'], 0, 10)) ?></td>
            </tr>
            <tr>
                <td class="label">Student Name</td>
                <td><?= e($student['full_name'] ?? '—') ?></td>
                <td class="label">Admission No.</td>
                <td><?= e($student['admission_number'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Class / Section</td>
                <td><?= e(($student['class_name'] ?? '—') . ' ' . ($student['section_name'] ?? '')) ?></td>
                <td class="label">Father's Name</td>
                <td><?= e($student['father_name'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Payment Mode</td>
                <td><?= e(PAYMENT_MODES[$rows[0]['payment_mode']] ?? ucfirst($rows[0]['payment_mode'])) ?></td>
                <td class="label">Reference No.</td>
                <td><strong><?= e($rows[0]['internal_ref_no'] ?? '—') ?></strong></td>
            </tr>
            <?php if (!empty($rows[0]['reference_number'])): ?>
            <tr>
                <td class="label">Txn/Cheque Ref.</td>
                <td colspan="3"><?= e($rows[0]['reference_number']) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee Structure Name</th>
                    <th>Period</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r):
                    $period = $r['month'] ? date('M', mktime(0, 0, 0, (int) $r['month'], 1)) : ($r['term_name'] ?? 'Annual');
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($r['fee_type_name'] ?? '—') ?></td>
                    <td><?= e($period) ?></td>
                    <td class="amount"><?= e(format_currency($r['amount'], '')) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="totals-row">
                    <td colspan="3" style="text-align:right;">Total Paid</td>
                    <td class="amount"><?= e(format_currency($total)) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="words">In Words: <?= e(amount_in_words((float) $total)) ?></div>

        <div class="signatures">
            <div>Received By: <?= e($rows[0]['received_by_name'] ?? '—') ?></div>
            <div>Authorized Signature</div>
        </div>

        <p style="text-align:center; color:#777; font-size:10px; margin-top:16px;">
            This is a computer-generated receipt. Printed on <?= e($printedAt) ?>.
        </p>
    </div>
</body>

</html>