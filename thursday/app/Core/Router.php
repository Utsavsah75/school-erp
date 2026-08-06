<?php

namespace App\Core;

/**
 * Minimal but complete MVC router.
 *
 * Routes are registered as:
 *   $router->get('/students', [StudentController::class, 'index']);
 *   $router->get('/students/{id}', [StudentController::class, 'show']);
 *   $router->post('/students', [StudentController::class, 'store'], ['auth', 'role:super_admin,principal']);
 *
 * Dynamic segments ({id}) are passed as ordered arguments to the action.
 */
class Router
{
    /** @var array<string, array<int, array{pattern:string, regex:string, action:mixed, middleware:array}>> */
    private array $routes = [
        'GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => [],
    ];

    public function get(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    public function put(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $action, $middleware);
    }

    public function patch(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add('PATCH', $pattern, $action, $middleware);
    }

    public function delete(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $action, $middleware);
    }

    /** Registers GET+POST for both viewing and submitting a form (rarely needed, but handy). */
    public function any(array $methods, string $pattern, callable|array $action, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->add(strtoupper($method), $pattern, $action, $middleware);
        }
    }

    private function add(string $method, string $pattern, callable|array $action, array $middleware): void
    {
        $pattern = '/' . trim($pattern, '/');
        $regex = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[$method][] = [
            'pattern'    => $pattern,
            'regex'      => $regex,
            'action'     => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $method = strtoupper($method);
        // HTML forms only support GET/POST — allow method spoofing via _method.
        if ($method === 'POST' && !empty($_POST['_method'])) {
            $spoof = strtoupper($_POST['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoof;
            }
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = base_path();
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/'; // keep root as-is
        }

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);

                foreach ($route['middleware'] as $mw) {
                    if (!$this->runMiddleware($mw)) {
                        return; // middleware handled the response (redirect/abort)
                    }
                }

                $this->callAction($route['action'], $matches);
                return;
            }
        }

        // No route matched.
        http_response_code(404);
        require dirname(__DIR__) . '/Views/errors/404.php';
    }

    private function runMiddleware(string $mw): bool
    {
        if ($mw === 'auth') {
            return (new AuthMiddleware())->handle();
        }
        if ($mw === 'guest') {
            return (new GuestMiddleware())->handle();
        }
        if ($mw === 'csrf') {
            return (new CsrfMiddleware())->handle();
        }
        if (str_starts_with($mw, 'role:')) {
            $roles = explode(',', substr($mw, 5));
            return (new RoleMiddleware($roles))->handle();
        }
        if (str_starts_with($mw, 'throttle:')) {
            // 'throttle:bucket,max,windowSecs,blockMins[,identifierPostField]'
            $parts = explode(',', substr($mw, 9));
            $bucket = $parts[0] ?? 'default';
            $max = (int) ($parts[1] ?? 5);
            $window = (int) ($parts[2] ?? 60);
            $block = (int) ($parts[3] ?? 15);
            $field = $parts[4] ?? null;
            return (new RateLimitMiddleware($bucket, $max, $window, $block, $field))->handle();
        }
        return true;
    }

    private function callAction(callable|array $action, array $params): void
    {
        try {
            if (is_array($action)) {
                [$class, $methodName] = $action;
                $controller = new $class();
                $controller->$methodName(...$params);
                return;
            }
            $action(...$params);
        } catch (\Throwable $e) {
            error_log('[ROUTER ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            $debug = (require dirname(__DIR__, 2) . '/config/app.php')['debug'];
            if ($debug) {
                echo '<pre style="padding:20px;background:#1e1e1e;color:#f66;">';
                echo htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString());
                echo '</pre>';
            } else {
                require dirname(__DIR__) . '/Views/errors/500.php';
            }
        }
    }
}
