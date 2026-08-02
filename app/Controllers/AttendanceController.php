<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;

/**
 * Individual Student Attendance module (MODULE_PERMISSIONS['attendance']).
 * index() lets the admin/teacher find a student (by class/section/name);
 * show() renders that one student's monthly attendance calendar, summary
 * and a print-friendly sheet — see app/Views/attendance/index.php and
 * app/Views/attendance/show.php.
 */
class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('attendance');
    }

    public function index(): void
    {
        $classId = $this->input('class_id', '');
        $sectionId = $this->input('section_id', '');
        $search = trim((string) $this->input('search', ''));

        $students = [];
        if ($classId && $sectionId) {
            $students = (new Student())->byClassSection((int) $classId, (int) $sectionId);
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $students = array_values(array_filter($students, function ($s) use ($needle) {
                    return str_contains(mb_strtolower($s['full_name']), $needle)
                        || str_contains(mb_strtolower((string) $s['roll_number']), $needle);
                }));
            }
        }

        $this->view('attendance/index', [
            'pageTitle' => 'Student Attendance',
            'classes'   => (new ClassModel())->all('display_order'),
            'sections'  => (new Section())->all('name'),
            'classId'   => $classId,
            'sectionId' => $sectionId,
            'search'    => $search,
            'students'  => $students,
            'recentActivity' => (new Attendance())->recentActivity(10),
        ]);
    }

    /**
     * AJAX: students in a class+section, optionally filtered by name/roll
     * number — powers the Student Selector's searchable autocomplete
     * dropdown (app/Views/attendance/index.php).
     */
    public function searchStudents(string $classId, string $sectionId): void
    {
        $students = (new Student())->byClassSection((int) $classId, (int) $sectionId);

        $q = trim((string) $this->input('q', ''));
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $students = array_values(array_filter($students, function ($s) use ($needle) {
                return str_contains(mb_strtolower($s['full_name']), $needle)
                    || str_contains(mb_strtolower((string) $s['roll_number']), $needle);
            }));
        }

        $this->json(array_map(fn ($s) => [
            'id'          => $s['id'],
            'full_name'   => $s['full_name'],
            'roll_number' => $s['roll_number'],
            'photo_url'   => upload_url($s['photo_path'] ?? null),
        ], array_slice($students, 0, 20)));
    }

    // ------------------------------------------------------------------
    // Mark Attendance — bulk grid for a class/section/date
    // ------------------------------------------------------------------

    /**
     * GET /attendance/mark — pick class/section/date, then shows every
     * active student in that section with a Present/Absent/Late/Leave
     * selector. If attendance already exists for that date it's
     * pre-filled, so opening this again for the same day is how editing
     * works (no separate edit screen needed).
     */
    public function markForm(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);
        $date = trim((string) $this->input('date', date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $students = [];
        $existing = [];
        if ($classId && $sectionId) {
            $students = (new Student())->byClassSection($classId, $sectionId);
            foreach ((new Attendance())->forClassSectionDate($classId, $sectionId, $date) as $row) {
                $existing[(int) $row['student_id']] = $row;
            }
        }

        $this->view('attendance/mark', [
            'pageTitle' => 'Mark Attendance',
            'classes' => (new ClassModel())->activeList(),
            'sections' => (new Section())->activeList(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'date' => $date,
            'students' => $students,
            'existing' => $existing,
            'recentActivity' => (new Attendance())->recentActivity(10),
        ]);
    }

    /** POST /attendance/mark — save the grid submitted by markForm(). */
    public function storeMark(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);
        $date = trim((string) $this->input('date', ''));
        $statuses = (array) $this->input('status', []);
        $remarks = (array) $this->input('remarks', []);

        if (!$classId || !$sectionId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->flashError('Please choose a class, section and date before saving attendance.');
            $this->back();
            return;
        }

        $entries = [];
        foreach ($statuses as $studentId => $status) {
            $entries[(int) $studentId] = (string) $status;
        }

        $count = (new Attendance())->markBulk($classId, $sectionId, $date, $entries, Auth::id(), $remarks);

        $classRow = (new ClassModel())->find($classId);
        $sectionRow = (new Section())->find($sectionId);
        $label = trim((string) ($classRow['name'] ?? '') . ' ' . (string) ($sectionRow['name'] ?? ''));
        log_activity('attendance_marked', "Marked attendance for {$count} student(s) — {$label} on {$date}.");

        $this->flashSuccess("Attendance saved for {$count} student(s) on {$date}.");
        $this->redirect(url('attendance/mark?class_id=' . $classId . '&section_id=' . $sectionId . '&date=' . $date));
    }

    // ------------------------------------------------------------------
    // Reports — monthly / yearly, with search, filter and export
    // ------------------------------------------------------------------

    /**
     * GET /attendance/reports — filterable list (date range, class,
     * section, student) plus a monthly-summary or yearly-summary view
     * when a single student is selected.
     */
    public function reports(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);
        $studentId = (int) $this->input('student_id', 0);
        $range = $this->input('range', 'monthly') === 'yearly' ? 'yearly' : 'monthly';
        $month = trim((string) $this->input('month', date('Y-m')));
        $year = trim((string) $this->input('year', date('Y')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        if (!preg_match('/^\d{4}$/', $year)) {
            $year = date('Y');
        }

        if ($range === 'yearly') {
            $from = $year . '-01-01';
            $to = $year . '-12-31';
        } else {
            $from = $month . '-01';
            $to = date('Y-m-t', strtotime($from));
        }

        $attendanceModel = new Attendance();
        $rows = (new Student())->count() > 0
            ? $attendanceModel->reportRows($from, $to, $classId, $sectionId, $studentId)
            : [];

        $yearlySummary = null;
        if ($range === 'yearly' && $studentId) {
            $yearlySummary = $attendanceModel->yearlySummary($studentId, $year);
        }

        $this->view('attendance/reports', [
            'pageTitle' => 'Attendance Reports',
            'classes' => (new ClassModel())->activeList(),
            'sections' => (new Section())->activeList(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'studentId' => $studentId,
            'range' => $range,
            'month' => $month,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'yearlySummary' => $yearlySummary,
            'recentActivity' => $attendanceModel->recentActivity(10),
        ]);
    }

    /** GET /attendance/reports/export/{format} — csv|pdf, same filters as reports(). */
    public function exportReport(string $format): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);
        $studentId = (int) $this->input('student_id', 0);
        $from = trim((string) $this->input('from', date('Y-m-01')));
        $to = trim((string) $this->input('to', date('Y-m-t')));

        $rows = (new Attendance())->reportRows($from, $to, $classId, $sectionId, $studentId);
        $headers = ['Date', 'Roll No.', 'Student', 'Class', 'Section', 'Status', 'Remarks'];
        $tableRows = array_map(static fn($r) => [
            format_date($r['date']), (string) ($r['roll_number'] ?? ''), (string) $r['student_name'],
            (string) ($r['class_name'] ?? ''), (string) ($r['section_name'] ?? ''),
            ucfirst($r['status']), (string) ($r['remarks'] ?? ''),
        ], $rows);

        if (function_exists('log_activity')) {
            log_activity('exported_record', 'Exported attendance report to ' . strtoupper($format) . '.');
        }

        if ($format === 'pdf') {
            $this->exportAttendancePdf('Attendance Report', $headers, $tableRows);
            return;
        }

        csv_download('attendance_report_' . date('Ymd_His') . '.csv', $headers, $tableRows);
    }

    private function exportAttendancePdf(string $title, array $headers, array $rows): void
    {
        ob_start();
        echo '<h4>' . e($title) . '</h4><table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;font-size:12px">';
        echo '<thead><tr>';
        foreach ($headers as $h) {
            echo '<th>' . e($h) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . e((string) $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream(strtolower(str_replace(' ', '_', $title)) . '.pdf', ['Attachment' => true]);
            return;
        }

        echo $html;
    }

    public function show(string $id): void
    {
        $studentId = (int) $id;
        $student = (new Student())->find($studentId);
        if (!$student) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $yearMonth = trim((string) $this->input('month', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $yearMonth = date('Y-m');
        }

        $attendanceModel = new Attendance();
        $dayStatuses = $attendanceModel->dayStatusesForMonth($studentId, $yearMonth);
        $summary = $attendanceModel->monthlySummary($studentId, $yearMonth);

        $daysInMonth = (int) date('t', strtotime($yearMonth . '-01'));
        $workingDays = array_sum($summary) - $summary['holiday'];
        $percentage = $workingDays > 0 ? round(($summary['present'] / $workingDays) * 100, 1) : 0.0;

        $className = '';
        $sectionName = '';
        if (!empty($student['class_id'])) {
            $class = (new ClassModel())->find((int) $student['class_id']);
            $className = $class['name'] ?? '';
        }
        if (!empty($student['section_id'])) {
            $section = (new Section())->find((int) $student['section_id']);
            $sectionName = $section['name'] ?? '';
        }

        $this->view('attendance/show', [
            'pageTitle'   => 'Attendance — ' . $student['full_name'],
            'student'     => $student,
            'className'   => $className,
            'sectionName' => $sectionName,
            'yearMonth'   => $yearMonth,
            'daysInMonth' => $daysInMonth,
            'dayStatuses' => $dayStatuses,
            'summary'     => $summary,
            'workingDays' => $workingDays,
            'percentage'  => $percentage,
        ]);
    }
}
