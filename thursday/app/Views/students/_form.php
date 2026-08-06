<?php
/**
 * Shared add/edit form fields for a Student.
 * Expects: $student (array|null), $classes, $sections, $parents, $academicYears, $errors
 * On create only: $nextAdmissionNumber (read-only preview of the auto-generated number)
 */
$student ??= [];
$val = fn(string $key, $default = '') => e((string) old($key, $student[$key] ?? $default));
$isEdit = !empty($student);
?>

<p class="text-muted small mb-2">
    <i class="bi bi-info-circle me-1"></i>Use the <strong><?= e(t('previous')) ?></strong> / <strong><?= e(t('next')) ?></strong> buttons below each step to move through the form — your entries are kept as you go.
</p>
<ol class="wizard-indicators" aria-hidden="true">
    <li class="wizard-indicator" data-wizard-indicator>1. Basic Info</li>
    <li class="wizard-indicator" data-wizard-indicator>2. Contact</li>
    <li class="wizard-indicator" data-wizard-indicator>3. Address</li>
    <li class="wizard-indicator" data-wizard-indicator>4. Guardian</li>
    <li class="wizard-indicator" data-wizard-indicator>5. Academic</li>
    <li class="wizard-indicator" data-wizard-indicator>6. Additional</li>
    <li class="wizard-indicator" data-wizard-indicator>7. Review</li>
</ol>

<div class="wizard-step" id="sBasic">
<h6 class="fw-bold mb-3"><?= e(t('h_basic_information')) ?></h6>
<div class="row g-3">
    <div class="col-md-4 text-center">
        <label class="form-label d-block">Photo <span class="text-danger">*</span></label>
        <img id="photoPreviewImg" src="<?= !empty($student['photo_path']) ? e(upload_url($student['photo_path'])) : '' ?>" class="rounded-circle mb-2 <?= empty($student['photo_path']) ? 'd-none' : '' ?>" style="width:120px;height:120px;object-fit:cover;" alt="Photo">
        <div id="photoPlaceholder" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2 <?= !empty($student['photo_path']) ? 'd-none' : '' ?>" style="width:120px;height:120px;">
            <i class="bi bi-person-fill" style="font-size:48px;color:#ccc;"></i>
        </div>
        <input type="file" name="photo" id="photo" class="form-control <?= field_error($errors, 'photo') ? 'is-invalid' : '' ?>" accept=".jpg,.jpeg,.png,.webp" <?= $isEdit ? '' : 'required' ?>>
        <div class="invalid-feedback"><?= e(field_error($errors, 'photo')) ?></div>
        <div class="form-text">JPG, PNG or WEBP. Max 5MB, min 200x200px.<?= $isEdit ? ' Leave blank to keep the current photo.' : '' ?></div>
    </div>

    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="full_name" class="form-control <?= field_error($errors, 'full_name') ? 'is-invalid' : '' ?>" value="<?= $val('full_name') ?>" minlength="3" maxlength="100" pattern="[A-Za-z ]{3,100}" required>
                <div class="invalid-feedback"><?= e(field_error($errors, 'full_name')) ?: '3-100 letters and spaces only.' ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Admission Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" value="<?= $isEdit ? e($student['admission_number']) : e($nextAdmissionNumber ?? '') ?>" readonly>
                <div class="form-text">Auto-generated<?= $isEdit ? '' : ' — reserved at the moment you click Save Student' ?>.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                <input type="text" name="roll_number" id="roll_number" class="form-control <?= field_error($errors, 'roll_number') ? 'is-invalid' : '' ?>" value="<?= $val('roll_number') ?>" placeholder="Auto-filled once Class/Section/Year are chosen">
                <div class="invalid-feedback"><?= e(field_error($errors, 'roll_number')) ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select <?= field_error($errors, 'gender') ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select --</option>
                    <?php foreach (GENDER_OPTIONS as $gv => $gl): ?>
                        <option value="<?= e($gv) ?>" <?= old('gender', $student['gender'] ?? '') === $gv ? 'selected' : '' ?>><?= e(tr_const(GENDER_OPTIONS, $gv)) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e(field_error($errors, 'gender')) ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" name="dob" id="dob" class="form-control <?= field_error($errors, 'dob') ? 'is-invalid' : '' ?>" value="<?= $val('dob') ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="form-text" id="ageDisplay"><?= $val('dob') !== '' ? e(student_age_display($val('dob'))) : '' ?></div>
                <div class="invalid-feedback"><?= e(field_error($errors, 'dob')) ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Blood Group</label>
                <select name="blood_group" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                        <option value="<?= e($bg) ?>" <?= old('blood_group', $student['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

</div>

<!-- ==================== 2. CONTACT / RELIGION ==================== -->
<div class="wizard-step" id="sContact">
<h6 class="fw-bold mb-3">Contact &amp; Religion</h6>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Religion <span class="text-danger">*</span></label>
        <select name="religion" id="religion" class="form-select <?= field_error($errors, 'religion') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach (RELIGION_OPTIONS as $r): ?>
                <option value="<?= e($r) ?>" <?= old('religion', $student['religion'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'religion')) ?></div>
    </div>
    <div class="col-md-4" id="religionOtherGroup" style="<?= old('religion', $student['religion'] ?? '') === 'Other' ? '' : 'display:none;' ?>">
        <label class="form-label">Please specify religion <span class="text-danger">*</span></label>
        <input type="text" name="religion_other" class="form-control <?= field_error($errors, 'religion_other') ? 'is-invalid' : '' ?>" value="<?= $val('religion_other') ?>">
        <div class="invalid-feedback"><?= e(field_error($errors, 'religion_other')) ?></div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Nationality <span class="text-danger">*</span></label>
        <select name="nationality" id="nationality" class="form-select <?= field_error($errors, 'nationality') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach (['Nepali', 'Indian', 'Chinese', 'Bhutanese', 'Bangladeshi', 'Pakistani', 'Sri Lankan', 'Other'] as $n): ?>
                <option value="<?= e($n) ?>" <?= old('nationality', $student['nationality'] ?? '') === $n ? 'selected' : '' ?>><?= e($n) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'nationality')) ?></div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
        <div class="input-group <?= field_error($errors, 'phone') ? 'is-invalid' : '' ?>">
            <input type="text" name="phone_country_code" id="phone_country_code" class="form-control" style="max-width:90px;" value="<?= $val('phone_country_code', '+977') ?>">
            <input type="text" name="phone" id="phone" class="form-control <?= field_error($errors, 'phone') ? 'is-invalid' : '' ?>" value="<?= $val('phone') ?>" maxlength="15" placeholder="98XXXXXXXX" required>
        </div>
        <div class="invalid-feedback d-block"><?= e(field_error($errors, 'phone')) ?></div>
    </div>
    <div class="col-md-4">
        <label class="form-label"><?= e(t('th_email')) ?></label>
        <input type="email" name="email" class="form-control <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= $val('email') ?>">
        <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
    </div>
