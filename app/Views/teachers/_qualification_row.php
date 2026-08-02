<?php
/** Expects $i (numeric index or '__INDEX__') and $q (row data array). */
$q ??= [];
?>
<div class="dynamic-row border rounded p-3 mb-2 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2" data-remove-row title="Remove"><i class="bi bi-x-lg"></i></button>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small">Degree <span class="text-danger">*</span></label>
            <input type="text" name="qualifications[<?= $i ?>][degree]" class="form-control form-control-sm" value="<?= e($q['degree'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">University</label>
            <input type="text" name="qualifications[<?= $i ?>][university]" class="form-control form-control-sm" value="<?= e($q['university'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Board</label>
            <input type="text" name="qualifications[<?= $i ?>][board]" class="form-control form-control-sm" value="<?= e($q['board'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Passing Year</label>
            <input type="number" min="1950" max="<?= date('Y') ?>" name="qualifications[<?= $i ?>][passing_year]" class="form-control form-control-sm" value="<?= e($q['passing_year'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">%/GPA</label>
            <input type="text" name="qualifications[<?= $i ?>][percentage_gpa]" class="form-control form-control-sm" value="<?= e($q['percentage_gpa'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Certifications</label>
            <input type="text" name="qualifications[<?= $i ?>][certifications]" class="form-control form-control-sm" value="<?= e($q['certifications'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Professional Training</label>
            <input type="text" name="qualifications[<?= $i ?>][professional_training]" class="form-control form-control-sm" value="<?= e($q['professional_training'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Teaching License No.</label>
            <input type="text" name="qualifications[<?= $i ?>][teaching_license_number]" class="form-control form-control-sm" value="<?= e($q['teaching_license_number'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">License Expiry</label>
            <input type="date" name="qualifications[<?= $i ?>][license_expiry_date]" class="form-control form-control-sm" value="<?= e($q['license_expiry_date'] ?? '') ?>">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="qualifications[<?= $i ?>][is_highest]" value="1" id="qhi_<?= $i ?>" <?= !empty($q['is_highest']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="qhi_<?= $i ?>">This is the highest qualification</label>
            </div>
        </div>
    </div>
</div>
