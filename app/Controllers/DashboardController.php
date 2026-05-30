<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PlatformRepositoryInterface;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $documents = $repo->listDocuments();
        $quizzes = $repo->listQuizzes();
        $submissions = $repo->listSubmissions();

        $this->render('dashboard/index', [
            'documentsCount' => count($documents),
            'quizzesCount' => count($quizzes),
            'submissionsCount' => count($submissions),
            'isAdmin' => true,
        ]);
    }
}
