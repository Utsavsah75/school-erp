<?php

namespace App\Core;

/**
 * Base Controller. Provides view rendering (with an automatic layout),
 * request-input helpers, validation, redirects, flashing, and JSON
 * responses so concrete controllers stay thin.
 */
abstract class Controller
{
    protected string $layout = 'layouts/app';

    /**
     * Render a view inside the main layout.
     * Pass $layout = null explicitly to render with NO layout (e.g. auth pages).
     * Omit $layout entirely to use the controller's default layout.
     * @param array<string,mixed> $data
     */
    protected function view(string $view, array $data = [], string|false|null $layout = false): void
    {
        $layout = $layout === false ? $this->layout : $layout;
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Capture the view's HTML, then hand it to the layout as $content.
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null || $layout === '') {
            echo $content;
            return;
        }

        $layoutFile = dirname(__DIR__) . '/Views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }
        require $layoutFile;
    }

    /** Render a view with no layout wrapper (partials, print pages, PDFs-to-HTML, etc). */
    protected function viewRaw(string $view, array $data = []): void
    {
        $this->view($view, $data, null);
    }

    protected function redirect(string $url): void
    {
        redirect($url);
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        redirect($ref);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ------------------------------------------------------------------
    // Request input helpers
    // ------------------------------------------------------------------

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    protected function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    /**
     * Validate $this->all() (or a supplied dataset) against $rules.
     * On failure: flashes errors + old input and redirects back, then exits.
     */
    protected function validate(array $rules, array $niceNames = [], ?array $data = null): array
    {
        $data ??= $this->all();
        $validator = new Validator($data, $rules, $niceNames);

        if ($validator->fails()) {
            Session::setErrors($validator->errors());
            Session::setOldInput($data);
            Session::flash('error', 'Please fix the errors below and try again.');
            $this->back();
            exit;
        }

        return $data;
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    // ------------------------------------------------------------------
    // Pagination helper
    // ------------------------------------------------------------------

    protected function currentPage(): int
    {
        $page = (int) ($_GET['page'] ?? 1);
        return max(1, $page);
    }

    protected function authorizeModule(string $module): void
    {
        Auth::authorize($module);
    }
}
