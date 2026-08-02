<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DuplicatePaymentException;
use App\Core\NepaliDate;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Fee;
use App\Models\FeeDiscount;
use App\Models\FeeFine;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;

/**
 * Fee Collection / Payment Collection screen (MODULE_PERMISSIONS['payments'])
 * — search a student, see their full fee ledger (Fee Head / Month / Term /
 * Fee / Concession / Fine / Payable / Paid / Balance / Paying), enter
 * amounts against one or more rows, and post them all as one transaction
 * with a single printable/PDF receipt. See app/Views/payments/collect.php.
 */
class PaymentController extends Controller
{
    /** Roles allowed to void a completed payment — a stricter check than the general 'payments' module gate. */
    private const VOID_ROLES = [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL];

    public function __construct()
    {
        $this->authorizeModule('payments');
    }

    /** Payment Report — paginated, filterable list of all payments (student, admission no., class, section, date, receipt no., status). */
    public function index(): void
    {
        $filters = [
            'q'              => trim((string) $this->input('q', '')),
            'student_id'     => (int) $this->input('student_id', 0),
            'class_id'       => (int) $this->input('class_id', 0),
            'section_id'     => (int) $this->input('section_id', 0),
            'date'           => trim((string) $this->input('date', '')),
            'receipt_number' => trim((string) $this->input('receipt_number', '')),
            'status'         => trim((string) $this->input('status', '')),
        ];
        // Drop empty filters so paginate_links() doesn't carry ?q=&class_id=0... into every link.
        $activeFilters = array_filter($filters, static fn($v) => $v !== '' && $v !== 0);

        $result = (new Payment())->paginatedReport($this->currentPage(), 20, $filters);

        $this->view('payments/index', [
            'pageTitle'     => 'Payment Report',
            'result'        => $result,
            'filters'       => $filters,
            'activeFilters' => $activeFilters,
            'classes'       => (new ClassModel())->all('display_order'),
            'sections'      => $filters['class_id'] ? (new Section())->forClass($filters['class_id']) : [],
            'paymentModes'  => Payment::PAYMENT_MODES,
        ]);
    }

    /**
     * Daily Collection Report — every payment posted on one day, split by
     * payment mode, with a running total. Defaults to today.
     */
    public function dailyCollection(): void
    {
        $date = trim((string) $this->input('date', ''));
        $date = $date !== '' ? $date : date('Y-m-d');

        $report = (new Payment())->dailyCollection($date);

        $this->view('payments/daily-collection', [
            'pageTitle' => 'Daily Collection Report',
            'date'      => $date,
            'report'    => $report,
            'paymentModes' => Payment::PAYMENT_MODES,
        ]);
    }

    /**
     * Class-wise Fee Report — assigned / collected / outstanding, one row
     * per class, straight off the fee ledger (so classes with fees due but
     * nothing paid yet still show up correctly).
     */
    public function classWiseReport(): void
    {
        $academicYearId = (int) $this->input('academic_year_id', 0);

        $this->view('payments/class-wise', [
            'pageTitle'      => 'Class-wise Fee Report',
            'summary'        => (new Payment())->classWiseSummary($academicYearId ?: null),
            'academicYears'  => (new AcademicYear())->all('id', 'DESC'),
            'academicYearId' => $academicYearId,
        ]);
    }

    public function collectForm(): void
    {
        $studentId = (int) $this->input('student_id', 0);
        $student = null;
        $ledger = [];
        $totals = ['total' => 0, 'paid' => 0, 'balance' => 0];
        $paymentHistory = [];

        if ($studentId) {
            $student = (new Student())->withDetails($studentId);
            if ($student) {
                $feeModel = new Fee();
                $ledger = $feeModel->ledgerForStudent($studentId);
                $totals = $feeModel->ledgerTotals($studentId);
                $paymentHistory = (new Payment())->forStudent($studentId);
            }
        }

        $this->view('payments/collect', [
            'pageTitle'      => 'Fee Collection',
            'student'        => $student,
            'ledger'         => $ledger,
            'totals'         => $totals,
            'paymentHistory' => $paymentHistory,
            'academicYears'  => (new AcademicYear())->all('id', 'DESC'),
            'classes'        => (new ClassModel())->all('display_order'),
            'today_bs'       => NepaliDate::todayBsString(),
            'paymentModes'   => Payment::PAYMENT_MODES,
            // Regenerated on every page load — submitted back as a hidden field so a
            // double-click / back-button resubmit of the exact same post is rejected.
            'idempotencyKey' => bin2hex(random_bytes(16)),
            'justPostedGroup' => $this->input('receipt', ''),
        ]);
    }

    /** AJAX: sections for a class (for the left-panel Class -> Section filter). */
    public function sectionsForClass(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $this->json((new Section())->forClass($classId));
    }

