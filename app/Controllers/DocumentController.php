<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Repositories\PlatformRepositoryInterface;
use App\Services\DocumentTextExtractorService;
use App\Services\QuizGenerationService;
use App\Support\Logger;

final class DocumentController extends Controller
{
    private const DRAFT_SESSION_KEY = 'quiz_generation_draft';

    public function index(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $documents = $repo->listDocuments($isAdmin ? null : (int) $user['id']);

        $this->render('documents/index', [
            'documents' => $documents,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $this->render('documents/create');
    }

    public function store(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            $this->redirect('/documents/create');
            return;
        }

        $mode = strtolower(trim((string) $request->input('upload_mode', 'ai')));
        if ($mode !== 'ai') {
            $this->flash('error', 'Chế độ không hợp lệ.');
            $this->redirect('/documents/create');
            return;
        }

        $this->handleGenerateWithAI($request, $user);
    }

    /** @param array<string,mixed> $user */
    private function handleGenerateWithAI(Request $request, array $user): void
    {
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            $this->flash('error', 'Vui lòng nhập tiêu đề tài liệu kiến thức.');
            $this->redirect('/documents/create');
            return;
        }

        $questionCount = (int) $request->input('question_count', 10);
        $questionCount = max(1, min(50, $questionCount));

        $difficulty = strtolower(trim((string) $request->input('difficulty', 'medium')));
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }

        $language = strtolower(trim((string) $request->input('language', 'vi')));
        if (!in_array($language, ['vi', 'en'], true)) {
            $language = 'vi';
        }

        $prepared = $this->prepareUploadedDocument(
            title: $title,
            maxBytes: 15 * 1024 * 1024,
            allowedExtensions: ['pdf', 'docx', 'txt']
        );

        if ($prepared === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $documentId = $repo->createDocument(
            userId: (int) $user['id'],
            title: $prepared['title'],
            originalFileName: $prepared['original_file_name'],
            storedFilePath: $prepared['stored_file_path'],
            mimeType: $prepared['mime_type'],
            extractedContent: $prepared['extracted_content']
        );

        $generationService = $this->container->get(QuizGenerationService::class);
        $logger = $this->container->get(Logger::class);

        try {
            $languageLabel = $language === 'en' ? 'English' : 'Tiếng Việt';
            $generated = $generationService->generateAiSuggestions(
                documentTitle: $prepared['title'] . ' (' . $languageLabel . ')',
                documentContent: $this->buildLanguageAwareContent($prepared['extracted_content'], $language),
                questionCount: $questionCount,
                difficulty: $difficulty
            );

            $draft = [
                'document_id' => $documentId,
                'document_title' => $prepared['title'],
                'title' => trim($generated['title'] ?? '') !== '' ? (string) $generated['title'] : 'Bai kiem tra AI tu ' . $prepared['title'],
                'document_content' => $prepared['extracted_content'],
                'questions' => $generated['questions'],
                'suggested_questions' => [],
                'generated_at' => date('Y-m-d H:i:s'),
                'generation_source' => 'ai',
                'ai_suggested_at' => date('Y-m-d H:i:s'),
                'ai_suggestion_count' => $questionCount,
            ];

            $this->container->get(Session::class)->put(self::DRAFT_SESSION_KEY, $draft);

            $this->flash('success', 'AI đã soạn đề xong. Bạn có thể rà soát và chỉnh sửa tại trang xem trước.');
            $this->redirect('/quizzes/preview');
        } catch (\Throwable $throwable) {
            $logger->error('Tao de AI that bai', ['message' => $throwable->getMessage()]);
            $this->flash('error', 'Không thể tạo đề bằng AI: ' . $throwable->getMessage());
            $this->redirect('/documents/create');
        }
    }

    /**
     * @param array<int,string> $allowedExtensions
     * @return array<string,mixed>|null
     */
    private function prepareUploadedDocument(
        string $title,
        int $maxBytes,
        array $allowedExtensions
    ): ?array {
        if (!isset($_FILES['document_file']) || !is_array($_FILES['document_file'])) {
            $this->flash('error', 'Vui lòng chọn tệp.');
            $this->redirect('/documents/create');
            return null;
        }

        $file = $_FILES['document_file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Tải tệp lên thất bại.');
            $this->redirect('/documents/create');
            return null;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $this->flash('error', 'Định dạng không hợp lệ. Hệ thống chỉ hỗ trợ: ' . strtoupper(implode(', ', $allowedExtensions)) . '.');
            $this->redirect('/documents/create');
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $this->flash('error', 'Kích thước tệp không hợp lệ.');
            $this->redirect('/documents/create');
            return null;
        }

        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/storage/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $this->flash('error', 'Không thể tạo thư mục tải lên.');
            $this->redirect('/documents/create');
            return null;
        }

        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            $suffix = uniqid('', true);
        }

        $storedName = date('YmdHis') . '_' . $suffix . '.' . $extension;
        $absolutePath = $uploadDir . '/' . $storedName;
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName) || !move_uploaded_file($tmpName, $absolutePath)) {
            $this->flash('error', 'Không thể lưu tệp đã tải lên.');
            $this->redirect('/documents/create');
            return null;
        }

        $extractor = $this->container->get(DocumentTextExtractorService::class);
        try {
            $extracted = $extractor->extract($absolutePath, $extension);
        } catch (\Throwable $throwable) {
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
            $this->flash('error', 'Không thể trích xuất nội dung từ tài liệu: ' . $throwable->getMessage());
            $this->redirect('/documents/create');
            return null;
        }

        return [
            'title' => $title,
            'original_file_name' => $originalName,
            'stored_file_path' => 'storage/uploads/' . $storedName,
            'mime_type' => (string) ($file['type'] ?? 'application/octet-stream'),
            'extracted_content' => $extracted,
        ];
    }

    private function buildLanguageAwareContent(string $content, string $language): string
    {
        $instruction = $language === 'en'
            ? "[LANGUAGE_REQUIREMENT]\nAll generated questions and options must be in English."
            : "[LANGUAGE_REQUIREMENT]\nTất cả câu hỏi và đáp án phải dùng tiếng Việt.";

        return $instruction . "\n\n" . $content;
    }

    public function show(Request $request): void
    {
        $user = $this->requireAuth();
        if ($user === null) {
            return;
        }

        $documentId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $document = $repo->findDocumentById($documentId);

        if ($document === null) {
            $this->flash('error', 'Không tìm thấy tài liệu.');
            $this->redirect('/documents');
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        if (!$isAdmin && (int) ($document['user_id'] ?? 0) !== (int) $user['id']) {
            $this->flash('error', 'Bạn không có quyền truy cập tài liệu này.');
            $this->redirect('/documents');
            return;
        }

        $this->render('documents/show', [
            'document' => $document,
            'preview' => mb_substr((string) $document['extracted_content'], 0, 2500, 'UTF-8'),
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
            $this->redirect('/documents');
            return;
        }

        $documentId = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $document = $repo->findDocumentById($documentId);
        if ($document === null) {
            $this->flash('error', 'Không tìm thấy tài liệu.');
            $this->redirect('/documents');
            return;
        }

        $isAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
        if (!$isAdmin && (int) ($document['user_id'] ?? 0) !== (int) $user['id']) {
            $this->flash('error', 'Bạn không có quyền xóa tài liệu này.');
            $this->redirect('/documents');
            return;
        }

        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) ($document['stored_file_path'] ?? ''));
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $repo->deleteDocument($documentId);
        $this->flash('success', 'Đã xóa tài liệu đã chọn.');
        $this->redirect('/documents');
    }
}
