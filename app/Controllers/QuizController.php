<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\PlatformRepositoryInterface;
use App\Services\QuizDocxExportService;
use App\Services\QuizGenerationService;
use App\Services\SubmissionEvaluationService;
use App\Support\Logger;
use App\Support\QuizRichContent;

final class QuizController extends Controller
{
    private const DRAFT_SESSION_KEY = 'quiz_generation_draft';
    private const DEFAULT_QUIZ_DIFFICULTY = 'medium';

    public function index(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quizzes = array_values(array_filter(
            $repo->listQuizzes(),
            static fn (array $quiz): bool => (int) ($quiz['created_by'] ?? 0) === (int) $user['id']
        ));

        $this->render('quizzes/index', [
            'quizzes' => $quizzes,
            'isAdmin' => $user['role'] === 'admin',
            'currentUserId' => (int) $user['id'],
        ]);
    }

    public function create(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $this->render('quizzes/create', [
            'hasDraft' => $this->getDraft() !== null,
        ]);
    }

    public function store(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/quizzes/create');
            return;
        }

        $validator = (new Validator())
            ->required('title', $request->input('title'), 'Vui long nhap tieu de quiz.');

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->errors()));
            $this->redirect('/quizzes/create');
            return;
        }

        $rawContent = trim((string) $request->input('raw_content'));
        if ($rawContent === '') {
            $this->flash('error', 'Vui lòng nhập nội dung đề trắc nghiệm.');
            $this->redirect('/quizzes/create');
            return;
        }

        $generationService = $this->container->get(QuizGenerationService::class);
        $logger = $this->container->get(Logger::class);
        $title = trim((string) $request->input('title'));

        try {
            $generated = $generationService->extractQuestionsFromDocument(
                documentTitle: $title,
                documentContent: $rawContent
            );

            $draft = [
                'document_id' => 0,
                'document_title' => $title,
                'title' => $title,
                'document_content' => $rawContent,
                'questions' => $generated['questions'],
                'suggested_questions' => [],
                'generated_at' => date('Y-m-d H:i:s'),
                'generation_source' => 'paste',
                'ai_suggested_at' => '',
            ];

            $this->saveDraft($draft);

            $this->redirect('/quizzes/preview');
        } catch (\Throwable $throwable) {
            $logger->error('Tạo đề thủ công thất bại', ['message' => $throwable->getMessage()]);
            $this->flash('error', 'Không thể tạo đề thủ công: ' . $throwable->getMessage());
            $this->redirect('/quizzes/create');
        }
    }

    public function preview(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $draft = $this->getDraft();
        if ($draft === null) {
            $this->flash('error', 'Không có dữ liệu xem trước. Hãy tạo bộ câu hỏi trước.');
            $this->redirect('/quizzes/create');
            return;
        }

        $questions = is_array($draft['questions'] ?? null) ? $draft['questions'] : [];
        $suggestedQuestions = is_array($draft['suggested_questions'] ?? null) ? $draft['suggested_questions'] : [];
        $selectedSuggestionIndexes = $this->filterSuggestionIndexes(
            $this->normalizeSuggestionIndexes($draft['selected_suggestions'] ?? []),
            $suggestedQuestions
        );

        $this->render('quizzes/preview', [
            'draft' => $draft,
            'questions' => $questions,
            'suggestedQuestions' => $suggestedQuestions,
            'selectedSuggestionIndexes' => $selectedSuggestionIndexes,
        ]);
    }

    public function suggestAiPreview(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/quizzes/preview');
            return;
        }

        $draft = $this->getDraft();
        if ($draft === null) {
            $this->flash('error', 'Không có dữ liệu xem trước để gợi ý.');
            $this->redirect('/quizzes/create');
            return;
        }

        $title = trim((string) $request->input('title', (string) ($draft['title'] ?? '')));

        $rawQuestions = $this->resolveSubmittedQuestions(
            $request,
            is_array($draft['questions'] ?? null) ? $draft['questions'] : []
        );
        $normalized = $this->normalizePreviewQuestions($rawQuestions);
        if ($normalized['errors'] !== []) {
            $this->flash('error', implode(' ', $normalized['errors']));
            $this->redirect('/quizzes/preview');
            return;
        }

        $suggestionCount = (int) $request->input('suggestion_count', 5);
        $selectedSuggestionIndexes = $this->normalizeSuggestionIndexes($request->input('include_suggestions', []));
        $generationService = $this->container->get(QuizGenerationService::class);
        $logger = $this->container->get(Logger::class);

        try {
            $generated = $generationService->generateAiSuggestions(
                documentTitle: (string) ($draft['document_title'] ?? ''),
                documentContent: (string) ($draft['document_content'] ?? ''),
                questionCount: $suggestionCount,
                difficulty: self::DEFAULT_QUIZ_DIFFICULTY
            );

            $existingSuggested = is_array($draft['suggested_questions'] ?? null) ? $draft['suggested_questions'] : [];
            $mergedSuggestionResult = $this->mergeSuggestionQuestions(
                $normalized['questions'],
                $existingSuggested,
                $generated['questions']
            );

            $draft['title'] = $title;
            $draft['questions'] = $normalized['questions'];
            $draft['suggested_questions'] = $mergedSuggestionResult['suggestions'];
            $draft['selected_suggestions'] = $this->filterSuggestionIndexes(
                $selectedSuggestionIndexes,
                $draft['suggested_questions']
            );
            $draft['ai_suggested_at'] = date('Y-m-d H:i:s');
            $draft['ai_suggestion_count'] = max(1, min($suggestionCount, 10));

            $this->saveDraft($draft);

            if ($mergedSuggestionResult['added_count'] > 0) {
                $this->flash('success', 'Da them ' . $mergedSuggestionResult['added_count'] . ' cau hoi goi y bang AI. Hay tick cac cau muon them vao de.');
            } else {
                $this->flash('success', 'AI da xu ly xong, nhung khong co cau moi do trung voi danh sach hien tai.');
            }
        } catch (\Throwable $throwable) {
            $logger->error('Gợi ý câu hỏi AI thất bại', ['message' => $throwable->getMessage()]);
            $this->flash('error', 'Không thể gợi ý câu hỏi bằng AI: ' . $throwable->getMessage());
        }

        $this->redirect('/quizzes/preview');
    }

    public function savePreview(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/quizzes/preview');
            return;
        }

        $draft = $this->getDraft();
        if ($draft === null) {
            $this->flash('error', 'Không có dữ liệu xem trước để lưu.');
            $this->redirect('/quizzes/create');
            return;
        }

        $title = trim((string) $request->input('title', (string) ($draft['title'] ?? '')));
        $rawQuestions = $this->resolveSubmittedQuestions(
            $request,
            is_array($draft['questions'] ?? null) ? $draft['questions'] : []
        );
        $normalized = $this->normalizePreviewQuestions($rawQuestions);

        if ($title === '') {
            $this->flash('error', 'Vui lòng nhập tiêu đề bài kiểm tra.');
            $this->redirect('/quizzes/preview');
            return;
        }

        if ($normalized['errors'] !== []) {
            $this->flash('error', implode(' ', $normalized['errors']));
            $this->redirect('/quizzes/preview');
            return;
        }

        $selectedSuggestionIndexes = $this->normalizeSuggestionIndexes($request->input('include_suggestions', []));
        $suggestedQuestions = is_array($draft['suggested_questions'] ?? null) ? $draft['suggested_questions'] : [];
        $selectedSuggestions = $this->pickSuggestedQuestions($suggestedQuestions, $selectedSuggestionIndexes);
        $finalQuestions = $this->mergePreviewQuestions($normalized['questions'], $selectedSuggestions);

        if ($finalQuestions === []) {
            $this->flash('error', 'Không có câu hỏi hợp lệ để lưu bài kiểm tra.');
            $this->redirect('/quizzes/preview');
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quizId = $repo->createQuiz(
            documentId: (int) $draft['document_id'],
            createdBy: (int) $user['id'],
            title: $title,
            difficulty: self::DEFAULT_QUIZ_DIFFICULTY,
            questions: $finalQuestions
        );

        $this->clearDraft();
        $this->flash('success', 'Đã lưu bài kiểm tra từ bản xem trước.');
        $this->redirect('/quizzes/' . $quizId);
    }

    public function discardPreview(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/quizzes/create');
            return;
        }

        $this->clearDraft();
        $this->flash('success', 'Đã hủy dữ liệu xem trước.');
        $this->redirect('/quizzes/create');
    }

    public function show(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $quizId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quiz = $repo->findQuizById($quizId);

        if ($quiz === null) {
            $this->flash('error', 'Không tìm thấy bài kiểm tra.');
            $this->redirect('/quizzes');
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        $isCreator = (int) ($quiz['created_by'] ?? 0) === (int) $user['id'];
        if (!$isAdmin && !$isCreator) {
            $this->flash('error', 'Bạn không có quyền xem trang quản lý bài kiểm tra này.');
            $this->redirect('/quizzes/' . $quizId . '/take');
            return;
        }

        $questions = $repo->findQuestionsByQuizId($quizId);

        $this->render('quizzes/show', [
            'quiz' => $quiz,
            'questions' => $questions,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function delete(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/quizzes');
            return;
        }

        $quizId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quiz = $repo->findQuizById($quizId);
        if ($quiz === null) {
            $this->flash('error', 'Không tìm thấy bài kiểm tra.');
            $this->redirect('/quizzes');
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        if (!$isAdmin && (int) ($quiz['created_by'] ?? 0) !== (int) $user['id']) {
            $this->flash('error', 'Bạn không có quyền xóa bài kiểm tra này.');
            $this->redirect('/quizzes');
            return;
        }

        $repo->deleteQuiz($quizId);
        $this->flash('success', 'Đã xóa bài kiểm tra đã chọn.');
        $this->redirect('/quizzes');
    }

    public function take(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $quizId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quiz = $repo->findQuizById($quizId);

        if ($quiz === null) {
            $this->flash('error', 'Không tìm thấy bài kiểm tra.');
            $this->redirect('/quizzes');
            return;
        }

        $questions = $repo->findQuestionsByQuizId($quizId);

        $this->render('quizzes/take', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    public function submit(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phien lam viec khong hop le.');
            $this->redirect('/quizzes');
            return;
        }

        $quizId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quiz = $repo->findQuizById($quizId);
        if ($quiz === null) {
            $this->flash('error', 'Bài kiểm tra không tồn tại.');
            $this->redirect('/quizzes');
            return;
        }

        $questions = $repo->findQuestionsByQuizId($quizId);

        /** @var array<int, string> $submitted */
        $submitted = [];
        $rawAnswers = $request->input('answers', []);
        if (is_array($rawAnswers)) {
            foreach ($rawAnswers as $questionId => $answer) {
                $submitted[(int) $questionId] = strtoupper((string) $answer);
            }
        }

        $evaluation = $this->container->get(SubmissionEvaluationService::class)->evaluate($questions, $submitted);
        $submissionId = $repo->createSubmission(
            quizId: $quizId,
            userId: (int) $user['id'],
            score: $evaluation['score'],
            totalQuestions: $evaluation['total_questions'],
            totalCorrect: $evaluation['total_correct'],
            answerRows: $evaluation['answer_rows']
        );

        $this->flash('success', 'Nop bai thanh cong.');
        $this->redirect('/submissions/' . $submissionId);
    }

    public function export(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $quizId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $quiz = $repo->findQuizById($quizId);

        if ($quiz === null) {
            $this->flash('error', 'Không tìm thấy bài kiểm tra.');
            $this->redirect('/quizzes');
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        $isCreator = (int) ($quiz['created_by'] ?? 0) === (int) $user['id'];
        if (!$isAdmin && !$isCreator) {
            $this->flash('error', 'Bạn không có quyền xuất bài kiểm tra này.');
            $this->redirect('/quizzes');
            return;
        }

        $questions = $repo->findQuestionsByQuizId($quizId);
        $withAnswers = ((string) $request->input('with_answers', '1')) === '1';

        try {
            $binary = (new QuizDocxExportService())->build($quiz, $questions, $withAnswers);
        } catch (\Throwable $throwable) {
            $this->container->get(Logger::class)->error('Xuất DOCX thất bại', ['message' => $throwable->getMessage()]);
            $this->flash('error', 'Không xuất được file Word: ' . $throwable->getMessage());
            $this->redirect('/quizzes');
            return;
        }

        $filename = 'bai_kiem_tra_' . $quizId . '_' . date('Ymd_His') . '.docx';
        $this->container->get(Response::class)->download(
            $binary,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    /** @param mixed $rawQuestions */
    private function normalizePreviewQuestions(mixed $rawQuestions): array
    {
        if (!is_array($rawQuestions)) {
            return [
                'questions' => [],
                'errors' => ['Du lieu cau hoi khong hop le.'],
            ];
        }

        $questions = [];
        $errors = [];

        foreach ($rawQuestions as $index => $rawQuestion) {
            if (!is_array($rawQuestion)) {
                continue;
            }

            $number = (int) $index + 1;
            $questionContent = QuizRichContent::sanitizeForStorage(trim((string) ($rawQuestion['question_content'] ?? '')));
            $answers = is_array($rawQuestion['answers'] ?? null) ? $rawQuestion['answers'] : [];
            $answerA = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['A'] ?? '')));
            $answerB = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['B'] ?? '')));
            $answerC = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['C'] ?? '')));
            $answerD = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['D'] ?? '')));
            $correctAnswer = strtoupper(trim((string) ($rawQuestion['correct_answer'] ?? '')));

            if ($questionContent === '') {
                $errors[] = "Cau {$number}: noi dung cau hoi khong duoc rong.";
                continue;
            }

            if ($answerA === '' || $answerB === '' || $answerC === '' || $answerD === '') {
                $errors[] = "Cau {$number}: tat ca dap an A/B/C/D phai duoc nhap.";
                continue;
            }

            if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = "Cau {$number}: dap an dung khong hop le.";
                continue;
            }

            $optionFingerprints = [
                mb_strtolower($answerA, 'UTF-8'),
                mb_strtolower($answerB, 'UTF-8'),
                mb_strtolower($answerC, 'UTF-8'),
                mb_strtolower($answerD, 'UTF-8'),
            ];

            if (count(array_unique($optionFingerprints)) < 4) {
                $errors[] = "Cau {$number}: dap an bi trung lap.";
                continue;
            }

            $src = strtolower(trim((string) ($rawQuestion['source'] ?? 'extract')));
            if (!in_array($src, ['ai', 'extract', 'manual'], true)) {
                $src = 'extract';
            }

            $questions[] = [
                'question_content' => $questionContent,
                'answers' => [
                    'A' => $answerA,
                    'B' => $answerB,
                    'C' => $answerC,
                    'D' => $answerD,
                ],
                'correct_answer' => $correctAnswer,
                'source' => $src,
            ];
        }

        if ($questions === [] && $errors === []) {
            $errors[] = 'Không có câu hỏi để lưu bài kiểm tra.';
        }

        return [
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve questions submitted from preview form.
     * Supports compact JSON payload to avoid PHP max_input_vars truncation.
     *
     * @param array<int, mixed> $fallbackQuestions
     */
    private function resolveSubmittedQuestions(Request $request, array $fallbackQuestions): mixed
    {
        $payload = trim((string) $request->input('questions_payload', ''));
        if ($payload !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->input('questions', $fallbackQuestions);
    }

    /** @return array<int, int> */
    private function normalizeSuggestionIndexes(mixed $rawIndexes): array
    {
        if (!is_array($rawIndexes)) {
            return [];
        }

        $result = [];

        foreach ($rawIndexes as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $index = (int) $value;
            if ($index < 0) {
                continue;
            }

            $result[$index] = $index;
        }

        return array_values($result);
    }

    /**
     * @param array<int, int> $indexes
     * @param array<int, mixed> $suggestions
     * @return array<int, int>
     */
    private function filterSuggestionIndexes(array $indexes, array $suggestions): array
    {
        $result = [];

        foreach ($indexes as $index) {
            if (!array_key_exists($index, $suggestions)) {
                continue;
            }

            $result[] = $index;
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $suggestedQuestions
     * @param array<int, int> $selectedIndexes
     * @return array<int, array<string, mixed>>
     */
    private function pickSuggestedQuestions(array $suggestedQuestions, array $selectedIndexes): array
    {
        $selected = [];

        foreach ($selectedIndexes as $index) {
            $question = $suggestedQuestions[$index] ?? null;
            if (!is_array($question)) {
                continue;
            }

            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $selected[] = $normalizedQuestion;
        }

        return $selected;
    }

    /**
     * @param array<int, array<string, mixed>> $baseQuestions
     * @param array<int, array<string, mixed>> $extraQuestions
     * @return array<int, array<string, mixed>>
     */
    private function mergePreviewQuestions(array $baseQuestions, array $extraQuestions): array
    {
        $merged = [];
        $fingerprints = [];

        foreach ($baseQuestions as $question) {
            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $fingerprint = $this->fingerprintPreviewQuestion($normalizedQuestion);
            if (isset($fingerprints[$fingerprint])) {
                continue;
            }

            $fingerprints[$fingerprint] = true;
            $merged[] = $normalizedQuestion;
        }

        foreach ($extraQuestions as $question) {
            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $fingerprint = $this->fingerprintPreviewQuestion($normalizedQuestion);
            if (isset($fingerprints[$fingerprint])) {
                continue;
            }

            $fingerprints[$fingerprint] = true;
            $merged[] = $normalizedQuestion;
        }

        return $merged;
    }

    /**
     * @param array<int, array<string, mixed>> $baseQuestions
     * @param array<int, mixed> $existingSuggestions
     * @param array<int, array<string, mixed>> $incomingSuggestions
     * @return array{suggestions: array<int, array<string, mixed>>, added_count: int}
     */
    private function mergeSuggestionQuestions(array $baseQuestions, array $existingSuggestions, array $incomingSuggestions): array
    {
        $fingerprints = [];

        foreach ($baseQuestions as $question) {
            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $fingerprints[$this->fingerprintPreviewQuestion($normalizedQuestion)] = true;
        }

        $mergedSuggestions = [];
        foreach ($existingSuggestions as $question) {
            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $fingerprint = $this->fingerprintPreviewQuestion($normalizedQuestion);
            if (isset($fingerprints[$fingerprint])) {
                continue;
            }

            $fingerprints[$fingerprint] = true;
            $mergedSuggestions[] = $normalizedQuestion;
        }

        $addedCount = 0;
        foreach ($incomingSuggestions as $question) {
            $normalizedQuestion = $this->normalizePreviewQuestionForMerge($question);
            if ($normalizedQuestion === null) {
                continue;
            }

            $fingerprint = $this->fingerprintPreviewQuestion($normalizedQuestion);
            if (isset($fingerprints[$fingerprint])) {
                continue;
            }

            $fingerprints[$fingerprint] = true;
            $mergedSuggestions[] = $normalizedQuestion;
            $addedCount++;
        }

        return [
            'suggestions' => $mergedSuggestions,
            'added_count' => $addedCount,
        ];
    }

    /** @param array<string, mixed> $question */
    private function normalizePreviewQuestionForMerge(array $question): ?array
    {
        $questionContent = QuizRichContent::sanitizeForStorage(trim((string) ($question['question_content'] ?? '')));
        $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
        $answerA = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['A'] ?? '')));
        $answerB = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['B'] ?? '')));
        $answerC = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['C'] ?? '')));
        $answerD = QuizRichContent::sanitizePlainAnswerForStorage(trim((string) ($answers['D'] ?? '')));
        $correctAnswer = strtoupper(trim((string) ($question['correct_answer'] ?? '')));

        if (
            $questionContent === ''
            || $answerA === ''
            || $answerB === ''
            || $answerC === ''
            || $answerD === ''
            || !in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)
        ) {
            return null;
        }

        $src = strtolower(trim((string) ($question['source'] ?? 'extract')));
        if (!in_array($src, ['ai', 'extract', 'manual'], true)) {
            $src = 'extract';
        }

        return [
            'question_content' => $questionContent,
            'answers' => [
                'A' => $answerA,
                'B' => $answerB,
                'C' => $answerC,
                'D' => $answerD,
            ],
            'correct_answer' => $correctAnswer,
            'source' => $src,
        ];
    }

    /** @param array<string, mixed> $question */
    private function fingerprintPreviewQuestion(array $question): string
    {
        $questionContent = mb_strtolower(trim((string) ($question['question_content'] ?? '')), 'UTF-8');
        $questionContent = preg_replace('/\s+/u', ' ', $questionContent) ?? $questionContent;
        $questionContent = preg_replace('/[^\p{L}\p{N}\s]/u', '', $questionContent) ?? $questionContent;

        $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
        $optionParts = [];
        foreach (['A', 'B', 'C', 'D'] as $label) {
            $answer = mb_strtolower(trim((string) ($answers[$label] ?? '')), 'UTF-8');
            $answer = preg_replace('/\s+/u', ' ', $answer) ?? $answer;
            $optionParts[] = $label . ':' . $answer;
        }

        return md5(trim($questionContent) . '|' . implode('|', $optionParts));
    }

    /** @param array<string, mixed> $draft */
    private function saveDraft(array $draft): void
    {
        $this->container->get(Session::class)->put(self::DRAFT_SESSION_KEY, $draft);
    }

    /** @return array<string, mixed>|null */
    private function getDraft(): ?array
    {
        $draft = $this->container->get(Session::class)->get(self::DRAFT_SESSION_KEY);

        return is_array($draft) ? $draft : null;
    }

    private function clearDraft(): void
    {
        $this->container->get(Session::class)->remove(self::DRAFT_SESSION_KEY);
    }

}
