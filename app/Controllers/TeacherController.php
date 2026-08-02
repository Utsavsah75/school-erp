<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAddress;
use App\Models\TeacherAssignment;
use App\Models\TeacherAttendanceSetting;
use App\Models\TeacherBankDetail;
use App\Models\TeacherContact;
use App\Models\TeacherDocument;
use App\Models\TeacherEmployment;
use App\Models\TeacherExperience;
use App\Models\TeacherLeave;
use App\Models\TeacherPayroll;
use App\Models\TeacherPermission;
use App\Models\TeacherQualification;
use App\Models\User;

/**
 * Admin-facing "Teachers" module. MODULE_PERMISSIONS['teachers'] gates it
 * to Super Admin / Principal / Vice Principal (config/constants.php).
 *
 * The Teacher record is split across 13 normalized tables (see
 * database/teacher_module_migration.sql) — this controller is the one
 * place that assembles/disassembles that split back into a single
 * request/response, so every Model itself can stay a plain single-table
 * class. create()/store() and edit()/update() share the same tab
 * structure; store()/update() both run inside one DB transaction so a
 * failure partway through never leaves a teacher half-saved.
 */
class TeacherController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('teachers');
    }

    public function index(): void
    {
        $teacherModel = new Teacher();

        $search = trim((string) $this->input('search', ''));
        $status = $this->input('status', '');

        $conditions = ['deleted_at' => null];
        if ($status !== '') {
            $conditions = ['status' => $status];
        }

        $result = $teacherModel->paginate($this->currentPage(), 20, $conditions, $search, 'full_name', 'ASC');
        if ($status === '') {
            // paginate()'s buildWhere() can't express "deleted_at IS NULL" alongside a search
            // term cleanly for every case, so soft-deleted rows are filtered again here.
            $result['data'] = array_values(array_filter($result['data'], fn($r) => empty($r['deleted_at'])));
        }

        $teacherIds = array_column($result['data'], 'id');
        $assignments = $teacherModel->assignmentsFor($teacherIds);

        foreach ($result['data'] as &$row) {
            $rowAssignments = $assignments[$row['id']] ?? [];
            $row['subjects_summary'] = implode(', ', array_unique(array_column($rowAssignments, 'subject_name')));
            $row['classes_summary'] = implode(', ', array_map(
                fn($a) => $a['class_name'] . '-' . $a['section_name'],
                array_slice($rowAssignments, 0, 4)
            )) . (count($rowAssignments) > 4 ? ' …' : '');
        }
        unset($row);

        $this->view('teachers/index', [
            'pageTitle' => 'Teachers',
            'result'    => $result,
            'search'    => $search,
            'status'    => $status,
        ]);
    }

    public function create(): void
    {
        $this->view('teachers/create', array_merge($this->formLookups(), [
            'pageTitle'        => 'Add Teacher',
            'teacher'          => null,
            'contact'          => [],
            'addresses'        => [],
            'employment'       => [],
            'bank'             => [],
            'qualifications'   => [],
            'experience'       => [],
            'assignments'      => [],
            'documents'        => [],
            'payroll'          => [],
            'leave'            => [],
            'attendanceSet'    => [],
            'permissions'      => [],
            'loginUser'        => [],
            'nextEmployeeCode' => next_employee_code(),
            'errors'           => Session::getErrors(),
        ]));
    }

    public function store(): void
    {
        $data = $this->validateTeacher();

        $duplicateErrors = find_teacher_duplicates($data);
        if (!empty($duplicateErrors)) {
            $this->failWithErrors($duplicateErrors, $data, url('teachers/create'));
        }

        $username = trim((string) ($data['username'] ?? ''));
        if ($username !== '' && (new User())->usernameExists($username)) {
            $this->failWithErrors(['username' => ['This username is already taken.']], $data, url('teachers/create'));
        }

        [$photoPath, $signaturePath] = $this->handleProfileUploads($data, null, null, 'teachers/create');

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $userId = $this->saveLoginUser($data, null);

            $teacherModel = new Teacher();
            $teacherId = $teacherModel->insert($this->buildTeacherRow($data, [
                'user_id'     => $userId,
                'photo_path'  => $photoPath,
                'signature_path' => $signaturePath,
                'created_by'  => Auth::id(),
                'updated_by'  => Auth::id(),
            ]));

            $this->saveAllSections($teacherId, $data);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            if ($photoPath) {
                delete_upload($photoPath);
            }
            if ($signaturePath) {
                delete_upload($signaturePath);
            }
            error_log('[TEACHER CREATE ERROR] ' . $e->getMessage());
            $this->failWithErrors(['general' => ['Could not save teacher. Please try again.']], $data, url('teachers/create'));
        }

        $employeeNumber = $teacherModel->find($teacherId)['employee_number'] ?? $teacherId;
        $this->flashSuccess("Teacher added successfully. Teacher ID: {$employeeNumber}");
        $this->redirect(url('teachers/' . $teacherId));
    }

    public function show(string $id): void
    {
        $teacherId = (int) $id;
        $teacherModel = new Teacher();
        $teacher = $teacherModel->find($teacherId);
        if (!$teacher || !empty($teacher['deleted_at'])) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $this->view('teachers/show', [
            'pageTitle'      => $teacher['full_name'],
            'teacherRow'     => $teacher,
            'contact'        => (new TeacherContact())->forTeacher($teacherId) ?: [],
            'addresses'      => $this->addressesByType($teacherId),
            'employment'     => (new TeacherEmployment())->forTeacher($teacherId) ?: [],
            'bank'           => (new TeacherBankDetail())->forTeacher($teacherId) ?: [],
            'qualifications' => (new TeacherQualification())->forTeacher($teacherId),
            'experience'     => (new TeacherExperience())->forTeacher($teacherId),
            'assignments'    => (new TeacherAssignment())->forTeacher($teacherId),
            'documents'      => (new TeacherDocument())->forTeacher($teacherId),
            'payroll'        => (new TeacherPayroll())->forTeacher($teacherId) ?: [],
            'leave'          => (new TeacherLeave())->forTeacher($teacherId) ?: [],
            'attendanceSet'  => (new TeacherAttendanceSetting())->forTeacher($teacherId) ?: [],
            'loginUser'      => $teacher['user_id'] ? (new User())->find($teacher['user_id']) : null,
        ]);
    }

    public function edit(string $id): void
    {
        $teacherId = (int) $id;
        $teacherModel = new Teacher();
        $teacher = $teacherModel->find($teacherId);
        if (!$teacher || !empty($teacher['deleted_at'])) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $permissionModel = new TeacherPermission();

        $this->view('teachers/edit', array_merge($this->formLookups($teacherId), [
            'pageTitle'      => 'Edit Teacher',
            'teacher'        => $teacher,
            'contact'        => (new TeacherContact())->forTeacher($teacherId) ?: [],
            'addresses'      => $this->addressesByType($teacherId),
            'employment'     => (new TeacherEmployment())->forTeacher($teacherId) ?: [],
            'bank'           => (new TeacherBankDetail())->forTeacher($teacherId) ?: [],
            'qualifications' => (new TeacherQualification())->forTeacher($teacherId),
            'experience'     => (new TeacherExperience())->forTeacher($teacherId),
            'assignments'    => (new TeacherAssignment())->forTeacher($teacherId),
            'documents'      => (new TeacherDocument())->forTeacher($teacherId),
            'payroll'        => (new TeacherPayroll())->forTeacher($teacherId) ?: [],
            'leave'          => (new TeacherLeave())->forTeacher($teacherId) ?: [],
            'attendanceSet'  => (new TeacherAttendanceSetting())->forTeacher($teacherId) ?: [],
            'permissions'    => $permissionModel->permissionsFor($teacherId),
            'loginUser'      => $teacher['user_id'] ? (new User())->find($teacher['user_id']) : [],
            'errors'         => Session::getErrors(),
        ]));
    }

    public function update(string $id): void
    {
        $teacherId = (int) $id;
        $teacherModel = new Teacher();
        $teacher = $teacherModel->find($teacherId);
        if (!$teacher || !empty($teacher['deleted_at'])) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $data = $this->validateTeacher($teacherId);

        $duplicateErrors = find_teacher_duplicates($data, $teacherId);
        if (!empty($duplicateErrors)) {
            $this->failWithErrors($duplicateErrors, $data, url('teachers/' . $teacherId . '/edit'));
        }

        $username = trim((string) ($data['username'] ?? ''));
        if ($username !== '' && (new User())->usernameExists($username, (int) $teacher['user_id'])) {
            $this->failWithErrors(['username' => ['This username is already taken.']], $data, url('teachers/' . $teacherId . '/edit'));
        }

        [$photoPath, $signaturePath] = $this->handleProfileUploads(
            $data,
            $teacher['photo_path'],
            $teacher['signature_path'],
            'teachers/' . $teacherId . '/edit'
        );

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $userId = $this->saveLoginUser($data, $teacher['user_id'] ? (int) $teacher['user_id'] : null);

            $teacherModel->update($teacherId, $this->buildTeacherRow($data, [
                'user_id'        => $userId,
                'photo_path'     => $photoPath,
                'signature_path' => $signaturePath,
                'updated_by'     => Auth::id(),
            ]));

            $this->saveAllSections($teacherId, $data);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[TEACHER UPDATE ERROR] ' . $e->getMessage());
            $this->failWithErrors(['general' => ['Could not update teacher. Please try again.']], $data, url('teachers/' . $teacherId . '/edit'));
        }

        $this->flashSuccess("Teacher updated successfully. Teacher ID: {$teacher['employee_number']}");
        $this->redirect(url('teachers/' . $teacherId));
    }

    public function destroy(int $id): void
    {
        $teacherModel = new Teacher();
        $teacher = $teacherModel->find($id);
        if ($teacher) {
            // Soft delete: keeps payroll/attendance/assignment history intact
            // for reporting instead of cascading a hard delete through 13 tables.
            $teacherModel->update($id, [
                'is_active'  => 0,
                'updated_by' => Auth::id(),
            ]);
            $this->pdoRaw()->prepare('UPDATE teachers SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
            $this->flashSuccess('Teacher deleted successfully.');
        }
        $this->redirect(url('teachers'));
    }

    public function deleteDocument(string $id, string $docId): void
    {
        $docModel = new TeacherDocument();
        $doc = $docModel->find((int) $docId);
        if ($doc && (int) $doc['teacher_id'] === (int) $id) {
            delete_upload($doc['file_path']);
            $docModel->delete((int) $docId);
            $this->flashSuccess('Document removed successfully.');
        }
        $this->redirect(url('teachers/' . $id . '/edit'));
    }

    /** AJAX helper: sections belonging to a given class, for the assignment row's section dropdown. */
    public function sectionsForClass(string $classId): void
    {
        $this->json((new Section())->forClass((int) $classId));
    }

    /** AJAX helper: subjects belonging to a given class, for the assignment row's subject dropdown. */
    public function subjectsForClass(string $classId): void
    {
        $this->json((new Subject())->where(['class_id' => (int) $classId], 'name', 'ASC'));
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function pdoRaw(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    private function formLookups(?int $excludeTeacherId = null): array
    {
        return [
            'classes'       => (new ClassModel())->all('display_order'),
            'sections'      => (new Section())->all('name'),
            'subjects'      => (new Subject())->all('name'),
            'academicYears' => (new AcademicYear())->all('start_date', 'DESC'),
            'managers'      => (new Teacher())->namesList($excludeTeacherId),
        ];
    }

    private function addressesByType(int $teacherId): array
    {
        $rows = (new TeacherAddress())->forTeacher($teacherId);
        $byType = ['permanent' => [], 'temporary' => []];
        foreach ($rows as $row) {
            $byType[$row['address_type']] = $row;
        }
        return $byType;
    }

    /** Flash field errors + old input and redirect back. */
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
     * Validates the "single row" fields — Personal, Contact, Employment,
     * Login. The dynamic multi-row sections (qualifications, experience,
     * assignments, documents) are validated per-row inside their own
     * build*() method instead, since the Validator class only expresses
     * flat field => rule maps.
     */
    private function validateTeacher(?int $teacherId = null): array
    {
        $isEdit = $teacherId !== null;
        $uniqueEmployeeNumber = 'unique:teachers,employee_number' . ($isEdit ? ",{$teacherId}" : '');

        $rules = [
            'first_name'        => 'required|max:80',
            'last_name'         => 'required|max:80',
            'employee_number'   => "required|max:30|{$uniqueEmployeeNumber}",
            'gender'            => 'required|in:male,female,other',
            'dob'               => 'required|date',
            'mobile_number'     => 'required|regex:/^[0-9+\-\s()]{7,20}$/',
            'personal_email'    => 'nullable|email',
            'official_email'    => 'nullable|email',
            'joining_date'      => 'required|date',
            'employment_type'   => 'required|in:full_time,part_time,contract,visiting',
            'designation'       => 'required|max:100',
            'employment_status' => 'required|in:active,on_leave,suspended,resigned,retired',
            'username'          => 'nullable|max:60',
            'role'              => 'nullable',
        ];

        $data = $this->validate($rules, [], $this->all());

        // Password: required on create, optional on edit (blank = keep existing).
        $password = trim((string) ($data['password'] ?? ''));
        if (!$isEdit && $password === '') {
            $this->failWithErrors(['password' => ['Password is required for a new login.']], $data);
        }
        if ($password !== '' && mb_strlen($password) < 8) {
            $this->failWithErrors(['password' => ['Password must be at least 8 characters.']], $data);
        }
        if ($password !== '' && $password !== ($data['password_confirmation'] ?? '')) {
            $this->failWithErrors(['password_confirmation' => ['Password confirmation does not match.']], $data);
        }
        if ($password !== '' && trim((string) ($data['role'] ?? '')) === '') {
            $this->failWithErrors(['role' => ['Select a role for this login.']], $data);
        }

        // Email is required overall, but the split personal/official fields
        // are individually optional — at least one must be present so the
        // teacher record still has a way to be reached.
        if (trim((string) ($data['personal_email'] ?? '')) === '' && trim((string) ($data['official_email'] ?? '')) === '') {
            $this->failWithErrors(['personal_email' => ['Enter at least one email address (personal or official).']], $data);
        }

        return $data;
    }

    private function buildTeacherRow(array $data, array $overrides): array
    {
        $firstName = trim($data['first_name']);
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $lastName = trim($data['last_name']);

        $email = trim((string) ($data['official_email'] ?? '')) ?: trim((string) ($data['personal_email'] ?? ''));

        $row = [
            'employee_number'    => trim($data['employee_number']),
            'first_name'         => $firstName,
            'middle_name'        => $middleName ?: null,
            'last_name'          => $lastName,
            'full_name'          => teacher_full_name($firstName, $middleName, $lastName),
            'gender'             => $data['gender'],
            'dob'                => $data['dob'],
            'blood_group'        => trim((string) ($data['blood_group'] ?? '')) ?: null,
            'religion'           => trim((string) ($data['religion'] ?? '')) ?: null,
            'nationality'        => trim((string) ($data['nationality'] ?? '')) ?: null,
            'marital_status'     => trim((string) ($data['marital_status'] ?? '')) ?: null,
            'citizenship_number' => trim((string) ($data['citizenship_number'] ?? '')) ?: null,
            'passport_number'    => trim((string) ($data['passport_number'] ?? '')) ?: null,
            'phone'              => trim((string) ($data['mobile_number'] ?? '')),
            'email'              => $email ?: null,
            'address'            => trim((string) ($data['permanent_address_line'] ?? '')) ?: null,
            'qualification'      => trim((string) ($data['highest_qualification'] ?? '')) ?: null,
            'experience_years'   => (isset($data['experience_years']) && $data['experience_years'] !== '') ? (float) $data['experience_years'] : null,
            'salary'             => (isset($data['basic_salary']) && $data['basic_salary'] !== '') ? (float) $data['basic_salary'] : null,
            'joining_date'       => $data['joining_date'],
            'status'             => $data['employment_status'],
            'is_active'          => 1,
        ];

        return array_merge($row, $overrides);
    }

    /**
     * Handles the Photo + Signature uploads (spec section 1). Runs before
     * the DB transaction opens since a failed upload should never leave a
     * transaction dangling. Returns [photoPath, signaturePath] — either may
     * be the pre-existing path unchanged (edit) or null (create, optional
     * signature).
     */
    private function handleProfileUploads(array $data, ?string $existingPhoto, ?string $existingSignature, string $redirectRoute): array
    {
        $photoPath = $existingPhoto;
        $signaturePath = $existingSignature;
        $newPhotoPath = null;
        $newSignaturePath = null;

        try {
            if ($this->file('photo') && ($this->file('photo')['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $newPhotoPath = handle_upload($this->file('photo'), 'teachers', true);
            } elseif ($existingPhoto === null) {
                $this->failWithErrors(['photo' => ['Teacher photo is required.']], $data, url($redirectRoute));
            }
            if ($this->file('signature') && ($this->file('signature')['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $newSignaturePath = handle_upload($this->file('signature'), 'teacher_signatures', true);
            }
        } catch (\RuntimeException $e) {
            if ($newPhotoPath) {
                delete_upload($newPhotoPath);
            }
            if ($newSignaturePath) {
                delete_upload($newSignaturePath);
            }
            $this->failWithErrors(['photo' => [$e->getMessage()]], $data, url($redirectRoute));
        }

        if ($newPhotoPath) {
            if ($existingPhoto) {
                delete_upload($existingPhoto);
            }
            $photoPath = $newPhotoPath;
        }
        if ($newSignaturePath) {
            if ($existingSignature) {
                delete_upload($existingSignature);
            }
            $signaturePath = $newSignaturePath;
        }

        return [$photoPath, $signaturePath];
    }

    /** Creates or updates the linked `users` login row (spec section 6). Returns the user_id (or null if no login was configured). */
    private function saveLoginUser(array $data, ?int $existingUserId): ?int
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $role = trim((string) ($data['role'] ?? ''));

        if ($username === '' && $role === '' && !$existingUserId) {
            return null; // no login configured for this teacher
        }

        $userModel = new User();
        $userData = [
            'full_name'          => teacher_full_name($data['first_name'], $data['middle_name'] ?? '', $data['last_name']),
            'email'              => trim((string) ($data['official_email'] ?? '')) ?: trim((string) ($data['personal_email'] ?? '')),
            'phone'              => trim((string) ($data['mobile_number'] ?? '')),
            'username'           => $username ?: null,
            'role'               => $role ?: ROLE_TEACHER,
            'two_factor_enabled' => !empty($data['two_factor_enabled']) ? 1 : 0,
            'is_active'          => !empty($data['account_active']) || !isset($data['account_active']) ? 1 : 0,
        ];
        if ($password !== '') {
            $userData['password'] = Auth::hashPassword($password);
        }

        if ($existingUserId) {
            $userModel->update($existingUserId, $userData);
            return $existingUserId;
        }

        if (empty($userData['email'])) {
            return null; // users.email is NOT NULL — can't create a login without one
        }
        $userData['password'] ??= Auth::hashPassword(bin2hex(random_bytes(8)));
        $userData['email_verified_at'] = date('Y-m-d H:i:s');
        return $userModel->insert($userData);
    }

    /** Saves every 1:1 and 1:many sub-table for a teacher, inside the caller's open transaction. */
    private function saveAllSections(int $teacherId, array $data): void
    {
        (new TeacherContact())->save($teacherId, [
            'mobile_number'                  => trim((string) ($data['mobile_number'] ?? '')) ?: null,
            'alternate_mobile_number'         => trim((string) ($data['alternate_mobile_number'] ?? '')) ?: null,
            'personal_email'                  => trim((string) ($data['personal_email'] ?? '')) ?: null,
            'official_email'                  => trim((string) ($data['official_email'] ?? '')) ?: null,
            'emergency_contact_name'          => trim((string) ($data['emergency_contact_name'] ?? '')) ?: null,
            'emergency_contact_number'        => trim((string) ($data['emergency_contact_number'] ?? '')) ?: null,
            'emergency_contact_relationship'  => trim((string) ($data['emergency_contact_relationship'] ?? '')) ?: null,
        ]);

        $addressModel = new TeacherAddress();
        $addressModel->save($teacherId, 'permanent', [
            'address_line'   => trim((string) ($data['permanent_address_line'] ?? '')) ?: null,
            'city'           => trim((string) ($data['permanent_city'] ?? '')) ?: null,
            'state_province' => trim((string) ($data['permanent_state_province'] ?? '')) ?: null,
            'country'        => trim((string) ($data['permanent_country'] ?? '')) ?: null,
            'postal_code'    => trim((string) ($data['permanent_postal_code'] ?? '')) ?: null,
        ]);
        $addressModel->save($teacherId, 'temporary', [
            'address_line'   => trim((string) ($data['temporary_address_line'] ?? '')) ?: null,
            'city'           => trim((string) ($data['temporary_city'] ?? '')) ?: null,
            'state_province' => trim((string) ($data['temporary_state_province'] ?? '')) ?: null,
            'country'        => trim((string) ($data['temporary_country'] ?? '')) ?: null,
            'postal_code'    => trim((string) ($data['temporary_postal_code'] ?? '')) ?: null,
        ]);

        (new TeacherEmployment())->save($teacherId, [
            'employment_type'      => $data['employment_type'] ?? null,
            'designation'          => trim((string) ($data['designation'] ?? '')) ?: null,
            'department'           => trim((string) ($data['department'] ?? '')) ?: null,
            'specialization'       => trim((string) ($data['specialization'] ?? '')) ?: null,
            'previous_school'      => trim((string) ($data['previous_school'] ?? '')) ?: null,
            'previous_experience_details' => trim((string) ($data['previous_experience_details'] ?? '')) ?: null,
            'reporting_manager_id' => !empty($data['reporting_manager_id']) ? (int) $data['reporting_manager_id'] : null,
            'salary_grade'         => trim((string) ($data['salary_grade'] ?? '')) ?: null,
            'basic_salary'         => (isset($data['basic_salary']) && $data['basic_salary'] !== '') ? (float) $data['basic_salary'] : null,
            'allowances'           => (isset($data['employment_allowances']) && $data['employment_allowances'] !== '') ? (float) $data['employment_allowances'] : null,
            'employment_status'    => $data['employment_status'] ?? 'active',
        ]);

        (new TeacherBankDetail())->save($teacherId, [
            'bank_account_number' => trim((string) ($data['bank_account_number'] ?? '')) ?: null,
            'bank_name'           => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'branch'              => trim((string) ($data['branch'] ?? '')) ?: null,
            'pan_number'          => trim((string) ($data['pan_number'] ?? '')) ?: null,
            'pf_number'           => trim((string) ($data['pf_number'] ?? '')) ?: null,
            'insurance_number'    => trim((string) ($data['insurance_number'] ?? '')) ?: null,
        ]);

        $this->saveQualifications($teacherId, $data);
        $this->saveExperience($teacherId, $data);
        $this->saveAssignments($teacherId, $data);
        $this->saveDocuments($teacherId);

        (new TeacherPayroll())->save($teacherId, [
            'salary_structure' => trim((string) ($data['salary_structure'] ?? '')) ?: null,
            'payment_type'     => $data['payment_type'] ?? 'bank_transfer',
            'tax_percentage'   => $this->nullableFloat($data['tax_percentage'] ?? null),
            'allowances'       => $this->nullableFloat($data['payroll_allowances'] ?? null),
            'deductions'       => $this->nullableFloat($data['deductions'] ?? null),
            'overtime_rate'    => $this->nullableFloat($data['overtime_rate'] ?? null),
            'bonus'            => $this->nullableFloat($data['bonus'] ?? null),
            'provident_fund'   => $this->nullableFloat($data['provident_fund'] ?? null),
            'insurance'        => $this->nullableFloat($data['payroll_insurance'] ?? null),
            'pension'          => $this->nullableFloat($data['pension'] ?? null),
            'net_salary'       => $this->nullableFloat($data['net_salary'] ?? null),
        ]);

        (new TeacherLeave())->save($teacherId, [
            'casual_leave_balance'      => $this->nullableFloat($data['casual_leave_balance'] ?? null) ?? 0,
            'sick_leave_balance'        => $this->nullableFloat($data['sick_leave_balance'] ?? null) ?? 0,
            'annual_leave_balance'      => $this->nullableFloat($data['annual_leave_balance'] ?? null) ?? 0,
            'maternity_paternity_leave' => $this->nullableFloat($data['maternity_paternity_leave'] ?? null) ?? 0,
            'leave_approver_id'         => !empty($data['leave_approver_id']) ? (int) $data['leave_approver_id'] : null,
        ]);

        // NOTE: attendance device/biometric settings are intentionally NOT
        // written here any more. They're configured afterwards on the
        // dedicated Teacher Attendance settings page — see
        // TeacherAttendanceController::edit()/update() and routes
        // 'teacher-attendance/{id}/settings'.

        $overrides = [];
        foreach (array_keys(TEACHER_OVERRIDABLE_MODULES) as $module) {
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $overrides[$module] = in_array($module, $data['permissions'], true);
            }
        }
        (new TeacherPermission())->save($teacherId, $overrides);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    /** Qualifications tab — dynamic rows (spec section 4). */
    private function saveQualifications(int $teacherId, array $data): void
    {
        $rows = [];
        foreach ($data['qualifications'] ?? [] as $q) {
            if (trim((string) ($q['degree'] ?? '')) === '') {
                continue;
            }
            $rows[] = [
                'degree'                  => trim($q['degree']),
                'university'              => trim((string) ($q['university'] ?? '')) ?: null,
                'board'                   => trim((string) ($q['board'] ?? '')) ?: null,
                'passing_year'            => trim((string) ($q['passing_year'] ?? '')) ?: null,
                'percentage_gpa'          => trim((string) ($q['percentage_gpa'] ?? '')) ?: null,
                'certifications'          => trim((string) ($q['certifications'] ?? '')) ?: null,
                'professional_training'   => trim((string) ($q['professional_training'] ?? '')) ?: null,
                'teaching_license_number' => trim((string) ($q['teaching_license_number'] ?? '')) ?: null,
                'license_expiry_date'     => trim((string) ($q['license_expiry_date'] ?? '')) ?: null,
                'is_highest'              => !empty($q['is_highest']) ? 1 : 0,
            ];
        }
        (new TeacherQualification())->replaceAll($teacherId, $rows);
    }

    /** Prior work experience — dynamic rows (spec section 3). */
    private function saveExperience(int $teacherId, array $data): void
    {
        $rows = [];
        foreach ($data['experience'] ?? [] as $ex) {
            if (trim((string) ($ex['institution_name'] ?? '')) === '') {
                continue;
            }
            $rows[] = [
                'institution_name' => trim($ex['institution_name']),
                'designation'      => trim((string) ($ex['designation'] ?? '')) ?: null,
                'from_date'        => trim((string) ($ex['from_date'] ?? '')) ?: null,
                'to_date'          => trim((string) ($ex['to_date'] ?? '')) ?: null,
                'description'      => trim((string) ($ex['description'] ?? '')) ?: null,
            ];
        }
        (new TeacherExperience())->replaceAll($teacherId, $rows);
    }

    /** Teaching Assignments tab — dynamic rows, the heart of spec section 5. */
    private function saveAssignments(int $teacherId, array $data): void
    {
        $rows = [];
        foreach ($data['assignments'] ?? [] as $a) {
            if (empty($a['class_id']) || empty($a['section_id']) || empty($a['subject_id']) || empty($a['academic_year_id'])) {
                continue;
            }
            $rows[] = [
                'academic_year_id'       => (int) $a['academic_year_id'],
                'campus'                 => trim((string) ($a['campus'] ?? '')) ?: null,
                'class_id'               => (int) $a['class_id'],
                'section_id'             => (int) $a['section_id'],
                'subject_id'             => (int) $a['subject_id'],
                'weekly_classes'         => !empty($a['weekly_classes']) ? (int) $a['weekly_classes'] : null,
                'classroom'              => trim((string) ($a['classroom'] ?? '')) ?: null,
                'room_number'            => trim((string) ($a['room_number'] ?? '')) ?: null,
                'effective_from'         => trim((string) ($a['effective_from'] ?? '')) ?: null,
                'effective_to'           => trim((string) ($a['effective_to'] ?? '')) ?: null,
                'is_class_teacher'       => !empty($a['is_class_teacher']) ? 1 : 0,
                'is_subject_coordinator' => !empty($a['is_subject_coordinator']) ? 1 : 0,
                'is_active'              => !empty($a['is_active']) || !isset($a['is_active']) ? 1 : 0,
            ];
        }
        (new TeacherAssignment())->replaceAll($teacherId, $rows);
    }

    /** Documents tab — multiple file uploads with a parallel name/remarks array (spec section 7). */
    private function saveDocuments(int $teacherId): void
    {
        if (empty($_FILES['document_files']['tmp_name']) || !is_array($_FILES['document_files']['tmp_name'])) {
            return;
        }
        $docModel = new TeacherDocument();
        $names = $_POST['document_names'] ?? [];
        $remarks = $_POST['document_remarks'] ?? [];
        foreach ($_FILES['document_files']['tmp_name'] as $i => $tmpName) {
            if (empty($tmpName)) {
                continue;
            }
            $file = [
                'name'     => $_FILES['document_files']['name'][$i],
                'tmp_name' => $tmpName,
                'error'    => $_FILES['document_files']['error'][$i],
                'size'     => $_FILES['document_files']['size'][$i],
            ];
            try {
                $path = handle_upload($file, 'teacher_documents', false);
                if ($path) {
                    $docModel->insert([
                        'teacher_id' => $teacherId,
                        'doc_name'   => trim((string) ($names[$i] ?? '')) ?: 'Other',
                        'file_path'  => $path,
                        'remarks'    => trim((string) ($remarks[$i] ?? '')) ?: null,
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            } catch (\RuntimeException $e) {
                // Skip a single bad document rather than failing the whole save.
                error_log('[TEACHER DOCUMENT UPLOAD SKIPPED] ' . $e->getMessage());
            }
        }
    }
}