    /**
     * AJAX: student search box — matches name, admission number, roll
     * number, the student's own mobile, or the parent/guardian's mobile —
     * optionally narrowed by session/class/section from the filter dropdowns.
     */
    public function searchStudent(): void
    {
        $term = trim((string) $this->input('term', ''));
        $academicYearId = (int) $this->input('academic_year_id', 0);
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);

        $where = ["s.status != 'graduated'"];
        $params = [];

        if ($term !== '') {
            $where[] = '(s.full_name LIKE :t1 OR s.admission_number LIKE :t2 OR s.roll_number LIKE :t3 OR s.phone LIKE :t4 OR p.phone LIKE :t5)';
            $params['t1'] = $params['t2'] = $params['t3'] = $params['t4'] = $params['t5'] = "%{$term}%";
        }
        if ($academicYearId) {
            $where[] = 's.academic_year_id = :ay';
            $params['ay'] = $academicYearId;
        }
        if ($classId) {
            $where[] = 's.class_id = :cid';
            $params['cid'] = $classId;
        }
        if ($sectionId) {
            $where[] = 's.section_id = :sid';
            $params['sid'] = $sectionId;
        }
        if ($term === '' && !$classId) {
            // Neither a search term nor a class filter — too broad, don't dump the whole school.
            $this->json([]);
            return;
        }

