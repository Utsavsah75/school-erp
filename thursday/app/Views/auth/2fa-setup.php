<?php
/** Expects: $alreadyEnabled, $secret?, $otpauthUri?, $recoveryCodes?, $errors — rendered inside the app layout */
$pageTitle = 'Two-Factor Authentication';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill me-1"></i> Two-Factor Authentication</h5>
                <?= flash_alerts() ?>

                <?php if ($alreadyEnabled): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i> Two-Factor Authentication is currently <strong>enabled</strong> on your account.</div>

                    <?php if (!empty($recoveryCodes)): ?>
                    <div class="alert alert-warning">
                        <strong>Save these recovery codes now</strong> — they won't be shown again. Each can be used once if you lose access to your authenticator app.
                        <div class="row row-cols-2 g-2 mt-2 font-monospace">
                            <?php foreach ($recoveryCodes as $code): ?>
                                <div class="col"><code><?= e($code) ?></code></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= e(url('2fa/disable')) ?>" class="mt-3" onsubmit="return confirm('Disable Two-Factor Authentication?');">
                        <?= csrf_field() ?>
                        <div class="mb-3" style="max-width:320px;">
                            <label class="form-label">Confirm your password to disable</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-shield-x me-1"></i> Disable 2FA</button>
                    </form>

                <?php else: ?>
                    <p class="text-muted small">
                        Scan this QR code with Google Authenticator, Microsoft Authenticator, Authy, or any TOTP app,
                        then enter the 6-digit code it shows to confirm setup.
                    </p>
                    <div class="text-center mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($otpauthUri) ?>"
                             alt="Scan with your authenticator app" width="220" height="220" class="border rounded p-2">
                    </div>
                    <p class="text-center text-muted small">
                        Can't scan? Enter this key manually: <code><?= e($secret) ?></code>
                    </p>

                    <form method="POST" action="<?= e(url('2fa/setup')) ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <div class="mb-3 d-flex justify-content-center">
                            <div class="otp-boxes" id="otpBoxes"></div>
                        </div>
                        <input type="hidden" name="code" id="codeInput">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle-fill me-1"></i> Verify &amp; Enable</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>.otp-boxes{display:flex;gap:8px}.otp-boxes input.otp-box{width:44px;height:50px;font-size:1.2rem;text-align:center}</style>
<script src="<?= e(asset('js/auth-common.js')) ?>"></script>
<?php if (!$alreadyEnabled): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var reader = buildOtpBoxes(document.getElementById('otpBoxes'), 6);
    document.querySelector('form[action*="2fa/setup"]').addEventListener('submit', function () {
        document.getElementById('codeInput').value = reader();
    });
});
</script>
<?php endif; ?>
