<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class WorkspaceController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $this->redirect($this->roleHomePath($user));
    }
}
