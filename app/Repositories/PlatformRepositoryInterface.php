<?php

declare(strict_types=1);

namespace App\Repositories;

interface PlatformRepositoryInterface
{
    public function createUser(string $name, string $email, string $passwordHash, string $role = 'user'): int;

    /** @return array<string, mixed>|null */
    public function findUserByEmail(string $email): ?array;

    /** @return array<string, mixed>|null */
    public function findUserById(int $id): ?array;

    /** @return array<int, array<string, mixed>> */
    public function listUsers(): array;

    public function countUsersByRole(string $role): int;

    public function updateUserRole(int $userId, string $role): void;

    public function updateUserLocked(int $userId, bool $locked): void;

    public function getSetting(string $key, string $default = ''): string;

    public function setSetting(string $key, string $value): void;

    /**
     * Thống kê dashboard: tổng + phân tách AI / trích xuất (nguồn chính theo nghiệp vụ). questions_manual có thể có nhưng không hiển thị trong đặc tả 2 nguồn.
     *
     * @return array{users:int, documents:int, questions:int, questions_ai:int, questions_extract:int, questions_manual:int}
     */
    public function getAdminDashboardStats(): array;

    /**
     * @return array<int, array{day:string, count:int}>
     */
    public function getDocumentUploadCountsByDay(int $days): array;

    /**
     * Mỗi phần tử: tổng câu tạo trong ngày; đếm riêng AI nếu cần biểu đồ
     *
     * @return array<int, array{day:string, total:int, ai:int, extract:int}>
     */
    public function getQuestionActivityByDay(int $days): array;

    public function deleteDocument(int $documentId): void;

    public function createQuestionReport(int $userId, int $questionId, string $reason): int;

    /** @return array<int, array<string, mixed>> */
    public function listQuestionReports(?string $status = null): array;

    public function updateQuestionReportStatus(int $reportId, string $status, string $adminNote = ''): void;

    /** @return array<string, mixed>|null */
    public function findQuestionReportById(int $reportId): ?array;

    public function createDocument(
        int $userId,
        string $title,
        string $originalFileName,
        string $storedFilePath,
        string $mimeType,
        string $extractedContent
    ): int;

    /** @return array<int, array<string, mixed>> */
    public function listDocuments(?int $ownerUserId = null): array;

    /** @return array<string, mixed>|null */
    public function findDocumentById(int $id): ?array;

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public function createQuiz(
        int $documentId,
        int $createdBy,
        string $title,
        string $difficulty,
        array $questions
    ): int;

    /** @return array<int, array<string, mixed>> */
    public function listQuizzes(): array;

    /** @return array<string, mixed>|null */
    public function findQuizById(int $id): ?array;

    public function deleteQuiz(int $quizId): void;

    /** @return array<int, array<string, mixed>> */
    public function findQuestionsByQuizId(int $quizId): array;

    /**
     * @param 'ai'|'extract'|'manual'|null $sourceFilter
     * @return array<int, array<string, mixed>>
     */
    public function listQuestions(?int $quizId = null, ?string $sourceFilter = null): array;

    /** @return array<string, mixed>|null */
    public function findQuestionById(int $questionId): ?array;

    public function createQuestion(
        int $quizId,
        string $questionContent,
        string $answerA,
        string $answerB,
        string $answerC,
        string $answerD,
        string $correctAnswer,
        string $source = 'manual'
    ): int;

    public function updateQuestion(
        int $questionId,
        string $questionContent,
        string $answerA,
        string $answerB,
        string $answerC,
        string $answerD,
        string $correctAnswer
    ): void;

    public function updateQuestionCorrectAnswer(int $questionId, string $correctAnswer): void;

    public function deleteQuestion(int $questionId): void;

    /**
     * @param array<int, array<string, mixed>> $answerRows
     */
    public function createSubmission(
        int $quizId,
        int $userId,
        int $score,
        int $totalQuestions,
        int $totalCorrect,
        array $answerRows
    ): int;

    /** @return array<int, array<string, mixed>> */
    public function listSubmissions(?int $userId = null): array;

    /** @return array<string, mixed>|null */
    public function findSubmissionById(int $submissionId): ?array;

    /** @return array<int, array<string, mixed>> */
    public function findSubmissionAnswers(int $submissionId): array;
}
