<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;

/**
 * Classes Management module (MODULE_PERMISSIONS['classes']). Mirrors the
 * Akkhor look already used by every other module (see app/Views/layouts/app.php
 * and app/Views/students for the same card/table/toolbar conventions):
 * a searchable/filterable/sortable "All Classes" table with bulk delete,
 * export/print, and separate Add/Edit/Details pages.
 */
class ClassController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('classes');
    }

    public function index(): void
    {
        $classModel = new ClassModel();

        $filters = [
            'name'             => trim((string) $this->input('name', '')),
            'teacher_id'       => $this->input('teacher_id', ''),
            'status'           => $this->input('status', ''),
            'academic_year_id' => $this->input('academic_year_id', ''),
            'shift'            => $this->input('shift', ''),
        ];
        $search = trim((string) $this->input('search', ''));
        $sort = $this->input('sort', 'display_order');
        $direction = $this->input('direction', 'ASC');

        $result = $classModel->paginateWithJoins($this->currentPage(), 10, $filters, $search, $sort, $direction);

        // Section / student counts for the rows currently on screen.
        $sectionModel = new Section();
        $studentModel = new Student();
        foreach ($result['data'] as &$row) {
            $row['sections_count'] = count($sectionModel->forClass((int) $row['id']));
            $row['students_count'] = $studentModel->countByClass((int) $row['id']);
        }
        unset($row);

        $this->view('classes/index', [
            'pageTitle'       => 'All Classes',
            'result'          => $result,
            'filters'         => $filters,
            'search'          => $search,
            'sort'            => $sort,
            'direction'       => $direction,
            'teachers'        => (new Teacher())->namesList(),
            'academicYears'   => (new AcademicYear())->all('start_date', 'DESC'),
            'stats'           => $this->summaryStats(),
            'errors'          => Session::getErrors(),
        ]);
    }

    public function create(): void
    {
        $this->view('classes/create', [
            'pageTitle'      => 'Add New Class',
            'teachers'       => (new Teacher())->namesList(),
            'academicYears'  => (new AcademicYear())->all('start_date', 'DESC'),
            'nextClassCode'  => next_class_code(),
            'errors'         => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateClass();

        $classModel = new ClassModel();
        $code = trim($data['code']) ?: next_class_code();
        if ($classModel->duplicateCode($code)) {
            $this->failWithErrors(['code' => ['This Class Code is already in use.']], $data, 'classes/create');
        }

        $id = $classModel->insert($this->payload($data, $code));

        log_activity('created_record', "Created class #{$id}: {$data['name']}");
        $this->flashSuccess("Class added successfully. Class ID: {$id}, Code: {$code}.");

        if ($this->input('save_and_add_another')) {
            $this->redirect(url('classes/create'));
            return;
        }
        $this->redirect(url('classes'));
    }

    /** Class Details page — /classes/{id}. */
    public function show(string $id): void
    {
        $classModel = new ClassModel();
        $class = $classModel->findActive((int) $id);
        if (!$class) {
            $this->flashError('Class not found.');
            $this->redirect(url('classes'));
            return;
        }

        $sections = (new Section())->forClass((int) $id);
        $students = (new Student())->raw(
            'SELECT s.*, sec.name AS section_name FROM students s LEFT JOIN sections sec ON sec.id = s.section_id
             WHERE s.class_id = :cid AND s.status = "active" ORDER BY sec.name ASC, s.roll_number ASC',
            ['cid' => (int) $id]
        );
        $subjects = (new Subject())->forClass((int) $id);

        $this->view('classes/show', [
            'pageTitle' => 'Class Details',
            'class'     => $class,
            'sections'  => $sections,
            'students'  => $students,
            'subjects'  => $subjects,
        ]);
    }

    public function edit(string $id): void
    {
        $classModel = new ClassModel();
        $class = $classModel->findActive((int) $id);
        if (!$class) {
            $this->flashError('Class not found.');
            $this->redirect(url('classes'));
            return;
        }

        $this->view('classes/edit', [
            'pageTitle'     => 'Edit Class',
            'class'         => $class,
            'teachers'      => (new Teacher())->namesList(),
            'academicYears' => (new AcademicYear())->all('start_date', 'DESC'),
            'errors'        => Session::getErrors(),
        ]);
    }

    public function update(string $id): void
    {
        $classId = (int) $id;
        $classModel = new ClassModel();
        $class = $classModel->findActive($classId);
        if (!$class) {
            $this->flashError('Class not found.');
            $this->redirect(url('classes'));
            return;
        }

        $data = $this->validateClass($classId);

        $code = trim($data['code']) ?: $class['code'];
        if ($classModel->duplicateCode($code, $classId)) {
            $this->failWithErrors(['code' => ['This Class Code is already in use.']], $data, 'classes/' . $classId . '/edit');
        }

        $classModel->update($classId, $this->payload($data, $code, true));

        log_activity('updated_record', "Updated class #{$classId}: {$data['name']}");
        $this->flashSuccess("Class updated successfully. Class ID: {$classId}.");
        $this->redirect(url('classes'));
    }

    public function destroy(string $id): void
    {
        $classId = (int) $id;
        $classModel = new ClassModel();
        $class = $classModel->findActive($classId);
        if (!$class) {
            $this->flashError('Class not found.');
            $this->redirect(url('classes'));
            return;
        }

        try {
            $classModel->delete($classId);
            log_activity('deleted_record', "Deleted class #{$classId}: {$class['name']}");
            $this->flashSuccess("Class deleted successfully. Deleted Class ID: {$classId}.");
        } catch (\Throwable $e) {
            error_log('[CLASS DELETE ERROR] ' . $e->getMessage());
            $this->flashError('This class is in use elsewhere (students, sections, subjects, or exams) and cannot be deleted. Mark it Inactive instead.');
        }
        $this->redirect(url('classes'));
    }

    public function bulkDestroy(): void
    {
        $ids = $this->input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            $this->flashError('Select at least one class to delete.');
            $this->redirect(url('classes'));
            return;
        }

        try {
            $deleted = (new ClassModel())->deleteMany($ids);
            log_activity('deleted_record', "Bulk deleted {$deleted} class(es).");
            $this->flashSuccess("{$deleted} class(es) deleted successfully.");
        } catch (\Throwable $e) {
            error_log('[CLASS BULK DELETE ERROR] ' . $e->getMessage());
            $this->flashError('Some of the selected classes are in use elsewhere and could not be deleted.');
        }
        $this->redirect(url('classes'));
    }

    public function print(): void
    {
        $filters = $this->currentFilters();
        $rows = (new ClassModel())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $this->viewRaw('classes/print', [
            'pageTitle' => 'All Classes', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A'),
        ]);
    }

    public function exportExcel(): void
    {
        $filters = $this->currentFilters();
        $rows = (new ClassModel())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $headers = ['Class ID', 'Class Name', 'Class Code', 'Class Teacher', 'Room Number', 'Capacity', 'Shift', 'Academic Year', 'Status', 'Created Date'];
        $csvRows = array_map(fn ($r) => [
            $r['id'], $r['name'], $r['code'], $r['teacher_name'] ?? '—', $r['room_number'] ?: '—',
            $r['capacity'] ?: '—', ucfirst($r['shift']), $r['academic_year_label'] ?? '—',
            ucfirst($r['status']), format_date($r['created_at']),
        ], $rows);

        log_activity('exported_record', 'Exported classes to CSV/Excel.');
        csv_download('classes-' . date('Ymd-His') . '.csv', $headers, $csvRows);
    }

    public function exportPdf(): void
    {
        $filters = $this->currentFilters();
        $rows = (new ClassModel())->allWithJoins($filters, trim((string) $this->input('search', '')));

        ob_start();
        $this->viewRaw('classes/print', ['pageTitle' => 'All Classes', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A')]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            log_activity('exported_record', 'Exported classes to PDF.');
            $dompdf->stream('classes-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Dependency not installed yet (composer install pending) — fall back to the printable HTML.
        echo $html;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function currentFilters(): array
    {
        return [
            'name'             => trim((string) $this->input('name', '')),
            'teacher_id'       => $this->input('teacher_id', ''),
            'status'           => $this->input('status', ''),
            'academic_year_id' => $this->input('academic_year_id', ''),
            'shift'            => $this->input('shift', ''),
        ];
    }

    private function summaryStats(): array
    {
        return [
            'total_classes'  => (new ClassModel())->activeCount(),
            'total_sections' => (new Section())->activeCount(),
            'total_students' => (new Student())->countByStatus('active'),
            'total_teachers' => (new Teacher())->countByStatus('active'),
        ];
    }

    private function payload(array $data, string $code, bool $isUpdate = false): array
    {
        $payload = [
            'name'                => trim($data['name']),
            'code'                => $code,
            'class_teacher_id'    => $data['class_teacher_id'] !== '' ? (int) $data['class_teacher_id'] : null,
            'room_number'         => trim((string) ($data['room_number'] ?? '')) ?: null,
            'capacity'            => $data['capacity'] !== '' ? (int) $data['capacity'] : null,
            'shift'               => $data['shift'] ?: 'morning',
            'academic_year_id'    => $data['academic_year_id'] !== '' ? (int) $data['academic_year_id'] : null,
            'subject_group'       => trim((string) ($data['subject_group'] ?? '')) ?: null,
            'class_monitor_name'  => trim((string) ($data['class_monitor_name'] ?? '')) ?: null,
            'start_time'          => trim((string) ($data['start_time'] ?? '')) ?: null,
            'end_time'            => trim((string) ($data['end_time'] ?? '')) ?: null,
            'description'         => trim((string) ($data['description'] ?? '')) ?: null,
            'status'              => !empty($data['status']) ? $data['status'] : 'active',
        ];
        if (!$isUpdate) {
            $payload['display_order'] = 0;
            $payload['created_by'] = Auth::id();
        }
        $payload['updated_by'] = Auth::id();
        return $payload;
    }

    private function validateClass(?int $classId = null): array
    {
        $rules = [
            'name'              => 'required|max:100',
            'code'              => 'nullable|max:20',
            'class_teacher_id'  => 'required',
            'room_number'       => 'nullable|max:50',
            'capacity'          => 'nullable|numeric',
            'shift'             => 'nullable|in:morning,day,evening',
            'academic_year_id'  => 'required',
            'subject_group'     => 'nullable|max:100',
            'class_monitor_name' => 'nullable|max:150',
            'description'       => 'nullable|max:1000',
            'status'            => 'nullable|in:active,inactive',
        ];
        return $this->validate($rules, [], $this->all());
    }

    private function failWithErrors(array $errors, array $oldInput, string $backTo): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url($backTo));
        exit;
    }
}
