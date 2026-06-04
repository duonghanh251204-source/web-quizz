<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private string $sessionPath;

    public function __construct()
    {
        $this->sessionPath = dirname(__DIR__, 2) . '/storage/sessions';
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!is_dir($this->sessionPath)) {
                @mkdir($this->sessionPath, 0775, true);
            }

            // Chỉ đổi đường dẫn nếu thư mục tồn tại và có quyền ghi.
            // Nếu không, PHP sẽ tự dùng thư mục tạm (tmp) mặc định của server.
            if (is_dir($this->sessionPath) && is_writable($this->sessionPath)) {
                session_save_path($this->sessionPath);
            }
            
            @session_start();
        }
    }

    public function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public function getFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public function isValidCsrf(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION['_csrf'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
