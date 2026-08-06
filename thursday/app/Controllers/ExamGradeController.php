<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ExamGrade;

/**
 * Exam Grades module (MODULE_PERMISSIONS['exam_grades']). One page: an
 * "Add New Grades" form on the left, a searchable/sortable "Exam Grade
 * Lists" table on the right — see app/Views/exam_grades/index.php.
 */
class ExamGradeController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('exam_grades');
    }

    public function index(): void
    {
        $gradeModel = new ExamGrade();

        $search = trim((string) $this->input('search', ''));
        $pointSearch = trim((string) $this->input('search_point', ''));
        $statusFilter = $this->input('status', '');
        $sort = $this->input('sort', 'percent_from');
        $direction = $this->input('direction', 'DESC');

        $result = $gradeModel->paginateActive($this->currentPage(), 10, $search, $statusFilter, $sort, $direction);

        // "Search by Point" is a small enough dataset to filter in PHP rather than adding another SQL branch.
        if ($pointSearch !== '') {
            $result['data'] = array_values(array_filter($result['data'], function ($g) use ($pointSearch) {
                return str_contains((string) $g['grade_point'], $pointSearch);
            }));
        }

        $this->view('exam_grades/index', [
            'pageTitle'    => 'Exam Grades',
            'result'       => $result,
            'search'       => $search,
            'pointSearch'  => $pointSearch,
            'statusFilter' => $statusFilter,
            'sort'         => $sort,
            'direction'    => $direction,
            'errors'       => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateGrade();
        $this->guardOverlap($data);

        $id = (new ExamGrade())->insert([
            'grade_name'   => trim($data['grade_name']),
            'grade_point'  => (float) $data['grade_point'],
            'percent_from' => (float) $data['percent_from'],
            'percent_upto' => (float) $data['percent_upto'],
            'remarks'      => trim((string) ($data['remarks'] ?? '')),
            'status'       => !empty($data['is_active']) || !isset($data['is_active']) ? 'active' : 'inactive',
        ]);

        log_activity('created_record', "Created exam grade #{$id}: {$data['grade_name']}");
        $this->flashSuccess('Grade added successfully.');
        $this->redirect(url('exam-grades'));
    }

    public function update(string $id): void
    {
        $gradeId = (int) $id;
        $gradeModel = new ExamGrade();
        if (!$gradeModel->findActive($gradeId)) {
            $this->flashError('Grade not found.');
            $this->redirect(url('exam-grades'));
            return;
        }

        $data = $this->validateGrade();
        $this->guardOverlap($data, $gradeId);

        $gradeModel->update($gradeId, [
            'grade_name'   => trim($data['grade_name']),
            'grade_point'  => (float) $data['grade_point'],
            'percent_from' => (float) $data['percent_from'],
            'percent_upto' => (float) $data['percent_upto'],
            'remarks'      => trim((string) ($data['remarks'] ?? '')),
            'status'       => !empty($data['is_active']) ? 'active' : 'inactive',
        ]);

        log_activity('updated_record', "Updated exam grade #{$gradeId}");
        $this->flashSuccess('Grade updated successfully.');
        $this->redirect(url('exam-grades'));
    }

    public function destroy(string $id): void
    {
        $gradeId = (int) $id;
        $gradeModel = new ExamGrade();
        if (!$gradeModel->findActive($gradeId)) {
            $this->flashError('Grade not found.');
            $this->redirect(url('exam-grades'));
            return;
        }

        $gradeModel->delete($gradeId);
        log_activity('deleted_record', "Deleted exam grade #{$gradeId}");
        $this->flashSuccess('Grade deleted successfully.');
        $this->redirect(url('exam-grades'));
    }

    public function bulkDestroy(): void
    {
        $ids = $this->input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            $this->flashError('Select at least one grade to delete.');
            $this->redirect(url('exam-grades'));
            return;
        }

        $deleted = (new ExamGrade())->deleteMany($ids);
        log_activity('deleted_record', "Bulk-deleted {$deleted} exam grade(s).");
        $this->flashSuccess("{$deleted} grade(s) deleted successfully.");
        $this->redirect(url('exam-grades'));
    }

    public function toggleStatus(string $id): void
    {
        $gradeId = (int) $id;
        if (!(new ExamGrade())->toggleStatus($gradeId)) {
            $this->flashError('Grade not found.');
        } else {
            log_activity('updated_record', "Toggled status for exam grade #{$gradeId}");
            $this->flashSuccess('Status updated.');
        }
        $this->redirect(url('exam-grades'));
    }

    private function validateGrade(): array
    {
        $rules = [
            'grade_name'   => 'required|max:20',
            'grade_point'  => 'required|numeric',
            'percent_from' => 'required|numeric',
            'percent_upto' => 'required|numeric',
            'remarks'      => 'nullable|max:255',
        ];
        $data = $this->validate($rules, [
            'percent_from' => 'Percentage From', 'percent_upto' => 'Percent Upto',
        ], $this->all());

        if ((float) $data['percent_from'] > (float) $data['percent_upto']) {
            $this->failWithErrors(['percent_upto' => ['Percent Upto must be greater than or equal to Percentage From.']], $data);
        }
        if ((float) $data['percent_from'] < 0 || (float) $data['percent_upto'] > 100) {
            $this->failWithErrors(['percent_upto' => ['Percentage values must be between 0 and 100.']], $data);
        }

        return $data;
    }

    private function guardOverlap(array $data, ?int $excludeId = null): void
    {
        $gradeModel = new ExamGrade();
        if ($gradeModel->rangeOverlaps((float) $data['percent_from'], (float) $data['percent_upto'], $excludeId)) {
            $this->failWithErrors(['percent_from' => ['This percentage range overlaps an existing grade band.']], $data);
        }
    }

    private function failWithErrors(array $errors, array $oldInput): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url('exam-grades'));
        exit;
    }
}
