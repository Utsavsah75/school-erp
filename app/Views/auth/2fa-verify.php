<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Two-Factor Verification | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;display:flex;align-items:center}
    .auth-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.2);max-width:440px;margin:0 auto}
    .otp-boxes{display:flex;gap:8px;justify-content:center}
    .otp-boxes input.otp-box{width:46px;height:52px;font-size:1.3rem;text-align:center}
    .btn-primary{background:#4e73df;border-color:#4e73df}
</style>
</head>
<body>
<div class="container">
<div class="card auth-card">
    <div class="card-body p-5 text-center">
        <i class="bi bi-shield-lock-fill" style="font-size:44px;color:#4e73df"></i>
        <h4 class="fw-bold mt-2">Two-Factor Verification</h4>
        <p class="text-muted small">Enter the 6-digit code from your authenticator app, or a recovery code.</p>

        <?= flash_alerts() ?>

        <form method="POST" action="<?= e(url('2fa/verify')) ?>" id="twofaForm">
            <?= csrf_field() ?>
            <div id="totpMode">
                <div class="otp-boxes mb-3" id="otpBoxes"></div>
            </div>
            <div id="recoveryMode" class="d-none mb-3">
                <input type="text" class="form-control text-center text-uppercase" id="recoveryInput" placeholder="Recovery code" maxlength="10" autocomplete="off">
            </div>
            <input type="hidden" name="code" id="codeInput">
            <button type="submit" class="btn btn-primary btn-lg w-100">Verify</button>
        </form>

        <p class="mt-3 mb-0">
            <a href="#" class="small" id="toggleRecovery">Use a recovery code instead</a>
        </p>
        <p class="mt-2 mb-0"><a href="<?= e(url('login')) ?>" class="small">&larr; Back to login</a></p>
    </div>
</div>
</div>
<script src="<?= e(asset('js/auth-common.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var box = document.getElementById('otpBoxes');
    var reader = buildOtpBoxes(box, 6);
    var usingRecovery = false;

    document.getElementById('toggleRecovery').addEventListener('click', function (e) {
        e.preventDefault();
        usingRecovery = !usingRecovery;
        document.getElementById('totpMode').classList.toggle('d-none', usingRecovery);
        document.getElementById('recoveryMode').classList.toggle('d-none', !usingRecovery);
        this.textContent = usingRecovery ? 'Use my authenticator app instead' : 'Use a recovery code instead';
        if (usingRecovery) document.getElementById('recoveryInput').focus();
    });

    document.getElementById('twofaForm').addEventListener('submit', function () {
        document.getElementById('codeInput').value = usingRecovery
            ? document.getElementById('recoveryInput').value.trim()
            : reader();
    });
});
</script>
</body>
</html>
