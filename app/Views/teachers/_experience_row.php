<?php
/** Expects $i (numeric index or '__INDEX__') and $ex (row data array). */
$ex ??= [];
?>
<div class="dynamic-row border rounded p-3 mb-2 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2" data-remove-row title="Remove"><i class="bi bi-x-lg"></i></button>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small">Institution</label>
            <input type="text" name="experience[<?= $i ?>][institution_name]" class="form-control form-control-sm" value="<?= e($ex['institution_name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Designation</label>
            <input type="text" name="experience[<?= $i ?>][designation]" class="form-control form-control-sm" value="<?= e($ex['designation'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">From</label>
            <input type="date" name="experience[<?= $i ?>][from_date]" class="form-control form-control-sm" value="<?= e($ex['from_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="experience[<?= $i ?>][to_date]" class="form-control form-control-sm" value="<?= e($ex['to_date'] ?? '') ?>">
        </div>
        <div class="col-md-11">
            <label class="form-label small">Description</label>
            <input type="text" name="experience[<?= $i ?>][description]" class="form-control form-control-sm" value="<?= e($ex['description'] ?? '') ?>">
        </div>
    </div>
</div>
