<?php

namespace App\Core;

/**
 * Application bootstrap. Wires up autoloading, error handling, timezone,
 * sessions, and hands off to the Router.
 */
class App
{
    private static string $rootPath;

    public static function run(string $rootPath): void
    {
        self::$rootPath = rtrim($rootPath, '/');

        require_once self::$rootPath . '/config/env.php';

        self::registerAutoloader();
        self::registerErrorHandling();

        $appConfig = require self::$rootPath . '/config/app.php';
        require_once self::$rootPath . '/config/constants.php';
        date_default_timezone_set($appConfig['timezone']);

        Session::start();
        Lang::start();

        $router = new Router();
        $routesCallback = require self::$rootPath . '/config/routes.php';
        $routesCallback($router);

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $router->dispatch($uri, $method);
    }

    /**
     * Prefer Composer's autoloader (for phpmailer/dompdf/phpspreadsheet).
     * Fall back to a simple PSR-4 autoloader for the App\ namespace so the
     * core system still runs even before `composer install` is run.
     */
    private static function registerAutoloader(): void
    {
        $composerAutoload = self::$rootPath . '/vendor/autoload.php';
        if (is_file($composerAutoload)) {
            require_once $composerAutoload;
            return;
        }

        spl_autoload_register(function (string $class) {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = self::$rootPath . '/app/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });

        // Helpers aren't autoloaded (they're plain functions), load explicitly.
        require_once self::$rootPath . '/app/Helpers/helpers.php';
    }

    private static function registerErrorHandling(): void
    {
        $debug = env('APP_DEBUG', false);

        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        $logFile = self::$rootPath . '/storage/logs/app.log';
        ini_set('log_errors', '1');
        ini_set('error_log', $logFile);

        set_exception_handler(function (\Throwable $e) use ($debug) {
            error_log('[UNCAUGHT] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            if ($debug) {
                echo '<pre style="padding:20px;background:#1e1e1e;color:#f66;">';
                echo htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString());
                echo '</pre>';
            } else {
                $errorView = self::$rootPath . '/app/Views/errors/500.php';
                if (is_file($errorView)) {
                    require $errorView;
                } else {
                    echo 'A server error occurred.';
                }
            }
        });
    }

    public static function rootPath(): string
    {
        return self::$rootPath;
    }
}
