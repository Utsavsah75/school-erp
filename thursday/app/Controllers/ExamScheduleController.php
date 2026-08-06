<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Section;
use App\Models\Subject;

/**
 * Exam Schedule module (MODULE_PERMISSIONS['exam_schedule']). One page:
 * an "Add New Exam" form on the left (with AJAX Class -> Section/Subject
 * dependent dropdowns) and a searchable/filterable/sortable "All Exam
 * Schedule" table with print/export/import/bulk-delete on the right —
 * see app/Views/exam_schedule/index.php.
 */
class ExamScheduleController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('exam_schedule');
    }

    public function index(): void
    {
        $scheduleModel = new ExamSchedule();

        $filters = [
            'class_id'         => $this->input('class_id', ''),
            'section_id'       => $this->input('section_id', ''),
            'subject_id'       => $this->input('subject_id', ''),
            'exam_type_id'     => $this->input('exam_type_id', ''),
            'academic_year_id' => $this->input('academic_year_id', ''),
            'status'           => $this->input('status', ''),
            'date'             => $this->input('date', ''),
        ];
        $search = trim((string) $this->input('search', ''));
        $sort = $this->input('sort', 'exam_date');
        $direction = $this->input('direction', 'DESC');

        $result = $scheduleModel->paginateWithJoins($this->currentPage(), 10, $filters, $search, $sort, $direction);

        $sections = !empty($filters['class_id'])
            ? (new Section())->forClass((int) $filters['class_id'])
            : (new Section())->all('name');
        $subjects = !empty($filters['class_id'])
            ? (new Subject())->forClass((int) $filters['class_id'])
            : (new Subject())->all('name');

        $this->view('exam_schedule/index', [
            'pageTitle'     => 'Exam Schedule',
            'result'        => $result,
            'filters'       => $filters,
            'search'        => $search,
            'sort'          => $sort,
            'direction'     => $direction,
            'classes'       => (new ClassModel())->all('display_order'),
            'sections'      => $sections,
            'subjects'      => $subjects,
            'examTypes'     => (new ExamType())->forDropdown(),
            'academicYears' => (new AcademicYear())->all('start_date', 'DESC'),
            'errors'        => Session::getErrors(),
        ]);
    }

    // ------------------------------------------------------------------
    // AJAX dependent dropdowns
    // ------------------------------------------------------------------

    /** AJAX: sections belonging to a class, for both the create form and the filter bar. */
    public function sectionsForClass(string $classId): void
    {
        $this->json((new Section())->forClass((int) $classId));
    }

    /** AJAX: subjects belonging to a class, for both the create form and the filter bar. */
    public function subjectsForClass(string $classId): void
    {
        $this->json((new Subject())->forClass((int) $classId));
    }

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function store(): void
    {
        $data = $this->validateSchedule();
        $this->guardOverlapAndDuplicate($data);

        $id = (new ExamSchedule())->insert([
            'exam_name'        => trim($data['exam_name']),
            'academic_year_id' => (int) $data['academic_year_id'],
            'exam_type_id'     => (int) $data['exam_type_id'],
            'class_id'         => (int) $data['class_id'],
            'section_id'       => (int) $data['section_id'],
            'subject_id'       => (int) $data['subject_id'],
            'exam_date'        => $data['exam_date'],
            'start_time'       => $data['start_time'],
            'end_time'         => $data['end_time'],
            'room_number'      => trim((string) ($data['room_number'] ?? '')),
            'max_marks'        => (float) $data['max_marks'],
            'passing_marks'    => (float) $data['passing_marks'],
            'status'           => !empty($data['is_active']) || !isset($data['is_active']) ? 'active' : 'inactive',
            'description'      => trim((string) ($data['description'] ?? '')),
            'created_by'       => \App\Core\Auth::id(),
            'updated_by'       => \App\Core\Auth::id(),
        ]);

        log_activity('created_record', "Created exam schedule #{$id}: {$data['exam_name']}");
        $this->flashSuccess("Exam scheduled successfully. Schedule ID: EXS-{$id}.");
        $this->redirect(url('exam-schedule'));
    }

    public function update(string $id): void
    {
        $scheduleId = (int) $id;
        $scheduleModel = new ExamSchedule();
        $existing = $scheduleModel->findActive($scheduleId);
        if (!$existing) {
            $this->flashError('Exam schedule not found.');
            $this->redirect(url('exam-schedule'));
            return;
        }

        $data = $this->validateSchedule();
        $this->guardOverlapAndDuplicate($data, $scheduleId);

        $scheduleModel->update($scheduleId, [
            'exam_name'        => trim($data['exam_name']),
            'academic_year_id' => (int) $data['academic_year_id'],
            'exam_type_id'     => (int) $data['exam_type_id'],
            'class_id'         => (int) $data['class_id'],
            'section_id'       => (int) $data['section_id'],
            'subject_id'       => (int) $data['subject_id'],
            'exam_date'        => $data['exam_date'],
            'start_time'       => $data['start_time'],
            'end_time'         => $data['end_time'],
            'room_number'      => trim((string) ($data['room_number'] ?? '')),
            'max_marks'        => (float) $data['max_marks'],
            'passing_marks'    => (float) $data['passing_marks'],
            'status'           => !empty($data['is_active']) ? 'active' : 'inactive',
            'description'      => trim((string) ($data['description'] ?? '')),
            'updated_by'       => \App\Core\Auth::id(),
        ]);

        log_activity('updated_record', "Updated exam schedule #{$scheduleId}");
        $this->flashSuccess("Exam schedule updated successfully. Schedule ID: EXS-{$scheduleId}.");
        $this->redirect(url('exam-schedule'));
    }

    public function destroy(string $id): void
    {
        $scheduleId = (int) $id;
        $scheduleModel = new ExamSchedule();
        if (!$scheduleModel->findActive($scheduleId)) {
            $this->flashError('Exam schedule not found.');
            $this->redirect(url('exam-schedule'));
            return;
        }

        $scheduleModel->delete($scheduleId);
        log_activity('deleted_record', "Deleted exam schedule #{$scheduleId}");
        $this->flashSuccess("Exam schedule deleted successfully. Deleted Schedule ID: EXS-{$scheduleId}.");
        $this->redirect(url('exam-schedule'));
    }

    public function bulkDestroy(): void
    {
        $ids = $this->input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            $this->flashError('Select at least one exam schedule row to delete.');
            $this->redirect(url('exam-schedule'));
            return;
        }

        $deleted = (new ExamSchedule())->deleteMany($ids);
        log_activity('deleted_record', "Bulk-deleted {$deleted} exam schedule row(s).");
        $this->flashSuccess("{$deleted} exam schedule row(s) deleted successfully.");
        $this->redirect(url('exam-schedule'));
    }

    public function toggleStatus(string $id): void
    {
        $scheduleId = (int) $id;
        if (!(new ExamSchedule())->toggleStatus($scheduleId)) {
            $this->flashError('Exam schedule not found.');
        } else {
            log_activity('updated_record', "Toggled status for exam schedule #{$scheduleId}");
            $this->flashSuccess('Status updated.');
        }
        $this->redirect(url('exam-schedule'));
    }

    // ------------------------------------------------------------------
    // Print / Export / Import
    // ------------------------------------------------------------------

    /** Print-friendly (no layout) view of the currently filtered schedule. */
    public function print(): void
    {
        $filters = [
            'class_id' => $this->input('class_id', ''), 'section_id' => $this->input('section_id', ''),
            'subject_id' => $this->input('subject_id', ''), 'exam_type_id' => $this->input('exam_type_id', ''),
            'academic_year_id' => $this->input('academic_year_id', ''), 'status' => $this->input('status', ''),
            'date' => $this->input('date', ''),
        ];
        $rows = (new ExamSchedule())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $this->viewRaw('exam_schedule/print', [
            'pageTitle' => 'Exam Schedule',
            'rows'      => $rows,
            'printedAt' => date('d M Y, h:i A'),
        ]);
    }

    /** Export the currently filtered schedule as a downloadable spreadsheet (CSV — opens natively in Excel/Sheets). */
    public function exportExcel(): void
    {
        $filters = [
            'class_id' => $this->input('class_id', ''), 'section_id' => $this->input('section_id', ''),
            'subject_id' => $this->input('subject_id', ''), 'exam_type_id' => $this->input('exam_type_id', ''),
            'academic_year_id' => $this->input('academic_year_id', ''), 'status' => $this->input('status', ''),
            'date' => $this->input('date', ''),
        ];
        $rows = (new ExamSchedule())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $headers = ['Exam Name', 'Exam Type', 'Class', 'Section', 'Subject', 'Date', 'Start Time', 'End Time', 'Room', 'Max Marks', 'Passing Marks', 'Status'];
        $csvRows = array_map(fn ($r) => [
            $r['exam_name'], $r['exam_type_name'], $r['class_name'], $r['section_name'], $r['subject_name'],
            format_date($r['exam_date']), $r['start_time'], $r['end_time'], $r['room_number'],
            $r['max_marks'], $r['passing_marks'], ucfirst($r['status']),
        ], $rows);

        log_activity('exported_record', 'Exported exam schedule to CSV/Excel.');
        csv_download('exam-schedule-' . date('Ymd-His') . '.csv', $headers, $csvRows);
    }

    /** Export the currently filtered schedule as a print-ready PDF (via dompdf, if installed). */
    public function exportPdf(): void
    {
        $filters = [
            'class_id' => $this->input('class_id', ''), 'section_id' => $this->input('section_id', ''),
            'subject_id' => $this->input('subject_id', ''), 'exam_type_id' => $this->input('exam_type_id', ''),
            'academic_year_id' => $this->input('academic_year_id', ''), 'status' => $this->input('status', ''),
            'date' => $this->input('date', ''),
        ];
        $rows = (new ExamSchedule())->allWithJoins($filters, trim((string) $this->input('search', '')));

        ob_start();
        $this->viewRaw('exam_schedule/print', [
            'pageTitle' => 'Exam Schedule', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A'),
        ]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            log_activity('exported_record', 'Exported exam schedule to PDF.');
            $dompdf->stream('exam-schedule-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Dependency not installed yet (composer install pending) — fall back to the printable HTML.
        echo $html;
    }

    public function importForm(): void
    {
        $this->view('exam_schedule/import', [
            'pageTitle' => 'Import Exam Schedule',
            'errors'    => Session::getErrors(),
        ]);
    }

    /**
     * Bulk-import from a CSV with header row:
     * exam_name,academic_year,exam_type,class,section,subject,exam_date,start_time,end_time,room_number,max_marks,passing_marks
     * Class/Section/Subject/Exam Type/Academic Year are matched by name — any row that can't be
     * resolved, is a duplicate, or overlaps an existing sitting is skipped and reported back.
     */
    public function import(): void
    {
        $file = $this->file('csv_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flashError('Please choose a valid CSV file to import.');
            $this->redirect(url('exam-schedule/import'));
            return;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->flashError('Could not read the uploaded file.');
            $this->redirect(url('exam-schedule/import'));
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->flashError('The CSV file appears to be empty.');
            $this->redirect(url('exam-schedule/import'));
            return;
        }
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $classModel = new ClassModel();
        $sectionModel = new Section();
        $subjectModel = new Subject();
        $examTypeModel = new ExamType();
        $academicYearModel = new AcademicYear();
        $scheduleModel = new ExamSchedule();

        $classesByName = array_column($classModel->all(), null, 'name');
        $typesByName = array_column($examTypeModel->forDropdown(), null, 'name');
        $yearsByLabel = array_column($academicYearModel->all(), null, 'label');

        $imported = 0;
        $skipped = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $r = array_combine($header, array_pad($row, count($header), null));
            if (empty($r['exam_name']) || empty($r['class']) || empty($r['section']) || empty($r['subject'])) {
                $skipped[] = "Row {$rowNum}: missing required field(s).";
                continue;
            }

            $class = $classesByName[trim($r['class'])] ?? null;
            $examType = $typesByName[trim($r['exam_type'] ?? '')] ?? null;
            $year = $yearsByLabel[trim($r['academic_year'] ?? '')] ?? $academicYearModel->current();
            $section = $class ? $sectionModel->firstWhere(['class_id' => $class['id'], 'name' => trim($r['section'])]) : false;
            $subject = $class ? $subjectModel->firstWhere(['class_id' => $class['id'], 'name' => trim($r['subject'])]) : false;

            if (!$class || !$section || !$subject || !$examType || !$year) {
                $skipped[] = "Row {$rowNum}: could not match Class/Section/Subject/Exam Type/Academic Year.";
                continue;
            }

            $payload = [
                'class_id' => $class['id'], 'section_id' => $section['id'], 'subject_id' => $subject['id'],
                'exam_type_id' => $examType['id'], 'exam_date' => trim((string) $r['exam_date']),
                'start_time' => trim((string) $r['start_time']), 'end_time' => trim((string) $r['end_time']),
            ];

            if ($scheduleModel->duplicateExists($payload) || $scheduleModel->overlappingExists($payload)) {
                $skipped[] = "Row {$rowNum}: duplicate or overlapping schedule — skipped.";
                continue;
            }

            $scheduleModel->insert([
                'exam_name' => trim($r['exam_name']), 'academic_year_id' => $year['id'], 'exam_type_id' => $examType['id'],
                'class_id' => $class['id'], 'section_id' => $section['id'], 'subject_id' => $subject['id'],
                'exam_date' => $payload['exam_date'], 'start_time' => $payload['start_time'], 'end_time' => $payload['end_time'],
                'room_number' => trim((string) ($r['room_number'] ?? '')),
                'max_marks' => (float) ($r['max_marks'] ?? 100), 'passing_marks' => (float) ($r['passing_marks'] ?? 40),
                'status' => 'active', 'created_by' => \App\Core\Auth::id(), 'updated_by' => \App\Core\Auth::id(),
            ]);
            $imported++;
        }
        fclose($handle);

        log_activity('created_record', "Imported {$imported} exam schedule row(s) from CSV.");
        if ($imported > 0) {
            $this->flashSuccess("{$imported} exam schedule row(s) imported successfully." . (count($skipped) ? ' ' . count($skipped) . ' row(s) skipped.' : ''));
        } else {
            $this->flashError('No rows were imported. ' . implode(' ', array_slice($skipped, 0, 5)));
        }
        $this->redirect(url('exam-schedule'));
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function validateSchedule(): array
    {
        $rules = [
            'exam_name'        => 'required|max:150',
            'academic_year_id' => 'required|exists:academic_years,id',
            'exam_type_id'     => 'required|exists:exam_types,id',
            'class_id'         => 'required|exists:classes,id',
            'section_id'       => 'required|exists:sections,id',
            'subject_id'       => 'required|exists:subjects,id',
            'exam_date'        => 'required|date',
            'start_time'       => 'required',
            'end_time'         => 'required',
            'room_number'      => 'nullable|max:30',
            'max_marks'        => 'required|numeric',
            'passing_marks'    => 'required|numeric',
            'description'      => 'nullable|max:1000',
        ];
        $data = $this->validate($rules, [], $this->all());

        // Cross-field checks the generic Validator can't express on its own.
        $fieldErrors = [];
        if (strtotime($data['end_time']) !== false && strtotime($data['start_time']) !== false
            && strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            $fieldErrors['end_time'] = ['End Time must be after Start Time.'];
        }
        if ((float) $data['passing_marks'] > (float) $data['max_marks']) {
            $fieldErrors['passing_marks'] = ['Passing Marks cannot exceed Maximum Marks.'];
        }
        if (!empty($fieldErrors)) {
            $this->failWithErrors($fieldErrors, $data);
        }

        return $data;
    }

    private function guardOverlapAndDuplicate(array $data, ?int $excludeId = null): void
    {
        $scheduleModel = new ExamSchedule();
        $payload = [
            'class_id' => (int) $data['class_id'], 'section_id' => (int) $data['section_id'],
            'subject_id' => (int) $data['subject_id'], 'exam_type_id' => (int) $data['exam_type_id'],
            'exam_date' => $data['exam_date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
        ];

        if ($scheduleModel->duplicateExists($payload, $excludeId)) {
            $this->failWithErrors(['subject_id' => ['This class/section already has this subject scheduled for this exam type on this date.']], $data);
        }
        if ($scheduleModel->overlappingExists($payload, $excludeId)) {
            $this->failWithErrors(['start_time' => ['This class/section already has another exam scheduled that overlaps this time on this date.']], $data);
        }
    }

    private function failWithErrors(array $errors, array $oldInput): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url('exam-schedule'));
        exit;
    }
}
