<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | School ERP</title>
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
                        <i class="bi bi-key-fill" style="font-size:48px;color:#4e73df"></i>
                        <h4 class="fw-bold mt-2">Forgot your password?</h4>
                        <p class="text-muted">Enter your email and we'll send you a reset link.</p>
                    </div>

                    <?= flash_alerts() ?>

                    <form method="POST" action="<?= e(url('forgot-password')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required autofocus>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send-fill me-1"></i> Send Reset Link
                        </button>
                    </form>

                    <div class="text-center text-muted my-3 small">— or get a one-time code instead —</div>

                    <form method="POST" action="<?= e(url('forgot-password/otp')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?= e(old('email')) ?>" required>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <button type="submit" name="channel" value="email" class="btn btn-outline-primary w-50">
                                <i class="bi bi-envelope me-1"></i> Email Code
                            </button>
                            <button type="submit" name="channel" value="sms" class="btn btn-outline-primary w-50">
                                <i class="bi bi-chat-dots me-1"></i> SMS Code
                            </button>
                        </div>
                    </form>

                    <?php if (config('features.security_questions_enabled')): ?>
                    <div class="text-center mb-3">
                        <a href="<?= e(url('forgot-password/security-questions')) ?>" class="small">Answer security questions instead</a>
                    </div>
                    <?php endif; ?>

                    <div class="text-center">
                        <a href="<?= e(url('login')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to login</a>
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
