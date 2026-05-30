<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;
use App\Support\Container;

abstract class Controller
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layout/main'): void
    {
        $session = $this->container->get(Session::class);
        $response = $this->container->get(Response::class);
        $viewEngine = $this->container->get(View::class);
        $authService = $this->container->get(AuthService::class);

        $data['_flash_success'] = $session->getFlash('success');
        $data['_flash_error'] = $session->getFlash('error');
        $data['_csrf_token'] = $session->csrfToken();
        $data['_current_user'] = $authService->user();

        $response->html($viewEngine->render($view, $data, $layout));
    }

    protected function redirect(string $path): void
    {
        $this->container->get(Response::class)->redirect($path);
    }

    protected function flash(string $key, string $message): void
    {
        $this->container->get(Session::class)->flash($key, $message);
    }

    protected function verifyCsrfToken(Request $request): bool
    {
        $token = $request->input('_csrf_token');

        return $this->container->get(Session::class)->isValidCsrf(is_string($token) ? $token : null);
    }

    /** @return array<string, mixed>|null */
    protected function currentUser(): ?array
    {
        return $this->container->get(AuthService::class)->user();
    }

    /**
     * @param array<int, string> $roles
     * @return array<string, mixed>|null
     */
    protected function requireAuth(array $roles = []): ?array
    {
        $user = $this->currentUser();

        if ($user === null) {
            $this->flash('error', 'Vui lòng đăng nhập để tiếp tục.');
            $this->redirect('/login');

            return null;
        }

        if ($roles !== [] && !in_array((string) ($user['role'] ?? ''), $roles, true)) {
            $this->flash('error', 'Bạn không có quyền truy cập chức năng này.');
            $this->redirect($this->roleHomePath($user));

            return null;
        }

        return $user;
    }

    protected function redirectIfAuthenticated(?string $path = null): void
    {
        $user = $this->currentUser();
        if ($user !== null) {
            $this->redirect($path ?? $this->roleHomePath($user));
        }
    }

    /** @param array<string, mixed>|null $user */
    protected function roleHomePath(?array $user = null): string
    {
        if ($user === null) {
            $user = $this->currentUser();
        }

        if ($user !== null && strtolower((string) ($user['role'] ?? '')) === 'admin') {
            return '/admin/dashboard';
        }

        return '/quizzes';
    }
}
