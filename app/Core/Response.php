<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function html(string $content, int $statusCode = 200): void
    {
        $this->setSecurityHeaders();
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    /** @param array<string, mixed> $payload */
    public function json(array $payload, int $statusCode = 200): void
    {
        $this->setSecurityHeaders();
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function redirect(string $location): void
    {
        if (str_starts_with($location, '/')) {
            $basePath = $this->basePath();
            if ($basePath !== '' && !str_starts_with($location, $basePath . '/')) {
                $location = rtrim($basePath, '/') . $location;
            }
        }

        header("Location: {$location}");
        exit;
    }

    public function download(string $content, string $filename, string $contentType = 'text/plain; charset=UTF-8'): void
    {
        $this->setSecurityHeaders();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($content));
        echo $content;
        exit;
    }

    private function setSecurityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer-when-downgrade');
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
