<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Import Exam Schedule</h5>
    <a href="<?= e(url('exam-schedule')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Exam Schedule</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Upload CSV</h6>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['general'][0]) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('exam-schedule/import')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Expected CSV Columns</h6>
                <p class="text-muted small mb-2">First row must be a header with these exact column names. Class / Section / Subject / Exam Type / Academic Year are matched by <strong>name</strong> — they must already exist in the system.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Column</th><th>Example</th></tr></thead>
                        <tbody>
                            <tr><td><code>exam_name</code></td><td>First Terminal Examination</td></tr>
                            <tr><td><code>academic_year</code></td><td>2025-2026</td></tr>
                            <tr><td><code>exam_type</code></td><td>Terminal</td></tr>
                            <tr><td><code>class</code></td><td>Class 5</td></tr>
                            <tr><td><code>section</code></td><td>A</td></tr>
                            <tr><td><code>subject</code></td><td>Mathematics</td></tr>
                            <tr><td><code>exam_date</code></td><td>2026-08-11</td></tr>
                            <tr><td><code>start_time</code></td><td>10:00</td></tr>
                            <tr><td><code>end_time</code></td><td>12:00</td></tr>
                            <tr><td><code>room_number</code></td><td>Hall-1</td></tr>
                            <tr><td><code>max_marks</code></td><td>100</td></tr>
                            <tr><td><code>passing_marks</code></td><td>40</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0">Rows that duplicate or overlap an existing schedule, or whose Class/Section/Subject/Exam Type can't be matched, are skipped and reported after import.</p>
            </div>
        </div>
    </div>
</div>
