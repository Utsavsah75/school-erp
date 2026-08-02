<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
<link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;display:flex;align-items:center}
    .auth-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.2);overflow:hidden}
    .auth-side{background:#224abe;color:#fff;padding:40px;display:flex;flex-direction:column;justify-content:center}
    .auth-side i{font-size:56px}
    .btn-primary{background:#4e73df;border-color:#4e73df}
    .btn-primary:hover{background:#3d5fc4;border-color:#3d5fc4}
    .form-label{color:#444}
</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card auth-card">
                <div class="row g-0">
                    <div class="col-md-5 auth-side">
                        <i class="bi bi-mortarboard-fill mb-3"></i>
                        <h3 class="fw-bold">School ERP</h3>
                        <p class="mb-0 opacity-75">One platform for admissions, attendance, fees, exams, and everything in between.</p>
                    </div>
                    <div class="col-md-7">
                        <div class="card-body p-5">
                            <h4 class="fw-bold mb-1">Welcome back</h4>
                            <p class="text-muted mb-4">Sign in to continue to your dashboard</p>

                            <?= flash_alerts() ?>

                            <?php if (!empty($unverifiedEmail)): ?>
                            <form method="POST" action="<?= e(url('resend-verification')) ?>" class="mb-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="email" value="<?= e($unverifiedEmail) ?>">
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-envelope-fill me-1"></i> Resend verification email
                                </button>
                            </form>
                            <?php endif; ?>

                            <form method="POST" action="<?= e(url('login')) ?>" novalidate>
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control form-control-lg <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required autofocus>
                                    <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control form-control-lg <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" required>
                                    <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <a href="<?= e(url('forgot-password')) ?>" class="small">Forgot password?</a>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-center text-white-50 mt-3 small">&copy; <?= date('Y') ?> School ERP. All rights reserved.</p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
</body>
</html>
