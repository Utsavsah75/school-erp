<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ClassModel;
use App\Models\Marks;
use App\Models\Message;
use App\Models\ParentModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLeaveRequest;

/**
 * Parent self-service actions that don't belong on the main dashboard
 * controller. Every method here is scoped to the logged-in parent's own
 * children — Auth::authorize('parent_portal') gates the module, and each
 * method additionally re-checks ownership of the specific student/message
 * being accessed (role alone isn't enough; a parent must only ever see
 * their OWN children's data, never another family's by guessing an id).
 */
class ParentController extends Controller
{
    /** Loads the parent record for the current login, or aborts if not linked / not the right role. */
    private function currentParent(): array
    {
        $this->authorizeModule('parent_portal');

        $parent = (new ParentModel())->findBy('user_id', Auth::id());
        if (!$parent) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
        return $parent;
    }

    /** Confirms $studentId is actually one of this parent's children; returns the student row or aborts. */
    private function ownChildOrAbort(array $parent, int $studentId): array
    {
        $student = (new Student())->find($studentId);
        if (!$student || (int) $student['parent_id'] !== (int) $parent['id']) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
        return $student;
    }

    /** Read-only profile view of one child — the parent-safe equivalent of /students/{id}, which parents can't access. */
    public function child(int $id): void
    {
        $parent = $this->currentParent();
        $student = $this->ownChildOrAbort($parent, $id);

        $class = (new ClassModel())->find($student['class_id']);
        $section = (new Section())->find($student['section_id']);

        $this->view('parents/child-profile', [
            'pageTitle' => $student['full_name'],
            'student'   => $student,
            'class'     => $class,
            'section'   => $section,
        ]);
    }

    /** Streams a PDF report card for one child covering all exams/marks on file. */
    public function reportCard(int $id): void
    {
        $parent = $this->currentParent();
        $student = $this->ownChildOrAbort($parent, $id);

        $class = (new ClassModel())->find($student['class_id']);
        $section = (new Section())->find($student['section_id']);
        $results = (new Marks())->forStudent($student['id'], 100);

        ob_start();
        require dirname(__DIR__) . '/Views/parents/report-card-pdf.php';
        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('report-card-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $student['admission_number']) . '.pdf', ['Attachment' => true]);
        exit;
    }

    /** Parent submits a leave request for one of their children. */
    public function storeLeave(): void
    {
        $parent = $this->currentParent();

        $data = $this->validate([
            'student_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'reason'     => 'required',
        ]);

        $this->ownChildOrAbort($parent, (int) $data['student_id']);

        (new StudentLeaveRequest())->insert([
            'student_id' => (int) $data['student_id'],
            'parent_id'  => $parent['id'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'],
            'status'     => 'pending',
        ]);

        $this->flashSuccess('Leave request submitted.');
        $this->redirect(url('dashboard') . '#communication');
    }

    /** Parent sends a message to one of their children's teachers. */
    public function sendMessage(): void
    {
        $parent = $this->currentParent();

        $data = $this->validate([
            'student_id'   => 'required|integer',
            'recipient_id' => 'required|integer',
            'subject'      => 'required|max:200',
            'body'         => 'required',
        ]);

        $student = $this->ownChildOrAbort($parent, (int) $data['student_id']);

        $messageModel = new Message();
        $eligibleTeachers = $messageModel->teachersForClassSection((int) $student['class_id'], (int) $student['section_id']);
        $recipientIsEligible = in_array((int) $data['recipient_id'], array_column($eligibleTeachers, 'user_id'), true);

        if (!$recipientIsEligible) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }

        $messageModel->insert([
            'sender_id'    => Auth::id(),
            'recipient_id' => (int) $data['recipient_id'],
            'student_id'   => (int) $data['student_id'],
            'subject'      => $data['subject'],
            'body'         => $data['body'],
            'is_read'      => 0,
        ]);

        $this->flashSuccess('Message sent.');
        $this->redirect(url('dashboard') . '#communication');
    }

    /** Marks one of the parent's received messages as read (e.g. when they open it). */
    public function markMessageRead(int $id): void
    {
        $this->currentParent();
        (new Message())->markReadFor($id, Auth::id());
        $this->json(['ok' => true]);
    }
}
