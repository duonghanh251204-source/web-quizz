<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->redirectIfAuthenticated();
        $this->render('auth/login', [], null);
    }

    public function login(Request $request): void
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/login');

            return;
        }

        $validator = (new Validator())
            ->required('email', $request->input('email'), 'Vui lòng nhập địa chỉ thư điện tử.')
            ->required('password', $request->input('password'), 'Vui lòng nhập mật khẩu.');

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->errors()));
            $this->redirect('/login');

            return;
        }

        $result = $this->container->get(AuthService::class)->login(
            email: trim((string) $request->input('email')),
            password: (string) $request->input('password')
        );

        if (!$result['success']) {
            $this->flash('error', $result['message']);
            $this->redirect('/login');

            return;
        }

        $this->flash('success', $result['message']);
        $this->redirect($this->roleHomePath());
    }

    public function showRegister(Request $request): void
    {
        $this->redirectIfAuthenticated();
        $this->render('auth/register', [], null);
    }

    public function register(Request $request): void
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/register');

            return;
        }

        $validator = (new Validator())
            ->required('name', $request->input('name'), 'Vui lòng nhập họ tên.')
            ->required('email', $request->input('email'), 'Vui lòng nhập địa chỉ thư điện tử.')
            ->required('password', $request->input('password'), 'Vui lòng nhập mật khẩu.');

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->errors()));
            $this->redirect('/register');

            return;
        }

        $password = (string) $request->input('password');
        if (strlen($password) < 6) {
            $this->flash('error', 'Mật khẩu tối thiểu 6 ký tự.');
            $this->redirect('/register');

            return;
        }

        $result = $this->container->get(AuthService::class)->register(
            name: trim((string) $request->input('name')),
            email: trim((string) $request->input('email')),
            password: $password
        );

        if (!$result['success']) {
            $this->flash('error', $result['message']);
            $this->redirect('/register');

            return;
        }

        $this->flash('success', $result['message']);
        $this->redirect($this->roleHomePath());
    }

    public function logout(Request $request): void
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect($this->roleHomePath());

            return;
        }

        $this->container->get(AuthService::class)->logout();
        $this->redirect('/');
    }
}
