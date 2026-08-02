<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ExamType;

/**
 * Exam Types module (MODULE_PERMISSIONS['exam_types']) — the lookup list
 * behind the Exam Schedule form's "Exam Type" dropdown (Terminal, Mid
 * Term, Final, Weekly, Monthly, Half Yearly, Annual, Practical, ...).
 * One page: create form + searchable list, same shape as SubjectController.
 */
class ExamController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('exam_types');
    }

    public function index(): void
    {
        $examTypeModel = new ExamType();
        $search = trim((string) $this->input('search', ''));

        $types = $search !== ''
            ? array_values(array_filter($examTypeModel->allActive(), function ($t) use ($search) {
                return stripos($t['name'], $search) !== false;
            }))
            : $examTypeModel->allActive();

        $this->view('exam_types/index', [
            'pageTitle' => 'Exam Types',
            'types'     => $types,
            'search'    => $search,
            'errors'    => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'        => 'required|max:60',
            'description' => 'nullable|max:255',
        ]);

        $examTypeModel = new ExamType();
        if ($examTypeModel->duplicateName($data['name'])) {
            $this->failWithErrors(['name' => ['This exam type already exists.']], $data);
        }

        $id = $examTypeModel->insert([
            'name'        => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ]);

        log_activity('created_record', "Created exam type #{$id}: {$data['name']}");
        $this->flashSuccess('Exam type added successfully.');
        $this->redirect(url('exam-types'));
    }

    public function update(string $id): void
    {
        $typeId = (int) $id;
        $examTypeModel = new ExamType();
        $type = $examTypeModel->findActive($typeId);
        if (!$type) {
            $this->flashError('Exam type not found.');
            $this->redirect(url('exam-types'));
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:60',
            'description' => 'nullable|max:255',
        ]);

        if ($examTypeModel->duplicateName($data['name'], $typeId)) {
            $this->failWithErrors(['name' => ['This exam type already exists.']], $data);
        }

        $examTypeModel->update($typeId, [
            'name'        => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ]);

        log_activity('updated_record', "Updated exam type #{$typeId}");
        $this->flashSuccess('Exam type updated successfully.');
        $this->redirect(url('exam-types'));
    }

    public function destroy(string $id): void
    {
        $typeId = (int) $id;
        $examTypeModel = new ExamType();
        if (!$examTypeModel->findActive($typeId)) {
            $this->flashError('Exam type not found.');
            $this->redirect(url('exam-types'));
            return;
        }

        if ($examTypeModel->isInUse($typeId)) {
            $this->flashError('This exam type is used by one or more exam schedules and cannot be deleted. Mark it Inactive instead.');
            $this->redirect(url('exam-types'));
            return;
        }

        $examTypeModel->delete($typeId);
        log_activity('deleted_record', "Deleted exam type #{$typeId}");
        $this->flashSuccess('Exam type deleted successfully.');
        $this->redirect(url('exam-types'));
    }

    private function failWithErrors(array $errors, array $oldInput): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url('exam-types'));
        exit;
    }
}
