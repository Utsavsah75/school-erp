<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Lang;

/**
 * Switches the UI language (English / Nepali). Open to guests too —
 * the login page itself carries the same globe button — so this
 * route intentionally has no ['auth'] middleware.
 */
class LanguageController extends Controller
{
    public function set(string $code): void
    {
        Lang::set($code);
        $this->back();
    }
}
