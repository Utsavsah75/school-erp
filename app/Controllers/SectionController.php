<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;

/**
 * Sections Management module (MODULE_PERMISSIONS['sections']). Same
 * conventions as ClassController / SubjectController — see those for the
 * shared Akkhor card/table/toolbar layout.
 */
class SectionController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('sections');
    }

    public function index(): void
    {
        $sectionModel = new Section();

        $filters = [
            'class_id'         => $this->input('class_id', ''),
            'teacher_id'       => $this->input('teacher_id', ''),
            'status'           => $this->input('status', ''),
            'academic_year_id' => $this->input('academic_year_id', ''),
        ];
        $search = trim((string) $this->input('search', ''));
        $sort = $this->input('sort', 'class');
        $direction = $this->input('direction', 'ASC');

        $result = $sectionModel->paginateWithJoins($this->currentPage(), 10, $filters, $search, $sort, $direction);

        $studentModel = new Student();
        foreach ($result['data'] as &$row) {
            $row['students_count'] = $studentModel->countBySection((int) $row['id']);
        }
        unset($row);

        $this->view('sections/index', [
            'pageTitle'     => 'All Sections',
            'result'        => $result,
            'filters'       => $filters,
            'search'        => $search,
            'sort'          => $sort,
            'direction'     => $direction,
            'classes'       => (new ClassModel())->activeList(),
            'teachers'      => (new Teacher())->namesList(),
            'academicYears' => (new AcademicYear())->all('start_date', 'DESC'),
            'stats'         => $this->summaryStats(),
            'errors'        => Session::getErrors(),
        ]);
    }

    public function create(): void
    {
        $this->view('sections/create', [
            'pageTitle'       => 'Add New Section',
            'classes'         => (new ClassModel())->activeList(),
            'teachers'        => (new Teacher())->namesList(),
            'nextSectionCode' => next_section_code(),
            'errors'          => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateSection();

        $sectionModel = new Section();
        $code = trim($data['code']) ?: next_section_code();
        if ($sectionModel->duplicateCode($code)) {
            $this->failWithErrors(['code' => ['This Section Code is already in use.']], $data, 'sections/create');
        }

        $payload = $this->payload($data, $code);
        $payload['created_by'] = Auth::id();
        $id = $sectionModel->insert($payload);

        log_activity('created_record', "Created section #{$id}: {$data['name']}");
        $this->flashSuccess("Section added successfully. Section ID: {$id}, Code: {$code}.");

        if ($this->input('save_and_add_another')) {
            $this->redirect(url('sections/create'));
            return;
        }
        $this->redirect(url('sections'));
    }

    /** Section Details page — /sections/{id}. */
    public function show(string $id): void
    {
        $sectionModel = new Section();
        $section = $sectionModel->findActive((int) $id);
        if (!$section) {
            $this->flashError('Section not found.');
            $this->redirect(url('sections'));
            return;
        }

        $students = (new Student())->bySection((int) $id);

        $this->view('sections/show', [
            'pageTitle' => 'Section Details',
            'section'   => $section,
            'students'  => $students,
        ]);
    }

    public function edit(string $id): void
    {
        $sectionModel = new Section();
        $section = $sectionModel->findActive((int) $id);
        if (!$section) {
            $this->flashError('Section not found.');
            $this->redirect(url('sections'));
            return;
        }

        $this->view('sections/edit', [
            'pageTitle' => 'Edit Section',
            'section'   => $section,
            'classes'   => (new ClassModel())->activeList(),
            'teachers'  => (new Teacher())->namesList(),
            'errors'    => Session::getErrors(),
        ]);
    }

    public function update(string $id): void
    {
        $sectionId = (int) $id;
        $sectionModel = new Section();
        $section = $sectionModel->findActive($sectionId);
        if (!$section) {
            $this->flashError('Section not found.');
            $this->redirect(url('sections'));
            return;
        }

        $data = $this->validateSection($sectionId);

        $code = trim($data['code']) ?: $section['code'];
        if ($sectionModel->duplicateCode($code, $sectionId)) {
            $this->failWithErrors(['code' => ['This Section Code is already in use.']], $data, 'sections/' . $sectionId . '/edit');
        }

        $sectionModel->update($sectionId, $this->payload($data, $code));

        log_activity('updated_record', "Updated section #{$sectionId}: {$data['name']}");
        $this->flashSuccess("Section updated successfully. Section ID: {$sectionId}.");
        $this->redirect(url('sections'));
    }

    public function destroy(string $id): void
    {
        $sectionId = (int) $id;
        $sectionModel = new Section();
        $section = $sectionModel->findActive($sectionId);
        if (!$section) {
            $this->flashError('Section not found.');
            $this->redirect(url('sections'));
            return;
        }

        try {
            $sectionModel->delete($sectionId);
            log_activity('deleted_record', "Deleted section #{$sectionId}: {$section['name']}");
            $this->flashSuccess("Section deleted successfully. Deleted Section ID: {$sectionId}.");
        } catch (\Throwable $e) {
            error_log('[SECTION DELETE ERROR] ' . $e->getMessage());
            $this->flashError('This section is in use elsewhere (students, timetable, or exams) and cannot be deleted. Mark it Inactive instead.');
        }
        $this->redirect(url('sections'));
    }

    public function bulkDestroy(): void
    {
        $ids = $this->input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            $this->flashError('Select at least one section to delete.');
            $this->redirect(url('sections'));
            return;
        }

        try {
            $deleted = (new Section())->deleteMany($ids);
            log_activity('deleted_record', "Bulk deleted {$deleted} section(s).");
            $this->flashSuccess("{$deleted} section(s) deleted successfully.");
        } catch (\Throwable $e) {
            error_log('[SECTION BULK DELETE ERROR] ' . $e->getMessage());
            $this->flashError('Some of the selected sections are in use elsewhere and could not be deleted.');
        }
        $this->redirect(url('sections'));
    }

    public function print(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Section())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $this->viewRaw('sections/print', [
            'pageTitle' => 'All Sections', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A'),
        ]);
    }

    public function exportExcel(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Section())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $headers = ['Section ID', 'Section Name', 'Section Code', 'Class', 'Teacher', 'Room', 'Capacity', 'Status', 'Created Date'];
        $csvRows = array_map(fn ($r) => [
            $r['id'], $r['name'], $r['code'], $r['class_name'] ?? '—', $r['teacher_name'] ?? '—',
            $r['room_number'] ?: '—', $r['capacity'] ?: '—', ucfirst($r['status']), format_date($r['created_at']),
        ], $rows);

        log_activity('exported_record', 'Exported sections to CSV/Excel.');
        csv_download('sections-' . date('Ymd-His') . '.csv', $headers, $csvRows);
    }

    public function exportPdf(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Section())->allWithJoins($filters, trim((string) $this->input('search', '')));

        ob_start();
        $this->viewRaw('sections/print', ['pageTitle' => 'All Sections', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A')]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            log_activity('exported_record', 'Exported sections to PDF.');
            $dompdf->stream('sections-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
            exit;
        }

        echo $html;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function currentFilters(): array
    {
        return [
            'class_id'         => $this->input('class_id', ''),
            'teacher_id'       => $this->input('teacher_id', ''),
            'status'           => $this->input('status', ''),
            'academic_year_id' => $this->input('academic_year_id', ''),
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

    private function payload(array $data, string $code): array
    {
        return [
            'class_id'         => (int) $data['class_id'],
            'name'             => trim($data['name']),
            'code'             => $code,
            'class_teacher_id' => $data['class_teacher_id'] !== '' ? (int) $data['class_teacher_id'] : null,
            'room_number'      => trim((string) ($data['room_number'] ?? '')) ?: null,
            'capacity'         => $data['capacity'] !== '' ? (int) $data['capacity'] : null,
            'shift'            => $data['shift'] ?: 'morning',
            'description'      => trim((string) ($data['description'] ?? '')) ?: null,
            'status'           => !empty($data['status']) ? $data['status'] : 'active',
            'updated_by'       => Auth::id(),
        ];
    }

    private function validateSection(?int $sectionId = null): array
    {
        $rules = [
            'name'             => 'required|max:50',
            'code'             => 'nullable|max:20',
            'class_id'         => 'required',
            'class_teacher_id' => 'nullable',
            'room_number'      => 'nullable|max:50',
            'capacity'         => 'nullable|numeric',
            'shift'            => 'nullable|in:morning,day,evening',
            'description'      => 'nullable|max:1000',
            'status'           => 'nullable|in:active,inactive',
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
