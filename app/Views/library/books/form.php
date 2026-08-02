<?php $isEdit = !empty($book); $errors = form_errors(); ?>
<link href="<?= e(asset('css/searchable-select.css')) ?>" rel="stylesheet">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= $isEdit ? 'Edit Book' : 'Add Book' ?></h5>
    <a href="<?= e(url('library/books')) ?>" class="btn btn-outline-secondary btn-sm">Back to Catalog</a>
</div>

<div class="card">
    <div class="card-header bg-light">Book Info</div>
    <div class="card-body">
        <form method="POST" action="<?= $isEdit ? e(url('library/books/' . $book['id'])) : e(url('library/books')) ?>" enctype="multipart/form-data" class="sa-loading-submit" data-loading-text="<?= $isEdit ? 'Updating book...' : 'Saving book...' ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-2 text-center">
                    <img id="coverPreviewImg" src="<?= !empty($book['cover_image']) ? e(url($book['cover_image'])) : '' ?>" alt="Book cover preview"
                         class="mb-2 border rounded <?= empty($book['cover_image']) ? 'd-none' : '' ?>" style="width:100px;height:130px;object-fit:cover;">
                    <div id="coverPlaceholder" class="bg-light border rounded d-inline-flex align-items-center justify-content-center mb-2 <?= !empty($book['cover_image']) ? 'd-none' : '' ?>" style="width:100px;height:130px;"><i class="bi bi-book fs-1 text-muted"></i></div>
                    <div id="coverPreviewLabel" class="small text-muted"><?= !empty($book['cover_image']) ? 'Change Image' : '' ?></div>
                    <input type="file" name="cover_image" id="coverImageInput" class="form-control form-control-sm <?= field_error($errors, 'cover_image') ? 'is-invalid' : '' ?>" accept=".jpg,.jpeg,.png,.webp">
                    <div class="invalid-feedback"><?= e(field_error($errors, 'cover_image')) ?></div>
                    <div class="form-text">jpg, jpeg, png, webp — max 2MB</div>
                </div>

                <div class="col-md-10">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Book Name *</label>
                            <input type="text" name="title" id="titleInput" class="form-control <?= field_error($errors, 'title') ? 'is-invalid' : '' ?>" required
                                   value="<?= e(old('title', $book['title'] ?? '')) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'title') ?: 'The book title already exists.') ?></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ISBN Number</label>
                            <input type="text" name="isbn" id="isbnInput" class="form-control <?= field_error($errors, 'isbn') ? 'is-invalid' : '' ?>"
                                   value="<?= e(old('isbn', $book['isbn'] ?? '')) ?>"
                                   placeholder="Auto-generated from Book Name — edit anytime">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'isbn') ?: 'ISBN already exists with another book.') ?></div>
                            <div class="form-text">An ISBN is an International Standard Book Number. ISBN must be unique.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Accession No. *</label>
                            <input type="text" name="accession_number" class="form-control <?= field_error($errors, 'accession_number') ? 'is-invalid' : '' ?>" required
                                   value="<?= e(old('accession_number', $book['accession_number'] ?? '')) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'accession_number')) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="categorySelect" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (string) old('category_id', $book['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Author</label>
                            <select name="author_id" id="authorSelect" class="form-select">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= (int) $a['id'] ?>" <?= (string) old('author_id', $book['author_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Publisher</label>
                            <select name="publisher_id" id="publisherSelect" class="form-select">
                                <option value="">Select Publisher</option>
                                <?php foreach ($publishers as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= (string) old('publisher_id', $book['publisher_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Edition</label>
                            <input type="text" name="edition" class="form-control" value="<?= e(old('edition', $book['edition'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Language</label>
                            <input type="text" name="language" class="form-control" value="<?= e(old('language', $book['language'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rack Number</label>
                            <input type="text" name="rack_number" class="form-control" value="<?= e(old('rack_number', $book['rack_number'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Shelf Number</label>
                            <input type="text" name="shelf_number" class="form-control" value="<?= e(old('shelf_number', $book['shelf_number'] ?? '')) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Total Copies *</label>
                            <input type="number" min="1" name="total_copies" class="form-control <?= field_error($errors, 'total_copies') ? 'is-invalid' : '' ?>" required
                                   value="<?= e(old('total_copies', (string) ($book['total_copies'] ?? 1))) ?>">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'total_copies')) ?></div>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="col-md-3">
                            <label class="form-label">Available Copies *</label>
                            <input type="number" min="0" name="available_copies" class="form-control" required
                                   value="<?= e(old('available_copies', (string) ($book['available_copies'] ?? 0))) ?>">
                            <small class="text-muted">Adjust only for corrections — issue/return updates this automatically.</small>
                        </div>
                        <?php else: ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mt-4">All copies start as available.</small>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="1000"><?= e(old('description', $book['description'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Submit' ?></button>
                <a href="<?= e(url('library/books')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="<?= e(asset('js/searchable-select.js')) ?>"></script>
<script>
(function () {
    var coverInput = document.getElementById('coverImageInput');
    var coverImg = document.getElementById('coverPreviewImg');
    var coverPlaceholder = document.getElementById('coverPlaceholder');
    var coverLabel = document.getElementById('coverPreviewLabel');

    var titleInput = document.getElementById('titleInput');
    var isbnInput = document.getElementById('isbnInput');
    var titleManuallyEdited = !!(titleInput && titleInput.value.trim() !== '');
    var isbnManuallyEdited = !!(isbnInput && isbnInput.value.trim() !== '');

    // ---- Automatic ISBN generation ---------------------------------------
    var checkUrl = '<?= e(url('library/books/check-isbn')) ?>';
    var currentBookId = <?= $isEdit ? (int) $book['id'] : 'null' ?>;
    var runId = 0;

    function lettersOnly(str) {
        return (str || '').replace(/[^A-Za-z]/g, '').toUpperCase();
    }
    function randomDigits() {
        return String(Math.floor(100 + Math.random() * 900));
    }
    function isbnTaken(isbn) {
        var url = checkUrl + '?isbn=' + encodeURIComponent(isbn) + (currentBookId ? '&exclude_id=' + currentBookId : '');
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.ok ? res.json() : { exists: false }; })
            .then(function (data) { return !!data.exists; })
            .catch(function () { return false; });
    }
    function generateIsbn() {
        if (!titleInput || !isbnInput || isbnManuallyEdited) return;
        var prefix = lettersOnly(titleInput.value).slice(0, 3);
        if (prefix.length < 3) return;
        var myRun = ++runId;
        var attempt = 0;
        (function tryNext() {
            if (myRun !== runId || attempt >= 10) return;
            attempt++;
            var candidate = prefix + randomDigits();
            isbnTaken(candidate).then(function (taken) {
                if (myRun !== runId) return;
                if (taken) { tryNext(); return; }
                isbnInput.value = candidate;
            });
        })();
    }
    if (titleInput && isbnInput) {
        isbnInput.addEventListener('input', function () { isbnManuallyEdited = true; });
        var debounceTimer;
        titleInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(generateIsbn, 400);
        });
    }

    // ---- Auto Book Name from the uploaded image filename -----------------
    function properCase(str) {
        return str.split(' ').filter(Boolean).map(function (w) {
            return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
        }).join(' ');
    }
    function nameFromFilename(filename) {
        var base = filename.replace(/\.[^./\\]+$/, ''); // strip extension
        base = base.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
        return properCase(base);
    }

    if (titleInput) {
        titleInput.addEventListener('input', function () { titleManuallyEdited = true; });
    }

    if (coverInput) {
        coverInput.addEventListener('change', function () {
            var file = coverInput.files && coverInput.files[0];
            if (!file) return;

            var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowedTypes.indexOf(file.type) === -1) {
                coverInput.value = '';
                if (window.SA) { window.SA.fileUpload.invalidType(); }
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                coverInput.value = '';
                if (window.SA) { window.SA.fileUpload.tooLarge(); }
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                coverImg.src = e.target.result;
                coverImg.classList.remove('d-none');
                if (coverPlaceholder) coverPlaceholder.classList.add('d-none');
                if (coverLabel) coverLabel.textContent = 'Change Image';
            };
            reader.readAsDataURL(file);

            if (titleInput && !titleManuallyEdited) {
                var derived = nameFromFilename(file.name);
                if (derived) {
                    titleInput.value = derived;
                    generateIsbn(); // book name changed programmatically — no 'input' event fires, so trigger it directly
                }
            }
        });
    }

    if (window.SearchableSelect) {
        SearchableSelect.enhance('#categorySelect', { placeholder: 'Select Category', searchPlaceholder: 'Search categories…' });
        SearchableSelect.enhance('#authorSelect', { placeholder: 'Select Author', searchPlaceholder: 'Search authors…' });
        SearchableSelect.enhance('#publisherSelect', { placeholder: 'Select Publisher', searchPlaceholder: 'Search publishers…' });
    }

    var totalInput = document.querySelector('input[name="total_copies"]');
    var availableInput = document.querySelector('input[name="available_copies"]');
    if (totalInput && availableInput) {
        var clamp = function () {
            var total = parseInt(totalInput.value, 10);
            if (!isNaN(total)) {
                availableInput.max = total;
                if (parseInt(availableInput.value, 10) > total) availableInput.value = total;
            }
        };
        totalInput.addEventListener('input', clamp);
        clamp();
    }
})();
</script>
