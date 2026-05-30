<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, mixed> $routeParams */
    public function __construct(private array $routeParams = [])
    {
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = $this->basePath();

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '') {
                $uri = '/';
            }
        }

        return $uri;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    private function basePath(): string
    {
        $appUrl = (string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '');
        $path = parse_url($appUrl, PHP_URL_PATH);

        if (!is_string($path)) {
            return '';
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '' : $path;
    }
}
