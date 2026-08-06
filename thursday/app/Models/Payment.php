<?php

namespace App\Models;

use App\Core\Model;
use App\Core\NepaliDate;
use App\Core\DuplicatePaymentException;

class Payment extends Model
{
    protected string $table = 'payments';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'fee_id', 'student_id', 'amount', 'discount_amount', 'fine_amount',
        'payment_mode', 'reference_number', 'internal_ref_no', 'receipt_number', 'receipt_group',
        'received_by', 'paid_at', 'paid_at_bs', 'notes', 'status', 'idempotency_key',
    ];

    protected array $searchable = ['receipt_number', 'reference_number'];

    public const PAYMENT_MODES = [
        'cash'          => 'Cash',
        'upi'           => 'UPI',
        'bank_transfer' => 'Bank Transfer',
        'online'        => 'Online',
        'cheque'        => 'Cheque',
        'card'          => 'Card',
    ];

    /** Top matches for the topbar Global Search — receipt/reference number, student name joined. */
    public function searchGlobal(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $t = '%' . $term . '%';
        $sql = "SELECT p.*, s.full_name AS student_name
                FROM payments p
                LEFT JOIN students s ON s.id = p.student_id
                WHERE p.receipt_number LIKE :t1 OR p.reference_number LIKE :t2
                ORDER BY p.paid_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['t1' => $t, 't2' => $t]);
    }

    /** Most recent payments, with student name joined in. */
    public function recent(int $limit = 5): array
    {
        $sql = "SELECT p.*, s.full_name AS student_name
                FROM payments p
                LEFT JOIN students s ON s.id = p.student_id
                ORDER BY p.paid_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    public function forStudent(int $studentId): array
    {
        return $this->raw(
            "SELECT p.*, ft.name AS fee_type_name, u.full_name AS received_by_name, f.status AS fee_status
             FROM payments p
             LEFT JOIN fees f ON f.id = p.fee_id
             LEFT JOIN fee_types ft ON ft.id = f.fee_type_id
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.student_id = :sid
             ORDER BY p.paid_at DESC",
            ['sid' => $studentId]
        );
    }

    public function totalCollected(?string $from = null, ?string $to = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status = "completed"';
        $params = [];
        if ($from) {
            $sql .= ' AND paid_at >= :from';
            $params['from'] = $from;
        }
        if ($to) {
            $sql .= ' AND paid_at <= :to';
            $params['to'] = $to;
        }
        $row = $this->raw($sql, $params);
        return (float) ($row[0]['total'] ?? 0);
    }

    public function totalCollectedToday(): float
    {
        return $this->totalCollected(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
    }

    /**
     * Paginated, student-joined payment listing for the Payment Report page.
     * Supports the full filter set: a free-text student search (name or
     * admission number), class, section, a single date, receipt number,
     * and payment status — any combination, all optional.
     *
     * @param array{q?:string,class_id?:int,section_id?:int,date?:string,receipt_number?:string,status?:string} $filters
     */
    public function paginatedReport(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildReportWhere($filters);

        $joinSql = "FROM payments p
                     LEFT JOIN students s ON s.id = p.student_id
                     LEFT JOIN classes c ON c.id = s.class_id
                     LEFT JOIN sections sec ON sec.id = s.section_id
                     {$whereSql}";

        $total = (int) ($this->raw("SELECT COUNT(*) AS c {$joinSql}", $params)[0]['c'] ?? 0);

        $sql = "SELECT p.*, s.full_name AS student_name, s.admission_number,
                       c.name AS class_name, sec.name AS section_name
                {$joinSql}
                ORDER BY p.paid_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql, $params);

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'last_page'   => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Shared WHERE-builder for paginatedReport()/dailyCollectionTotals()/etc. so every filtered report agrees on what each filter means. */
    private function buildReportWhere(array $filters): array
    {
        $where = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(s.full_name LIKE :q1 OR s.admission_number LIKE :q2)';
            $params['q1'] = $params['q2'] = "%{$q}%";
        }
        if (!empty($filters['student_id'])) {
            $where[] = 's.id = :student_id';
            $params['student_id'] = (int) $filters['student_id'];
        }
        if (!empty($filters['class_id'])) {
            $where[] = 's.class_id = :class_id';
            $params['class_id'] = (int) $filters['class_id'];
        }
        if (!empty($filters['section_id'])) {
            $where[] = 's.section_id = :section_id';
            $params['section_id'] = (int) $filters['section_id'];
        }
        if (!empty($filters['date'])) {
            $where[] = 'p.paid_at >= :from AND p.paid_at <= :to';
            $params['from'] = $filters['date'] . ' 00:00:00';
            $params['to'] = $filters['date'] . ' 23:59:59';
        }
        if (!empty($filters['receipt_number'])) {
            $where[] = 'p.receipt_number LIKE :receipt';
            $params['receipt'] = '%' . trim((string) $filters['receipt_number']) . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$whereSql, $params];
    }

    /**
     * Daily Collection Report — every payment posted on one calendar day
     * (defaults to today), plus the running total, broken down by payment
     * mode so the front-desk cash/UPI/bank split is visible at a glance.
     */
    public function dailyCollection(string $date): array
    {
        $rows = $this->raw(
            "SELECT p.*, s.full_name AS student_name, s.admission_number,
                    c.name AS class_name, sec.name AS section_name,
                    ft.name AS fee_type_name, u.full_name AS received_by_name
             FROM payments p
             LEFT JOIN students s ON s.id = p.student_id
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN fees f ON f.id = p.fee_id
             LEFT JOIN fee_types ft ON ft.id = f.fee_type_id
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.paid_at >= :from AND p.paid_at <= :to AND p.status = 'completed'
             ORDER BY p.paid_at ASC",
            ['from' => $date . ' 00:00:00', 'to' => $date . ' 23:59:59']
        );

        $byMode = [];
        $total = 0.0;
        foreach ($rows as $r) {
            $mode = $r['payment_mode'];
            $byMode[$mode] = ($byMode[$mode] ?? 0) + (float) $r['amount'];
            $total += (float) $r['amount'];
        }

        return ['rows' => $rows, 'total' => $total, 'by_mode' => $byMode, 'count' => count($rows)];
    }

    /**
     * Class-wise Fee Report — for every class (optionally one section),
     * how much has been assigned, collected, and is still outstanding,
     * built straight off the `fees` ledger rather than payments, so a
     * class with fees assigned but nothing paid yet still shows up.
     */
    public function classWiseSummary(?int $academicYearId = null): array
    {
        $where = ["s.status != 'graduated'"];
        $params = [];
        if ($academicYearId) {
            $where[] = 's.academic_year_id = :ay';
            $params['ay'] = $academicYearId;
        }
        $whereSql = implode(' AND ', $where);

        return $this->raw(
            "SELECT c.id AS class_id, c.name AS class_name,
                    COUNT(DISTINCT s.id) AS student_count,
                    COALESCE(SUM(f.amount_due), 0) AS total_due,
                    COALESCE(SUM(f.amount_paid), 0) AS total_paid,
                    COALESCE(SUM(f.amount_due - f.amount_paid), 0) AS total_balance
             FROM classes c
             LEFT JOIN students s ON s.class_id = c.id AND {$whereSql}
             LEFT JOIN fees f ON f.student_id = s.id
             GROUP BY c.id, c.name
             ORDER BY c.display_order ASC",
            $params
        );
    }

    public function nextReceiptNumber(): string
    {
        return (new ReceiptSequence())->next();
    }

    /** Next auto-generated payment Reference No. — 001, 002, 003, ... one per receipt (never per fee-head row). */
    public function nextReferenceNumber(): string
    {
        return (new ReferenceSequence())->next();
    }

    /**
     * The institutional bill number for a receipt_group — e.g. "2083/001".
     * Assigned once, the first time anyone opens/downloads the bill for that
     * group, then reused on every later view so reprinting never changes it.
     */
    public function billNumberForGroup(string $group): string
    {
        $existing = $this->raw(
            'SELECT bill_number FROM payments WHERE receipt_group = :g AND bill_number IS NOT NULL LIMIT 1',
            ['g' => $group]
        );
        if (!empty($existing[0]['bill_number'])) {
            return $existing[0]['bill_number'];
        }

        $billNumber = (new BillSequence())->next();
        $this->db->query(
            'UPDATE payments SET bill_number = :b WHERE receipt_group = :g',
            ['b' => $billNumber, 'g' => $group]
        );
        return $billNumber;
    }

    /** True if a payment was already recorded with this idempotency key (double-submit guard). */
    public function idempotencyKeyUsed(string $key): bool
    {
        return (bool) $this->findBy('idempotency_key', $key);
    }

    /** Every payment row sharing one receipt_group — the printable receipt's line items. */
    public function forReceiptGroup(string $group): array
    {
        return $this->raw(
            "SELECT p.*, ft.name AS fee_type_name, f.month, f.term_id, t.name AS term_name,
                    u.full_name AS received_by_name
             FROM payments p
             LEFT JOIN fees f ON f.id = p.fee_id
             LEFT JOIN fee_types ft ON ft.id = f.fee_type_id
             LEFT JOIN academic_terms t ON t.id = f.term_id
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.receipt_group = :g
             ORDER BY p.id ASC",
            ['g' => $group]
        );
    }

    /**
     * Record a payment against a single fee row inside a transaction:
     * insert the payment, then ask Fee to recalculate amount_paid/status.
     * Rejects a reused receipt_number or idempotency_key outright, so a
     * double-submit (double-click, browser back-button + resubmit) can
     * never record the same money twice.
     */
    public function record(array $data): int
    {
        if (!empty($data['receipt_number']) && $this->findBy('receipt_number', $data['receipt_number'])) {
            throw new \RuntimeException('This receipt number has already been used.');
        }
        if (!empty($data['idempotency_key']) && $this->idempotencyKeyUsed($data['idempotency_key'])) {
            throw new DuplicatePaymentException('This payment was already submitted.');
        }

        $this->db->beginTransaction();
        try {
            $paidAt = $data['paid_at'] ?? date('Y-m-d H:i:s');
            $id = $this->insert([
                'fee_id'           => (int) $data['fee_id'],
                'student_id'       => (int) $data['student_id'],
                'amount'           => (float) $data['amount'],
                'discount_amount'  => (float) ($data['discount_amount'] ?? 0),
                'fine_amount'      => (float) ($data['fine_amount'] ?? 0),
                'payment_mode'     => $data['payment_mode'],
                'reference_number' => $data['reference_number'] ?? null,
                'internal_ref_no'  => $data['internal_ref_no'] ?? null,
                'receipt_number'   => $data['receipt_number'] ?? $this->nextReceiptNumber(),
                'receipt_group'    => $data['receipt_group'] ?? ($data['receipt_number'] ?? $this->nextReceiptNumber()),
                'received_by'      => $data['received_by'] ?? null,
                'paid_at'          => $paidAt,
                'paid_at_bs'       => $this->safeBs($paidAt),
                'notes'            => $data['notes'] ?? null,
                'status'           => 'completed',
                'idempotency_key'  => $data['idempotency_key'] ?? null,
            ]);

            (new Fee())->recalculateStatus((int) $data['fee_id']);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Void (soft-cancel) a payment: never deletes the row — keeps the full
     * audit trail — just flips status to 'cancelled' and recalculates the
     * parent fee's amount_paid/status so the balance becomes due again.
     */
    public function void(int $paymentId, int $voidedBy, string $reason): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['status'] === 'cancelled') {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->update($paymentId, [
                'status'      => 'cancelled',
                'voided_at'   => date('Y-m-d H:i:s'),
                'voided_by'   => $voidedBy,
                'void_reason' => $reason,
            ]);
            (new Fee())->recalculateStatus((int) $payment['fee_id']);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function safeBs(?string $datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }
        try {
            return NepaliDate::adToBsString(substr($datetime, 0, 10));
        } catch (\Throwable $e) {
            return null;
        }
    }
}