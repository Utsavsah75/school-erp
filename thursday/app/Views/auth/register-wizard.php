<?php
/** Expects: $role, $method, $token, $recaptchaSite, $otpLength, $otpTtl, $errors */
$roleLabels = ['parent' => 'Parent', 'student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff / Employee'];
$roleLabel = $roleLabels[$role] ?? st($role);
$showMethodPicker = $role !== 'student';
$resendCooldown = \App\Core\Settings::int('otp_resend_cooldown_seconds', 60);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($roleLabel) ?> Registration | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
<link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
<?php if ($recaptchaSite): ?><script src="https://www.google.com/recaptcha/api.js" async defer></script><?php endif; ?>
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;padding:40px 0}
    .auth-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.2)}
    .btn-primary{background:#4e73df;border-color:#4e73df}
    .btn-primary:hover{background:#3d5fc4;border-color:#3d5fc4}
    .method-pill{border-radius:30px;padding:8px 16px;border:1px solid #dfe3ec;background:#fff;color:#444;font-size:.85rem;cursor:pointer;text-decoration:none;display:inline-block}
    .method-pill.active{background:#4e73df;border-color:#4e73df;color:#fff}
    .otp-group input.otp-box{width:46px;height:52px;font-size:1.3rem;letter-spacing:0}
    .otp-boxes{display:flex;gap:8px;justify-content:center}
    .step-panel{display:none}
    .step-panel.active{display:block}
    .steps-indicator{display:flex;gap:8px;justify-content:center;margin-bottom:24px}
    .steps-indicator .dot{width:10px;height:10px;border-radius:50%;background:#dfe3ec}
    .steps-indicator .dot.active{background:#4e73df}
</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card auth-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill" style="font-size:44px;color:#4e73df"></i>
                        <h4 class="fw-bold mt-2 mb-0"><?= e($roleLabel) ?> Registration</h4>
                        <p class="text-muted small mb-0"><a href="<?= e(url('register')) ?>">&larr; Choose a different role</a></p>
                    </div>

                    <?= flash_alerts() ?>

                    <div class="steps-indicator">
                        <div class="dot active" data-dot="details"></div>
                        <div class="dot" data-dot="verify"></div>
                        <div class="dot" data-dot="success"></div>
                    </div>

                    <?php if ($showMethodPicker): ?>
                    <div class="d-flex gap-2 justify-content-center flex-wrap mb-4">
                        <a class="method-pill <?= $method === 'mobile_otp' ? 'active' : '' ?>" href="<?= e(url('register/wizard?role=' . $role . '&method=mobile_otp')) ?>"><i class="bi bi-phone me-1"></i>Mobile + SMS OTP</a>
                        <a class="method-pill <?= $method === 'email_code' ? 'active' : '' ?>" href="<?= e(url('register/wizard?role=' . $role . '&method=email_code')) ?>"><i class="bi bi-envelope me-1"></i>Email + Code</a>
                        <a class="method-pill <?= $method === 'phone_email' ? 'active' : '' ?>" href="<?= e(url('register/wizard?role=' . $role . '&method=phone_email')) ?>"><i class="bi bi-shield-check me-1"></i>Phone + Email</a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info py-2 px-3 small text-center mb-4">
                        <i class="bi bi-info-circle-fill me-1"></i> A verification code will be sent to your parent/guardian's phone or email on file.
                    </div>
                    <?php endif; ?>

                    <!-- STEP 1: Details -->
                    <div class="step-panel active" data-step="details">
                        <form id="detailsForm" novalidate>
                            <input type="hidden" name="token" value="<?= e($token) ?>">
                            <input type="hidden" name="role" value="<?= e($role) ?>">
                            <input type="hidden" name="method" value="<?= e($method) ?>">
                            <?= csrf_field() ?>

                            <?php if ($role !== 'student'): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= e(t('th_full_name')) ?></label>
                                <input type="text" name="full_name" class="form-control" required autofocus>
                            </div>
                            <?php endif; ?>

                            <?php if ($role === 'parent' || $role === 'student'): ?>
                            <div class="mb-3">
                                <label class="form-label">Child's Admission Number</label>
                                <input type="text" name="admission_number" class="form-control" placeholder="e.g. STU-2026-000123" required <?= $role === 'student' ? 'autofocus' : '' ?>>
                            </div>
                            <?php endif; ?>

                            <?php if ($role === 'teacher'): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= e(t('th_employee_code')) ?></label>
                                <input type="text" name="employee_code" class="form-control" placeholder="e.g. EMP0042" required>
                            </div>
                            <?php endif; ?>

                            <?php if ($role === 'staff'): ?>
                            <div class="mb-3">
                                <label class="form-label">Employee Code (from your invite)</label>
                                <input type="text" name="employee_code" class="form-control" placeholder="Code provided by your administrator" required>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array($method, ['mobile_otp', 'phone_email'], true)): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= e(t('th_phone_number')) ?></label>
                                <input type="tel" name="phone" class="form-control" placeholder="+977 98XXXXXXXX" required>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array($method, ['email_code', 'phone_email'], true)): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= e(t('th_email_address')) ?></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label"><?= e(t('th_password')) ?></label>
                                <input type="password" name="password" id="pwInput" class="form-control" required>
                                <div class="progress mt-2" style="height:6px;">
                                    <div class="progress-bar" id="pwBar" style="width:0%"></div>
                                </div>
                                <small class="text-muted" id="pwLabel"></small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label"><?= e(t('th_confirm_password')) ?></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                            <?php if ($recaptchaSite): ?>
                            <div class="mb-3 d-flex justify-content-center">
                                <div class="g-recaptcha" data-sitekey="<?= e($recaptchaSite) ?>"></div>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="detailsSubmit">
                                <i class="bi bi-send-fill me-1"></i> Continue
                            </button>
                        </form>
                    </div>

                    <!-- STEP 2: Verify OTP(s) -->
                    <div class="step-panel" data-step="verify">
                        <p class="text-muted text-center mb-4">Enter the <?= (int) $otpLength ?>-digit code(s) below. They expire in <?= (int) $otpTtl ?> minutes.</p>
                        <div id="otpGroups"></div>
                        <button type="button" class="btn btn-primary btn-lg w-100 mt-3" id="completeBtn" disabled>
                            <i class="bi bi-check-circle-fill me-1"></i> Complete Registration
                        </button>
                    </div>

                    <!-- STEP 3: Success -->
                    <div class="step-panel text-center" data-step="success">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:56px"></i>
                        <h5 class="fw-bold mt-3"><?= e(t('h_registration_successful')) ?></h5>
                        <p class="text-muted" id="successMessage">Your account has been created.</p>
                        <a href="<?= e(url('login')) ?>" class="btn btn-primary btn-lg w-100 mt-2">Go to Login</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
<script src="<?= e(asset('js/auth-common.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initPasswordStrengthMeter(document.getElementById('pwInput'), document.getElementById('pwBar'), document.getElementById('pwLabel'));

    var otpLength = <?= (int) $otpLength ?>;
    var resendCooldown = <?= (int) $resendCooldown ?>;
    var csrfToken = '<?= e(csrf_token()) ?>';
    var startUrl = '<?= e(url('register/start')) ?>';
    var verifyUrl = '<?= e(url('register/verify-otp')) ?>';
    var resendUrl = '<?= e(url('register/resend-otp')) ?>';
    var completeUrl = '<?= e(url('register/complete')) ?>';

    var verifiedChannels = new Set();
    var expectedChannels = [];
    var wizardToken = document.querySelector('input[name="token"]').value;

    function goToStep(name) {
        document.querySelectorAll('.step-panel').forEach(function (el) {
            el.classList.toggle('active', el.dataset.step === name);
        });
        document.querySelectorAll('[data-dot]').forEach(function (el) {
            el.classList.toggle('active', el.dataset.dot === name);
        });
    }

    document.getElementById('detailsForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        var btn = document.getElementById('detailsSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending code...';

        var formData = new FormData(this);
        if (window.grecaptcha && document.querySelector('.g-recaptcha')) {
            formData.set('g-recaptcha-response', grecaptcha.getResponse());
        }
        var payload = {};
        formData.forEach(function (v, k) { payload[k] = v; });

        var res = await postForm(startUrl, payload);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Continue';

        if (!res.ok) {
            toastError(res.message || 'Please check your details and try again.');
            if (window.grecaptcha) grecaptcha.reset();
            return;
        }

        expectedChannels = (res.targets || []).map(function (t) { return t.channel; });
        var container = document.getElementById('otpGroups');
        container.innerHTML = '';

        (res.targets || []).forEach(function (target) {
            var wrap = document.createElement('div');
            wrap.className = 'otp-group mb-4';
            wrap.innerHTML =
                '<label class="form-label d-block text-center">' +
                (target.channel === 'sms' ? '<i class="bi bi-phone me-1"></i>Code sent via SMS to ' : '<i class="bi bi-envelope me-1"></i>Code sent via Email to ') +
                '<strong>' + target.masked + '</strong></label>' +
                '<div class="otp-boxes" data-channel="' + target.channel + '"></div>' +
                '<div class="text-center mt-2">' +
                '<span class="badge bg-secondary d-none" data-verified-badge>Verified <i class="bi bi-check-lg"></i></span>' +
                '<button type="button" class="btn btn-link btn-sm resend-btn" data-channel="' + target.channel + '"><?= e(t('resend_code')) ?></button>' +
                '</div>';
            container.appendChild(wrap);

            var boxesEl = wrap.querySelector('.otp-boxes');
            var reader = buildOtpBoxes(boxesEl, otpLength);

            boxesEl.addEventListener('otp-change', async function () {
                var code = reader();
                if (code.length !== otpLength) return;
                var vres = await postForm(verifyUrl, {
                    token: wizardToken, channel: target.channel, code: code, _token: csrfToken
                });
                if (vres.ok) {
                    verifiedChannels.add(target.channel);
                    wrap.querySelector('[data-verified-badge]').classList.remove('d-none');
                    boxesEl.querySelectorAll('input').forEach(function (b) { b.disabled = true; });
                    if (verifiedChannels.size === expectedChannels.length) {
                        document.getElementById('completeBtn').disabled = false;
                        toastSuccess('All codes verified.');
                    }
                } else {
                    toastError(vres.message || 'Incorrect code.');
                    boxesEl.querySelectorAll('input').forEach(function (b) { b.value = ''; });
                    boxesEl.querySelector('input').focus();
                }
            });

            wrap.querySelector('.resend-btn').addEventListener('click', async function () {
                var thisBtn = this;
                var rres = await postForm(resendUrl, {
                    token: wizardToken, channel: target.channel, _token: csrfToken
                });
                if (rres.ok) {
                    toastSuccess(rres.message);
                    startResendTimer(thisBtn, resendCooldown);
                } else {
                    toastError(rres.message);
                    if (rres.cooldown) startResendTimer(thisBtn, rres.cooldown);
                }
            });
        });

        goToStep('verify');
    });

    document.getElementById('completeBtn').addEventListener('click', async function () {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating account...';

        var res = await postForm(completeUrl, { token: wizardToken, _token: csrfToken });

        if (!res.ok) {
            toastError(res.message || 'Something went wrong. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Complete Registration';
            return;
        }

        document.getElementById('successMessage').textContent = res.message || 'Your account has been created.';
        goToStep('success');
    });
});
</script>
</body>
</html>
