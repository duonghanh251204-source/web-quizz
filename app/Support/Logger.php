<?php

declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public function __construct(private string $logFile)
    {
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $payload = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line = "[{$timestamp}] {$level}: {$message}{$payload}" . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
