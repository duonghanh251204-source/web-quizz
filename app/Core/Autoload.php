<?php

declare(strict_types=1);

namespace App\Core;

final class Autoload
{
    private const PREFIX = 'App\\';

    public static function load(string $className): void
    {
        if (!str_starts_with($className, self::PREFIX)) {
            return;
        }

        $relativeClass = substr($className, strlen(self::PREFIX));
        $file = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
}
