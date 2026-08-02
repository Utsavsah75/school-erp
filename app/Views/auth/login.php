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
<?php if ($recaptchaSite): ?><script src="https://www.google.com/recaptcha/api.js" async defer></script><?php endif; ?>
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;display:flex;align-items:center;padding:24px 0}
    .auth-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.2);overflow:hidden}
    .auth-side{background:#224abe;color:#fff;padding:40px;display:flex;flex-direction:column;justify-content:center}
    .auth-side i{font-size:56px}
    .btn-primary{background:#4e73df;border-color:#4e73df}
    .btn-primary:hover{background:#3d5fc4;border-color:#3d5fc4}
    .form-label{color:#444}
    .login-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;border-bottom:1px solid #eee}
    .login-tabs button{border:none;background:none;padding:8px 10px;font-size:.82rem;color:#777;border-bottom:2px solid transparent;white-space:nowrap}
    .login-tabs button.active{color:#4e73df;border-color:#4e73df;font-weight:600}
    .login-pane{display:none}
    .login-pane.active{display:block}
    .otp-boxes{display:flex;gap:8px;justify-content:center}
    .otp-boxes input.otp-box{width:44px;height:50px;font-size:1.2rem;text-align:center}
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
                        <p class="mt-4 mb-0 small">New here? <a href="<?= e(url('register')) ?>" class="text-white text-decoration-underline">Create an account</a></p>
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

                            <div class="login-tabs" id="loginTabs">
                                <button type="button" class="active" data-tab="email">Email</button>
                                <button type="button" data-tab="username">Username</button>
                                <button type="button" data-tab="student_id">Student ID</button>
                                <button type="button" data-tab="employee_id">Employee ID</button>
                                <button type="button" data-tab="phone">Phone + OTP</button>
                            </div>

                            <?php
                            $identifierFields = [
                                'email'       => ['label' => 'Email Address', 'type' => 'email', 'placeholder' => 'you@example.com'],
                                'username'    => ['label' => 'Username', 'type' => 'text', 'placeholder' => 'jdoe'],
                                'student_id'  => ['label' => 'Student ID (Admission No.)', 'type' => 'text', 'placeholder' => 'STU-2026-000123'],
                                'employee_id' => ['label' => 'Employee ID', 'type' => 'text', 'placeholder' => 'EMP0042'],
                            ];
                            ?>
                            <?php foreach ($identifierFields as $type => $f): ?>
                            <div class="login-pane <?= $type === 'email' ? 'active' : '' ?>" data-pane="<?= $type ?>">
                                <form method="POST" action="<?= e(url('login')) ?>" novalidate>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="login_type" value="<?= $type ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= e($f['label']) ?></label>
                                        <input type="<?= $f['type'] ?>" name="<?= $type ?>" class="form-control form-control-lg <?= field_error($errors, $type) ? 'is-invalid' : '' ?>" value="<?= e(old($type)) ?>" placeholder="<?= e($f['placeholder']) ?>" required <?= $type === 'email' ? 'autofocus' : '' ?>>
                                        <div class="invalid-feedback"><?= e(field_error($errors, $type)) ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control form-control-lg <?= field_error($errors, 'password') ? 'is-invalid' : '' ?>" required>
                                        <div class="invalid-feedback"><?= e(field_error($errors, 'password')) ?></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember_<?= $type ?>">
                                            <label class="form-check-label" for="remember_<?= $type ?>">Remember me</label>
                                        </div>
                                        <a href="<?= e(url('forgot-password')) ?>" class="small">Forgot password?</a>
                                    </div>
                                    <?php if ($recaptchaSite): ?>
                                    <div class="mb-3 d-flex justify-content-center">
                                        <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSite) ?>"></div>
                                    </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>

                            <div class="login-pane" data-pane="phone">
                                <div id="phoneStep1">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" id="phoneInput" class="form-control form-control-lg" placeholder="+977 98XXXXXXXX" required>
                                    </div>
                                    <?php if ($recaptchaSite): ?>
                                    <div class="mb-3 d-flex justify-content-center">
                                        <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSite) ?>"></div>
                                    </div>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-primary btn-lg w-100" id="sendPhoneOtpBtn">
                                        <i class="bi bi-send-fill me-1"></i> Send Login Code
                                    </button>
                                </div>
                                <div id="phoneStep2" class="d-none">
                                    <p class="text-muted small text-center">Enter the 6-digit code sent to your phone.</p>
                                    <div class="otp-boxes mb-3" id="phoneOtpBoxes"></div>
                                    <div class="form-check mb-3 justify-content-center d-flex">
                                        <input class="form-check-input me-1" type="checkbox" id="remember_phone">
                                        <label class="form-check-label" for="remember_phone">Remember me</label>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-lg w-100" id="verifyPhoneOtpBtn">Verify &amp; Sign In</button>
                                    <button type="button" class="btn btn-link btn-sm w-100 mt-1" id="resendPhoneOtpBtn">Resend code</button>
                                </div>
                            </div>

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
<script src="<?= e(asset('js/auth-common.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#loginTabs button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#loginTabs button').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.login-pane').forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelector('.login-pane[data-pane="' + btn.dataset.tab + '"]').classList.add('active');
        });
    });

    var csrfToken = '<?= e(csrf_token()) ?>';
    var phoneReader = buildOtpBoxes(document.getElementById('phoneOtpBoxes'), 6);
    var currentPhone = '';

    document.getElementById('sendPhoneOtpBtn').addEventListener('click', async function () {
        var btn = this;
        currentPhone = document.getElementById('phoneInput').value.trim();
        if (!currentPhone) { toastError('Please enter your phone number.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
        var payload = { phone: currentPhone, _token: csrfToken };
        if (window.grecaptcha && document.querySelector('[data-pane="phone"] .g-recaptcha')) {
            payload['g-recaptcha-response'] = grecaptcha.getResponse();
        }
        var res = await postForm('<?= e(url('login/phone/start')) ?>', payload);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Send Login Code';

        if (!res.ok) { toastError(res.message); return; }
        toastSuccess(res.message);
        document.getElementById('phoneStep1').classList.add('d-none');
        document.getElementById('phoneStep2').classList.remove('d-none');
        startResendTimer(document.getElementById('resendPhoneOtpBtn'), 60);
    });

    document.getElementById('resendPhoneOtpBtn').addEventListener('click', async function () {
        var res = await postForm('<?= e(url('login/phone/start')) ?>', { phone: currentPhone, _token: csrfToken });
        if (res.ok) { toastSuccess(res.message); startResendTimer(this, 60); } else { toastError(res.message); }
    });

    document.getElementById('verifyPhoneOtpBtn').addEventListener('click', async function () {
        var btn = this;
        var code = phoneReader();
        if (code.length !== 6) { toastError('Please enter the 6-digit code.'); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';

        var res = await postForm('<?= e(url('login/phone/verify')) ?>', {
            phone: currentPhone, code: code, remember: document.getElementById('remember_phone').checked ? '1' : '',
            _token: csrfToken,
        });
        btn.disabled = false;
        btn.innerHTML = 'Verify &amp; Sign In';

        if (!res.ok) { toastError(res.message); return; }
        window.location.href = res.redirect;
    });
});
</script>
</body>
</html>
