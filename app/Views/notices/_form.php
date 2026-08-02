<?php
/**
 * Shared Add/Edit Notice fields. Expects:
 *   $formAction, $notice (array|null), $classes, $categories, $errors, $submitLabel
 */
$n = $notice ?? [];
$val = fn (string $key, $default = '') => e(old($key, $n[$key] ?? $default));
$audience = old('audience', $n['audience'] ?? 'all');
?>
<form method="POST" action="<?= e($formAction) ?>" enctype="multipart/form-data" id="noticeForm" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" maxlength="200" class="form-control <?= field_error($errors, 'title') ? 'is-invalid' : '' ?>" value="<?= $val('title') ?>" required>
        <div class="invalid-feedback"><?= e(field_error($errors, 'title')) ?></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Description <span class="text-danger">*</span></label>
        <textarea name="body" rows="4" maxlength="5000" class="form-control <?= field_error($errors, 'body') ? 'is-invalid' : '' ?>" required><?= $val('body') ?></textarea>
        <div class="invalid-feedback"><?= e(field_error($errors, 'body')) ?></div>
    </div>

    <div class="row g-2">
        <div class="col-md-4 mb-3">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select <?= field_error($errors, 'category') ? 'is-invalid' : '' ?>" required>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= old('category', $n['category'] ?? 'General') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e(field_error($errors, 'category')) ?></div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Priority <span class="text-danger">*</span></label>
            <select name="priority" class="form-select <?= field_error($errors, 'priority') ? 'is-invalid' : '' ?>" required>
                <?php foreach (\App\Models\Notice::priorityOptions() as $pv => $pl): ?>
                <option value="<?= e($pv) ?>" <?= old('priority', $n['priority'] ?? 'medium') === $pv ? 'selected' : '' ?>><?= e($pl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select">
                <?php foreach (\App\Models\Notice::statusOptions() as $sv => $sl): ?>
                <option value="<?= e($sv) ?>" <?= old('status', $n['status'] ?? 'published') === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Audience <span class="text-danger">*</span></label>
        <select name="audience" id="audienceSelect" class="form-select <?= field_error($errors, 'audience') ? 'is-invalid' : '' ?>" required>
            <?php foreach (\App\Models\Notice::audienceOptions() as $av => $al): ?>
            <option value="<?= e($av) ?>" <?= $audience === $av ? 'selected' : '' ?>><?= e($al) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= e(field_error($errors, 'audience')) ?></div>
        <div class="form-text">"Class" notifies everyone in that class; "Section" narrows to one section; "Individual Student" targets one student.</div>
    </div>

    <div class="row g-2">
        <div class="col-md-4 mb-3 audience-field audience-class audience-section audience-student" style="display:none;">
            <label class="form-label">Class <span class="text-danger">*</span></label>
            <select name="class_id" id="noticeClass" class="form-select <?= field_error($errors, 'class_id') ? 'is-invalid' : '' ?>">
                <option value="">-- Select --</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= (string) old('class_id', $n['class_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e(field_error($errors, 'class_id')) ?></div>
        </div>
        <div class="col-md-4 mb-3 audience-field audience-section audience-student" style="display:none;">
            <label class="form-label">Section <span class="text-danger">*</span></label>
            <select name="section_id" id="noticeSection" class="form-select <?= field_error($errors, 'section_id') ? 'is-invalid' : '' ?>">
                <option value="">-- Select Class First --</option>
                <?php foreach (($sections ?? []) as $s): ?>
                <option value="<?= e($s['id']) ?>" <?= (string) old('section_id', $n['section_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e(field_error($errors, 'section_id')) ?></div>
        </div>
        <div class="col-md-4 mb-3 audience-field audience-student" style="display:none;">
            <label class="form-label">Student <span class="text-danger">*</span></label>
            <select name="student_id" id="noticeStudent" class="form-select <?= field_error($errors, 'student_id') ? 'is-invalid' : '' ?>" data-placeholder="Type to search a student...">
                <?php if (!empty($n['student_id'])): ?>
                <option value="<?= e($n['student_id']) ?>" selected><?= e($n['student_name'] ?? ('Student #' . $n['student_id'])) ?></option>
                <?php endif; ?>
            </select>
            <div class="invalid-feedback"><?= e(field_error($errors, 'student_id')) ?></div>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-md-6 mb-3">
            <label class="form-label">Publish Date <span class="text-danger">*</span></label>
            <input type="date" name="publish_date" class="form-control <?= field_error($errors, 'publish_date') ? 'is-invalid' : '' ?>" value="<?= $val('publish_date', date('Y-m-d')) ?>" required>
            <div class="invalid-feedback"><?= e(field_error($errors, 'publish_date')) ?></div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control <?= field_error($errors, 'expiry_date') ? 'is-invalid' : '' ?>" value="<?= $val('expiry_date') ?>">
            <div class="invalid-feedback"><?= e(field_error($errors, 'expiry_date')) ?></div>
            <div class="form-text">Leave blank for a notice that never expires.</div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Attachment</label>
        <input type="file" name="attachment" class="form-control <?= field_error($errors, 'attachment') ? 'is-invalid' : '' ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        <div class="invalid-feedback"><?= e(field_error($errors, 'attachment')) ?></div>
        <div class="form-text">PDF, DOC, DOCX, JPG or PNG.</div>
        <?php if (!empty($n['attachment_path'])): ?>
        <div class="mt-2 small">
            Current file: <a href="<?= e(upload_url($n['attachment_path'])) ?>" target="_blank"><?= e($n['attachment_name'] ?? 'attachment') ?></a>
            &nbsp;
            <label class="form-check-label"><input type="checkbox" class="form-check-input" name="remove_attachment" value="1"> Remove</label>
        </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i><?= e($submitLabel) ?></button>
    <a href="<?= e(url('notices')) ?>" class="btn btn-light">Cancel</a>
</form>

<script>
(function () {
    var sectionsUrl = <?= json_encode(url('notices/sections-for-class')) ?>;
    var studentSearchUrl = <?= json_encode(url('notices/search-students')) ?>;
    var audienceSelect = document.getElementById('audienceSelect');
    var classSelect = document.getElementById('noticeClass');
    var sectionSelect = document.getElementById('noticeSection');
    var studentSelect = document.getElementById('noticeStudent');

    function toggleAudienceFields() {
        var value = audienceSelect.value;
        document.querySelectorAll('.audience-field').forEach(function (el) { el.style.display = 'none'; });
        document.querySelectorAll('.audience-' + value).forEach(function (el) { el.style.display = ''; });
    }
    audienceSelect.addEventListener('change', toggleAudienceFields);
    toggleAudienceFields();

    if (classSelect) {
        classSelect.addEventListener('change', function () {
            if (!this.value) {
                sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
                return;
            }
            sectionSelect.disabled = true;
            fetch(sectionsUrl + '/' + this.value).then(function (r) { return r.json(); }).then(function (rows) {
                sectionSelect.innerHTML = '<option value="">-- Select --</option>';
                rows.forEach(function (row) { sectionSelect.appendChild(new Option(row.name, row.id)); });
                sectionSelect.disabled = false;
            }).catch(function () { sectionSelect.disabled = false; });
        });
    }

    // Lightweight searchable student dropdown (no extra JS library dependency).
    if (studentSelect) {
        var wrap = document.createElement('div');
        wrap.className = 'position-relative';
        studentSelect.parentNode.insertBefore(wrap, studentSelect);
        wrap.appendChild(studentSelect);
        studentSelect.classList.add('d-none');

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.placeholder = studentSelect.dataset.placeholder || 'Type to search...';
        if (studentSelect.selectedOptions.length) { input.value = studentSelect.selectedOptions[0].text; }
        wrap.appendChild(input);

        var list = document.createElement('div');
        list.className = 'list-group position-absolute w-100 shadow-sm';
        list.style.zIndex = 20;
        list.style.maxHeight = '220px';
        list.style.overflowY = 'auto';
        list.style.display = 'none';
        wrap.appendChild(list);

        var searchTimer = null;
        input.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = input.value.trim();
            searchTimer = setTimeout(function () {
                var params = new URLSearchParams({ q: q });
                if (classSelect && classSelect.value) { params.set('class_id', classSelect.value); }
                if (sectionSelect && sectionSelect.value) { params.set('section_id', sectionSelect.value); }
                fetch(studentSearchUrl + '?' + params.toString()).then(function (r) { return r.json(); }).then(function (rows) {
                    list.innerHTML = '';
                    if (!rows.length) {
                        list.style.display = 'none';
                        return;
                    }
                    rows.forEach(function (row) {
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action small';
                        item.textContent = row.full_name + ' (' + row.admission_number + ')';
                        item.addEventListener('click', function () {
                            studentSelect.innerHTML = '';
                            studentSelect.appendChild(new Option(row.full_name, row.id, true, true));
                            input.value = row.full_name;
                            list.style.display = 'none';
                        });
                        list.appendChild(item);
                    });
                    list.style.display = '';
                }).catch(function () {});
            }, 300);
        });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) { list.style.display = 'none'; }
        });
    }
})();
</script>
