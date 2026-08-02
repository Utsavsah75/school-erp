<?php
/** Expects: $mailStatus (from Mailer::status()) */
$errors = \App\Core\Session::getErrors();
$fieldError = fn($key) => field_error($errors, $key);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Settings</h5>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-envelope-paper-heart me-1"></i>Mail Setup</h6>

                <?php if ($mailStatus['configured']): ?>
                <div class="alert alert-success py-2 px-3 mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    SMTP is configured. Password reset, verification, and notification emails will be sent for real.
                </div>
                <?php else: ?>
                <div class="alert alert-warning py-2 px-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Email is <strong>not fully configured yet</strong>. Every "send email" action in the app will show
                    an honest error instead of pretending to succeed, and will write a copy of what would have been
                    sent to <code><?= e($mailStatus['log_path']) ?></code> for local testing.
                </div>
                <?php endif; ?>

                <table class="table table-sm table-borderless mb-4">
                    <tr>
                        <td class="text-muted" style="width:180px;">Driver (MAIL_MAILER)</td>
                        <td><span class="badge bg-secondary"><?= e($mailStatus['driver']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">.env file</td>
                        <td><?= $mailStatus['env_file_exists'] ? '<span class="text-success"><i class="bi bi-check-lg"></i> Found</span>' : '<span class="text-danger"><i class="bi bi-x-lg"></i> Missing — copy .env.example to .env</span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">PHPMailer installed</td>
                        <td><?= $mailStatus['phpmailer_installed'] ? '<span class="text-success"><i class="bi bi-check-lg"></i> Yes</span>' : '<span class="text-danger"><i class="bi bi-x-lg"></i> No — run <code>composer install</code></span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">SMTP Host</td>
                        <td><?= $mailStatus['host'] !== '' ? e($mailStatus['host']) . ':' . e((string) $mailStatus['port']) : '<span class="text-muted">not set</span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">SMTP Username</td>
                        <td><?= $mailStatus['username'] !== '' ? e($mailStatus['username']) : '<span class="text-muted">not set</span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Encryption</td>
                        <td><?= e($mailStatus['encryption']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">From</td>
                        <td><?= e($mailStatus['from_name']) ?> &lt;<?= e($mailStatus['from_address']) ?>&gt;</td>
                    </tr>
                </table>

                <?php if (!$mailStatus['configured']): ?>
                <div class="border rounded p-3 mb-4 bg-body-tertiary">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Setup instructions</h6>
                    <ol class="small mb-0 ps-3">
                        <li class="mb-1">If <code>.env</code> doesn't exist in the project root, copy
                            <code>.env.example</code> to <code>.env</code>.
                        </li>
                        <li class="mb-1">Run <code>composer install</code> in the project root so PHPMailer is
                            available.</li>
                        <li class="mb-1">Edit <code>.env</code> and set:
                            <pre class="small bg-dark text-light rounded p-2 mt-1 mb-1">MAIL_MAILER=smtp
MAIL_HOST=smtp."publiclibrary48@gmail.com";
MAIL_PORT=587
MAIL_USERNAME="publiclibrary48@gmail.com"
MAIL_PASSWORD="gjkx qtup hmph fozg"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="publiclibrary48@gmail.com"
MAIL_FROM_NAME="School ERP"</pre>
                        </li>
                        <li class="mb-1">
                            For Gmail: use an <a href="https://myaccount.google.com/apppasswords" target="_blank"
                                rel="noopener">App Password</a> (not your normal password), host
                            <code>smtp.gmail.com</code>, port <code>587</code>, encryption <code>tls</code>.
                        </li>
                        <li class="mb-1">For Outlook/Office365: host <code>smtp.office365.com</code>, port
                            <code>587</code>, encryption <code>tls</code>.
                        </li>
                        <li>Reload this page — the status above updates automatically once the values are set correctly.
                        </li>
                    </ol>
                </div>
                <?php endif; ?>

                <h6 class="fw-bold mb-2"><i class="bi bi-send-check me-1"></i>Send a Test Email</h6>
                <form method="POST" action="<?= e(url('settings/test-email')) ?>" class="row g-2 align-items-start">
                    <?= csrf_field() ?>
                    <div class="col-sm-8">
                        <input type="email" name="test_email"
                            class="form-control <?= $fieldError('test_email') ? 'is-invalid' : '' ?>"
                            placeholder="you@example.com" value="<?= e((string) old('test_email')) ?>" required>
                        <div class="invalid-feedback d-block"><?= e($fieldError('test_email')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-envelope-fill me-1"></i>Send
                            Test Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>