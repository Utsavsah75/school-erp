<?php

namespace App\Models;

use App\Core\Model;
use App\Core\NepaliDate;

/**
 * A single assigned fee (one fee_type charged to one student for one
 * academic_year, optionally scoped to a term or a month). Business rules
 * around duplicate assignment (see FeeType::ONE_TIME_ONLY_CATEGORIES /
 * MONTHLY_CATEGORIES) are enforced in assignOne()/assignBulk() rather
 * than relied on purely from the DB unique key, since MySQL treats each
 * NULL in a unique index as distinct — two one-time fees (term_id AND
 * month both NULL) would otherwise slip past it.
 */
class Fee extends Model
{
    protected string $table = 'fees';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'student_id', 'fee_type_id', 'academic_year_id', 'term_id', 'month',
        'fee_date', 'fee_date_bs', 'due_date', 'due_date_bs',
        'amount_due', 'amount_paid', 'discount_amount', 'fine_amount', 'tax_amount',
        'payable_amount', 'status', 'assigned_by', 'notes',
    ];

    public function totalDue(): float
    {
        $row = $this->raw('SELECT COALESCE(SUM(payable_amount - amount_paid),0) AS total FROM fees WHERE status NOT IN ("paid","cancelled")');
        return (float) ($row[0]['total'] ?? 0);
    }

    /**
     * Paginated Fee Due Report: one row per student with any outstanding balance,
     * with their total owed. Backs the dashboard's "Outstanding Fees" card.
     */
    public function duePaginated(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $countRow = $this->raw(
            "SELECT COUNT(*) AS c FROM (
                SELECT f.student_id FROM fees f
                WHERE f.status NOT IN ('paid','cancelled')
                GROUP BY f.student_id
            ) t"
        );
        $total = (int) ($countRow[0]['c'] ?? 0);

        $sql = "SELECT s.id AS student_id, s.full_name, s.admission_number,
                       c.name AS class_name, sec.name AS section_name,
                       SUM(f.payable_amount - f.amount_paid) AS balance_due
                FROM fees f
                JOIN students s ON s.id = f.student_id
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN sections sec ON sec.id = s.section_id
                WHERE f.status NOT IN ('paid','cancelled')
                GROUP BY f.student_id, s.id, s.full_name, s.admission_number, c.name, sec.name
                ORDER BY balance_due DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql);

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'last_page'   => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function dueTotalForStudent(int $studentId): float
    {
        $row = $this->raw(
            'SELECT COALESCE(SUM(payable_amount - amount_paid),0) AS total FROM fees WHERE student_id = :sid AND status NOT IN ("paid","cancelled")',
            ['sid' => $studentId]
        );
        return (float) ($row[0]['total'] ?? 0);
    }

    /**
     * All fees for one student, joined with fee type / term / year names — used by the
     * Student Profile page and the Parent Dashboard. (Kept separate from
     * ledgerForStudent() so callers that just want "every fee for this student" don't
     * need to know about the academic-year filter.)
     */
    public function forStudent(int $studentId, int $limit = 50): array
    {
        $sql = "SELECT f.*, ft.name AS fee_type_name, ft.category, ft.recurrence_type,
                       t.name AS term_name, ay.label AS academic_year_label
                FROM fees f
                JOIN fee_types ft ON ft.id = f.fee_type_id
                LEFT JOIN academic_terms t ON t.id = f.term_id
                LEFT JOIN academic_years ay ON ay.id = f.academic_year_id
                WHERE f.student_id = :sid
                ORDER BY f.fee_date DESC, f.id DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql, ['sid' => $studentId]);
    }

    /** Full fee ledger for one student — the table the Payment Collection screen renders. */
    public function ledgerForStudent(int $studentId, ?int $academicYearId = null): array
    {
        $sql = "SELECT f.*, ft.name AS fee_type_name, ft.category, ft.recurrence_type,
                       t.name AS term_name, ay.label AS academic_year_label
                FROM fees f
                JOIN fee_types ft ON ft.id = f.fee_type_id
                LEFT JOIN academic_terms t ON t.id = f.term_id
                LEFT JOIN academic_years ay ON ay.id = f.academic_year_id
                WHERE f.student_id = :sid";
        $params = ['sid' => $studentId];
        if ($academicYearId !== null) {
            $sql .= ' AND f.academic_year_id = :ay';
            $params['ay'] = $academicYearId;
        }
        $sql .= ' ORDER BY f.month IS NULL, f.month ASC, f.fee_date ASC, f.id ASC';
        return $this->raw($sql, $params);
    }

    /** Specific ledger rows by id (for a student), joined — used by the "Print Selected" fee-heads statement. */
    public function rowsByIds(int $studentId, array $feeIds): array
    {
        $feeIds = array_values(array_filter(array_map('intval', $feeIds)));
        if (!$feeIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($feeIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT f.*, ft.name AS fee_type_name, ft.category, ft.recurrence_type, t.name AS term_name
             FROM fees f
             JOIN fee_types ft ON ft.id = f.fee_type_id
             LEFT JOIN academic_terms t ON t.id = f.term_id
             WHERE f.student_id = ? AND f.id IN ({$placeholders})
             ORDER BY f.month IS NULL, f.month ASC, f.fee_date ASC, f.id ASC"
        );
        $stmt->execute(array_merge([$studentId], $feeIds));
        return $stmt->fetchAll();
    }

    /** Running totals for the ledger header (Total / Balance / Paid). */
    public function ledgerTotals(int $studentId, ?int $academicYearId = null): array
    {
        $sql = "SELECT
                    COALESCE(SUM(payable_amount),0) AS total,
                    COALESCE(SUM(amount_paid),0) AS paid,
                    COALESCE(SUM(payable_amount - amount_paid),0) AS balance
                FROM fees WHERE student_id = :sid AND status != 'cancelled'";
        $params = ['sid' => $studentId];
        if ($academicYearId !== null) {
            $sql .= ' AND academic_year_id = :ay';
            $params['ay'] = $academicYearId;
        }
        $row = $this->raw($sql, $params);
        return [
            'total'   => (float) ($row[0]['total'] ?? 0),
            'paid'    => (float) ($row[0]['paid'] ?? 0),
            'balance' => (float) ($row[0]['balance'] ?? 0),
        ];
    }

    /** Does this student already have a (non-cancelled) fee for this type/year/term/month? */
    public function exists(int $studentId, int $feeTypeId, int $academicYearId, ?int $termId, ?int $month): bool
    {
        $sql = 'SELECT id FROM fees WHERE student_id = :sid AND fee_type_id = :ftid AND academic_year_id = :ay
                AND status != "cancelled"
                AND ' . ($termId === null ? 'term_id IS NULL' : 'term_id = :tid')
              . ' AND ' . ($month === null ? 'month IS NULL' : 'month = :m');
        $params = ['sid' => $studentId, 'ftid' => $feeTypeId, 'ay' => $academicYearId];
        if ($termId !== null) {
            $params['tid'] = $termId;
        }
        if ($month !== null) {
            $params['m'] = $month;
        }
        return !empty($this->raw($sql, $params));
    }

    /**
     * Assign one fee to one student, honouring the recurrence business rules.
     * Returns ['created' => bool, 'reason' => string|null, 'id' => int|null].
     */
    public function assignOne(array $feeType, array $params): array
    {
        $studentId = (int) $params['student_id'];
        $academicYearId = (int) $params['academic_year_id'];
        $termId = $params['term_id'] !== null ? (int) $params['term_id'] : null;
        $month = $params['month'] !== null ? (int) $params['month'] : null;

        $isOneTimeOnly = in_array($feeType['category'], FeeType::ONE_TIME_ONLY_CATEGORIES, true);
        $isMonthly = $feeType['recurrence_type'] === 'monthly';

        // Business rule: Admission Fee / Annual Fee charged only once per session —
        // check without term/month so a second attempt in any term is caught.
        if ($isOneTimeOnly && $this->exists($studentId, (int) $feeType['id'], $academicYearId, null, null)) {
            return ['created' => false, 'reason' => 'already_assigned_this_session', 'id' => null];
        }

        // Business rule: monthly-recurring fees (Tuition/Monthly/Hostel/Transport)
        // need a month, and can only be charged once per month.
        if ($isMonthly) {
            if ($month === null) {
                return ['created' => false, 'reason' => 'month_required', 'id' => null];
            }
            if ($this->exists($studentId, (int) $feeType['id'], $academicYearId, null, $month)) {
                return ['created' => false, 'reason' => 'already_assigned_this_month', 'id' => null];
            }
            $termId = null; // monthly fees are keyed by month, not term
        } elseif ($this->exists($studentId, (int) $feeType['id'], $academicYearId, $termId, null)) {
            return ['created' => false, 'reason' => 'already_assigned', 'id' => null];
        }

        $amountDue = (float) $params['amount'];
        $feeDate = $params['fee_date'] ?? date('Y-m-d');
        $dueDate = $params['due_date'] ?? null;

        try {
            $id = $this->insert([
                'student_id'       => $studentId,
                'fee_type_id'      => (int) $feeType['id'],
                'academic_year_id' => $academicYearId,
                'term_id'          => $isMonthly ? null : $termId,
                'month'            => $isMonthly ? $month : null,
                'fee_date'         => $feeDate,
                'fee_date_bs'      => $this->safeBs($feeDate),
                'due_date'         => $dueDate,
                'due_date_bs'      => $dueDate ? $this->safeBs($dueDate) : null,
                'amount_due'       => $amountDue,
                'amount_paid'      => 0,
                'discount_amount'  => 0,
                'fine_amount'      => 0,
                'tax_amount'       => 0,
                'payable_amount'   => $amountDue,
                'status'           => 'unpaid',
                'assigned_by'      => $params['assigned_by'] ?? null,
                'notes'            => $params['notes'] ?? null,
            ]);
            return ['created' => true, 'reason' => null, 'id' => $id];
        } catch (\Throwable $e) {
            // DB unique key caught a race/edge case the app-level check missed.
            error_log('[FEE ASSIGN ERROR] ' . $e->getMessage());
            return ['created' => false, 'reason' => 'duplicate', 'id' => null];
        }
    }

    /**
     * Assign one fee type to many students in one go (Bulk Fee Assignment).
     * @param int[] $studentIds
     * @return array{assigned:int, skipped:int, skipped_details:array}
     */
    public function assignBulk(array $feeType, array $studentIds, array $commonParams): array
    {
        $assigned = 0;
        $skipped = 0;
        $skippedDetails = [];

        foreach ($studentIds as $studentId) {
            $result = $this->assignOne($feeType, array_merge($commonParams, ['student_id' => $studentId]));
            if ($result['created']) {
                $assigned++;
            } else {
                $skipped++;
                $skippedDetails[] = ['student_id' => $studentId, 'reason' => $result['reason']];
            }
        }

        return ['assigned' => $assigned, 'skipped' => $skipped, 'skipped_details' => $skippedDetails];
    }

    /** Apply a discount to a fee row: recalculates payable_amount and logs to fee_discounts. */
    public function applyDiscount(int $feeId, string $type, float $value, ?string $reason, ?int $givenBy): float
    {
        $fee = $this->find($feeId);
        if (!$fee) {
            throw new \RuntimeException('Fee not found.');
        }
        $amount = $type === 'percentage' ? round((float) $fee['amount_due'] * $value / 100, 2) : $value;

        (new FeeDiscount())->insert([
            'fee_id' => $feeId, 'type' => $type, 'value' => $value, 'amount' => $amount,
            'reason' => $reason, 'given_by' => $givenBy,
        ]);

        $newDiscountTotal = (float) $fee['discount_amount'] + $amount;
        $this->recalculatePayable($feeId, ['discount_amount' => $newDiscountTotal]);

        return $amount;
    }

    /** Apply a fine to a fee row: recalculates payable_amount and logs to fee_fines. */
    public function applyFine(int $feeId, string $type, float $rate, bool $isManual, ?string $notes): float
    {
        $fee = $this->find($feeId);
        if (!$fee) {
            throw new \RuntimeException('Fee not found.');
        }

        $amount = match ($type) {
            'percentage' => round((float) $fee['amount_due'] * $rate / 100, 2),
            'daily'      => round($rate * max(0, $this->daysOverdue($fee)), 2),
            default      => $rate,
        };

        (new FeeFine())->insert([
            'fee_id' => $feeId, 'type' => $type, 'rate' => $rate, 'amount' => $amount,
            'applied_on' => date('Y-m-d'), 'is_manual' => $isManual ? 1 : 0, 'notes' => $notes,
        ]);

        $newFineTotal = (float) $fee['fine_amount'] + $amount;
        $this->recalculatePayable($feeId, ['fine_amount' => $newFineTotal]);

        return $amount;
    }

    private function daysOverdue(array $fee): int
    {
        if (empty($fee['due_date'])) {
            return 0;
        }
        $due = strtotime($fee['due_date']);
        $today = strtotime(date('Y-m-d'));
        return $due && $today > $due ? (int) floor(($today - $due) / 86400) : 0;
    }

    /** Recompute payable_amount = amount_due - discount + fine + tax, then refresh status. */
    public function recalculatePayable(int $feeId, array $overrides = []): void
    {
        $fee = $this->find($feeId);
        if (!$fee) {
            return;
        }
        $discount = $overrides['discount_amount'] ?? (float) $fee['discount_amount'];
        $fine = $overrides['fine_amount'] ?? (float) $fee['fine_amount'];
        $tax = $overrides['tax_amount'] ?? (float) $fee['tax_amount'];
        $payable = round((float) $fee['amount_due'] - $discount + $fine + $tax, 2);

        $this->update($feeId, [
            'discount_amount' => $discount,
            'fine_amount'     => $fine,
            'tax_amount'      => $tax,
            'payable_amount'  => $payable,
        ]);

        $this->recalculateStatus($feeId);
    }

    /** Recalculate amount_paid + status for a fee row from its payments (call after recording a payment). */
    public function recalculateStatus(int $feeId): void
    {
        $fee = $this->find($feeId);
        if (!$fee) {
            return;
        }
        $row = $this->raw(
            'SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE fee_id = :fid AND status = "completed"',
            ['fid' => $feeId]
        );
        $paid = (float) ($row[0]['total'] ?? 0);
        $status = $fee['status'] === 'cancelled' ? 'cancelled' : 'unpaid';
        if ($status !== 'cancelled') {
            if ($paid >= (float) $fee['payable_amount'] && (float) $fee['payable_amount'] > 0) {
                $status = 'paid';
            } elseif ($paid > 0) {
                $status = 'partial';
            }
        }
        $this->update($feeId, ['amount_paid' => $paid, 'status' => $status]);
    }

    /** BS conversion that degrades to null instead of throwing on out-of-range/blank dates. */
    private function safeBs(?string $adDate): ?string
    {
        if (empty($adDate)) {
            return null;
        }
        try {
            return NepaliDate::adToBsString($adDate);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
