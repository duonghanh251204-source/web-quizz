<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PlatformRepositoryInterface;

final class SubmissionController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $submissions = $repo->listSubmissions($user['role'] === 'admin' ? null : (int) $user['id']);

        $this->render('submissions/index', [
            'submissions' => $submissions,
            'isAdmin' => $user['role'] === 'admin',
        ]);
    }

    public function show(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $submissionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $submission = $repo->findSubmissionById($submissionId);

        if ($submission === null) {
            $this->flash('error', 'Không tìm thấy kết quả bài làm.');
            $this->redirect('/submissions');
            return;
        }

        if ($user['role'] !== 'admin' && (int) $submission['user_id'] !== (int) $user['id']) {
            $this->flash('error', 'Bạn không có quyền xem kết quả này.');
            $this->redirect('/submissions');
            return;
        }

        $answers = $repo->findSubmissionAnswers($submissionId);

        $this->render('submissions/show', [
            'submission' => $submission,
            'answers' => $answers,
        ]);
    }
}
