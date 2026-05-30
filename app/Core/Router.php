<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Container;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, action: array{0: class-string, 1: string}}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(private Container $container)
    {
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function get(string $path, array $action): void
    {
        $this->register('GET', $path, $action);
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function post(string $path, array $action): void
    {
        $this->register('POST', $path, $action);
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    private function register(string $method, string $path, array $action): void
    {
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'action' => $action,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }

            [$controllerClass, $controllerMethod] = $route['action'];
            $controller = new $controllerClass($this->container);
            $actionRequest = new Request($params);
            $controller->{$controllerMethod}($actionRequest);
            return;
        }

        http_response_code(404);
        echo '404 - Không tìm thấy trang.';
    }
}
