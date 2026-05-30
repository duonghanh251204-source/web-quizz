<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $viewName, array $data = [], ?string $layout = 'layout/main'): string
    {
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $viewName) . '.php';
        if (!is_file($viewPath)) {
            throw new RuntimeException("View không tồn tại: {$viewName}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $this->prefixRootPaths($content);
        }

        $layoutPath = __DIR__ . '/../Views/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutPath)) {
            throw new RuntimeException("Layout không tồn tại: {$layout}");
        }

        ob_start();
        require $layoutPath;
        $html = (string) ob_get_clean();

        return $this->prefixRootPaths($html);
    }

    private function prefixRootPaths(string $html): string
    {
        $basePath = $this->basePath();
        if ($basePath === '') {
            return $html;
        }

        return (string) preg_replace_callback(
            '/\b(href|src|action)=(["\'])\/(?!\/)([^"\']*)\2/i',
            static function (array $matches) use ($basePath): string {
                $attribute = $matches[1];
                $quote = $matches[2];
                $path = $matches[3];
                $fullPath = rtrim($basePath, '/') . '/' . ltrim($path, '/');

                return sprintf('%s=%s%s%s', $attribute, $quote, $fullPath, $quote);
            },
            $html
        ) ?? $html;
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
