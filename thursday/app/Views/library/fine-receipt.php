<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Library Fine Receipt <?= e($payment['receipt_number']) ?></title>
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
    <div class="print-bar">
        <button onclick="window.print()"><?= e(t('print')) ?></button>
    </div>

    <div class="receipt-box">
        <div class="school-header">
            <h1><?= e(config('app.name', 'School ERP')) ?></h1>
            <div class="addr">Library Fine Receipt</div>
        </div>

        <div class="receipt-title">LIBRARY FINE RECEIPT</div>

        <table class="meta-table">
            <tr>
                <td class="label">Receipt No.</td>
                <td><strong><?= e($payment['receipt_number']) ?></strong></td>
                <td class="label">Date</td>
                <td><?= e(format_datetime($payment['paid_at'])) ?></td>
            </tr>
            <tr>
                <td class="label">Borrower</td>
                <td><?= e($payment['student_name'] ?? $payment['teacher_name'] ?? '—') ?></td>
                <td class="label">ID No.</td>
                <td><?= e($payment['admission_number'] ?? $payment['employee_number'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">Payment Mode</td>
                <td><?= e(tr_const(PAYMENT_MODES, $payment['payment_mode'])) ?></td>
                <td class="label">Reference No.</td>
                <td><?= e($payment['reference_number'] ?: '—') ?></td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= e(t('th_book')) ?></th>
                    <th><?= e(t('th_due_date')) ?></th>
                    <th class="amount">Fine Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?= e($payment['book_title']) ?></td>
                    <td><?= e(format_date($payment['due_date'])) ?></td>
                    <td class="amount"><?= e(format_currency($payment['amount'], '')) ?></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="3" style="text-align:right;">Total Paid</td>
                    <td class="amount"><?= e(format_currency($payment['amount'])) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="words">In Words: <?= e(amount_in_words((float) $payment['amount'])) ?></div>

        <div class="signatures">
            <div>Received By: <?= e($payment['received_by_name'] ?? '—') ?></div>
            <div>Authorized Signature</div>
        </div>

        <p style="text-align:center; color:#777; font-size:10px; margin-top:16px;">
            This is a computer-generated receipt. Printed on <?= e(date('d M Y, h:i A')) ?>.
        </p>
    </div>
</body>

</html>