<?php $pageTitle = 'Security Questions'; ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-patch-question-fill me-2"></i>Security Questions</h5>
                <p class="text-muted small mb-3">Optional password-recovery fallback. Answers are stored hashed, never in plain text.</p>
                <?= flash_alerts() ?>
                <form method="POST" action="<?= e(url('security-questions/setup')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <?php for ($i = 1; $i <= 3; $i++):
                        $existing = $questions[$i - 1] ?? null; ?>
                    <div class="mb-3 border rounded p-3">
                        <label class="form-label">Question <?= $i ?></label>
                        <input type="text" name="question_<?= $i ?>" class="form-control mb-2 <?= field_error($errors, "question_{$i}") ? 'is-invalid' : '' ?>"
                            value="<?= e($existing['question'] ?? old("question_{$i}")) ?>" required minlength="5">
                        <div class="invalid-feedback"><?= e(field_error($errors, "question_{$i}")) ?></div>

                        <label class="form-label">Answer</label>
                        <input type="text" name="answer_<?= $i ?>" class="form-control <?= field_error($errors, "answer_{$i}") ? 'is-invalid' : '' ?>"
                            placeholder="<?= $existing ? 'Re-enter the answer to keep/update this question' : '' ?>" required minlength="2">
                        <div class="invalid-feedback"><?= e(field_error($errors, "answer_{$i}")) ?></div>
                    </div>
                    <?php endfor; ?>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i> Save Security Questions</button>
                </form>
            </div>
        </div>
    </div>
</div>