        $sql = "SELECT s.id, s.full_name, s.admission_number, s.roll_number, s.class_id, s.section_id,
                       c.name AS class_name, sec.name AS section_name, p.father_name
                FROM students s
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN sections sec ON sec.id = s.section_id
                LEFT JOIN parents p ON p.id = s.parent_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY s.full_name ASC LIMIT 15';
        $this->json((new Student())->raw($sql, $params));
    }

    /** AJAX: refresh just the ledger table for a student (after posting a payment, or on first load). */
    public function ledgerJson(): void
    {
        $studentId = (int) $this->input('student_id', 0);
        if (!$studentId) {
            $this->json(['error' => 'student_id required'], 422);
            return;
        }
        $feeModel = new Fee();
        $this->json([
            'ledger' => $feeModel->ledgerForStudent($studentId),
            'totals' => $feeModel->ledgerTotals($studentId),
        ]);
    }

    /**
     * Post one or more payments in a single submit — the ledger table
     * posts a map of fee_id => paying_amount for every row the user
     * entered a "Paying" amount against. All rows share one receipt_group
     * so a single receipt can be printed listing every fee head paid.
     */
    public function store(): void
    {
        $studentId = (int) $this->input('student_id', 0);
        $payments = $this->input('paying', []); // ['fee_id' => amount, ...]
        $paymentMode = (string) $this->input('payment_mode', 'cash');
        $referenceNumber = trim((string) $this->input('reference_number', ''));
        $remarks = trim((string) $this->input('remarks', ''));
        $paidAtBs = trim((string) $this->input('trans_date_bs', ''));
        $idempotencyKey = trim((string) $this->input('idempotency_key', ''));

        if (!$studentId || empty($payments) || !is_array($payments)) {
            $this->flashError('Enter a paying amount against at least one fee row.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        if (!array_key_exists($paymentMode, Payment::PAYMENT_MODES)) {
            $this->flashError('Select a valid payment mode.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        if ($idempotencyKey !== '' && (new Payment())->idempotencyKeyUsed($idempotencyKey)) {
            // Exact same form submitted twice (double-click / back-button resubmit) — not an error,
            // just don't record it again. Send the cashier back to a clean page.
            $this->flashSuccess('This payment was already recorded — nothing was posted twice.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        $paidAt = $paidAtBs !== '' ? ($this->bsToAdSafe($paidAtBs) ?? date('Y-m-d')) : date('Y-m-d');
        $paidAt .= ' ' . date('H:i:s');

        $paymentModel = new Payment();
        $feeModel = new Fee();
        $count = 0;
        $totalPosted = 0.0;
        $receiptGroup = $paymentModel->nextReceiptNumber();
        // One auto-generated Reference No. per payment transaction (per receipt_group), not
        // per fee-head row — every row in this group gets stamped with the same value.
        $internalRefNo = $paymentModel->nextReferenceNumber();

        try {
            $rowIndex = 0;
            foreach ($payments as $feeId => $amount) {
                $amount = (float) $amount;
                if ($amount <= 0) {
                    continue;
                }
                $fee = $feeModel->find((int) $feeId);
                if (!$fee || (int) $fee['student_id'] !== $studentId || $fee['status'] === 'cancelled') {
                    continue;
                }
                $outstanding = (float) $fee['payable_amount'] - (float) $fee['amount_paid'];
                if ($outstanding <= 0) {
                    continue; // already fully paid — nothing to record for this row
                }
                if ($amount > $outstanding + 0.01) {
                    $amount = $outstanding; // never allow overpayment past the balance for that row
                }

                $rowIndex++;
                $paymentModel->record([
                    'fee_id'           => (int) $feeId,
                    'student_id'       => $studentId,
                    'amount'           => $amount,
                    'payment_mode'     => $paymentMode,
                    'reference_number' => $referenceNumber ?: null,
                    'internal_ref_no'  => $rowIndex === 1 ? $internalRefNo : null,
                    'receipt_number'   => $receiptGroup . ($rowIndex > 1 ? '-' . $rowIndex : ''),
                    'receipt_group'    => $receiptGroup,
                    'received_by'      => Auth::id(),
                    'paid_at'          => $paidAt,
                    'notes'            => $remarks ?: null,
                    // The idempotency key only needs to guard the transaction as a whole,
                    // so it's stamped on the first row and left null on the rest.
                    'idempotency_key'  => ($rowIndex === 1 && $idempotencyKey !== '') ? $idempotencyKey : null,
                ]);
                $count++;
                $totalPosted += $amount;
            }
        } catch (DuplicatePaymentException $e) {
            $this->flashSuccess('This payment was already recorded — nothing was posted twice.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        if ($count > 0) {
            log_activity('payment_recorded', "Recorded payment of Rs. " . number_format($totalPosted, 2) . " for student #{$studentId} across {$count} fee row(s), receipt {$receiptGroup}.");
            $this->flashSuccess("Payment of " . format_currency($totalPosted) . " recorded successfully. Receipt: {$receiptGroup}.");
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId . '&receipt=' . urlencode($receiptGroup));
        } else {
            $this->flashError('No payment was recorded — check the amounts entered (rows already fully paid are skipped).');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
        }
    }

    /** Apply a discount to a single fee row (Fixed / Percentage / Scholarship / Sibling / Staff). */
    public function applyDiscount(): void
    {
        $feeId = (int) $this->input('fee_id', 0);
        $studentId = (int) $this->input('student_id', 0);
        $type = (string) $this->input('type', 'fixed');
        $value = (float) $this->input('value', 0);
        $reason = trim((string) $this->input('reason', ''));

        if (!$feeId || $value <= 0 || !array_key_exists($type, FeeDiscount::TYPES)) {
            $this->flashError('Enter a discount value greater than zero.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        $amount = (new Fee())->applyDiscount($feeId, $type, $value, $reason ?: null, Auth::id());
        log_activity('fee_discount_applied', "Applied " . FeeDiscount::TYPES[$type] . " of Rs. " . number_format($amount, 2) . " to fee #{$feeId}.");
        $this->flashSuccess("Discount of " . format_currency($amount) . " applied.");
        $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
    }

    /** Apply a fine to a single fee row (Fixed / Daily / Percentage). */
    public function applyFine(): void
    {
        $feeId = (int) $this->input('fee_id', 0);
        $studentId = (int) $this->input('student_id', 0);
        $type = (string) $this->input('type', 'fixed');
        $rate = (float) $this->input('rate', 0);
        $notes = trim((string) $this->input('notes', ''));

        if (!$feeId || $rate <= 0 || !array_key_exists($type, FeeFine::TYPES)) {
            $this->flashError('Enter a fine amount/rate greater than zero.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        $amount = (new Fee())->applyFine($feeId, $type, $rate, true, $notes ?: null);
        log_activity('fee_fine_applied', "Applied a fine of Rs. " . number_format($amount, 2) . " to fee #{$feeId}.");
        $this->flashSuccess("Fine of " . format_currency($amount) . " applied.");
        $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
    }

    /** Void (cancel) a completed payment — keeps the row for audit, restores the balance to the fee. */
    public function void(string $id): void
    {
        if (!Auth::hasRole(self::VOID_ROLES)) {
            $this->flashError('You are not authorized to void payments.');
            $this->back();
            return;
        }

        $paymentId = (int) $id;
        $studentId = (int) $this->input('student_id', 0);
        $reason = trim((string) $this->input('reason', ''));

        if ($reason === '') {
            $this->flashError('A reason is required to void a payment.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        $payment = (new Payment())->find($paymentId);
        if (!$payment || (int) $payment['student_id'] !== $studentId) {
            $this->flashError('Payment not found.');
            $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
            return;
        }

        $ok = (new Payment())->void($paymentId, (int) Auth::id(), $reason);
        if ($ok) {
            log_activity('payment_voided', "Voided payment #{$paymentId} (receipt {$payment['receipt_number']}): {$reason}");
            $this->flashSuccess('Payment voided and the balance restored.');
        } else {
            $this->flashError('This payment is already voided.');
        }
        $this->redirect(url('payments/collect') . '?student_id=' . $studentId);
    }

    /** Printable receipt (browser print view) for every payment row sharing one receipt_group. */
    public function receipt(string $group): void
    {
        $rows = (new Payment())->forReceiptGroup($group);
        if (empty($rows)) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }
        $student = (new Student())->withDetails((int) $rows[0]['student_id']);

        $this->viewRaw('payments/receipt', [
            'receiptGroup' => $group,
            'rows'         => $rows,
            'student'      => $student,
            'total'        => array_sum(array_column($rows, 'amount')),
            'printedAt'    => date('d M Y, h:i A'),
        ]);
    }

    /** Same receipt, streamed as a PDF via dompdf. */
    public function receiptPdf(string $group): void
    {
        $rows = (new Payment())->forReceiptGroup($group);
        if (empty($rows)) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }
        $student = (new Student())->withDetails((int) $rows[0]['student_id']);

        ob_start();
        $this->viewRaw('payments/receipt', [
            'receiptGroup' => $group,
            'rows'         => $rows,
            'student'      => $student,
            'total'        => array_sum(array_column($rows, 'amount')),
            'printedAt'    => date('d M Y, h:i A'),
            'forPdf'       => true,
        ]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            log_activity('exported_record', "Downloaded PDF receipt {$group}.");
            $dompdf->stream('receipt-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $group) . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Dependency not installed yet (composer install pending) — fall back to the printable HTML.
        echo $html;
    }

    /**
     * Institutional fee bill (CTEVT/NBPI-style printable bill) for a receipt
     * group. The Bill No. is auto-generated in "{BS year}/{running number}"
     * format (e.g. 2083/001) the first time it's opened, then reused on every
     * later view/download. Every fee head paid under this receipt group is
     * auto-listed as a particular with the total computed automatically —
     * see app/Views/payments/institutional-bill.php.
     */
    public function institutionalBill(string $group): void
    {
        $rows = (new Payment())->forReceiptGroup($group);
        if (empty($rows)) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }
        $student = (new Student())->withDetails((int) $rows[0]['student_id']);

        $this->viewRaw('payments/institutional-bill', [
            'receiptGroup' => $group,
            'billNumber'   => (new Payment())->billNumberForGroup($group),
            'rows'         => $rows,
            'student'      => $student,
            'total'        => array_sum(array_column($rows, 'amount')),
            'billDateBs'   => $rows[0]['paid_at_bs'] ?? $this->adToBsSafe($rows[0]['paid_at'] ?? null),
            'printedAt'    => date('d M Y, h:i A'),
        ]);
    }

    /** Same institutional bill, streamed as a PDF via dompdf. */
    public function institutionalBillPdf(string $group): void
    {
        $rows = (new Payment())->forReceiptGroup($group);
        if (empty($rows)) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }
        $student = (new Student())->withDetails((int) $rows[0]['student_id']);

        ob_start();
        $this->viewRaw('payments/institutional-bill', [
            'receiptGroup' => $group,
            'billNumber'   => (new Payment())->billNumberForGroup($group),
            'rows'         => $rows,
            'student'      => $student,
            'total'        => array_sum(array_column($rows, 'amount')),
            'billDateBs'   => $rows[0]['paid_at_bs'] ?? $this->adToBsSafe($rows[0]['paid_at'] ?? null),
            'printedAt'    => date('d M Y, h:i A'),
            'forPdf'       => true,
        ]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A5', 'portrait');
            $dompdf->render();
            log_activity('exported_record', "Downloaded PDF institutional bill for receipt {$group}.");
            $dompdf->stream('bill-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $group) . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Dependency not installed yet (composer install pending) — fall back to the printable HTML.
        echo $html;
    }

    /** Printable statement for specific fee-ledger rows the user checked via the "Print" column (due slip, not a receipt). */
    public function printLedger(): void
    {
        $studentId = (int) $this->input('student_id', 0);
        $feeIds = array_filter(explode(',', (string) $this->input('fee_ids', '')));
        $student = $studentId ? (new Student())->withDetails($studentId) : null;

        if (!$student || empty($feeIds)) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $rows = (new Fee())->rowsByIds($studentId, $feeIds);

        $this->viewRaw('payments/print-ledger', [
            'student'   => $student,
            'rows'      => $rows,
            'printedAt' => date('d M Y, h:i A'),
        ]);
    }

    private function bsToAdSafe(string $bsDate): ?string
    {
        try {
            return NepaliDate::bsStringToAd($bsDate);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Reverse of bsToAdSafe — used when a payment row has no stored paid_at_bs to fall back on. */
    private function adToBsSafe(?string $adDateTime): ?string
    {
        if (empty($adDateTime)) {
            return null;
        }
        try {
            return NepaliDate::adToBsString(substr($adDateTime, 0, 10));
        } catch (\Throwable $e) {
            return null;
        }
    }
}