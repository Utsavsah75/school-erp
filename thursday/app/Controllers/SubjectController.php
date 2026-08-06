<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ClassModel;
use App\Models\Subject;

/**
 * Subject Management module (MODULE_PERMISSIONS['subjects']). One page:
 * a "Create New Subject" form on the left, a searchable/filterable/
 * sortable "All Subjects" table with bulk delete on the right — see
 * app/Views/subjects/index.php.
 */
class SubjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('subjects');
    }

    public function index(): void
    {
        $subjectModel = new Subject();

        $search = trim((string) $this->input('search', ''));
        $classFilter = $this->input('class_id', '');
        $typeFilter = $this->input('subject_type', '');
        $sort = $this->input('sort', 'code');
        $direction = $this->input('direction', 'ASC');

        $result = $subjectModel->paginateWithClass(
            $this->currentPage(),
            10,
            ['class_id' => $classFilter ?: null, 'subject_type' => $typeFilter ?: null],
            $search,
            $sort,
            $direction
        );

        $this->view('subjects/index', [
            'pageTitle'        => 'Subjects',
            'result'           => $result,
            'search'           => $search,
            'classFilter'      => $classFilter,
            'typeFilter'       => $typeFilter,
            'sort'             => $sort,
            'direction'        => $direction,
            'classes'          => (new ClassModel())->all('display_order'),
            'nextSubjectCode'  => next_subject_code(),
            'errors'           => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateSubject();

        if ((new Subject())->duplicateInClass($data['name'], (int) $data['class_id'])) {
            $this->failWithErrors(['name' => ['This subject already exists for the selected class.']], $data);
        }

        $id = (new Subject())->insert([
            'name'         => trim($data['name']),
            'code'         => trim($data['code']) ?: next_subject_code(),
            'class_id'     => (int) $data['class_id'],
            'subject_type' => $data['subject_type'],
            'is_elective'  => !empty($data['is_elective']) ? 1 : 0,
            'is_active'    => !empty($data['is_active']) || !isset($data['is_active']) ? 1 : 0,
        ]);

        $this->flashSuccess("Subject added successfully. Subject ID: SUB-{$id}, Code: {$data['code']}.");
        $this->redirect(url('subjects'));
    }

    public function update(string $id): void
    {
        $subjectId = (int) $id;
        $subjectModel = new Subject();
        $subject = $subjectModel->find($subjectId);
        if (!$subject) {
            $this->flashError('Subject not found.');
            $this->redirect(url('subjects'));
            return;
        }

        $data = $this->validateSubject($subjectId);

        if ($subjectModel->duplicateInClass($data['name'], (int) $data['class_id'], $subjectId)) {
            $this->failWithErrors(['name' => ['This subject already exists for the selected class.']], $data);
        }

        $subjectModel->update($subjectId, [
            'name'         => trim($data['name']),
            'code'         => trim($data['code']) ?: $subject['code'],
            'class_id'     => (int) $data['class_id'],
            'subject_type' => $data['subject_type'],
            'is_elective'  => !empty($data['is_elective']) ? 1 : 0,
            'is_active'    => !empty($data['is_active']) ? 1 : 0,
        ]);

        $this->flashSuccess("Subject updated successfully. Subject ID: SUB-{$subjectId}.");
        $this->redirect(url('subjects'));
    }

    public function destroy(string $id): void
    {
        $subjectId = (int) $id;
        $subject = (new Subject())->find($subjectId);
        if (!$subject) {
            $this->flashError('Subject not found.');
            $this->redirect(url('subjects'));
            return;
        }

        try {
            (new Subject())->delete($subjectId);
            $this->flashSuccess("Subject deleted successfully. Deleted Subject ID: SUB-{$subjectId}.");
        } catch (\Throwable $e) {
            // Most likely a foreign-key reference from exam/marks/timetable rows.
            error_log('[SUBJECT DELETE ERROR] ' . $e->getMessage());
            $this->flashError('This subject is in use elsewhere (exams, timetable, or teacher assignments) and cannot be deleted. Mark it Inactive instead.');
        }
        $this->redirect(url('subjects'));
    }

    public function bulkDestroy(): void
    {
        $ids = $this->input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            $this->flashError('Select at least one subject to delete.');
            $this->redirect(url('subjects'));
            return;
        }

        try {
            $deleted = (new Subject())->deleteMany($ids);
            $this->flashSuccess("{$deleted} subject(s) deleted successfully.");
        } catch (\Throwable $e) {
            error_log('[SUBJECT BULK DELETE ERROR] ' . $e->getMessage());
            $this->flashError('Some of the selected subjects are in use elsewhere and could not be deleted.');
        }
        $this->redirect(url('subjects'));
    }

    private function validateSubject(?int $subjectId = null): array
    {
        $rules = [
            'name'         => 'required|max:100',
            'subject_type' => 'required|in:theory,practical,mathematics,optional',
            'class_id'     => 'required',
            'code'         => 'nullable|max:20',
        ];
        return $this->validate($rules, [], $this->all());
    }

    private function failWithErrors(array $errors, array $oldInput): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url('subjects'));
        exit;
    }
}
