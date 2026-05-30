<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\PlatformRepositoryInterface;
use App\Support\QuizRichContent;

final class QuestionController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if ($request->uri() === '/questions') {
            $q = http_build_query($request->all());
            $this->redirect('/admin/questions' . ($q !== '' ? '?' . $q : ''));
            return;
        }

        $quizId = (int) $request->input('quiz_id', 0);
        $sourceRaw = strtolower(trim((string) $request->input('source', '')));
        $sourceFilter = in_array($sourceRaw, ['ai', 'extract'], true) ? $sourceRaw : null;
        $repo = $this->container->get(PlatformRepositoryInterface::class);

        $this->render('questions/index', [
            'questions' => $repo->listQuestions($quizId > 0 ? $quizId : null, $sourceFilter),
            'quizzes' => $repo->listQuizzes(),
            'selectedQuizId' => $quizId,
            'selectedSource' => $sourceFilter ?? '',
        ]);
    }

    public function create(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $selectedQuizId = (int) $request->input('quiz_id', 0);

        $this->render('questions/create', [
            'quizzes' => $repo->listQuizzes(),
            'selectedQuizId' => $selectedQuizId,
        ]);
    }

    public function store(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/questions/create');
            return;
        }

        $validator = (new Validator())
            ->required('quiz_id', $request->input('quiz_id'), 'Vui long chon quiz.')
            ->required('question_content', $request->input('question_content'), 'Vui long nhap noi dung cau hoi.')
            ->required('answer_a', $request->input('answer_a'), 'Vui long nhap dap an A.')
            ->required('answer_b', $request->input('answer_b'), 'Vui long nhap dap an B.')
            ->required('answer_c', $request->input('answer_c'), 'Vui long nhap dap an C.')
            ->required('answer_d', $request->input('answer_d'), 'Vui long nhap dap an D.')
            ->inArray('correct_answer', $request->input('correct_answer'), ['A', 'B', 'C', 'D'], 'Dap an dung khong hop le.');

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->errors()));
            $this->redirect('/questions/create');
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quizId = (int) $request->input('quiz_id');
        $quiz = $repo->findQuizById($quizId);

        if ($quiz === null) {
            $this->flash('error', 'Quiz khong ton tai.');
            $this->redirect('/questions/create');
            return;
        }

        $repo->createQuestion(
            quizId: $quizId,
            questionContent: QuizRichContent::sanitizeForStorage(trim((string) $request->input('question_content'))),
            answerA: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_a'))),
            answerB: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_b'))),
            answerC: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_c'))),
            answerD: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_d'))),
            correctAnswer: (string) $request->input('correct_answer')
        );

        $this->flash('success', 'Da tao cau hoi moi.');
        $this->redirect('/admin/questions?quiz_id=' . $quizId);
    }

    public function edit(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $questionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $question = $repo->findQuestionById($questionId);

        if ($question === null) {
            $this->flash('error', 'Khong tim thay cau hoi.');
            $this->redirect('/admin/questions');
            return;
        }

        $this->render('questions/edit', [
            'question' => $question,
        ]);
    }

    public function update(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/admin/questions');
            return;
        }

        $questionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $question = $repo->findQuestionById($questionId);

        if ($question === null) {
            $this->flash('error', 'Khong tim thay cau hoi.');
            $this->redirect('/admin/questions');
            return;
        }

        $validator = (new Validator())
            ->required('question_content', $request->input('question_content'), 'Vui long nhap noi dung cau hoi.')
            ->required('answer_a', $request->input('answer_a'), 'Vui long nhap dap an A.')
            ->required('answer_b', $request->input('answer_b'), 'Vui long nhap dap an B.')
            ->required('answer_c', $request->input('answer_c'), 'Vui long nhap dap an C.')
            ->required('answer_d', $request->input('answer_d'), 'Vui long nhap dap an D.')
            ->inArray('correct_answer', $request->input('correct_answer'), ['A', 'B', 'C', 'D'], 'Dap an dung khong hop le.');

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->errors()));
            $this->redirect('/questions/' . $questionId . '/edit');
            return;
        }

        $repo->updateQuestion(
            questionId: $questionId,
            questionContent: QuizRichContent::sanitizeForStorage(trim((string) $request->input('question_content'))),
            answerA: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_a'))),
            answerB: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_b'))),
            answerC: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_c'))),
            answerD: QuizRichContent::sanitizePlainAnswerForStorage(trim((string) $request->input('answer_d'))),
            correctAnswer: (string) $request->input('correct_answer')
        );

        $this->flash('success', 'Da cap nhat cau hoi.');
        $this->redirect('/admin/questions?quiz_id=' . (int) $question['quiz_id']);
    }

    public function updateCorrectAnswer(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/admin/questions');
            return;
        }

        $questionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $question = $repo->findQuestionById($questionId);

        if ($question === null) {
            $this->flash('error', 'Khong tim thay cau hoi.');
            $this->redirect('/admin/questions');
            return;
        }

        $correctAnswer = strtoupper(trim((string) $request->input('correct_answer', '')));
        if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
            $this->flash('error', 'Dap an dung khong hop le.');
            $this->redirect('/admin/questions?quiz_id=' . (int) $question['quiz_id']);
            return;
        }

        $repo->updateQuestionCorrectAnswer($questionId, $correctAnswer);

        $this->flash('success', 'Da cap nhat dap an dung.');
        $this->redirect('/admin/questions?quiz_id=' . (int) $question['quiz_id']);
    }

    public function delete(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/admin/questions');
            return;
        }

        $questionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $question = $repo->findQuestionById($questionId);

        if ($question === null) {
            $this->flash('error', 'Khong tim thay cau hoi.');
            $this->redirect('/admin/questions');
            return;
        }

        $repo->deleteQuestion($questionId);
        $this->flash('success', 'Da xoa cau hoi.');
        $this->redirect('/admin/questions?quiz_id=' . (int) $question['quiz_id']);
    }

    public function report(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect($this->safeReturnPath($request));

            return;
        }

        $questionId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $question = $repo->findQuestionById($questionId);

        if ($question === null) {
            $this->flash('error', 'Không tìm thấy câu hỏi.');
            $this->redirect($this->safeReturnPath($request));

            return;
        }

        $reason = strtolower(trim((string) $request->input('reason', 'other')));
        if (!in_array($reason, ['knowledge', 'format', 'other'], true)) {
            $reason = 'other';
        }

        $repo->createQuestionReport((int) $user['id'], $questionId, $reason);
        $this->flash('success', 'Cảm ơn bạn — chúng tôi đã ghi nhận báo cáo.');
        $this->redirect($this->safeReturnPath($request));
    }

    private function safeReturnPath(Request $request): string
    {
        $return = trim((string) $request->input('return', ''));
        if ($return !== '' && str_starts_with($return, '/') && !str_starts_with($return, '//')) {
            return $return;
        }

        return '/quizzes';
    }
}
