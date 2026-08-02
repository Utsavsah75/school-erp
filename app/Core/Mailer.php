<?php

namespace App\Core;

/**
 * Sends transactional email (password resets, verification, notifications).
 *
 * IMPORTANT: send() returns an array, not a bool:
 *   ['success' => bool, 'transport' => 'smtp'|'mail'|'log'|'none', 'error' => ?string]
 *
 * `success` is true ONLY when the message was genuinely handed off to a real
 * transport (SMTP via PHPMailer, or PHP's mail()). It is never true just
 * because the message was written to storage/logs/mail.log — that log exists
 * purely so developers can see what *would* have been sent while SMTP isn't
 * configured yet. Callers must check `success` before telling the user their
 * email was sent (see AuthController / ParentsController for the pattern).
 */
class Mailer
{
    /**
     * @return array{success: bool, transport: string, error: ?string}
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): array
    {
        $config = self::config();
        $driver = $config['mailer'] ?? 'smtp';
        $logPath = self::logPath();

        switch ($driver) {
            case 'smtp':
                if (empty($config['host']) || empty($config['username'])) {
                    self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'NOT SENT — SMTP not configured');
                    return [
                        'success'   => false,
                        'transport' => 'none',
                        'error'     => 'SMTP is not configured yet. Set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in your .env file (see Settings > Mail Setup for step-by-step instructions).',
                    ];
                }
                if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                    self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'NOT SENT — PHPMailer not installed');
                    return [
                        'success'   => false,
                        'transport' => 'none',
                        'error'     => 'The PHPMailer library is not installed. Run "composer install" in the project root, then try again.',
                    ];
                }
                return self::sendViaPhpMailer($toEmail, $toName, $subject, $htmlBody, $config, $logPath);

            case 'mail':
                if (!function_exists('mail')) {
                    self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'NOT SENT — mail() unavailable');
                    return [
                        'success'   => false,
                        'transport' => 'none',
                        'error'     => "PHP's mail() function is not available on this server.",
                    ];
                }
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$config['from_name']} <{$config['from_address']}>\r\n";
                $sent = @mail($toEmail, $subject, $htmlBody, $headers);
                self::log($logPath, $toEmail, $toName, $subject, $htmlBody, $sent ? 'SENT via mail()' : 'FAILED via mail()');
                return $sent
                    ? ['success' => true, 'transport' => 'mail', 'error' => null]
                    : ['success' => false, 'transport' => 'mail', 'error' => "PHP's mail() function reported failure. Check your server's sendmail/MTA configuration, or switch MAIL_MAILER to smtp."];

            default: // 'log' — explicit dev/testing mode, never reports success
                self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'LOGGED ONLY (MAIL_MAILER=log)');
                return [
                    'success'   => false,
                    'transport' => 'log',
                    'error'     => 'MAIL_MAILER is set to "log" — emails are written to storage/logs/mail.log instead of being delivered. Set MAIL_MAILER=smtp and configure SMTP credentials in .env to send real email.',
                ];
        }
    }

    /**
     * True only if the currently configured driver can plausibly deliver
     * real email right now (SMTP: host+username set AND PHPMailer installed;
     * mail: the mail() function exists). Used to fail fast with setup
     * instructions before even looking up a user, so the public
     * forgot-password form can show a configuration error without leaking
     * whether a given email address has an account.
     */
    public static function isConfigured(): bool
    {
        $config = self::config();
        return match ($config['mailer'] ?? 'smtp') {
            'smtp'  => !empty($config['host']) && !empty($config['username']) && class_exists(\PHPMailer\PHPMailer\PHPMailer::class),
            'mail'  => function_exists('mail'),
            default => false,
        };
    }

    /** Structured status for the Settings > Mail Setup page. */
    public static function status(): array
    {
        $config = self::config();
        $driver = $config['mailer'] ?? 'smtp';

        return [
            'driver'            => $driver,
            'configured'        => self::isConfigured(),
            'host'              => $config['host'],
            'port'              => $config['port'],
            'username'          => $config['username'],
            'encryption'        => $config['encryption'],
            'from_address'      => $config['from_address'],
            'from_name'         => $config['from_name'],
            'phpmailer_installed' => class_exists(\PHPMailer\PHPMailer\PHPMailer::class),
            'env_file_exists'   => is_file(dirname(__DIR__, 2) . '/.env'),
            'log_path'          => self::logPath(),
        ];
    }

    /**
     * Sends a small test email using whatever driver is currently
     * configured, so an admin can verify SMTP settings actually work.
     * @return array{success: bool, transport: string, error: ?string}
     */
    public static function sendTest(string $toEmail, string $toName = 'Administrator'): array
    {
        $html = '<p>Hi ' . htmlspecialchars($toName, ENT_QUOTES) . ',</p>'
            . '<p>This is a test email from your School ERP installation, sent ' . date('Y-m-d H:i:s') . '.</p>'
            . '<p>If you received this, your SMTP configuration is working correctly and password reset / notification emails will be delivered.</p>';

        return self::send($toEmail, $toName, 'School ERP — Test Email', $html);
    }

    // ------------------------------------------------------------------

    private static function config(): array
    {
        return (require dirname(__DIR__, 2) . '/config/app.php')['mail'];
    }

    private static function logPath(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs/mail.log';
    }

    private static function log(string $path, string $toEmail, string $toName, string $subject, string $htmlBody, string $status): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $entry = sprintf(
            "[%s] %s\nTo: %s <%s>\nSubject: %s\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $status,
            $toName,
            $toEmail,
            $subject,
            str_repeat('-', 60),
            strip_tags($htmlBody)
        );
        @file_put_contents($path, $entry, FILE_APPEND);
    }

    private static function sendViaPhpMailer(string $toEmail, string $toName, string $subject, string $htmlBody, array $config, string $logPath): array
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'] ?: false;
            $mail->Port = $config['port'];

            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'SENT via SMTP');
            return ['success' => true, 'transport' => 'smtp', 'error' => null];
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            error_log('[MAIL ERROR] ' . $message);
            self::log($logPath, $toEmail, $toName, $subject, $htmlBody, 'FAILED via SMTP: ' . $message);
            return ['success' => false, 'transport' => 'smtp', 'error' => $message];
        }
    }
}
