<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\FeeType;

/**
 * Fee Types module (MODULE_PERMISSIONS['fees']). One page: a "Create
 * New Fee Type" form on the left, a searchable/filterable "All Fee
 * Types" table on the right — see app/Views/fee-types/index.php.
 */
class FeeTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('fees');
    }

    public function index(): void
    {
        $feeTypeModel = new FeeType();

        $search = trim((string) $this->input('search', ''));
        $categoryFilter = $this->input('category', '');

        $conditions = [];
        if ($categoryFilter !== '') {
            $conditions['category'] = $categoryFilter;
        }

        $result = $feeTypeModel->paginate($this->currentPage(), 15, $conditions, $search, 'category', 'ASC');

        $this->view('fee-types/index', [
            'pageTitle'      => 'Fee Types',
            'result'         => $result,
            'search'         => $search,
            'categoryFilter' => $categoryFilter,
            'categories'     => FeeType::CATEGORIES,
            'recurrences'    => FeeType::RECURRENCE_TYPES,
            'errors'         => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateFeeType();

        if ((new FeeType())->duplicateName(trim($data['name']))) {
            $this->failWithErrors(['name' => ['A fee type with this name already exists.']], $data);
        }

        $id = (new FeeType())->insert([
            'name'            => trim($data['name']),
            'category'        => $data['category'],
            'recurrence_type' => $data['recurrence_type'],
            'default_amount'  => (float) ($data['default_amount'] ?? 0),
            'is_active'       => !empty($data['is_active']) || !isset($data['is_active']) ? 1 : 0,
            'is_recurring'    => $data['recurrence_type'] !== 'one_time' ? 1 : 0,
        ]);

        log_activity('fee_type_created', "Created fee type #{$id}: {$data['name']}");
        $this->flashSuccess("Fee type added successfully.");
        $this->redirect(url('fee-types'));
    }

    public function update(string $id): void
    {
        $feeTypeId = (int) $id;
        $feeTypeModel = new FeeType();
        $feeType = $feeTypeModel->find($feeTypeId);
        if (!$feeType) {
            $this->flashError('Fee type not found.');
            $this->redirect(url('fee-types'));
            return;
        }

        $data = $this->validateFeeType();

        if ($feeTypeModel->duplicateName(trim($data['name']), $feeTypeId)) {
            $this->failWithErrors(['name' => ['A fee type with this name already exists.']], $data);
        }

        $feeTypeModel->update($feeTypeId, [
            'name'            => trim($data['name']),
            'category'        => $data['category'],
            'recurrence_type' => $data['recurrence_type'],
            'default_amount'  => (float) ($data['default_amount'] ?? 0),
            'is_active'       => !empty($data['is_active']) ? 1 : 0,
            'is_recurring'    => $data['recurrence_type'] !== 'one_time' ? 1 : 0,
        ]);

        log_activity('fee_type_updated', "Updated fee type #{$feeTypeId}: {$data['name']}");
        $this->flashSuccess("Fee type updated successfully.");
        $this->redirect(url('fee-types'));
    }

    public function destroy(string $id): void
    {
        $feeTypeId = (int) $id;
        $feeTypeModel = new FeeType();
        $feeType = $feeTypeModel->find($feeTypeId);
        if (!$feeType) {
            $this->flashError('Fee type not found.');
            $this->redirect(url('fee-types'));
            return;
        }

        if ($feeTypeModel->isInUse($feeTypeId)) {
            $this->flashError('This fee type has fees already assigned against it and cannot be deleted. Mark it Inactive instead.');
            $this->redirect(url('fee-types'));
            return;
        }

        $feeTypeModel->delete($feeTypeId);
        log_activity('fee_type_deleted', "Deleted fee type #{$feeTypeId}: {$feeType['name']}");
        $this->flashSuccess("Fee type deleted successfully.");
        $this->redirect(url('fee-types'));
    }

    private function validateFeeType(): array
    {
        $rules = [
            'name'            => 'required|max:100',
            'category'        => 'required|in:' . implode(',', array_keys(FeeType::CATEGORIES)),
            'recurrence_type' => 'required|in:' . implode(',', array_keys(FeeType::RECURRENCE_TYPES)),
            'default_amount'  => 'nullable',
        ];
        return $this->validate($rules, [], $this->all());
    }

    private function failWithErrors(array $errors, array $oldInput): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        $this->redirect(url('fee-types'));
        exit;
    }
}
