<?php
/**
 * Shared tabbed Add/Edit form for the Teacher Management Module.
 * Expects: $teacher (array|null), $contact, $addresses, $employment, $bank,
 * $qualifications, $experience, $assignments, $documents, $payroll, $leave,
 * $permissions, $loginUser, $classes, $sections, $subjects,
 * $academicYears, $managers, $errors, $nextEmployeeCode (create only)
 *
 * Attendance device/biometric setup is configured separately on the
 * Teacher Attendance settings page (TeacherController::attendanceSettings*)
 * once the teacher record exists — see database/teacher_module_migration.sql
 * for the teacher_attendance_settings table this still writes to.
 */
$teacher ??= [];
$isEdit = !empty($teacher);
$val = fn(string $key, $default = '') => e((string) old($key, $teacher[$key] ?? $default));
$permanent = $addresses['permanent'] ?? [];
$temporary = $addresses['temporary'] ?? [];
$cval = fn(string $key, $default = '') => e((string) old($key, $contact[$key] ?? $default));
$eval = fn(string $key, $default = '') => e((string) old($key, $employment[$key] ?? $default));
$bval = fn(string $key, $default = '') => e((string) old($key, $bank[$key] ?? $default));
$pval = fn(string $key, $default = '') => e((string) old($key, $payroll[$key] ?? $default));
$lval = fn(string $key, $default = '') => e((string) old($key, $leave[$key] ?? $default));
$uval = fn(string $key, $default = '') => e((string) old($key, $loginUser[$key] ?? $default));
?>
<p class="text-muted small mb-2">
    <i class="bi bi-info-circle me-1"></i>Use the <strong>Previous</strong> / <strong>Next</strong> buttons below each step to move through the form — your entries are kept as you go.
    Attendance device/biometric setup has moved to the <a href="<?= e(url('teacher-attendance/settings')) ?>">Teacher Attendance</a> page and is configured after the teacher is saved.
</p>
<ol class="wizard-indicators" aria-hidden="true">
    <li class="wizard-indicator" data-wizard-indicator>1. Personal</li>
    <li class="wizard-indicator" data-wizard-indicator>2. Contact</li>
    <li class="wizard-indicator" data-wizard-indicator>3. Employment</li>
    <li class="wizard-indicator" data-wizard-indicator>4. Qualifications</li>
    <li class="wizard-indicator" data-wizard-indicator>5. Assignments</li>
    <li class="wizard-indicator" data-wizard-indicator>6. Payroll</li>
    <li class="wizard-indicator" data-wizard-indicator>7. Leave</li>
    <li class="wizard-indicator" data-wizard-indicator>8. Documents</li>
    <li class="wizard-indicator" data-wizard-indicator>9. Login</li>
    <li class="wizard-indicator" data-wizard-indicator>10. Review</li>
</ol>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= e($errors['general'][0]) ?></div>
<?php endif; ?>

