<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\NepaliDate;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Section;
use App\Models\Student;

/**
 * Bulk Fee Assignment (MODULE_PERMISSIONS['fees']). Filters by session /
 * class / section / fee type / month / term / fee date / due date, then
 * assigns the selected fee type to every matched student in one go —
 * see app/Views/fees/bulk-assign.php.
 *
 * Business rules (Admission/Annual once per session, Tuition/Hostel/
 * Transport once per month, no duplicate assignment) live in
 * Fee::assignOne()/assignBulk() so this controller stays a thin
 * request/response layer.
 */
class FeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('fees');
    }

    /** Fee Due Report — paginated list of students with any outstanding balance. */
    public function dueReport(): void
    {
        $result = (new Fee())->duePaginated($this->currentPage(), 20);

        $this->view('fees/due', [
            'pageTitle' => 'Outstanding Fees',
            'result'    => $result,
        ]);
    }

    public function bulkAssignForm(): void
    {
        $this->view('fees/bulk-assign', [
            'pageTitle'      => 'Bulk Fee Assignment',
            'academicYears'  => (new AcademicYear())->all('id', 'DESC'),
            'classes'        => (new ClassModel())->all('display_order'),
            'feeTypes'       => (new FeeType())->active(),
            'today_bs'       => NepaliDate::todayBsString(),
        ]);
    }

    /** AJAX: sections for a class (class -> section cascade). */
    public function sectionsForClass(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $this->json((new Section())->forClass($classId));
    }

    /** AJAX: terms for an academic year. */
    public function termsForYear(): void
    {
        $yearId = (int) $this->input('academic_year_id', 0);
        $this->json((new AcademicTerm())->forYear($yearId));
    }

    /**
     * AJAX: "Fetch Students" — returns matching students plus, for each,
     * whether this exact fee assignment already exists (so the Preview
     * table can show it disabled/checked-off rather than let the user
     * attempt an assignment that will just get skipped).
     */
    public function fetchStudents(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = $this->input('section_id', '');
        $feeTypeId = (int) $this->input('fee_type_id', 0);
        $academicYearId = (int) $this->input('academic_year_id', 0);
        $termId = $this->input('term_id', '') !== '' ? (int) $this->input('term_id') : null;
        $month = $this->input('month', '') !== '' ? (int) $this->input('month') : null;

        if (!$classId || !$feeTypeId || !$academicYearId) {
            $this->json(['error' => 'Session, Class and Fee Type are required.'], 422);
            return;
        }

        $feeType = (new FeeType())->find($feeTypeId);
        if (!$feeType) {
            $this->json(['error' => 'Fee type not found.'], 404);
            return;
        }

        $studentModel = new Student();
        $students = $sectionId !== ''
            ? $studentModel->byClassSection($classId, (int) $sectionId)
            : $studentModel->raw('SELECT * FROM students WHERE class_id = :cid AND status = "active" ORDER BY roll_number ASC', ['cid' => $classId]);

        $feeModel = new Fee();
        $structureAmount = (new FeeStructure())->lookup($academicYearId, $classId, $feeTypeId, $feeType['recurrence_type'] === 'monthly' ? null : $termId);
        $defaultAmount = $structureAmount ?? (float) $feeType['default_amount'];

        $rows = [];
        foreach ($students as $s) {
            $alreadyAssigned = $feeType['recurrence_type'] === 'monthly'
                ? $feeModel->exists((int) $s['id'], $feeTypeId, $academicYearId, null, $month)
                : $feeModel->exists((int) $s['id'], $feeTypeId, $academicYearId, in_array($feeType['category'], FeeType::ONE_TIME_ONLY_CATEGORIES, true) ? null : $termId, null);

            $rows[] = [
                'id'               => $s['id'],
                'full_name'        => $s['full_name'],
                'admission_number' => $s['admission_number'],
                'roll_number'      => $s['roll_number'],
                'amount'           => $defaultAmount,
                'already_assigned' => $alreadyAssigned,
            ];
        }

        $this->json([
            'fee_type'        => $feeType,
            'default_amount'  => $defaultAmount,
            'students'        => $rows,
        ]);
    }

    /** "Assign to Selected" / "Assign to All" — persists the bulk assignment. */
    public function bulkAssignStore(): void
    {
        $feeTypeId = (int) $this->input('fee_type_id', 0);
        $academicYearId = (int) $this->input('academic_year_id', 0);
        $termId = $this->input('term_id', '') !== '' ? (int) $this->input('term_id') : null;
        $month = $this->input('month', '') !== '' ? (int) $this->input('month') : null;
        $feeDateBs = trim((string) $this->input('fee_date_bs', ''));
        $dueDateBs = trim((string) $this->input('due_date_bs', ''));
        $studentAmounts = $this->input('amounts', []); // ['student_id' => amount, ...]

        $feeType = (new FeeType())->find($feeTypeId);
        if (!$feeType || !$academicYearId || empty($studentAmounts) || !is_array($studentAmounts)) {
            $this->flashError('Select a session, fee type, and at least one student before assigning.');
            $this->redirect(url('fees/bulk-assign'));
            return;
        }

        $feeDate = $feeDateBs !== '' ? $this->bsToAdSafe($feeDateBs) : date('Y-m-d');
        $dueDate = $dueDateBs !== '' ? $this->bsToAdSafe($dueDateBs) : null;

        $commonParams = [
            'academic_year_id' => $academicYearId,
            'term_id'          => $termId,
            'month'            => $month,
            'fee_date'         => $feeDate,
            'due_date'         => $dueDate,
            'assigned_by'      => Auth::id(),
        ];

        $feeModel = new Fee();
        $assigned = 0;
        $skipped = 0;
        foreach ($studentAmounts as $studentId => $amount) {
            $result = $feeModel->assignOne($feeType, array_merge($commonParams, [
                'student_id' => (int) $studentId,
                'amount'     => (float) $amount,
            ]));
            $result['created'] ? $assigned++ : $skipped++;
        }

        log_activity('fee_bulk_assigned', "Assigned '{$feeType['name']}' to {$assigned} student(s), {$skipped} skipped (already assigned).");

        if ($assigned > 0) {
            $this->flashSuccess("{$feeType['name']} assigned to {$assigned} student(s)." . ($skipped ? " {$skipped} already had this fee and were skipped." : ''));
        } else {
            $this->flashError("No fees were assigned — all {$skipped} selected student(s) already had this fee for the selected period.");
        }
        $this->redirect(url('fees/bulk-assign'));
    }

    private function bsToAdSafe(string $bsDate): ?string
    {
        try {
            return NepaliDate::bsStringToAd($bsDate);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
