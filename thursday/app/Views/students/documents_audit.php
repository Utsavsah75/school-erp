<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_document_storage_health_check')) ?></h5>
    <a href="<?= e(url('students')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Students</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small text-uppercase">Total Documents</div>
            <div class="fs-3 fw-bold"><?= (int) $total ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small text-uppercase">Available on Disk</div>
            <div class="fs-3 fw-bold text-success"><?= (int) ($total - $missing) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small text-uppercase">Missing on Disk</div>
            <div class="fs-3 fw-bold <?= $missing > 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $missing ?></div>
        </div></div>
    </div>
</div>

<?php if ($missing > 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <?= (int) $missing ?> document record(s) point to a file that isn't in <code>public/uploads/documents/</code>.
    This is expected for demo/seed data that was never actually uploaded — those rows show a friendly
    "Document not available" on the student profile instead of an error. If a document you uploaded yourself
    shows as missing, check the <code>file_path</code> column below against what's really on disk.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm">
                <thead>
                    <tr>
                        <th><?= e(t('th_status')) ?></th>
                        <th><?= e(t('th_student')) ?></th>
                        <th>Doc Type</th>
                        <th>Original Filename</th>
                        <th>Stored Path (file_path)</th>
                        <th><?= e(t('th_uploaded')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr class="<?= $r['file_exists'] ? '' : 'table-danger' ?>">
                            <td>
                                <?php if ($r['file_exists']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Missing</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['student_id'])): ?>
                                    <a href="<?= e(url('students/' . $r['student_id'])) ?>"><?= e($r['student_name'] ?? ('#' . $r['student_id'])) ?></a>
                                    <div class="text-muted small"><?= e($r['admission_number'] ?? '') ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown student</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(ucwords(str_replace('_', ' ', $r['doc_type']))) ?></td>
                            <td><?= e($r['original_name'] ?? '') ?></td>
                            <td><code class="small"><?= e($r['file_path'] ?? '') ?></code></td>
                            <td class="text-muted small"><?= e(format_datetime($r['uploaded_at'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No documents uploaded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
