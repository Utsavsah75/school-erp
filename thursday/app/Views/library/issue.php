<?php
/**
 * Issue a New Book
 *
 * Borrower and book pickers use the reusable SearchableSelect component
 * (public/assets/js/searchable-select.js + searchable-select.css) which
 * progressively enhances the original <select> elements below, so the
 * server-rendered data-* attributes on each <option> remain the single
 * source of truth for the borrower/book info cards and form submission.
 */
?>
<link href="<?= e(asset('css/searchable-select.css')) ?>" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_issue_new_book')) ?></h5>
    <a href="<?= e(url('library/transactions')) ?>" class="btn btn-outline-secondary btn-sm">View Issued Books</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e(url('library/issue')) ?>" id="issueForm">
            <?= csrf_field() ?>
            <input type="hidden" name="book_id" id="bookId">
            <input type="hidden" name="book_copy_id" id="bookCopyId">
            <input type="hidden" name="borrower_id" id="borrowerId">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Borrower Type</label>
                    <select id="borrowerType" name="borrower_type" class="form-select">
                        <option value="student"><?= e(t('th_student')) ?></option>
                        <option value="teacher">Teacher / Staff</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label" id="borrowerSelectLabel">Student Id</label>
                    <select id="studentSelect">
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" data-name="<?= e($s['full_name']) ?>"
                            data-code="<?= e($s['admission_number']) ?>" data-email="<?= e($s['email'] ?? '') ?>"
                            data-phone="<?= e($s['phone'] ?? '') ?>"
                            data-photo="<?= e($s['photo_path'] ? upload_url($s['photo_path']) : '') ?>">
                            <?= e($s['admission_number']) ?> — <?= e($s['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="teacherSelect" class="d-none">
                        <option value="">-- Select Teacher / Staff --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" data-name="<?= e($t['full_name']) ?>"
                            data-code="<?= e($t['employee_number']) ?>" data-email="<?= e($t['email'] ?? '') ?>"
                            data-phone="<?= e($t['phone'] ?? '') ?>"
                            data-photo="<?= e($t['photo_path'] ? upload_url($t['photo_path']) : '') ?>">
                            <?= e($t['employee_number']) ?> — <?= e($t['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="borrowerInfoBox" class="border rounded p-3 mt-3  d-none position-relative">
                <div class="pe-5" style="max-width: calc(100% - 116px);">
                    <strong id="borrowerInfoName"></strong><br>
                    <span class="text-muted">Email: </span><span id="borrowerInfoEmail"></span><br>
                    <span class="text-muted">Mobile number: </span><span id="borrowerInfoPhone"></span>
                </div>
                <div class="position-absolute top-50 end-0 translate-middle-y me-5">
                    <img id="borrowerInfoPhoto" src="" alt="Student photo" class="d-none photo-thumb photo-thumb-square"
                        title="">
                    <div id="borrowerInfoPhotoFallback" class="photo-fallback photo-thumb-square">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-8">
                    <label class="form-label"><?= e(t('th_isbn_number')) ?></label>
                    <select id="bookSelect">
                        <option value="">-- Select ISBN --</option>
                        <?php foreach ($books as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" data-title="<?= e($b['title']) ?>"
                            data-author="<?= e($b['author_name'] ?? '') ?>"
                            data-accession="<?= e($b['accession_number']) ?>"
                            data-available="<?= (int) $b['available_copies'] ?>"
                            data-cover="<?= e($b['cover_image'] ? url($b['cover_image']) : '') ?>">
                            <?= e($b['isbn'] ?: $b['accession_number']) ?> — <?= e($b['title']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="bookInfoBox" class="border rounded p-3 mt-3  d-none position-relative">
                <div class="pe-5" style="max-width: calc(100% - 132px);">
                    <span class="text-success fw-semibold">Book exists.</span><br>
                    <strong id="bookInfoTitle"></strong><br>
                    <span class="text-muted">Author: </span><span id="bookInfoAuthor"></span><br>
                    <span class="text-muted">Accession No: </span><span id="bookInfoAccession"></span><br>
                    <span class="text-muted">Available copies: </span><span id="bookInfoAvailable"></span>
                </div>
                <div class="position-absolute top-50 end-0 translate-middle-y me-5">
                    <img id="bookInfoCover" src="" alt="Book cover" class="d-none photo-thumb photo-thumb-book"
                        title="">
                    <div id="bookInfoCoverFallback" class="photo-fallback photo-thumb-book">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1" id="copyCodeRow" style="display:none;">
                <div class="col-md-8">
                    <label class="form-label">Available Copy Code</label>
                    <div id="copySelectMount"></div>
                    <div class="form-text">Only on-shelf copies of this title are listed. Issuing one never affects the
                        others.</div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary" id="issueSubmitBtn" disabled><?= e(t('issue_book_btn')) ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.photo-thumb,
.photo-fallback {
    display: block;
    border-radius: 10px;
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    background-color: #fff;
}

.photo-thumb-square,
.photo-fallback.photo-thumb-square {
    width: 100px;
    height: 100px;
}

.photo-thumb-book,
.photo-fallback.photo-thumb-book {
    width: 125px;
    height: 140px;
}

.photo-thumb {
    object-fit: cover;
}

.photo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 2rem;
    background-color: #f8f9fa;
}
</style>

<script src="<?= e(asset('js/searchable-select.js')) ?>"></script>
<script>
const borrowerType = document.getElementById('borrowerType');
const studentSelect = document.getElementById('studentSelect');
const teacherSelect = document.getElementById('teacherSelect');
const borrowerSelectLabel = document.getElementById('borrowerSelectLabel');
const borrowerId = document.getElementById('borrowerId');
const borrowerInfoBox = document.getElementById('borrowerInfoBox');
const borrowerInfoPhoto = document.getElementById('borrowerInfoPhoto');
const borrowerInfoPhotoFallback = document.getElementById('borrowerInfoPhotoFallback');

const bookId = document.getElementById('bookId');
const bookInfoBox = document.getElementById('bookInfoBox');
const bookInfoCover = document.getElementById('bookInfoCover');
const bookInfoCoverFallback = document.getElementById('bookInfoCoverFallback');
const submitBtn = document.getElementById('issueSubmitBtn');

const copyId = document.getElementById('bookCopyId');
const copyCodeRow = document.getElementById('copyCodeRow');
const copySelectMount = document.getElementById('copySelectMount');
const availableCopiesUrlBase = <?= json_encode(url('library/books')) ?>;

const copyWidget = new SearchableSelect(copySelectMount, {
    options: [],
    placeholder: 'Select a copy…',
    searchPlaceholder: 'Search copy code…',
    disabled: true,
    onChange: function(value) {
        copyId.value = value || '';
        checkReady();
    },
});

function checkReady() {
    submitBtn.disabled = !(borrowerId.value && bookId.value && copyId.value);
}

function showPhoto(imgEl, fallbackEl, src, hoverText) {
    if (src) {
        imgEl.src = src;
        imgEl.title = hoverText || '';
        imgEl.classList.remove('d-none');
        fallbackEl.classList.add('d-none');
        imgEl.onerror = function() {
            // Broken/missing file on disk: fall back gracefully instead of a broken-image icon.
            imgEl.classList.add('d-none');
            fallbackEl.classList.remove('d-none');
        };
    } else {
        imgEl.classList.add('d-none');
        fallbackEl.classList.remove('d-none');
    }
}

function onBorrowerChosen(value, optionEl) {
    if (!value || !optionEl) {
        borrowerId.value = '';
        borrowerInfoBox.classList.add('d-none');
        checkReady();
        return;
    }
    const d = optionEl.dataset;
    borrowerId.value = value;
    document.getElementById('borrowerInfoName').textContent = 'Name: ' + d.name + ' (' + d.code + ')';
    document.getElementById('borrowerInfoEmail').textContent = d.email || '—';
    document.getElementById('borrowerInfoPhone').textContent = d.phone || '—';
    showPhoto(borrowerInfoPhoto, borrowerInfoPhotoFallback, d.photo || '', d.photo ? '' : 'No Image Available');
    borrowerInfoBox.classList.remove('d-none');
    checkReady();
}

const studentWidget = SearchableSelect.enhance('#studentSelect', {
    placeholder: '-- Select Student --',
    searchPlaceholder: 'Search students…',
    onChange: onBorrowerChosen,
});
const teacherWidget = SearchableSelect.enhance('#teacherSelect', {
    placeholder: '-- Select Teacher / Staff --',
    searchPlaceholder: 'Search teachers / staff…',
    onChange: onBorrowerChosen,
});

// The two widget roots sit right after their original (now-hidden) <select>.
const studentWidgetRoot = studentSelect.nextElementSibling;
const teacherWidgetRoot = teacherSelect.nextElementSibling;
teacherWidgetRoot.classList.add('d-none'); // "Student" is the default borrower type

borrowerType.addEventListener('change', function() {
    const isTeacher = borrowerType.value === 'teacher';
    teacherWidgetRoot.classList.toggle('d-none', !isTeacher);
    studentWidgetRoot.classList.toggle('d-none', isTeacher);
    borrowerSelectLabel.textContent = isTeacher ? 'Employee Id' : 'Student Id';
    studentWidget.setValue(null, {
        silent: true
    });
    teacherWidget.setValue(null, {
        silent: true
    });
    borrowerId.value = '';
    borrowerInfoBox.classList.add('d-none');
    checkReady();
});

const bookWidget = SearchableSelect.enhance('#bookSelect', {
    placeholder: '-- Select ISBN --',
    searchPlaceholder: 'Search by title, ISBN, accession no…',
    onChange: function(value, optionEl) {
        copyId.value = '';
        copyWidget.setValue(null, {
            silent: true
        });

        if (!value || !optionEl) {
            bookId.value = '';
            bookInfoBox.classList.add('d-none');
            copyCodeRow.style.display = 'none';
            copyWidget.setDisabled(true);
            checkReady();
            return;
        }
        const d = optionEl.dataset;
        bookId.value = value;
        document.getElementById('bookInfoTitle').textContent = d.title;
        document.getElementById('bookInfoAuthor').textContent = d.author || '—';
        document.getElementById('bookInfoAccession').textContent = d.accession;
        document.getElementById('bookInfoAvailable').textContent = d.available;
        showPhoto(bookInfoCover, bookInfoCoverFallback, d.cover || '', d.cover ? '' : 'No Cover Available');
        bookInfoBox.classList.remove('d-none');
        checkReady();

        copyCodeRow.style.display = '';
        copyWidget.setDisabled(true);
        copyWidget.setOptions([{
            value: '',
            label: 'Loading copies…',
            disabled: true
        }]);

        fetch(`${availableCopiesUrlBase}/${value}/available-copies`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then((res) => res.json())
            .then((copies) => {
                if (!Array.isArray(copies) || copies.length === 0) {
                    copyWidget.setOptions([]);
                    copyWidget.setPlaceholder('No available copies');
                    return;
                }
                copyWidget.setOptions(copies.map((c) => ({
                    value: String(c.id),
                    label: c.copy_code
                })));
                copyWidget.setPlaceholder('Select a copy…');
                copyWidget.setDisabled(false);
            })
            .catch(() => {
                copyWidget.setOptions([]);
                copyWidget.setPlaceholder('Could not load copies — try again');
            });
    },
});

document.getElementById('issueForm').addEventListener('submit', function(e) {
    if (!borrowerId.value || !bookId.value || !copyId.value) {
        e.preventDefault();
        if (window.SA) { window.SA.warning('Missing Information', 'Please select a borrower, a book, and an available copy.'); }
        else { alert('Please select a borrower, a book, and an available copy.'); }
    }
});
</script>