<?php

/**
 * Hand-built Composer-compatible autoloader.
 *
 * NOTE: This was assembled manually because the sandbox this fix was written
 * in has no outbound access to packagist.org (only github.com is reachable),
 * so `composer install` could not actually be executed here. It replicates
 * exactly what `composer install` would have produced for this project's
 * composer.json — PSR-4 for App\ and PHPMailer\PHPMailer\, plus the
 * autoloaded helpers file — so app/Core/App.php's existing
 * `if (is_file(vendor/autoload.php))` branch behaves the same either way.
 *
 * On your real server, run `composer install` (or `composer require
 * phpmailer/phpmailer dompdf/dompdf phpoffice/phpspreadsheet`) and Composer
 * will overwrite this file with its real, complete autoloader — that's fine
 * and expected, this file is just a working stand-in until then. dompdf and
 * phpspreadsheet are NOT included here (only PHPMailer), since this fix was
 * scoped to email; PDF/Excel export will still need a real composer install.
 */

spl_autoload_register(function (string $class): void {
    // PSR-4: App\ => app/
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return;
    }

    // PSR-4: PHPMailer\PHPMailer\ => vendor/phpmailer/phpmailer/src/
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return;
    }
});

// Composer's "files" autoload entry (app/Helpers/helpers.php) — plain
// functions, not classes, so they must be required directly rather than
// autoloaded.
require_once __DIR__ . '/../app/Helpers/helpers.php';
