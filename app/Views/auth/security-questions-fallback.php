<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Security Questions | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
<link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;display:flex;align-items:center}
    .auth-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.2)}
    .btn-primary{background:#4e73df;border-color:#4e73df}
    .btn-primary:hover{background:#3d5fc4;border-color:#3d5fc4}
</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card auth-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-patch-question-fill" style="font-size:48px;color:#4e73df"></i>
                        <h4 class="fw-bold mt-2">Security Questions</h4>
                        <p class="text-muted">A weaker fallback — prefer the emailed link or a one-time code when possible.</p>
                    </div>

                    <?= flash_alerts() ?>

                    <?php if (empty($questions)): ?>
                    <form method="GET" action="<?= e(url('forgot-password/security-questions')) ?>" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg" value="<?= e($email) ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Continue</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="<?= e(url('forgot-password/security-questions')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <?php foreach ($questions as $q): ?>
                        <div class="mb-3">
                            <label class="form-label"><?= e($q['question']) ?></label>
                            <input type="text" name="answers[]" class="form-control" required>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Verify Answers</button>
                    </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= e(url('forgot-password')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to other recovery options</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
</body>
</html>
