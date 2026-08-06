<?php
/**
 * Expects $i (numeric index or '__INDEX__') and $a (row data array).
 * Also relies on $classes, $sections, $subjects, $academicYears, which are
 * already in scope because this partial is include()'d from _form.php.
 */
$a ??= [];
$rowClassId = $a['class_id'] ?? '';
$rowSections = $rowClassId !== '' ? array_filter($sections, fn($s) => (string) $s['class_id'] === (string) $rowClassId) : [];
$rowSubjects = $rowClassId !== '' ? array_filter($subjects, fn($s) => (string) $s['class_id'] === (string) $rowClassId) : [];
?>
<div class="dynamic-row border rounded p-3 mb-2 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2" data-remove-row title="Remove"><i class="bi bi-x-lg"></i></button>
    <div class="row g-2">
        <div class="col-md-2">
            <label class="form-label small">Academic Year <span class="text-danger">*</span></label>
            <select name="assignments[<?= $i ?>][academic_year_id]" class="form-select form-select-sm">
                <option value="">--</option>
                <?php foreach ($academicYears as $y): ?>
                    <option value="<?= e($y['id']) ?>" <?= (string) ($a['academic_year_id'] ?? '') === (string) $y['id'] ? 'selected' : '' ?>><?= e($y['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Campus</label>
            <input type="text" name="assignments[<?= $i ?>][campus]" class="form-control form-control-sm" value="<?= e($a['campus'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Class <span class="text-danger">*</span></label>
            <select name="assignments[<?= $i ?>][class_id]" class="form-select form-select-sm assignment-class">
                <option value="">--</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (string) $rowClassId === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Section <span class="text-danger">*</span></label>
            <select name="assignments[<?= $i ?>][section_id]" class="form-select form-select-sm assignment-section">
                <option value="">--</option>
                <?php foreach ($rowSections as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (string) ($a['section_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Subject <span class="text-danger">*</span></label>
            <select name="assignments[<?= $i ?>][subject_id]" class="form-select form-select-sm assignment-subject">
                <option value="">--</option>
                <?php foreach ($rowSubjects as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (string) ($a['subject_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Weekly Classes</label>
            <input type="number" min="0" max="60" name="assignments[<?= $i ?>][weekly_classes]" class="form-control form-control-sm" value="<?= e($a['weekly_classes'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Classroom</label>
            <input type="text" name="assignments[<?= $i ?>][classroom]" class="form-control form-control-sm" value="<?= e($a['classroom'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small"><?= e(t('th_room_number')) ?></label>
            <input type="text" name="assignments[<?= $i ?>][room_number]" class="form-control form-control-sm" value="<?= e($a['room_number'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Effective From</label>
            <input type="date" name="assignments[<?= $i ?>][effective_from]" class="form-control form-control-sm" value="<?= e($a['effective_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Effective To</label>
            <input type="date" name="assignments[<?= $i ?>][effective_to]" class="form-control form-control-sm" value="<?= e($a['effective_to'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <div class="form-check form-check-inline mt-4">
                <input type="checkbox" class="form-check-input" name="assignments[<?= $i ?>][is_class_teacher]" value="1" id="ict_<?= $i ?>" <?= !empty($a['is_class_teacher']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="ict_<?= $i ?>">Class Teacher</label>
            </div>
            <div class="form-check form-check-inline mt-4">
                <input type="checkbox" class="form-check-input" name="assignments[<?= $i ?>][is_subject_coordinator]" value="1" id="isc_<?= $i ?>" <?= !empty($a['is_subject_coordinator']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="isc_<?= $i ?>">Subject Coordinator</label>
            </div>
            <div class="form-check form-check-inline mt-4">
                <input type="checkbox" class="form-check-input" name="assignments[<?= $i ?>][is_active]" value="1" id="iaa_<?= $i ?>" <?= ($a === [] || !empty($a['is_active'])) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="iaa_<?= $i ?>">Active</label>
            </div>
        </div>
    </div>
</div>