<div class="wizard-body">

    <!-- ==================== 1. PERSONAL INFORMATION ==================== -->
    <div class="wizard-step" id="tPersonal">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <label class="form-label d-block">Photo <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                <?php if (!empty($teacher['photo_path'])): ?>
                    <img id="photoPreview" src="<?= e(upload_url($teacher['photo_path'])) ?>" class="rounded-circle mb-2" style="width:110px;height:110px;object-fit:cover;" alt="Photo">
                <?php else: ?>
                    <img id="photoPreview" class="rounded-circle mb-2 d-none" style="width:110px;height:110px;object-fit:cover;" alt="Photo">
                    <div id="photoPlaceholder" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:110px;height:110px;">
                        <i class="bi bi-person-fill" style="font-size:42px;color:#ccc;"></i>
                    </div>
                <?php endif; ?>
                <input type="file" name="photo" id="photo" class="form-control form-control-sm <?= field_error($errors, 'photo') ? 'is-invalid' : '' ?>" accept=".jpg,.jpeg,.png,.webp" <?= $isEdit ? '' : 'required' ?>>
                <div class="invalid-feedback"><?= e(field_error($errors, 'photo')) ?></div>
                <div class="form-text small">Max 5MB.<?= $isEdit ? ' Leave blank to keep current.' : '' ?></div>
            </div>
            <div class="col-md-3 text-center">
                <label class="form-label d-block">Signature Upload</label>
                <?php if (!empty($teacher['signature_path'])): ?>
                    <img id="signaturePreview" src="<?= e(upload_url($teacher['signature_path'])) ?>" class="mb-2 border rounded" style="width:110px;height:60px;object-fit:contain;" alt="Signature">
                <?php else: ?>
                    <img id="signaturePreview" class="mb-2 border rounded d-none" style="width:110px;height:60px;object-fit:contain;" alt="Signature">
                    <div id="signaturePlaceholder" class="border rounded bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:110px;height:60px;">
                        <i class="bi bi-vector-pen text-muted"></i>
                    </div>
                <?php endif; ?>
                <input type="file" name="signature" id="signature" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text small">Optional.</div>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                        <input type="text" name="employee_number" class="form-control <?= field_error($errors, 'employee_number') ? 'is-invalid' : '' ?>" value="<?= $isEdit ? $val('employee_number') : e(old('employee_number', $nextEmployeeCode ?? '')) ?>" required>
                        <div class="invalid-feedback"><?= e(field_error($errors, 'employee_number')) ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teacher ID</label>
                        <input type="text" class="form-control bg-light" value="<?= $isEdit ? e($teacher['id']) : 'Auto-generated on save' ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Full Name (auto)</label>
                        <input type="text" id="fullNamePreview" class="form-control bg-light" value="<?= e(teacher_full_name($val('first_name'), $val('middle_name'), $val('last_name'))) ?>" readonly>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" id="first_name" class="form-control <?= field_error($errors, 'first_name') ? 'is-invalid' : '' ?>" value="<?= $val('first_name') ?>" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'first_name')) ?></div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?= $val('middle_name') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" id="last_name" class="form-control <?= field_error($errors, 'last_name') ? 'is-invalid' : '' ?>" value="<?= $val('last_name') ?>" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'last_name')) ?></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select <?= field_error($errors, 'gender') ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select --</option>
                    <?php foreach (GENDER_OPTIONS as $gv => $gl): ?>
                        <option value="<?= e($gv) ?>" <?= old('gender', $teacher['gender'] ?? '') === $gv ? 'selected' : '' ?>><?= e($gl) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e(field_error($errors, 'gender')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" name="dob" id="dob" class="form-control <?= field_error($errors, 'dob') ? 'is-invalid' : '' ?>" value="<?= $val('dob') ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="form-text" id="ageDisplay"><?= $val('dob') !== '' ? e(student_age_display($val('dob'))) : '' ?></div>
                <div class="invalid-feedback"><?= e(field_error($errors, 'dob')) ?></div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Blood Group</label>
                <select name="blood_group" class="form-select">
                    <option value="">--</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                        <option value="<?= e($bg) ?>" <?= old('blood_group', $teacher['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Religion</label>
                <select name="religion" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (RELIGION_OPTIONS as $r): ?>
                        <option value="<?= e($r) ?>" <?= old('religion', $teacher['religion'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nationality</label>
                <select name="nationality" id="nationality" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (['Nepali', 'Indian', 'Chinese', 'Bhutanese', 'Bangladeshi', 'Pakistani', 'Sri Lankan', 'Other'] as $n): ?>
                        <option value="<?= e($n) ?>" <?= old('nationality', $teacher['nationality'] ?? '') === $n ? 'selected' : '' ?>><?= e($n) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Marital Status</label>
                <select name="marital_status" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (MARITAL_STATUS_OPTIONS as $mv => $ml): ?>
                        <option value="<?= e($mv) ?>" <?= old('marital_status', $teacher['marital_status'] ?? '') === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Citizenship No. / National ID</label>
                <input type="text" name="citizenship_number" class="form-control" value="<?= $val('citizenship_number') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Passport Number</label>
                <input type="text" name="passport_number" class="form-control" value="<?= $val('passport_number') ?>">
            </div>
        </div>
    </div>

    <!-- ==================== 2. CONTACT INFORMATION ==================== -->
    <div class="wizard-step" id="tContact">
        <h6 class="fw-bold mb-3">Contact</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" name="mobile_number" class="form-control <?= field_error($errors, 'mobile_number') ? 'is-invalid' : '' ?>" value="<?= $cval('mobile_number', $teacher['phone'] ?? '') ?>" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'mobile_number')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Alternate Mobile</label>
                <input type="text" name="alternate_mobile_number" class="form-control" value="<?= $cval('alternate_mobile_number') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Personal Email</label>
                <input type="email" name="personal_email" class="form-control <?= field_error($errors, 'personal_email') ? 'is-invalid' : '' ?>" value="<?= $cval('personal_email') ?>">
                <div class="invalid-feedback"><?= e(field_error($errors, 'personal_email')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Official School Email</label>
                <input type="email" name="official_email" class="form-control <?= field_error($errors, 'official_email') ? 'is-invalid' : '' ?>" value="<?= $cval('official_email') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Emergency Contact Person</label>
                <input type="text" name="emergency_contact_name" class="form-control" value="<?= $cval('emergency_contact_name') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Emergency Contact Number</label>
                <input type="text" name="emergency_contact_number" class="form-control" value="<?= $cval('emergency_contact_number') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Relationship</label>
                <input type="text" name="emergency_contact_relationship" class="form-control" value="<?= $cval('emergency_contact_relationship') ?>">
            </div>
        </div>

        <h6 class="fw-bold mb-3">Permanent Address</h6>
        <div class="row g-3 mb-2">
            <div class="col-md-4"><label class="form-label">Address</label><input type="text" name="permanent_address_line" id="permanent_address_line" class="form-control" value="<?= e(old('permanent_address_line', $permanent['address_line'] ?? '')) ?>"></div>
            <div class="col-md-2"><label class="form-label">City</label><input type="text" name="permanent_city" id="permanent_city" class="form-control" value="<?= e(old('permanent_city', $permanent['city'] ?? '')) ?>"></div>
            <div class="col-md-2">
                <label class="form-label">State/Province</label>
                <select name="permanent_state_province" id="permanent_state_province" class="form-select">
                    <option value="">Select country first</option>
                </select>
                <input type="text" name="permanent_state_province" id="permanent_state_province_text" class="form-control mt-1 d-none" placeholder="Enter province/state" value="<?= e(old('permanent_state_province', $permanent['state_province'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Country</label>
                <select name="permanent_country" id="permanent_country" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (['Nepal', 'India', 'China', 'Bhutan', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Other'] as $co): ?>
                        <option value="<?= e($co) ?>" <?= e(old('permanent_country', $permanent['country'] ?? 'Nepal')) === $co ? 'selected' : '' ?>><?= e($co) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Postal Code</label><input type="text" name="permanent_postal_code" id="permanent_postal_code" class="form-control" value="<?= e(old('permanent_postal_code', $permanent['postal_code'] ?? '')) ?>"></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Temporary Address</h6>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="sameAsPermanent">
                <label class="form-check-label small" for="sameAsPermanent">Same as permanent</label>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Address</label><input type="text" name="temporary_address_line" id="temporary_address_line" class="form-control" value="<?= e(old('temporary_address_line', $temporary['address_line'] ?? '')) ?>"></div>
            <div class="col-md-2"><label class="form-label">City</label><input type="text" name="temporary_city" id="temporary_city" class="form-control" value="<?= e(old('temporary_city', $temporary['city'] ?? '')) ?>"></div>
            <div class="col-md-2">
                <label class="form-label">State/Province</label>
                <select name="temporary_state_province" id="temporary_state_province" class="form-select">
                    <option value="">Select country first</option>
                </select>
                <input type="text" name="temporary_state_province" id="temporary_state_province_text" class="form-control mt-1 d-none" placeholder="Enter province/state" value="<?= e(old('temporary_state_province', $temporary['state_province'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Country</label>
                <select name="temporary_country" id="temporary_country" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (['Nepal', 'India', 'China', 'Bhutan', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Other'] as $co): ?>
                        <option value="<?= e($co) ?>" <?= e(old('temporary_country', $temporary['country'] ?? '')) === $co ? 'selected' : '' ?>><?= e($co) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Postal Code</label><input type="text" name="temporary_postal_code" id="temporary_postal_code" class="form-control" value="<?= e(old('temporary_postal_code', $temporary['postal_code'] ?? '')) ?>"></div>
        </div>
    </div>

    <!-- ==================== 3. EMPLOYMENT INFORMATION ==================== -->
    <div class="wizard-step" id="tEmployment">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                <input type="date" name="joining_date" class="form-control <?= field_error($errors, 'joining_date') ? 'is-invalid' : '' ?>" value="<?= $val('joining_date') ?>" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'joining_date')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                <select name="employment_type" class="form-select <?= field_error($errors, 'employment_type') ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select --</option>
                    <?php foreach (EMPLOYMENT_TYPES as $etv => $etl): ?>
                        <option value="<?= e($etv) ?>" <?= old('employment_type', $employment['employment_type'] ?? '') === $etv ? 'selected' : '' ?>><?= e($etl) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e(field_error($errors, 'employment_type')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Designation <span class="text-danger">*</span></label>
                <input type="text" name="designation" class="form-control <?= field_error($errors, 'designation') ? 'is-invalid' : '' ?>" value="<?= $eval('designation') ?>" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'designation')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= $eval('department') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Reporting Manager / Principal</label>
                <select name="reporting_manager_id" class="form-select">
                    <option value="">-- None --</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= (string) old('reporting_manager_id', $employment['reporting_manager_id'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?> (<?= e($m['employee_number']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Qualification (summary)</label>
                <input type="text" name="highest_qualification" class="form-control" value="<?= $val('qualification') ?>" placeholder="e.g. M.Ed in Education">
            </div>
            <div class="col-md-4">
                <label class="form-label">Specialization</label>
                <input type="text" name="specialization" class="form-control" value="<?= $eval('specialization') ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Experience (Years)</label>
                <input type="number" step="0.1" min="0" name="experience_years" class="form-control" value="<?= $val('experience_years') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Previous School</label>
                <input type="text" name="previous_school" class="form-control" value="<?= $eval('previous_school') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Previous Experience Details</label>
                <input type="text" name="previous_experience_details" class="form-control" value="<?= $eval('previous_experience_details') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Salary Grade</label>
                <input type="text" name="salary_grade" class="form-control" value="<?= $eval('salary_grade') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Basic Salary</label>
                <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" value="<?= $val('salary') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Allowances</label>
                <input type="number" step="0.01" min="0" name="employment_allowances" class="form-control" value="<?= $eval('allowances') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Employment Status <span class="text-danger">*</span></label>
                <select name="employment_status" class="form-select <?= field_error($errors, 'employment_status') ? 'is-invalid' : '' ?>" required>
                    <?php foreach (TEACHER_STATUSES as $sv => $sl): ?>
                        <option value="<?= e($sv) ?>" <?= old('employment_status', $employment['employment_status'] ?? 'active') === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e(field_error($errors, 'employment_status')) ?></div>
            </div>
        </div>

        <h6 class="fw-bold mb-3">Bank &amp; Tax Details</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><label class="form-label">Bank Account Number</label><input type="text" name="bank_account_number" class="form-control" value="<?= $bval('bank_account_number') ?>"></div>
            <div class="col-md-3"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?= $bval('bank_name') ?>"></div>
            <div class="col-md-3"><label class="form-label">Branch</label><input type="text" name="branch" class="form-control" value="<?= $bval('branch') ?>"></div>
            <div class="col-md-3"><label class="form-label">PAN / Tax Number</label><input type="text" name="pan_number" class="form-control" value="<?= $bval('pan_number') ?>"></div>
            <div class="col-md-3"><label class="form-label">PF Number</label><input type="text" name="pf_number" class="form-control" value="<?= $bval('pf_number') ?>"></div>
            <div class="col-md-3"><label class="form-label">Insurance Number</label><input type="text" name="insurance_number" class="form-control" value="<?= $bval('insurance_number') ?>"></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Prior Work Experience</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="experience"><i class="bi bi-plus-lg me-1"></i>Add Row</button>
        </div>
        <div id="experienceRows">
            <?php foreach ((old('experience') ?? $experience ?: []) as $i => $ex): ?>
                <?php include __DIR__ . '/_experience_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <template id="experienceTemplate"><?php $i = '__INDEX__'; $ex = []; include __DIR__ . '/_experience_row.php'; ?></template>
    </div>

    <!-- ==================== 4. QUALIFICATIONS ==================== -->
    <div class="wizard-step" id="tQualifications">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Qualifications <span class="text-muted small">(add as many as needed)</span></h6>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="qualifications"><i class="bi bi-plus-lg me-1"></i>Add Qualification</button>
        </div>
        <div id="qualificationsRows">
            <?php foreach ((old('qualifications') ?? $qualifications ?: []) as $i => $q): ?>
                <?php include __DIR__ . '/_qualification_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <template id="qualificationsTemplate"><?php $i = '__INDEX__'; $q = []; include __DIR__ . '/_qualification_row.php'; ?></template>
    </div>

    <!-- ==================== 5. TEACHING ASSIGNMENTS ==================== -->
    <div class="wizard-step" id="tAssignments">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Teaching Assignments <span class="text-muted small">(one row per Class + Section + Subject)</span></h6>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="assignments"><i class="bi bi-plus-lg me-1"></i>Add Assignment</button>
        </div>
        <div id="assignmentsRows">
            <?php foreach ((old('assignments') ?? $assignments ?: []) as $i => $a): ?>
                <?php include __DIR__ . '/_assignment_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <template id="assignmentsTemplate"><?php $i = '__INDEX__'; $a = []; include __DIR__ . '/_assignment_row.php'; ?></template>
    </div>

    <!-- ==================== 6. PAYROLL ==================== -->
    <div class="wizard-step" id="tPayroll">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Salary Structure</label>
                <input type="text" name="salary_structure" class="form-control" value="<?= $pval('salary_structure') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Type</label>
                <select name="payment_type" class="form-select payroll-calc">
                    <?php foreach (PAYMENT_TYPES as $ptv => $ptl): ?>
                        <option value="<?= e($ptv) ?>" <?= old('payment_type', $payroll['payment_type'] ?? 'bank_transfer') === $ptv ? 'selected' : '' ?>><?= e($ptl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tax Percentage</label>
                <input type="number" step="0.01" min="0" max="100" name="tax_percentage" class="form-control payroll-calc" value="<?= $pval('tax_percentage') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Allowances</label>
                <input type="number" step="0.01" min="0" name="payroll_allowances" class="form-control payroll-calc" value="<?= $pval('allowances') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Deductions</label>
                <input type="number" step="0.01" min="0" name="deductions" class="form-control payroll-calc" value="<?= $pval('deductions') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Overtime Rate</label>
                <input type="number" step="0.01" min="0" name="overtime_rate" class="form-control" value="<?= $pval('overtime_rate') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bonus</label>
                <input type="number" step="0.01" min="0" name="bonus" class="form-control payroll-calc" value="<?= $pval('bonus') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Provident Fund</label>
                <input type="number" step="0.01" min="0" name="provident_fund" class="form-control payroll-calc" value="<?= $pval('provident_fund') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Insurance</label>
                <input type="number" step="0.01" min="0" name="payroll_insurance" class="form-control payroll-calc" value="<?= $pval('insurance') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pension</label>
                <input type="number" step="0.01" min="0" name="pension" class="form-control payroll-calc" value="<?= $pval('pension') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Net Salary (auto)</label>
                <input type="number" step="0.01" name="net_salary" id="net_salary" class="form-control bg-light fw-bold" value="<?= $pval('net_salary') ?>" readonly>
                <div class="form-text small">Basic + Allowances + Bonus − Deductions − PF − Insurance − Pension − Tax%</div>
            </div>
        </div>
    </div>

    <!-- ==================== 8. LEAVE MANAGEMENT ==================== -->
    <div class="wizard-step" id="tLeave">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Casual Leave Balance</label><input type="number" step="0.5" min="0" name="casual_leave_balance" class="form-control" value="<?= $lval('casual_leave_balance', 12) ?>"></div>
            <div class="col-md-3"><label class="form-label">Sick Leave Balance</label><input type="number" step="0.5" min="0" name="sick_leave_balance" class="form-control" value="<?= $lval('sick_leave_balance', 6) ?>"></div>
            <div class="col-md-3"><label class="form-label">Annual Leave Balance</label><input type="number" step="0.5" min="0" name="annual_leave_balance" class="form-control" value="<?= $lval('annual_leave_balance', 15) ?>"></div>
            <div class="col-md-3"><label class="form-label">Maternity/Paternity Leave</label><input type="number" step="0.5" min="0" name="maternity_paternity_leave" class="form-control" value="<?= $lval('maternity_paternity_leave', 0) ?>"></div>
            <div class="col-md-6">
                <label class="form-label">Leave Approver</label>
                <select name="leave_approver_id" class="form-select">
                    <option value="">-- None --</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= (string) old('leave_approver_id', $leave['leave_approver_id'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?> (<?= e($m['employee_number']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ==================== 9. DOCUMENTS ==================== -->
    <div class="wizard-step" id="tDocuments">
        <?php if ($isEdit && !empty($documents)): ?>
            <h6 class="fw-bold mb-2">Existing Documents</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Name</th><th>Remarks</th><th>Uploaded</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($documents as $d): ?>
                            <tr>
                                <td><?= e($d['doc_name']) ?></td>
                                <td><?= e($d['remarks'] ?? '—') ?></td>
                                <td><?= e(format_date($d['uploaded_at'])) ?></td>
                                <td>
                                    <a href="<?= e(url('documents/teacher/' . $d['id'] . '/download')) ?>" class="btn btn-sm btn-outline-primary" onclick="if(window.SA){window.SA.toast('success','Downloading file...');}"><i class="bi bi-download"></i></a>
                                    <a href="<?= e(url('teachers/' . $teacher['id'] . '/documents/' . $d['id'] . '/delete')) ?>" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); document.getElementById('delDoc<?= $d['id'] ?>').requestSubmit ? document.getElementById('delDoc<?= $d['id'] ?>').requestSubmit() : document.getElementById('delDoc<?= $d['id'] ?>').dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));"><i class="bi bi-trash-fill"></i></a>
                                    <form id="delDoc<?= $d['id'] ?>" method="POST" action="<?= e(url('teachers/' . $teacher['id'] . '/documents/' . $d['id'] . '/delete')) ?>" class="d-none confirm-delete" data-confirm-message="Delete this document? This action cannot be undone."><?= csrf_field() ?></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Upload New Documents</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="documents"><i class="bi bi-plus-lg me-1"></i>Add Document</button>
        </div>
        <div id="documentsRows"></div>
        <template id="documentsTemplate"><?php $i = '__INDEX__'; include __DIR__ . '/_document_row.php'; ?></template>
    </div>

    <!-- ==================== 10. LOGIN & PERMISSIONS ==================== -->
    <div class="wizard-step" id="tLogin">
        <h6 class="fw-bold mb-3">Login Credentials</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control <?= field_error($errors, 'username') ? 'is-invalid' : '' ?>" value="<?= $uval('username') ?>" autocomplete="off">
                <div class="invalid-feedback"><?= e(field_error($errors, 'username')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Password <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                <input type="password" name="password" class="form-control <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                <div class="form-text small"><?= $isEdit ? 'Leave blank to keep current password.' : 'Minimum 8 characters.' ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control <?= field_error($errors, 'password_confirmation') ? 'is-invalid' : '' ?>" autocomplete="new-password">
                <div class="invalid-feedback"><?= e(field_error($errors, 'password_confirmation')) ?></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select <?= field_error($errors, 'role') ? 'is-invalid' : '' ?>">
                    <option value="">-- Select --</option>
                    <?php foreach (TEACHER_LOGIN_ROLES as $rv => $rl): ?>
                        <option value="<?= e($rv) ?>" <?= old('role', $loginUser['role'] ?? ROLE_TEACHER) === $rv ? 'selected' : '' ?>><?= e($rl) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e(field_error($errors, 'role')) ?></div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="two_factor_enabled" id="two_factor_enabled" value="1" <?= old('two_factor_enabled', $loginUser['two_factor_enabled'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="two_factor_enabled">Two-Factor Authentication</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="account_active" id="account_active" value="1" <?= old('account_active', $loginUser['is_active'] ?? true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="account_active">Account Active</label>
                </div>
            </div>
            <?php if ($isEdit && !empty($loginUser['last_login_at'])): ?>
                <div class="col-md-4">
                    <label class="form-label">Last Login</label>
                    <input type="text" class="form-control bg-light" value="<?= e(format_datetime($loginUser['last_login_at'])) ?>" readonly>
                </div>
            <?php endif; ?>
        </div>

        <h6 class="fw-bold mb-3">Module Permission Overrides</h6>
        <p class="text-muted small">Defaults come from the selected role. Check a box to explicitly grant access; uncheck to explicitly revoke it.</p>
        <div class="row g-2">
            <?php $overrides = old('permissions', array_keys(array_filter($permissions ?? []))); ?>
            <?php foreach (TEACHER_OVERRIDABLE_MODULES as $mv => $ml): ?>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= e($mv) ?>" id="perm_<?= e($mv) ?>" <?= in_array($mv, $overrides, true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="perm_<?= e($mv) ?>"><?= e($ml) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ==================== 10. REVIEW & SUBMIT ==================== -->
    <div class="wizard-step" id="tReview">
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-1"></i>
            Review the summary below, then click <strong><?= $isEdit ? 'Update Teacher' : 'Save Teacher' ?></strong> to submit. Use <strong>Previous</strong> if anything needs changing.
        </div>
        <div id="reviewSummary" class="row g-3"></div>
    </div>

</div>

<div class="d-flex justify-content-between align-items-center mt-4 border-top pt-3">
    <button type="button" class="btn btn-light d-none" data-wizard-prev><i class="bi bi-arrow-left me-1"></i>Previous</button>
    <div class="d-flex gap-2 ms-auto">
        <a href="<?= e(url($isEdit ? 'teachers/' . $teacher['id'] : 'teachers')) ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-primary" data-wizard-next>Next<i class="bi bi-arrow-right ms-1"></i></button>
        <button type="submit" class="btn btn-primary d-none" data-wizard-submit><i class="bi bi-check-circle-fill me-1"></i><?= $isEdit ? 'Update Teacher' : 'Save Teacher' ?></button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<script src="<?= e(asset('data/nepal-address.js')) ?>"></script>
<script src="<?= e(asset('data/address-data.js')) ?>"></script>
<script src="<?= e(asset('js/form-wizard.js')) ?>"></script>
<script>
(function () {
    // ---- multi-step wizard: Previous/Next buttons, per-step validation,
    // Save button only reachable on the final Review step ----
    var teacherForm = document.getElementById('teacherForm');
    var wizard = window.FormWizard ? window.FormWizard.init(teacherForm) : null;

    // ---- searchable dropdowns (select2 needs its <select> visible to size
    // itself correctly, so (re)initialise the moment each relevant step is
    // first shown rather than once at page load) ----
    function initSelect2InStep(stepEl) {
        if (!window.jQuery || !stepEl) return;
        jQuery(stepEl).find('select[name="reporting_manager_id"], select[name="leave_approver_id"]').each(function () {
            if (!jQuery(this).hasClass('select2-hidden-accessible')) {
                jQuery(this).select2({ width: '100%', placeholder: 'Search...' });
            }
        });
    }
    if (teacherForm) {
        initSelect2InStep(document.getElementById('tPersonal'));
        teacherForm.addEventListener('wizard:step', function (e) { initSelect2InStep(e.detail.step); });
    }

    // ---- live Full Name preview + Age ----
    function refreshName() {
        var f = document.getElementById('first_name').value.trim();
        var m = document.getElementById('middle_name').value.trim();
        var l = document.getElementById('last_name').value.trim();
        document.getElementById('fullNamePreview').value = [f, m, l].filter(Boolean).join(' ');
    }
    ['first_name', 'middle_name', 'last_name'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', refreshName);
    });

    document.getElementById('dob').addEventListener('change', function () {
        var d = new Date(this.value);
        if (isNaN(d.getTime())) { document.getElementById('ageDisplay').textContent = ''; return; }
        var today = new Date();
        var years = today.getFullYear() - d.getFullYear();
        var m = today.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < d.getDate())) years--;
        document.getElementById('ageDisplay').textContent = years + ' years old';
    });

    // ---- photo / signature preview ----
    function wireUpload(inputId, previewId, placeholderId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.getElementById(previewId);
                img.src = e.target.result;
                img.classList.remove('d-none');
                var ph = document.getElementById(placeholderId);
                if (ph) ph.classList.add('d-none');
            };
            reader.readAsDataURL(this.files[0]);
        });
    }
    wireUpload('photo', 'photoPreview', 'photoPlaceholder');
    wireUpload('signature', 'signaturePreview', 'signaturePlaceholder');

    // ---- Country -> Province/State cascading dropdowns (permanent + temporary
    // addresses). Nepal gets its 7 provinces (NEPAL_PROVINCES); the other six
    // named countries get PROVINCES_BY_COUNTRY; "Other" (or no country yet)
    // falls back to a free-text province/state field. Exactly one of the
    // select/text pair is enabled at a time so only one value ever posts.
    function wireAddressCountry(prefix) {
        var countryEl = document.getElementById(prefix + '_country');
        var provinceSelect = document.getElementById(prefix + '_state_province');
        var provinceText = document.getElementById(prefix + '_state_province_text');
        if (!countryEl || !provinceSelect || !provinceText) return null;

        function showSelect(options, current) {
            provinceSelect.classList.remove('d-none');
            provinceSelect.disabled = false;
            provinceText.classList.add('d-none');
            provinceText.disabled = true;
            provinceSelect.innerHTML = '<option value="">-- Select --</option>' +
                options.map(function (p) {
                    return '<option value="' + p + '"' + (p === current ? ' selected' : '') + '>' + p + '</option>';
                }).join('');
        }
        function showText() {
            provinceSelect.classList.add('d-none');
            provinceSelect.disabled = true;
            provinceText.classList.remove('d-none');
            provinceText.disabled = false;
        }
        function apply(country, current) {
            if (country === 'Nepal') {
                showSelect(Object.keys(NEPAL_PROVINCES), current);
            } else if (country && PROVINCES_BY_COUNTRY[country]) {
                showSelect(PROVINCES_BY_COUNTRY[country], current);
            } else {
                showText();
            }
        }

        apply(countryEl.value.trim(), provinceText.value);
        countryEl.addEventListener('change', function () { apply(this.value.trim(), ''); });
        return { countryEl: countryEl, provinceSelect: provinceSelect, provinceText: provinceText, apply: apply };
    }
    var permanentAddress = wireAddressCountry('permanent');
    var temporaryAddress = wireAddressCountry('temporary');

    // ---- "same as permanent" address copy ----
    document.getElementById('sameAsPermanent').addEventListener('change', function () {
        if (!this.checked) return;
        ['address_line', 'city', 'postal_code'].forEach(function (f) {
            document.getElementById('temporary_' + f).value = document.getElementById('permanent_' + f).value;
        });
        if (permanentAddress && temporaryAddress) {
            var country = permanentAddress.countryEl.value;
            var province = permanentAddress.provinceSelect.disabled
                ? permanentAddress.provinceText.value
                : permanentAddress.provinceSelect.value;
            temporaryAddress.countryEl.value = country;
            temporaryAddress.apply(country.trim(), province);
        }
    });

    // ---- dynamic add-row for qualifications / experience / assignments / documents ----
    var counters = { qualifications: 0, experience: 0, assignments: 0, documents: 0 };
    document.querySelectorAll('[data-add-row]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var section = this.getAttribute('data-add-row');
            var template = document.getElementById(section + 'Template');
            var container = document.getElementById(section + 'Rows');
            var idx = counters[section]++;
            var html = template.innerHTML.replace(/__INDEX__/g, idx);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            container.appendChild(wrapper.firstElementChild);
        });
    });
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-remove-row]')) {
            var row = e.target.closest('.dynamic-row');
            if (row) row.remove();
        }
    });
    // seed one empty assignment/qualification/document row by default for a new teacher
    <?php if (!$isEdit): ?>
    document.querySelector('[data-add-row="qualifications"]').click();
    document.querySelector('[data-add-row="assignments"]').click();
    document.querySelector('[data-add-row="documents"]').click();
    <?php endif; ?>

    // ---- class -> section/subject cascading dropdowns for assignment rows ----
    document.addEventListener('change', function (e) {
        if (!e.target.matches('.assignment-class')) return;
        var row = e.target.closest('.dynamic-row');
        var classId = e.target.value;
        var sectionSel = row.querySelector('.assignment-section');
        var subjectSel = row.querySelector('.assignment-subject');
        if (!classId) { return; }
        fetch('<?= e(url('teachers/sections-for-class')) ?>/' + classId).then(r => r.json()).then(function (data) {
            sectionSel.innerHTML = '<option value="">-- Select --</option>' + data.map(s => '<option value="' + s.id + '">' + s.name + '</option>').join('');
        });
        fetch('<?= e(url('teachers/subjects-for-class')) ?>/' + classId).then(r => r.json()).then(function (data) {
            subjectSel.innerHTML = '<option value="">-- Select --</option>' + data.map(s => '<option value="' + s.id + '">' + s.name + '</option>').join('');
        });
    });

    // ---- payroll net salary auto-calc ----
    function recalcPayroll() {
        var basic = parseFloat(document.querySelector('input[name="basic_salary"]').value) || 0;
        var allow = parseFloat(document.querySelector('input[name="payroll_allowances"]').value) || 0;
        var ded = parseFloat(document.querySelector('input[name="deductions"]').value) || 0;
        var bonus = parseFloat(document.querySelector('input[name="bonus"]').value) || 0;
        var pf = parseFloat(document.querySelector('input[name="provident_fund"]').value) || 0;
        var ins = parseFloat(document.querySelector('input[name="payroll_insurance"]').value) || 0;
        var pension = parseFloat(document.querySelector('input[name="pension"]').value) || 0;
        var taxPct = parseFloat(document.querySelector('input[name="tax_percentage"]').value) || 0;
        var gross = basic + allow + bonus;
        var tax = gross * (taxPct / 100);
        var net = gross - ded - pf - ins - pension - tax;
        document.getElementById('net_salary').value = net.toFixed(2);
    }
    document.querySelectorAll('.payroll-calc').forEach(function (el) { el.addEventListener('input', recalcPayroll); });
    document.querySelector('input[name="basic_salary"]').addEventListener('input', recalcPayroll);

    // ---- Review & Submit summary ----
    if (!teacherForm) { return; }
    teacherForm.addEventListener('wizard:step', function (e) {
        if (!e.detail || !e.detail.step || e.detail.step.id !== 'tReview') { return; }
        var rows = [
            ['Full Name', document.getElementById('fullNamePreview').value],
            ['Employee Code', document.querySelector('input[name="employee_number"]').value],
            ['Gender', document.querySelector('select[name="gender"]').value],
            ['Mobile', document.querySelector('input[name="mobile_number"]').value],
            ['Designation', document.querySelector('input[name="designation"]').value],
            ['Employment Type', document.querySelector('select[name="employment_type"]').value],
            ['Employment Status', document.querySelector('select[name="employment_status"]').value],
            ['Username', document.querySelector('input[name="username"]').value || '—'],
            ['Role', document.querySelector('select[name="role"]').value || '—'],
            ['Net Salary', document.getElementById('net_salary').value || '—'],
            ['Qualifications entered', document.querySelectorAll('#qualificationsRows .dynamic-row').length],
            ['Teaching Assignments entered', document.querySelectorAll('#assignmentsRows .dynamic-row').length],
            ['Documents attached', document.querySelectorAll('#documentsRows .dynamic-row').length],
        ];
        document.getElementById('reviewSummary').innerHTML = rows.map(function (r) {
            return '<div class="col-md-4"><div class="border rounded p-2 h-100"><div class="text-muted small">' + r[0] + '</div><div class="fw-semibold">' + (r[1] || '—') + '</div></div></div>';
        }).join('');
    });
})();
</script>

