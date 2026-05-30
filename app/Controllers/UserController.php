<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PlatformRepositoryInterface;

final class UserController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $this->redirect('/admin/dashboard');
    }
}
