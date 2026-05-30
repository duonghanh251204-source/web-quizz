<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class LeaderboardController extends Controller
{
    public function redirectGone(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $this->redirect('/quizzes');
    }
}

