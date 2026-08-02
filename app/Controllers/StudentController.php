<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Fee;
use App\Models\LibraryFinePayment;
use App\Models\Marks;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('students');
    }

    public function index(): void
    {
        $studentModel = new Student();

        $search = trim((string) $this->input('search', ''));
        $classId = $this->input('class_id', '');
        $status = $this->input('status', '');

        $conditions = [];
        if ($classId !== '') {
            $conditions['class_id'] = (int) $classId;
        }
        if ($status !== '') {
            $conditions['status'] = $status;
        }

        $result = $studentModel->paginate($this->currentPage(), 20, $conditions, $search, 'created_at', 'DESC');

        // Join class/section names in for display (paginate() doesn't join).
        $classModel = new ClassModel();
        $sectionModel = new Section();
        $classes = array_column($classModel->all('name'), 'name', 'id');
        $sections = array_column($sectionModel->all('name'), 'name', 'id');
        foreach ($result['data'] as &$row) {
            $row['class_name'] = $classes[$row['class_id']] ?? '-';
            $row['section_name'] = $sections[$row['section_id']] ?? '-';
        }
        unset($row);

        $this->view('students/index', [
            'pageTitle' => 'Students',
            'result'    => $result,
            'classes'   => $classModel->all('display_order'),
            'search'    => $search,
            'classId'   => $classId,
            'status'    => $status,
            'statuses'  => STUDENT_STATUSES,
        ]);
    }

    public function create(): void
    {
        $this->view('students/create', [
            'pageTitle'           => 'Add Student',
            'classes'             => (new ClassModel())->all('display_order'),
            'sections'            => (new Section())->all('name'),
            'parents'             => (new ParentModel())->all('father_name'),
            'academicYears'       => (new AcademicYear())->all('start_date', 'DESC'),
            'errors'              => Session::getErrors(),
            'nextAdmissionNumber' => peek_next_admission_number(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validateAdmission();

        // Duplicate checks beyond what the Validator's plain `unique` rule
        // can express — composite phone+country-code, and name+DOB combos
        // (spec section 16).
        $duplicateErrors = find_student_duplicates($data);
        if (!empty($duplicateErrors)) {
            $this->failWithErrors($duplicateErrors, $data, url('students/create'));
        }

        // Roll number: manual value must be unique within its Class+Section+
        // Academic Year scope; blank means auto-generate (spec section 2).
        $classId = (int) $data['class_id'];
        $sectionId = (int) $data['section_id'];
        $yearId = (int) $data['academic_year_id'];
        $rollNumber = trim((string) ($data['roll_number'] ?? ''));
        if ($rollNumber !== '' && !roll_number_is_unique($rollNumber, $classId, $sectionId, $yearId)) {
            $this->failWithErrors(['roll_number' => ['This roll number is already used in the selected class/section/year.']], $data, url('students/create'));
        }

        // Photo is required (spec: Required Fields list).
        if (empty($this->file('photo')) || ($this->file('photo')['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->failWithErrors(['photo' => ['Student photo is required.']], $data, url('students/create'));
        }

        $photoPath = null;
        try {
            $this->assertMinResolution($this->file('photo'), 200, 200);
            $photoPath = handle_upload($this->file('photo'), 'students', true);
        } catch (\RuntimeException $e) {
            if ($photoPath) {
                delete_upload($photoPath);
            }
            $this->failWithErrors(['photo' => [$e->getMessage()]], $data, url('students/create'));
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $parentId = $this->resolveParentId($data);

            if ($rollNumber === '') {
                $rollNumber = (string) next_roll_number($classId, $sectionId, $yearId);
            }

            $admissionNumber = reserve_admission_number_locked();

            $studentModel = new Student();
            $studentId = $studentModel->insert($this->buildStudentRow($data, [
                'admission_number' => $admissionNumber,
                'roll_number'      => $rollNumber,
                'parent_id'        => $parentId,
                'photo_path'       => $photoPath,
                'created_by'       => Auth::id(),
                'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? null,
            ]));

            $skippedDocs = $this->storeDocuments($studentId);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            if ($photoPath) {
                delete_upload($photoPath);
            }
            error_log('[STUDENT ADMISSION ERROR] ' . $e->getMessage());
            $this->failWithErrors(['general' => ['Could not save student. Please try again.']], $data, url('students/create'));
            return;
        }

        $this->flashSuccess("Student added successfully. Student ID: {$admissionNumber}");
        if (!empty($skippedDocs)) {
            Session::flash('warning', 'Some documents were not saved: ' . implode('; ', $skippedDocs));
        }
        $this->redirect(url('students/' . $studentId));
    }

    /**
     * Storage health-check report (super_admin only): every uploaded
     * document across every student, flagged as OK / missing on disk, so a
     * bad file_path or a demo/seed record with no real file behind it is
     * visible at a glance instead of discovered one broken download at a
     * time. Nothing here is destructive — it's read-only diagnostics.
     */
    public function documentsAudit(): void
    {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            return;
        }

        $rows = (new StudentDocument())->allWithStudent();
        $missing = 0;
        foreach ($rows as &$row) {
            $row['file_exists'] = uploaded_file_exists($row['file_path'] ?? null);
            if (!$row['file_exists']) {
                $missing++;
            }
        }
        unset($row);

        $this->view('students/documents_audit', [
            'pageTitle' => 'Document Storage Health Check',
            'rows'      => $rows,
            'total'     => count($rows),
            'missing'   => $missing,
        ]);
    }

    public function show(string $id): void
    {
        // Validate the id before it ever reaches a query — a non-numeric or empty
        // segment used to fall straight into (int) casting and confusing 500s.
        if (!ctype_digit(trim($id))) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        try {
            $studentModel = new Student();
            $student = $studentModel->find((int) $id);
            if (!$student) {
                http_response_code(404);
                require dirname(__DIR__) . '/Views/errors/404.php';
                return;
            }

            $classModel = new ClassModel();
            $sectionModel = new Section();
            $class = $student['class_id'] ? $classModel->find($student['class_id']) : null;
            $section = $student['section_id'] ? $sectionModel->find($student['section_id']) : null;
            $parent = $student['parent_id'] ? (new ParentModel())->find($student['parent_id']) : null;

            // Latest 10 payments for the "Recent Payment History" table. Payment::forStudent()
            // is already ordered newest-first, so a simple array_slice gives us the latest 10
            // without a second query shape to maintain. Library fine payments are recorded in
            // a separate ledger (library_fine_payments) since they aren't tied to a `fees` row,
            // so merge them in here — tagged distinctly — to give one combined history.
            $feePayments = (new Payment())->forStudent($student['id']);
            $finePayments = array_map(static function (array $row): array {
                $row['kind'] = 'library_fine';
                $row['fee_type_name'] = 'Library Fine — ' . $row['book_title'];
                $row['discount_amount'] = 0;
                $row['fine_amount'] = 0;
                $row['fee_status'] = 'paid';
                $row['status'] = 'completed';
                return $row;
            }, (new LibraryFinePayment())->forStudent($student['id']));
            $feePayments = array_map(static function (array $row): array {
                $row['kind'] = 'fee';
                return $row;
            }, $feePayments);

            $allPayments = array_merge($feePayments, $finePayments);
            usort($allPayments, static fn($a, $b) => strtotime($b['paid_at']) <=> strtotime($a['paid_at']));
            $payments = array_slice($allPayments, 0, 10);

            $this->view('students/show', [
                'pageTitle' => $student['full_name'],
                'student'   => $student,
                'class'     => $class,
                'section'   => $section,
                'parent'    => $parent,
                'fees'      => (new Fee())->forStudent($student['id']),
                'payments'  => $payments,
                'marks'     => (new Marks())->forStudent($student['id'], 10),
                'documents' => (new StudentDocument())->forStudent($student['id']),
            ]);
        } catch (\Throwable $e) {
            // Never let a DB/schema hiccup surface as the framework's generic 500 page —
            // log the real cause and show a friendly error instead.
            error_log('[STUDENT SHOW ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            $message = 'We could not load this student\'s profile right now. Please try again, or contact support if this keeps happening.';
            require dirname(__DIR__) . '/Views/errors/500.php';
        }
    }

    public function edit(string $id): void
    {
        $studentModel = new Student();
        $student = $studentModel->find((int) $id);
        if (!$student) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $this->view('students/edit', [
            'pageTitle'     => 'Edit Student',
            'student'       => $student,
            'classes'       => (new ClassModel())->all('display_order'),
            'sections'      => (new Section())->all('name'),
            'parents'       => (new ParentModel())->all('father_name'),
            'academicYears' => (new AcademicYear())->all('start_date', 'DESC'),
            'errors'        => Session::getErrors(),
        ]);
    }

    public function update(string $id): void
    {
        $studentModel = new Student();
        $student = $studentModel->find((int) $id);
        if (!$student) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $data = $this->validateAdmission((int) $id);

        $duplicateErrors = find_student_duplicates($data, (int) $id);
        if (!empty($duplicateErrors)) {
            $this->failWithErrors($duplicateErrors, $data, url('students/' . $id . '/edit'));
        }

        $classId = (int) $data['class_id'];
        $sectionId = (int) $data['section_id'];
        $yearId = (int) $data['academic_year_id'];
        $rollNumber = trim((string) ($data['roll_number'] ?? ''));
        if ($rollNumber !== '' && !roll_number_is_unique($rollNumber, $classId, $sectionId, $yearId, (int) $id)) {
            $this->failWithErrors(['roll_number' => ['This roll number is already used in the selected class/section/year.']], $data, url('students/' . $id . '/edit'));
        }
        if ($rollNumber === '') {
            $rollNumber = (string) next_roll_number($classId, $sectionId, $yearId);
        }

        $photoPath = $student['photo_path'];
        $newPhotoPath = null;
        try {
            if ($this->file('photo') && ($this->file('photo')['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $this->assertMinResolution($this->file('photo'), 200, 200);
                $newPhotoPath = handle_upload($this->file('photo'), 'students', true);
            }
        } catch (\RuntimeException $e) {
            if ($newPhotoPath) {
                delete_upload($newPhotoPath);
            }
            $this->failWithErrors(['photo' => [$e->getMessage()]], $data, url('students/' . $id . '/edit'));
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $parentId = $this->resolveParentId($data, (int) $student['parent_id']);

            if ($newPhotoPath) {
                delete_upload($photoPath);
                $photoPath = $newPhotoPath;
            }

            $studentModel->update((int) $id, $this->buildStudentRow($data, [
                'roll_number' => $rollNumber,
                'parent_id'   => $parentId,
                'photo_path'  => $photoPath,
                'updated_by'  => Auth::id(),
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            ]));

            $skippedDocs = $this->storeDocuments((int) $id);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            if ($newPhotoPath) {
                delete_upload($newPhotoPath);
            }
            error_log('[STUDENT UPDATE ERROR] ' . $e->getMessage());
            $this->failWithErrors(['general' => ['Could not update student. Please try again.']], $data, url('students/' . $id . '/edit'));
            return;
        }

        $this->flashSuccess("Student updated successfully. Student ID: {$student['admission_number']}");
        if (!empty($skippedDocs)) {
            Session::flash('warning', 'Some documents were not saved: ' . implode('; ', $skippedDocs));
        }
        $this->redirect(url('students/' . $id));
    }

    public function destroy(string $id): void
    {
        $studentModel = new Student();
        $student = $studentModel->find((int) $id);
        if (!$student) {
            $this->redirect(url('students'));
            return;
        }

        $studentId = (int) $id;
        $studentLabel = $student['admission_number'] ?? ('STU-' . str_pad((string) $studentId, 5, '0', STR_PAD_LEFT));
        $parentId = !empty($student['parent_id']) ? (int) $student['parent_id'] : null;

        delete_upload($student['photo_path']);
        $studentModel->delete($studentId);

        // Only remove the parent record once none of their other children
        // remain linked — a parent with siblings still enrolled must stay.
        if ($parentId) {
            $parentModel = new ParentModel();
            if ($parentModel->childrenCount($parentId) === 0) {
                $parent = $parentModel->find($parentId);
                if ($parent && !empty($parent['user_id'])) {
                    (new User())->delete((int) $parent['user_id']);
                }
                $parentModel->delete($parentId);
            }
        }

        $this->flashSuccess("Student deleted successfully. Deleted Student ID: {$studentLabel}");
        $this->redirect(url('students'));
    }

    /** AJAX helper: sections belonging to a given class, for the section dropdown. */
    public function sectionsForClass(string $classId): void
    {
        $sections = (new Section())->forClass((int) $classId);
        $this->json($sections);
    }

    /** AJAX helper: suggests the next Roll Number for Class+Section+Year (spec section 2). */
    public function nextRollNumber(): void
    {
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);
        $yearId = (int) $this->input('academic_year_id', 0);
        if (!$classId || !$sectionId || !$yearId) {
            $this->json(['roll_number' => null]);
            return;
        }
        $this->json(['roll_number' => next_roll_number($classId, $sectionId, $yearId)]);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function validateAdmission(?int $studentId = null): array
    {
        $uniqueEmail = 'unique:students,email' . ($studentId ? ",{$studentId}" : '');

        $rules = [
            'full_name'          => 'required|regex:/^[A-Za-z ]{3,100}$/',
            'gender'             => 'required|in:male,female,other',
            'dob'                => 'required|date',
            'blood_group'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion'           => 'required',
            'religion_other'     => 'nullable|max:100',
            'nationality'        => 'required',
            'phone_country_code' => 'required|max:6',
            'phone'              => 'required|regex:/^[0-9]{10,15}$/',
            'email'              => "nullable|email|{$uniqueEmail}",
            'address_country'    => 'required|max:100',
            'address_district'   => 'required|max:100',
            'class_id'           => 'required|integer|exists:classes,id',
            'section_id'         => 'required|integer|exists:sections,id',
            'academic_year_id'   => 'required|integer|exists:academic_years,id',
            'admission_date'     => 'required|date',
            'status'             => 'required|in:active,inactive,graduated,transferred,suspended,promoted',
        ];
        // Guardian phone and parent/guardian presence use conditional logic
        // the simple Validator rule set can't express, so they're checked
        // manually below instead of being declared here.

        $data = $this->validate($rules, [], $this->all());

        // DOB age range 3-100 (spec section 5) — not expressible via the
        // simple Validator rule set, so checked manually here.
        $age = student_age_years($data['dob']);
        if ($age === null || $age < 3 || $age > 100) {
            $this->failWithErrors(['dob' => ['Student age must be between 3 and 100 years.']], $data);
        }

        // "Other" religion requires the free-text field to be filled.
        if (($data['religion'] ?? '') === 'Other' && trim((string) ($data['religion_other'] ?? '')) === '') {
            $this->failWithErrors(['religion_other' => ['Please specify the religion.']], $data);
        }

        // At least one parent/guardian identity is required: either an
        // existing parent_id was picked, or enough inline fields were given
        // to create a new one.
        $hasExistingParent = !empty($data['parent_id']);
        $hasInlineGuardian = trim((string) ($data['father_name'] ?? '')) !== ''
            || trim((string) ($data['mother_name'] ?? '')) !== ''
            || trim((string) ($data['guardian_name'] ?? '')) !== '';
        if (!$hasExistingParent && !$hasInlineGuardian) {
            $this->failWithErrors(['guardian' => ['Select an existing parent or enter at least one parent/guardian name.']], $data);
        }
        if (!$hasExistingParent) {
            $parentPhone = trim((string) ($data['parent_phone'] ?? ''));
            if (!preg_match('/^[0-9]{10,15}$/', $parentPhone)) {
                $this->failWithErrors(['parent_phone' => ['Parent/Guardian phone must be 10-15 digits.']], $data);
            }
        }

        return $data;
    }

    /** Flash field errors + old input and redirect back, matching how Controller::validate() already fails. */
    private function failWithErrors(array $errors, array $oldInput, ?string $redirectTo = null): void
    {
        Session::setErrors($errors);
        Session::setOldInput($oldInput);
        Session::flash('error', 'Please fix the errors below and try again.');
        if ($redirectTo) {
            $this->redirect($redirectTo);
        } else {
            $this->back();
        }
        exit;
    }

    /**
     * Spec section 11: photo must be at least 200x200px. Must run BEFORE
     * handle_upload() moves the file off its tmp_name — once moved, the
     * original upload's tmp path no longer exists to inspect.
     */
    private function assertMinResolution(array $file, int $minWidth, int $minHeight): void
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return; // let handle_upload()'s own checks report the real problem
        }
        $dims = @getimagesize($file['tmp_name']);
        if ($dims === false) {
            throw new \RuntimeException('Uploaded file is not a valid image.');
        }
        [$width, $height] = $dims;
        if ($width < $minWidth || $height < $minHeight) {
            throw new \RuntimeException("Image must be at least {$minWidth}x{$minHeight}px (uploaded: {$width}x{$height}).");
        }
    }

    private function resolveParentId(array $data, ?int $existingParentId = null): ?int
    {
        if (!empty($data['parent_id'])) {
            return (int) $data['parent_id'];
        }

        $hasInlineGuardian = trim((string) ($data['father_name'] ?? '')) !== ''
            || trim((string) ($data['mother_name'] ?? '')) !== ''
            || trim((string) ($data['guardian_name'] ?? '')) !== '';

        if (!$hasInlineGuardian) {
            return $existingParentId;
        }

        $parentModel = new ParentModel();
        $parentData = [
            'father_name'           => trim((string) ($data['father_name'] ?? '')) ?: null,
            'mother_name'           => trim((string) ($data['mother_name'] ?? '')) ?: null,
            'guardian_name'         => trim((string) ($data['guardian_name'] ?? '')) ?: null,
            'guardian_relationship' => trim((string) ($data['guardian_relationship'] ?? '')) ?: null,
            'occupation'            => trim((string) ($data['parent_occupation'] ?? '')) ?: null,
            'phone_country_code'    => $data['parent_phone_country_code'] ?? '+977',
            'phone'                 => trim((string) ($data['parent_phone'] ?? '')),
            'email'                 => trim((string) ($data['parent_email'] ?? '')) ?: null,
        ];

        if ($existingParentId) {
            $parentModel->update($existingParentId, $parentData);
            return $existingParentId;
        }

        return $parentModel->insert($parentData);
    }

    private function buildStudentRow(array $data, array $overrides): array
    {
        $row = [
            'full_name'          => trim($data['full_name']),
            'gender'             => $data['gender'],
            'dob'                => $data['dob'],
            'blood_group'        => trim((string) ($data['blood_group'] ?? '')) ?: null,
            'religion'           => $data['religion'],
            'religion_other'     => $data['religion'] === 'Other' ? trim((string) ($data['religion_other'] ?? '')) : null,
            'nationality'        => $data['nationality'],
            'phone_country_code' => $data['phone_country_code'],
            'phone'              => trim($data['phone']),
            'email'              => trim((string) ($data['email'] ?? '')) ?: null,
            'address'            => $this->addressSummary($data),
            'address_country'    => trim((string) ($data['address_country'] ?? '')) ?: null,
            'address_province'   => trim((string) ($data['address_province'] ?? '')) ?: null,
            'address_district'   => trim((string) ($data['address_district'] ?? '')) ?: null,
            'address_municipality' => trim((string) ($data['address_municipality'] ?? '')) ?: null,
            'address_ward'       => trim((string) ($data['address_ward'] ?? '')) ?: null,
            'address_street'     => trim((string) ($data['address_street'] ?? '')) ?: null,
            'address_postal_code' => trim((string) ($data['address_postal_code'] ?? '')) ?: null,
            'class_id'           => (int) $data['class_id'],
            'section_id'         => (int) $data['section_id'],
            'academic_year_id'   => (int) $data['academic_year_id'],
            'admission_date'     => $data['admission_date'],
            'status'             => $data['status'],
            'id_card_number'     => trim((string) ($data['id_card_number'] ?? '')) ?: null,
            'previous_school'    => trim((string) ($data['previous_school'] ?? '')) ?: null,
            'previous_class'     => trim((string) ($data['previous_class'] ?? '')) ?: null,
            'birth_certificate_number' => trim((string) ($data['birth_certificate_number'] ?? '')) ?: null,
            'citizenship_number' => trim((string) ($data['citizenship_number'] ?? '')) ?: null,
            'passport_number'    => trim((string) ($data['passport_number'] ?? '')) ?: null,
            'scholarship_status' => trim((string) ($data['scholarship_status'] ?? '')) ?: null,
            'medical_conditions' => trim((string) ($data['medical_conditions'] ?? '')) ?: null,
            'allergies'          => trim((string) ($data['allergies'] ?? '')) ?: null,
            'emergency_contact_name' => trim((string) ($data['emergency_contact_name'] ?? '')) ?: null,
            'emergency_contact_phone' => trim((string) ($data['emergency_contact_phone'] ?? '')) ?: null,
            'emergency_contact_relationship' => trim((string) ($data['emergency_contact_relationship'] ?? '')) ?: null,
            'remarks'            => trim((string) ($data['remarks'] ?? '')) ?: null,
        ];

        return array_merge($row, $overrides);
    }

    private function addressSummary(array $data): string
    {
        $parts = array_filter([
            $data['address_street'] ?? '',
            $data['address_municipality'] ?? '',
            !empty($data['address_ward']) ? 'Ward ' . $data['address_ward'] : '',
            $data['address_district'] ?? '',
            $data['address_province'] ?? '',
            $data['address_country'] ?? '',
        ]);
        return implode(', ', $parts);
    }

    /**
     * Optional multi-file document uploads (spec section 22/23).
     * @return string[] Human-readable reasons for any file that was rejected
     *                   and NOT saved — e.g. wrong type or too large. Callers
     *                   surface these as a flash warning so a rejected upload
     *                   is never silently dropped.
     */
    private function storeDocuments(int $studentId): array
    {
        if (empty($_FILES['documents']['tmp_name']) || !is_array($_FILES['documents']['tmp_name'])) {
            return [];
        }
        $docModel = new StudentDocument();
        $types = $_POST['document_types'] ?? [];
        $skipped = [];
        foreach ($_FILES['documents']['tmp_name'] as $i => $tmpName) {
            if (empty($tmpName)) {
                continue;
            }
            $file = [
                'name'     => $_FILES['documents']['name'][$i],
                'tmp_name' => $tmpName,
                'error'    => $_FILES['documents']['error'][$i],
                'size'     => $_FILES['documents']['size'][$i],
            ];
            try {
                $path = handle_upload($file, 'documents', false);
                if ($path) {
                    $docModel->insert([
                        'student_id'    => $studentId,
                        'doc_type'      => $types[$i] ?? 'other',
                        'file_path'     => $path,
                        'original_name' => $file['name'],
                    ]);
                }
            } catch (\RuntimeException $e) {
                // Skip a single bad document rather than failing the whole admission;
                // the student record and its other documents are still saved. The
                // reason is still surfaced to the user (see callers), not just logged,
                // so "why didn't my file show up" never has to be guessed at.
                error_log('[STUDENT DOCUMENT UPLOAD SKIPPED] ' . $e->getMessage());
                $skipped[] = ($file['name'] ?: 'a file') . ': ' . $e->getMessage();
            }
        }
        return $skipped;
    }
}