</div>

</div>

<!-- ==================== 3. ADDRESS ==================== -->
<div class="wizard-step" id="sAddress">
<h6 class="fw-bold mb-3"><?= e(t('th_address')) ?> <span class="text-danger">*</span></h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Country <span class="text-danger">*</span></label>
        <select name="address_country" id="address_country" class="form-select <?= field_error($errors, 'address_country') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach (['Nepal', 'India', 'China', 'Bhutan', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Other'] as $co): ?>
                <option value="<?= e($co) ?>" <?= $val('address_country', 'Nepal') === $co ? 'selected' : '' ?>><?= e($co) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'address_country')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Province / State</label>
        <select name="address_province" id="address_province" class="form-select">
            <option value="">Select country first</option>
        </select>
        <input type="text" name="address_province" id="address_province_text" class="form-control d-none" placeholder="Enter province/state" value="<?= $val('address_province') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">District <span class="text-danger">*</span></label>
        <select name="address_district" id="address_district_select" class="form-select d-none" required></select>
        <input type="text" name="address_district" id="address_district" list="districtList" class="form-control <?= field_error($errors, 'address_district') ? 'is-invalid' : '' ?>" value="<?= $val('address_district') ?>" required>
        <datalist id="districtList"></datalist>
        <div class="invalid-feedback d-block"><?= e(field_error($errors, 'address_district')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Municipality / City</label>
        <input type="text" name="address_municipality" class="form-control" value="<?= $val('address_municipality') ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label">Ward</label>
        <input type="text" name="address_ward" class="form-control" value="<?= $val('address_ward') ?>" maxlength="10">
    </div>
    <div class="col-md-6">
        <label class="form-label">Street</label>
        <input type="text" name="address_street" class="form-control" value="<?= $val('address_street') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label"><?= e(t('th_postal_code')) ?></label>
        <input type="text" name="address_postal_code" class="form-control" value="<?= $val('address_postal_code') ?>">
    </div>
</div>

</div>

<!-- ==================== 4. PARENT / GUARDIAN ==================== -->
<div class="wizard-step" id="sGuardian" data-wizard-validate="validateStudentGuardianStep">
<h6 class="fw-bold mb-3"><?= e(t('h_parent_guardian')) ?> <span class="text-danger">*</span></h6>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Choose Existing Parent/Guardian</label>
        <select name="parent_id" id="parent_id" class="form-select">
            <option value="">-- None (enter new details below) --</option>
            <?php foreach ($parents as $p): ?>
                <option value="<?= e($p['id']) ?>" <?= (string) old('parent_id', $student['parent_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= e((new \App\Models\ParentModel())->displayName($p)) ?> (<?= e($p['phone']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="invalid-feedback d-block"><?= e(field_error($errors, 'guardian')) ?></div>

<div id="newGuardianFields" class="row g-3 mt-1">
    <div class="col-md-3"><label class="form-label"><?= e(t('th_father_name')) ?></label><input type="text" name="father_name" class="form-control" value="<?= $val('father_name') ?>"></div>
    <div class="col-md-3"><label class="form-label"><?= e(t('th_mother_name')) ?></label><input type="text" name="mother_name" class="form-control" value="<?= $val('mother_name') ?>"></div>
    <div class="col-md-3"><label class="form-label"><?= e(t('th_guardian_name')) ?></label><input type="text" name="guardian_name" class="form-control" value="<?= $val('guardian_name') ?>"></div>
    <div class="col-md-3"><label class="form-label"><?= e(t('th_relationship')) ?></label><input type="text" name="guardian_relationship" class="form-control" value="<?= $val('guardian_relationship') ?>" placeholder="e.g. Uncle"></div>
    <div class="col-md-3">
        <label class="form-label">Parent Phone <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="text" name="parent_phone_country_code" class="form-control" style="max-width:90px;" value="<?= $val('parent_phone_country_code', '+977') ?>">
            <input type="text" name="parent_phone" class="form-control <?= field_error($errors, 'parent_phone') ? 'is-invalid' : '' ?>" value="<?= $val('parent_phone') ?>" maxlength="15">
        </div>
        <div class="invalid-feedback d-block"><?= e(field_error($errors, 'parent_phone')) ?></div>
    </div>
    <div class="col-md-3"><label class="form-label">Parent Email</label><input type="email" name="parent_email" class="form-control" value="<?= $val('parent_email') ?>"></div>
    <div class="col-md-3"><label class="form-label"><?= e(t('th_occupation')) ?></label><input type="text" name="parent_occupation" class="form-control" value="<?= $val('parent_occupation') ?>"></div>
</div>

</div>

<!-- ==================== 5. ACADEMIC ==================== -->
<div class="wizard-step" id="sAcademic">
<h6 class="fw-bold mb-3"><?= e(t('h_academic_details')) ?></h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Class <span class="text-danger">*</span></label>
        <select name="class_id" id="class_id" class="form-select <?= field_error($errors, 'class_id') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= (string) old('class_id', $student['class_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'class_id')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Section <span class="text-danger">*</span></label>
        <select name="section_id" id="section_id" class="form-select <?= field_error($errors, 'section_id') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach ($sections as $s): ?>
                <option value="<?= e($s['id']) ?>" data-class="<?= e($s['class_id']) ?>" <?= (string) old('section_id', $student['section_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'section_id')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
        <select name="academic_year_id" id="academic_year_id" class="form-select <?= field_error($errors, 'academic_year_id') ? 'is-invalid' : '' ?>" required>
            <option value="">-- Select --</option>
            <?php foreach ($academicYears as $ay): ?>
                <option value="<?= e($ay['id']) ?>" <?= (string) old('academic_year_id', $student['academic_year_id'] ?? '') === (string) $ay['id'] ? 'selected' : '' ?>><?= e($ay['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'academic_year_id')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Admission Date <span class="text-danger">*</span></label>
        <input type="date" name="admission_date" class="form-control <?= field_error($errors, 'admission_date') ? 'is-invalid' : '' ?>" value="<?= $val('admission_date', date('Y-m-d')) ?>" required>
        <div class="invalid-feedback"><?= e(field_error($errors, 'admission_date')) ?></div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <?php foreach (STUDENT_STATUSES as $sv => $sl): ?>
                <option value="<?= e($sv) ?>" <?= old('status', $student['status'] ?? 'active') === $sv ? 'selected' : '' ?>><?= e(tr_const(STUDENT_STATUSES, $sv)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

</div>

    <!-- ==================== 6. ADDITIONAL INFORMATION (optional) ==================== -->
    <div class="wizard-step" id="sAdditional">
    <h6 class="fw-bold mb-3"><?= e(t('h_additional_information')) ?> <span class="text-muted small">(optional)</span></h6>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><label class="form-label">Student ID Card Number</label><input type="text" name="id_card_number" class="form-control" value="<?= $val('id_card_number') ?>"></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('th_previous_school')) ?></label><input type="text" name="previous_school" class="form-control" value="<?= $val('previous_school') ?>"></div>
        <div class="col-md-3"><label class="form-label">Previous Class</label><input type="text" name="previous_class" class="form-control" value="<?= $val('previous_class') ?>"></div>
        <div class="col-md-3"><label class="form-label">Scholarship Status</label><input type="text" name="scholarship_status" class="form-control" value="<?= $val('scholarship_status') ?>"></div>
        <div class="col-md-3"><label class="form-label">Birth Certificate Number</label><input type="text" name="birth_certificate_number" class="form-control" value="<?= $val('birth_certificate_number') ?>"></div>
        <div class="col-md-3"><label class="form-label">Citizenship / National ID</label><input type="text" name="citizenship_number" class="form-control" value="<?= $val('citizenship_number') ?>"></div>
        <div class="col-md-3"><label class="form-label"><?= e(t('th_passport_number')) ?></label><input type="text" name="passport_number" class="form-control" value="<?= $val('passport_number') ?>"></div>
        <div class="col-md-6"><label class="form-label">Medical Conditions</label><input type="text" name="medical_conditions" class="form-control" value="<?= $val('medical_conditions') ?>"></div>
        <div class="col-md-6"><label class="form-label">Allergies</label><input type="text" name="allergies" class="form-control" value="<?= $val('allergies') ?>"></div>
        <div class="col-md-4"><label class="form-label">Emergency Contact Name</label><input type="text" name="emergency_contact_name" class="form-control" value="<?= $val('emergency_contact_name') ?>"></div>
        <div class="col-md-4"><label class="form-label">Emergency Contact Phone</label><input type="text" name="emergency_contact_phone" class="form-control" value="<?= $val('emergency_contact_phone') ?>" maxlength="15"></div>
        <div class="col-md-4"><label class="form-label">Emergency Contact Relationship</label><input type="text" name="emergency_contact_relationship" class="form-control" value="<?= $val('emergency_contact_relationship') ?>"></div>
        <div class="col-md-12">
            <label class="form-label">Document Uploads</label>
            <input type="file" name="documents[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-text">Birth certificate, transfer certificate, marksheet, citizenship — PDF/JPG/PNG, max 5MB each.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Remarks / Notes</label>
            <textarea name="remarks" class="form-control" rows="2"><?= $val('remarks') ?></textarea>
        </div>
    </div>
    </div>

    <!-- ==================== 7. REVIEW & SUBMIT ==================== -->
    <div class="wizard-step" id="sReview">
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-1"></i>
            Review the summary below, then click <strong><?= $isEdit ? 'Update Student' : 'Save Student' ?></strong> to submit. Use <strong><?= e(t('previous')) ?></strong> if anything needs changing.
        </div>
        <div id="studentReviewSummary" class="row g-3"></div>
    </div>

<div class="d-flex justify-content-between align-items-center mt-4 border-top pt-3">
    <button type="button" class="btn btn-light d-none" data-wizard-prev><i class="bi bi-arrow-left me-1"></i>Previous</button>
    <div class="d-flex gap-2 ms-auto">
        <a href="<?= e(url($isEdit ? 'students' : 'students')) ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-primary" data-wizard-next>Next<i class="bi bi-arrow-right ms-1"></i></button>
        <button type="submit" class="btn btn-primary d-none" data-wizard-submit><i class="bi bi-check-circle-fill me-1"></i><?= $isEdit ? 'Update Student' : 'Save Student' ?></button>
    </div>
</div>

<link href="<?= e(asset('css/searchable-select.css')) ?>" rel="stylesheet">
<script src="<?= e(asset('data/nepal-address.js')) ?>"></script>
<script src="<?= e(asset('data/address-data.js')) ?>"></script>
<script src="<?= e(asset('js/form-wizard.js')) ?>"></script>
<script src="<?= e(asset('js/searchable-select.js')) ?>"></script>
<script>
(function () {
    // Photo: instant preview on file select, no save required.
    var photoInput = document.getElementById('photo');
    var photoImg = document.getElementById('photoPreviewImg');
    var photoPlaceholder = document.getElementById('photoPlaceholder');
    if (photoInput) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                photoImg.src = e.target.result;
                photoImg.classList.remove('d-none');
                if (photoPlaceholder) photoPlaceholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    // Parent list can get long — make it searchable. The widget keeps the
    // original #parent_id <select> as the source of truth (value + change
    // event), so the guardian-step logic elsewhere on this page keeps working.
    if (window.SearchableSelect) {
        SearchableSelect.enhance('#parent_id', { placeholder: 'Select existing parent (optional)', searchPlaceholder: 'Search parents…' });
    }
})();
</script>
<script>
/**
 * Parent/Guardian step (spec section 6): either an existing parent must be
 * chosen, or enough new-guardian info entered to create one — at least one
 * name plus a phone number. Registered on window because FormWizard invokes
 * data-wizard-validate functions by (global) name.
 */
function validateStudentGuardianStep(step) {
    var parentSelect = step.querySelector('#parent_id');
    var feedback = step.querySelector('.invalid-feedback.d-block');
    if (parentSelect && parentSelect.value) {
        if (feedback) feedback.textContent = '';
        return true;
    }
    var father = (step.querySelector('[name="father_name"]').value || '').trim();
    var mother = (step.querySelector('[name="mother_name"]').value || '').trim();
    var guardian = (step.querySelector('[name="guardian_name"]').value || '').trim();
    var phoneField = step.querySelector('[name="parent_phone"]');
    var phone = (phoneField.value || '').trim();

    if (!father && !mother && !guardian) {
        if (feedback) feedback.textContent = 'Choose an existing parent above, or enter at least one guardian name below.';
        step.querySelector('[name="father_name"]').focus();
        return false;
    }
    if (!phone) {
        if (feedback) feedback.textContent = 'Parent/guardian phone number is required.';
        phoneField.classList.add('is-invalid');
        phoneField.focus();
        return false;
    }
    phoneField.classList.remove('is-invalid');
    if (feedback) feedback.textContent = '';
    return true;
}
window.validateStudentGuardianStep = validateStudentGuardianStep;

(function () {
    // ---- multi-step wizard: Previous/Next buttons, per-step validation,
    // Save button only reachable on the final Review step ----
    var studentForm = document.getElementById('studentForm');
    if (window.FormWizard && studentForm) {
        window.FormWizard.init(studentForm);
    }

    // ---- Review & Submit summary ----
    if (studentForm) {
        studentForm.addEventListener('wizard:step', function (e) {
            if (!e.detail || !e.detail.step || e.detail.step.id !== 'sReview') { return; }
            var genderSelect = document.querySelector('select[name="gender"]');
            var parentSelect = document.getElementById('parent_id');
            var classSelectEl = document.getElementById('class_id');
            var sectionSelectEl = document.getElementById('section_id');
            var guardianLabel = parentSelect && parentSelect.value
                ? (parentSelect.options[parentSelect.selectedIndex] ? parentSelect.options[parentSelect.selectedIndex].text : 'Existing parent')
                : [
                    document.querySelector('[name="father_name"]').value,
                    document.querySelector('[name="mother_name"]').value,
                    document.querySelector('[name="guardian_name"]').value,
                  ].filter(Boolean).join(' / ');
            var rows = [
                ['Full Name', document.getElementById('full_name').value],
                ['Gender', genderSelect ? genderSelect.value : ''],
                ['Date of Birth', document.getElementById('dob').value],
                ['Phone', document.getElementById('phone').value],
                ['Country', document.getElementById('address_country').value],
                ['District', document.getElementById('address_district').value],
                ['Parent / Guardian', guardianLabel || '—'],
                ['Class', classSelectEl && classSelectEl.selectedIndex > 0 ? classSelectEl.options[classSelectEl.selectedIndex].text : '—'],
                ['Section', sectionSelectEl && sectionSelectEl.selectedIndex > 0 ? sectionSelectEl.options[sectionSelectEl.selectedIndex].text : '—'],
                ['Admission Date', document.querySelector('[name="admission_date"]').value],
            ];
            document.getElementById('studentReviewSummary').innerHTML = rows.map(function (r) {
                return '<div class="col-md-4"><div class="border rounded p-2 h-100"><div class="text-muted small">' + r[0] + '</div><div class="fw-semibold">' + (r[1] || '—') + '</div></div></div>';
            }).join('');
        });
    }

    // Filter the Section dropdown to only the selected Class's sections.
    var classSelect = document.getElementById('class_id');
    var sectionSelect = document.getElementById('section_id');
    if (classSelect && sectionSelect) {
        var allOptions = Array.prototype.slice.call(sectionSelect.options);

        function filterSections() {
            var classId = classSelect.value;
            var currentVal = sectionSelect.value;
            sectionSelect.innerHTML = '';
            sectionSelect.appendChild(new Option('-- Select --', ''));
            allOptions.forEach(function (opt) {
                if (opt.value === '' || !classId || opt.dataset.class === classId) {
                    if (opt.value !== '') {
                        var o = new Option(opt.text, opt.value);
                        o.dataset.class = opt.dataset.class;
                        sectionSelect.appendChild(o);
                    }
                }
            });
            if ([...sectionSelect.options].some(o => o.value === currentVal)) {
                sectionSelect.value = currentVal;
            }
        }

        classSelect.addEventListener('change', function () { filterSections(); maybeSuggestRoll(); });
        filterSections();
    }

    // Roll Number auto-suggestion (spec section 2): only overwrite if the
    // user hasn't typed their own value yet.
    var rollInput = document.getElementById('roll_number');
    var yearSelect = document.getElementById('academic_year_id');
    var rollUserEdited = false;
    if (rollInput) {
        rollInput.addEventListener('input', function () { rollUserEdited = true; });
    }
    function maybeSuggestRoll() {
        if (!rollInput || rollUserEdited || rollInput.value.trim() !== '') return;
        var classId = classSelect ? classSelect.value : '';
        var sectionId = sectionSelect ? sectionSelect.value : '';
        var yearId = yearSelect ? yearSelect.value : '';
        if (!classId || !sectionId || !yearId) return;
        var params = new URLSearchParams({ class_id: classId, section_id: sectionId, academic_year_id: yearId });
        fetch('<?= e(url('students/next-roll-number')) ?>?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.roll_number && !rollUserEdited && rollInput.value.trim() === '') {
                    rollInput.value = data.roll_number;
                }
            })
            .catch(function () {});
    }
    if (sectionSelect) sectionSelect.addEventListener('change', maybeSuggestRoll);
    if (yearSelect) yearSelect.addEventListener('change', maybeSuggestRoll);

    // DOB -> live age display (spec section 5)
    var dobInput = document.getElementById('dob');
    var ageDisplay = document.getElementById('ageDisplay');
    if (dobInput && ageDisplay) {
        dobInput.addEventListener('change', function () {
            if (!this.value) { ageDisplay.textContent = ''; return; }
            var dob = new Date(this.value);
            var now = new Date();
            var years = now.getFullYear() - dob.getFullYear();
            var months = now.getMonth() - dob.getMonth();
            if (now.getDate() < dob.getDate()) months--;
            if (months < 0) { years--; months += 12; }
            ageDisplay.textContent = (years >= 0) ? ('Age: ' + years + ' Years ' + months + ' Months') : '';
        });
    }

    // Religion "Other" reveal
    var religionSelect = document.getElementById('religion');
    var religionOtherGroup = document.getElementById('religionOtherGroup');
    if (religionSelect && religionOtherGroup) {
        religionSelect.addEventListener('change', function () {
            religionOtherGroup.style.display = this.value === 'Other' ? '' : 'none';
        });
    }

    // Nationality -> auto-detect phone country code (manual override still allowed)
    var nationalityInput = document.getElementById('nationality');
    var phoneCodeInput = document.getElementById('phone_country_code');
    var NATIONALITY_CODES = NATIONALITY_PHONE_CODES;
    if (nationalityInput && phoneCodeInput) {
        nationalityInput.addEventListener('change', function () {
            var code = NATIONALITY_CODES[this.value];
            if (code) phoneCodeInput.value = code;
        });
    }

    // Parent/Guardian: toggle the inline "new guardian" fields based on whether an existing parent is selected
    var parentSelect = document.getElementById('parent_id');
    var newGuardianFields = document.getElementById('newGuardianFields');
    function toggleGuardianFields() {
        if (!parentSelect || !newGuardianFields) return;
        newGuardianFields.style.display = parentSelect.value ? 'none' : '';
    }
    if (parentSelect) {
        parentSelect.addEventListener('change', toggleGuardianFields);
        toggleGuardianFields();
    }

    // Country -> Province/State -> District cascading dropdowns (spec
    // section 4/5). Nepal cascades all the way to its 77 districts
    // (NEPAL_PROVINCES, from nepal-address.js); the other named countries
    // get a province/state dropdown from PROVINCES_BY_COUNTRY and a free-text
    // District; "Other" leaves both Province and District as free text.
    // Exactly one of the paired select/text inputs is ever enabled at a
    // time (the disabled one doesn't submit), so address_province and
    // address_district each still post a single, unambiguous value.
    var countryInput = document.getElementById('address_country');
    var provinceSelect = document.getElementById('address_province');
    var provinceText = document.getElementById('address_province_text');
    var districtSelect = document.getElementById('address_district_select');
    var districtText = document.getElementById('address_district');
    var districtList = document.getElementById('districtList');

    function showProvinceAsSelect(options, current) {
        provinceSelect.classList.remove('d-none');
        provinceSelect.disabled = false;
        provinceText.classList.add('d-none');
        provinceText.disabled = true;
        provinceSelect.innerHTML = '<option value="">-- Select --</option>' +
            options.map(function (p) {
                return '<option value="' + p + '"' + (p === current ? ' selected' : '') + '>' + p + '</option>';
            }).join('');
    }
    function showProvinceAsText() {
        provinceSelect.classList.add('d-none');
        provinceSelect.disabled = true;
        provinceText.classList.remove('d-none');
        provinceText.disabled = false;
    }
    function showDistrictAsSelect(options, current) {
        districtSelect.classList.remove('d-none');
        districtSelect.disabled = false;
        districtText.classList.add('d-none');
        districtText.disabled = true;
        districtText.removeAttribute('required');
        districtSelect.setAttribute('required', 'required');
        districtSelect.innerHTML = '<option value="">-- Select --</option>' +
            options.map(function (d) {
                return '<option value="' + d + '"' + (d === current ? ' selected' : '') + '>' + d + '</option>';
            }).join('');
    }
    function showDistrictAsText() {
        districtSelect.classList.add('d-none');
        districtSelect.disabled = true;
        districtSelect.removeAttribute('required');
        districtText.classList.remove('d-none');
        districtText.disabled = false;
        districtText.setAttribute('required', 'required');
    }

    function applyCountry(country, initialProvince, initialDistrict) {
        if (country === 'Nepal') {
            showProvinceAsSelect(Object.keys(NEPAL_PROVINCES), initialProvince);
            var districts = NEPAL_PROVINCES[initialProvince] || [];
            if (districts.length) {
                showDistrictAsSelect(districts, initialDistrict);
            } else {
                showDistrictAsText();
            }
            districtList.innerHTML = '';
        } else if (country && PROVINCES_BY_COUNTRY[country]) {
            showProvinceAsSelect(PROVINCES_BY_COUNTRY[country], initialProvince);
            showDistrictAsText();
            districtList.innerHTML = '';
        } else {
            // "Other" or no country chosen yet: everything free text.
            showProvinceAsText();
            showDistrictAsText();
            districtList.innerHTML = '';
        }
    }

    if (countryInput && provinceSelect && provinceText && districtSelect && districtText) {
        var initialCountry = countryInput.value.trim();
        applyCountry(initialCountry, '<?= addslashes($val('address_province')) ?>', '<?= addslashes($val('address_district')) ?>');

        countryInput.addEventListener('change', function () { applyCountry(this.value.trim(), '', ''); });

        provinceSelect.addEventListener('change', function () {
            if (countryInput.value.trim() === 'Nepal') {
                var districts = NEPAL_PROVINCES[this.value] || [];
                if (districts.length) {
                    showDistrictAsSelect(districts, '');
                } else {
                    showDistrictAsText();
                }
            }
        });
    }
})();
</script>
