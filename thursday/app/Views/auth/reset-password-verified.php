<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password | School ERP</title>
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
                        <i class="bi bi-shield-lock-fill" style="font-size:48px;color:#4e73df"></i>
                        <h4 class="fw-bold mt-2"><?= e(t('h_set_new_password')) ?></h4>
                        <p class="text-muted">Your identity has been verified. Choose a new password below.</p>
                    </div>

                    <?= flash_alerts() ?>

                    <form method="POST" action="<?= e(url('forgot-password/otp/reset')) ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label"><?= e(t('th_new_password')) ?></label>
                            <input type="password" name="password" class="form-control form-control-lg <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" required minlength="8">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                            <div class="form-text">At least 8 characters.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?= e(t('th_confirm_new_password')) ?></label>
                            <input type="password" name="password_confirmation" class="form-control form-control-lg" required minlength="8">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check-circle-fill me-1"></i> Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
</body>
</html>
