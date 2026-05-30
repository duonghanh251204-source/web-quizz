<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\PlatformRepositoryInterface;

final class AuthService
{
    private const USER_SESSION_KEY = 'auth_user_id';

    public function __construct(
        private PlatformRepositoryInterface $repository,
        private Session $session
    ) {
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function register(string $name, string $email, string $password): array
    {
        $existing = $this->repository->findUserByEmail($email);
        if ($existing !== null) {
            return [
                'success' => false,
                'message' => 'Địa chỉ thư điện tử đã được sử dụng.',
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->repository->createUser($name, $email, $hash, 'user');
        $this->session->put(self::USER_SESSION_KEY, $userId);

        return [
            'success' => true,
            'message' => 'Đăng ký thành công.',
        ];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->repository->findUserByEmail($email);
        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác.',
            ];
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if (!password_verify($password, $hash)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác.',
            ];
        }

        if (!empty($user['is_locked'])) {
            return [
                'success' => false,
                'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ];
        }

        $this->session->put(self::USER_SESSION_KEY, (int) $user['id']);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công.',
        ];
    }

    public function logout(): void
    {
        $this->session->remove(self::USER_SESSION_KEY);
        $this->session->invalidate();
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $userId = (int) $this->session->get(self::USER_SESSION_KEY, 0);
        if ($userId <= 0) {
            return null;
        }

        $user = $this->repository->findUserById($userId);
        if ($user === null) {
            $this->session->remove(self::USER_SESSION_KEY);

            return null;
        }

        if (!empty($user['is_locked'])) {
            $this->session->remove(self::USER_SESSION_KEY);

            return null;
        }

        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }
}
