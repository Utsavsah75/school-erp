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
                        <p class="text-muted" id="fp-subtitle">Enter your email and we'll send a 6-digit verification code.</p>
                    </div>

                    <?= flash_alerts() ?>

                    <form method="POST" action="<?= e(url('forgot-password/otp')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="channel" id="fp-channel" value="email">

                        <div class="mb-3" id="fp-email-field">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="fp-email" class="form-control form-control-lg <?= field_error($errors, 'email') ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" autofocus>
                            <div class="invalid-feedback"><?= e(field_error($errors, 'email')) ?></div>
                        </div>

                        <div class="mb-3 d-none" id="fp-phone-field">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" name="phone" id="fp-phone" class="form-control form-control-lg <?= field_error($errors, 'phone') ? 'is-invalid' : '' ?>" value="<?= e(old('phone')) ?>" placeholder="e.g. 98XXXXXXXX">
                            <div class="invalid-feedback"><?= e(field_error($errors, 'phone')) ?></div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-send-fill me-1"></i> <span id="fp-submit-label">Send Code</span>
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary w-50 fp-tab active" data-channel="email">
                                <i class="bi bi-envelope me-1"></i> Email
                            </button>
                            <button type="button" class="btn btn-outline-primary w-50 fp-tab" data-channel="sms">
                                <i class="bi bi-chat-dots me-1"></i> SMS
                            </button>
                        </div>
                    </form>

                    <?php if (config('features.security_questions_enabled')): ?>
                    <div class="text-center my-3">
                        <a href="<?= e(url('forgot-password/security-questions')) ?>" class="small">Answer security questions instead</a>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= e(url('login')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
<script>
document.querySelectorAll('.fp-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var channel = btn.dataset.channel;
        document.getElementById('fp-channel').value = channel;

        document.querySelectorAll('.fp-tab').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            b.classList.toggle('btn-primary', b === btn);
            b.classList.toggle('btn-outline-primary', b !== btn);
        });

        var emailField = document.getElementById('fp-email-field');
        var phoneField = document.getElementById('fp-phone-field');
        var emailInput = document.getElementById('fp-email');
        var phoneInput = document.getElementById('fp-phone');
        var subtitle = document.getElementById('fp-subtitle');
        var submitLabel = document.getElementById('fp-submit-label');

        if (channel === 'sms') {
            emailField.classList.add('d-none');
            phoneField.classList.remove('d-none');
            emailInput.required = false;
            phoneInput.required = true;
            phoneInput.focus();
            subtitle.textContent = "Enter your mobile number and we'll send a 6-digit verification code via SMS.";
            submitLabel.textContent = 'Send SMS Code';
        } else {
            phoneField.classList.add('d-none');
            emailField.classList.remove('d-none');
            phoneInput.required = false;
            emailInput.required = true;
            emailInput.focus();
            subtitle.textContent = "Enter your email and we'll send a 6-digit verification code.";
            submitLabel.textContent = 'Send Email Code';
        }
    });
});

// If the server re-rendered this page with a phone validation error, land back on the SMS tab.
<?php if (field_error($errors, 'phone')): ?>
document.querySelector('.fp-tab[data-channel="sms"]').click();
<?php else: ?>
document.getElementById('fp-email').required = true;
<?php endif; ?>
</script>
</body>
</html>
