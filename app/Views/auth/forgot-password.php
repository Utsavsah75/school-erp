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
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap"
        rel="stylesheet">
    <link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        min-height: 100vh;
        display: flex;
        align-items: center
    }

    .auth-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, .2)
    }

    .btn-primary {
        background: #4e73df;
        border-color: #4e73df
    }

    .btn-primary:hover {
        background: #3d5fc4;
        border-color: #3d5fc4
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card auth-card">
                    <div class="card-body p-5">
                        <?php
                        // Reflect the previously chosen channel on validation-error reloads,
                        // so the page doesn't silently flip back to email under the user.
                        $isSmsMode = (old('channel') === 'sms');
                    ?>
                        <div class="text-center mb-4">
                            <i class="bi bi-key-fill" style="font-size:48px;color:#4e73df"></i>
                            <h4 class="fw-bold mt-2">Forgot your password?</h4>
                            <p class="text-muted" id="introText">
                                <?= $isSmsMode
                            ? "Enter your registered mobile number — we'll send a 6-digit verification code by SMS."
                            : "Enter your email — we'll send a 6-digit verification code to your email or the phone on your account." ?></p>
                        </div>

                        <?= flash_alerts() ?>

                        <form method="POST" action="<?= e(url('forgot-password/otp')) ?>" novalidate>
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label"
                                    id="loginLabel"><?= $isSmsMode ? 'Mobile Number' : 'Email Address' ?></label>
                                <input type="<?= $isSmsMode ? 'tel' : 'email' ?>"
                                    name="<?= $isSmsMode ? 'phone' : 'email' ?>" id="loginInput"
                                    class="form-control form-control-lg <?= (field_error($errors, 'email') || field_error($errors, 'phone')) ? 'is-invalid' : '' ?>"
                                    value="<?= $isSmsMode ? e(old('phone')) : e(old('email')) ?>"
                                    placeholder="<?= $isSmsMode ? 'Enter your registered mobile number' : '' ?>"
                                    required autofocus>
                                <div class="invalid-feedback">
                                    <?= e(field_error($errors, 'email') ?: field_error($errors, 'phone')) ?></div>
                                <div class="form-text" id="loginHelp">
                                    <?= $isSmsMode ? "We'll send the verification code via SMS to this number." : 'Used to look up your account — the code itself can go to either channel below.' ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="channel" value="email" id="emailBtn"
                                    class="btn <?= $isSmsMode ? 'btn-outline-primary' : 'btn-primary' ?> btn-lg w-50">
                                    <i class="bi bi-envelope-check-fill me-1"></i> Email Code
                                </button>
                                <button type="submit" name="channel" value="sms" id="smsBtn"
                                    class="btn <?= $isSmsMode ? 'btn-primary' : 'btn-outline-primary' ?> btn-lg w-50">
                                    <i class="bi bi-chat-dots-fill me-1"></i> SMS Code
                                </button>
                            </div>
                        </form>
                        <script>
                        (function() {
                            var mode = <?= $isSmsMode ? "'sms'" : "'email'" ?>;
                            var introText = document.getElementById('introText');
                            var loginInput = document.getElementById('loginInput');
                            var loginLabel = document.getElementById('loginLabel');
                            var loginHelp = document.getElementById('loginHelp');
                            var emailBtn = document.getElementById('emailBtn');
                            var smsBtn = document.getElementById('smsBtn');

                            function toEmailMode() {
                                mode = 'email';
                                introText.textContent =
                                    "Enter your email — we'll send a 6-digit verification code to your email or the phone on your account.";
                                loginLabel.textContent = 'Email Address';
                                loginInput.type = 'email';
                                loginInput.name = 'email';
                                loginInput.placeholder = '';
                                loginInput.value = '';
                                loginHelp.textContent =
                                    'Used to look up your account — the code itself can go to either channel below.';
                                emailBtn.classList.add('btn-primary');
                                emailBtn.classList.remove('btn-outline-primary');
                                smsBtn.classList.add('btn-outline-primary');
                                smsBtn.classList.remove('btn-primary');
                                loginInput.focus();
                            }

                            function toSmsMode() {
                                mode = 'sms';
                                introText.textContent =
                                    "Enter your registered mobile number — we'll send a 6-digit verification code by SMS.";
                                loginLabel.textContent = 'Mobile Number';
                                loginInput.type = 'tel';
                                loginInput.name = 'phone';
                                loginInput.placeholder = 'Enter your registered mobile number';
                                loginInput.value = '';
                                loginHelp.textContent = "We'll send the verification code via SMS to this number.";
                                smsBtn.classList.add('btn-primary');
                                smsBtn.classList.remove('btn-outline-primary');
                                emailBtn.classList.add('btn-outline-primary');
                                emailBtn.classList.remove('btn-primary');
                                loginInput.focus();
                            }

                            // First click on a channel button switches the page into that
                            // channel's entry mode; only a click while already in that mode
                            // lets the form actually submit.
                            emailBtn.addEventListener('click', function(e) {
                                if (mode !== 'email') {
                                    e.preventDefault();
                                    toEmailMode();
                                }
                            });
                            smsBtn.addEventListener('click', function(e) {
                                if (mode !== 'sms') {
                                    e.preventDefault();
                                    toSmsMode();
                                }
                            });
                        })();
                        </script>

                        <?php if (config('features.security_questions_enabled')): ?>
                        <div class="text-center mb-3">
                            <a href="<?= e(url('forgot-password/security-questions')) ?>" class="small">Answer security
                                questions instead</a>
                        </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="<?= e(url('login')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to
                                login</a>
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