<?php

/**
 * Front controller — the ONLY publicly accessible PHP entry point.
 * Everything else (app/, config/, database/, storage/) is outside the
 * web root or blocked via .htaccess.
 */

require dirname(__DIR__) . '/app/Core/App.php';

\App\Core\App::run(dirname(__DIR__));
