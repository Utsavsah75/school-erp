<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Marks;
use App\Models\Notice;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function index(): void
    {
        $role = Auth::role();

        match ($role) {
            ROLE_STUDENT => $this->studentDashboard(),
            ROLE_PARENT  => $this->parentDashboard(),
            default      => $this->staffDashboard(),
        };
    }

    /** Admin / Principal / Teacher / Accountant / Librarian / Receptionist dashboard. */
    private function staffDashboard(): void
    {
        $role = Auth::role();
        $userId = Auth::id();

        $studentModel = new Student();
        $teacherModel = new Teacher();
        $paymentModel = new Payment();
        $examModel = new Exam();
        $attendanceModel = new Attendance();
        $eventModel = new Event();
        $noticeModel = new Notice();
        $notificationModel = new Notification();
        $feeModel = new Fee();
        $libraryFineModel = new \App\Models\LibraryFinePayment();
        $bookIssueModel = new \App\Models\BookIssue();

        // "Collected Today" / "Outstanding Fees" cover the school's whole
        // cash position, so library fines — collected via the same receipt
        // ledger as general fees — are folded into both totals rather than
        // shown as a separate, easy-to-miss number.
        $stats = [
            'total_students'   => $studentModel->countByStatus('active'),
            'total_teachers'   => $teacherModel->countByStatus('active'),
            'collected_today'  => $paymentModel->totalCollectedToday() + $libraryFineModel->totalCollectedToday(),
            'outstanding_fees' => $feeModel->totalDue() + $bookIssueModel->totalOutstandingFines(),
        ];

        $data = [
            'pageTitle'        => 'Dashboard',
            'stats'            => $stats,
            'recentStudents'   => $studentModel->recent(5),
            'recentPayments'   => $paymentModel->recent(5),
            'upcomingExams'    => $examModel->upcoming(5),
            'attendanceToday'  => $attendanceModel->summaryForDate(date('Y-m-d')),
            'upcomingEvents'   => $eventModel->upcoming(6),
            'recentNotices'    => $role ? $noticeModel->visibleTo($role, 5) : [],
            'notifications'    => $userId ? $notificationModel->forUser($userId, 5) : [],
            'unreadCount'      => $userId ? $notificationModel->unreadCount($userId) : 0,
        ];

        $this->view('dashboard/index', $data);
    }

    /** Dashboard shown when the logged-in user's role is "student". */
    private function studentDashboard(): void
    {
        $userId = Auth::id();
        $studentModel = new Student();
        $student = $studentModel->findBy('user_id', $userId);

        if (!$student) {
            $this->view('dashboard/no-record', [
                'pageTitle' => 'Dashboard',
                'message'   => 'Your account is not yet linked to a student record. Please contact the school office.',
            ]);
            return;
        }

        $classModel = new ClassModel();
        $sectionModel = new Section();
        $parentModel = new ParentModel();

        $class = $classModel->find($student['class_id']);
        $section = $sectionModel->find($student['section_id']);
        $parent = $student['parent_id'] ? $parentModel->find($student['parent_id']) : null;

        $examModel = new Exam();
        $feeModel = new Fee();
        $eventModel = new Event();
        $noticeModel = new Notice();
        $marksModel = new Marks();
        $attendanceModel = new Attendance();

        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');

        $data = [
            'pageTitle'   => 'My Dashboard',
            'student'     => $student,
            'class'       => $class,
            'section'     => $section,
            'parent'      => $parent,
            'stats'       => [
                'upcoming_exams'  => count($examModel->upcomingForClass($student['class_id'], 50)),
                'due_fees'        => $feeModel->dueTotalForStudent($student['id']),
                'upcoming_events' => count($eventModel->upcoming(50)),
                'attendance_pct'  => $attendanceModel->studentPercentage($student['id'], $monthStart, $today),
            ],
            'notices'     => $noticeModel->visibleTo(ROLE_STUDENT, 5),
            'examResults' => $marksModel->forStudent($student['id'], 10),
        ];

        $this->view('dashboard/student', $data);
    }

    /** Dashboard shown when the logged-in user's role is "parent". */
    private function parentDashboard(): void
    {
        $userId = Auth::id();
        $parentModel = new ParentModel();
        $parent = $parentModel->findBy('user_id', $userId);

        if (!$parent) {
            $this->view('dashboard/no-record', [
                'pageTitle' => 'Dashboard',
                'message'   => 'Your account is not yet linked to a parent record. Please contact the school office.',
            ]);
            return;
        }

        $children = $parentModel->children($parent['id']);

        $searchQuery = trim((string) $this->input('q', ''));
        if ($searchQuery !== '') {
            $children = array_values(array_filter(
                $children,
                fn($c) => stripos($c['full_name'], $searchQuery) !== false
            ));
        }

        $feeModel = new Fee();
        $paymentModel = new \App\Models\Payment();
        $examModel = new Exam();
        $marksModel = new Marks();
        $attendanceModel = new Attendance();
        $noticeModel = new Notice();
        $notificationModel = new Notification();
        $homeworkModel = new \App\Models\Homework();
        $timetableModel = new \App\Models\TimetableSlot();
        $examTimetableModel = new \App\Models\ExamTimetable();
        $transportModel = new \App\Models\StudentTransport();
        $eventModel = new Event();
        $leaveModel = new \App\Models\StudentLeaveRequest();
        $messageModel = new \App\Models\Message();

        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');

        $totalDue = 0.0;
        $totalPaid = 0.0;
        $upcomingExamCount = 0;
        $resultsPublished = 0;
        $attendanceSum = 0.0;
        $pendingHomework = 0;

        $feesByChild = [];
        $paymentsByChild = [];
        $resultsByChild = [];
        $attendancePctByChild = [];
        $attendanceHistoryByChild = [];
        $attendanceMonthlyByChild = [];
        $homeworkByChild = [];
        $timetableByChild = [];
        $examScheduleByChild = [];
        $transportByChild = [];

        foreach ($children as $child) {
            $due = $feeModel->dueTotalForStudent($child['id']);
            $totalDue += $due;
            $feesByChild[$child['id']] = $feeModel->forStudent($child['id']);
            $paymentsByChild[$child['id']] = $paymentModel->where(['student_id' => $child['id']], 'paid_at', 'DESC');
            foreach ($paymentsByChild[$child['id']] as $p) {
                $totalPaid += (float) $p['amount'];
            }

            $upcomingExamCount += count($examModel->upcomingForClass($child['class_id'], 50));

            $childResults = $marksModel->forStudent($child['id'], 20);
            $resultsByChild[$child['id']] = $childResults;
            $resultsPublished += count($childResults);

            $pct = $attendanceModel->studentPercentage($child['id'], $monthStart, $today);
            $attendancePctByChild[$child['id']] = $pct;
            $attendanceSum += $pct;
            $attendanceHistoryByChild[$child['id']] = $attendanceModel->history($child['id'], date('Y-m-d', strtotime('-60 days')), $today, 30);
            $attendanceMonthlyByChild[$child['id']] = $attendanceModel->monthlySummary($child['id'], date('Y-m'));

            $hw = $homeworkModel->forStudent($child['class_id'], $child['section_id'], $child['id'], 30);
            $homeworkByChild[$child['id']] = $hw;
            foreach ($hw as $item) {
                if (empty($item['submission_status']) || $item['submission_status'] === 'pending') {
                    $pendingHomework++;
                }
            }

            $timetableByChild[$child['id']] = $timetableModel->forClassSection($child['class_id'], $child['section_id']);
            $examScheduleByChild[$child['id']] = $examTimetableModel->forClass($child['class_id'], 20);
            $transportByChild[$child['id']] = $transportModel->forStudent($child['id']);
        }

        $childCount = max(1, count($children));

        $data = [
            'pageTitle'            => 'My Dashboard',
            'parent'               => $parent,
            'children'             => $children,
            'feesByChild'          => $feesByChild,
            'paymentsByChild'      => $paymentsByChild,
            'resultsByChild'       => $resultsByChild,
            'attendancePctByChild' => $attendancePctByChild,
            'attendanceHistoryByChild' => $attendanceHistoryByChild,
            'attendanceMonthlyByChild' => $attendanceMonthlyByChild,
            'homeworkByChild'      => $homeworkByChild,
            'timetableByChild'     => $timetableByChild,
            'examScheduleByChild'  => $examScheduleByChild,
            'transportByChild'     => $transportByChild,
            'stats'                => [
                'due_fees'          => $totalDue,
                'total_paid'        => $totalPaid,
                'upcoming_exams'    => $upcomingExamCount,
                'results_published' => $resultsPublished,
                'attendance_pct'    => count($children) ? round($attendanceSum / $childCount, 1) : 0.0,
                'pending_homework'  => $pendingHomework,
                'children_count'    => count($children),
            ],
            'searchQuery'     => $searchQuery,
            'notices'         => $searchQuery !== ''
                ? array_values(array_filter($noticeModel->visibleTo(ROLE_PARENT, 50), fn($n) => stripos($n['title'], $searchQuery) !== false || stripos($n['body'], $searchQuery) !== false))
                : $noticeModel->visibleTo(ROLE_PARENT, 5),
            'upcomingEvents'  => $eventModel->upcoming(6),
            'notifications'   => $notificationModel->forUser($userId, 10),
            'unreadCount'     => $notificationModel->unreadCount($userId),
            'myLeaveRequests' => $leaveModel->forParent($parent['id'], 10),
            'myMessages'      => $messageModel->forUser($userId, 20),
            'teachersByChild' => array_combine(
                array_column($children, 'id'),
                array_map(
                    fn($c) => $messageModel->teachersForClassSection($c['class_id'], $c['section_id']),
                    $children
                )
            ),
        ];

        $this->view('dashboard/parent', $data);
    }
}
