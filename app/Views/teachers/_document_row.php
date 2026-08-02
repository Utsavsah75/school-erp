<?php /** Expects $i (numeric index or '__INDEX__'). Documents are always new uploads (existing ones listed separately above). */ ?>
<div class="dynamic-row border rounded p-3 mb-2 position-relative">
    <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2" data-remove-row title="Remove"><i class="bi bi-x-lg"></i></button>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small">Document Name</label>
            <input type="text" name="document_names[]" class="form-control form-control-sm" list="docTypeList" placeholder="e.g. Citizenship">
            <datalist id="docTypeList">
                <?php foreach (TEACHER_DOCUMENT_TYPES as $dt): ?>
                    <option value="<?= e($dt) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="col-md-4">
            <label class="form-label small">File</label>
            <input type="file" name="document_files[]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Remarks</label>
            <input type="text" name="document_remarks[]" class="form-control form-control-sm">
        </div>
    </div>
</div>
