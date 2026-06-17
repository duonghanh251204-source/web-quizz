<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class SqlitePlatformRepository implements PlatformRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function createUser(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at)
             VALUES (:name, :email, :password_hash, :role, :created_at)'
        );

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findUserById(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listUsers(): array
    {
        $stmt = $this->connection->query(
            'SELECT id, name, email, role, is_locked, created_at
             FROM users
             ORDER BY id DESC'
        );

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function countUsersByRole(string $role): int
    {
        $stmt = $this->connection->prepare('SELECT COUNT(*) AS c FROM users WHERE role = :role');
        $stmt->execute([':role' => $role]);
        $row = $stmt->fetch();

        return (int) ($row['c'] ?? 0);
    }

    public function updateUserRole(int $userId, string $role): void
    {
        $stmt = $this->connection->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute([':role' => $role, ':id' => $userId]);
    }

    public function updateUserLocked(int $userId, bool $locked): void
    {
        $stmt = $this->connection->prepare('UPDATE users SET is_locked = :locked WHERE id = :id');
        $stmt->execute([':locked' => $locked ? 1 : 0, ':id' => $userId]);
    }

    public function getSetting(string $key, string $default = ''): string
    {
        $stmt = $this->connection->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();

        if (!is_array($row) || $row['setting_value'] === null) {
            return $default;
        }

        $v = (string) $row['setting_value'];

        return $v !== '' ? $v : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
             ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
    }

    public function getAdminDashboardStats(): array
    {
        $users = (int) $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $documents = (int) $this->connection->query('SELECT COUNT(*) FROM documents')->fetchColumn();
        $questions = (int) $this->connection->query('SELECT COUNT(*) FROM questions')->fetchColumn();
        $ai = (int) $this->connection->query("SELECT COUNT(*) FROM questions WHERE source = 'ai'")->fetchColumn();
        $extract = (int) $this->connection->query("SELECT COUNT(*) FROM questions WHERE source = 'extract'")->fetchColumn();
        $manual = (int) $this->connection->query("SELECT COUNT(*) FROM questions WHERE source = 'manual'")->fetchColumn();

        return [
            'users' => $users,
            'documents' => $documents,
            'questions' => $questions,
            'questions_ai' => $ai,
            'questions_extract' => $extract,
            'questions_manual' => $manual,
        ];
    }

    public function getDocumentUploadCountsByDay(int $days): array
    {
        $days = max(1, min($days, 90));
        $start = (new DateTimeImmutable())->modify('-' . $days . ' days')->format('Y-m-d 00:00:00');
        $stmt = $this->connection->prepare(
            'SELECT date(created_at) AS day, COUNT(*) AS c
             FROM documents
             WHERE created_at >= :start
             GROUP BY day
             ORDER BY day ASC'
        );
        $stmt->execute([':start' => $start]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'day' => (string) ($row['day'] ?? ''),
                'count' => (int) ($row['c'] ?? 0),
            ];
        }

        return $out;
    }

    public function getQuestionActivityByDay(int $days): array
    {
        $days = max(1, min($days, 90));
        $start = (new DateTimeImmutable())->modify('-' . $days . ' days')->format('Y-m-d 00:00:00');
        $stmt = $this->connection->prepare(
            'SELECT date(created_at) AS day,
                    COUNT(*) AS total,
                    SUM(CASE WHEN source = \'ai\' THEN 1 ELSE 0 END) AS ai,
                    SUM(CASE WHEN source = \'extract\' THEN 1 ELSE 0 END) AS ex
             FROM questions
             WHERE created_at >= :start
             GROUP BY day
             ORDER BY day ASC'
        );
        $stmt->execute([':start' => $start]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'day' => (string) ($row['day'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
                'ai' => (int) ($row['ai'] ?? 0),
                'extract' => (int) ($row['ex'] ?? 0),
            ];
        }

        return $out;
    }

    public function deleteDocument(int $documentId): void
    {
        $stmt = $this->connection->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute([':id' => $documentId]);
    }

    public function createQuestionReport(int $userId, int $questionId, string $reason): int
    {
        $r = strtolower(trim($reason));
        if (!in_array($r, ['knowledge', 'format', 'other'], true)) {
            $r = 'other';
        }

        $stmt = $this->connection->prepare(
            'INSERT INTO question_reports (question_id, user_id, reason, status, created_at)
             VALUES (:qid, :uid, :reason, \'open\', :created_at)'
        );
        $stmt->execute([
            ':qid' => $questionId,
            ':uid' => $userId,
            ':reason' => $r,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listQuestionReports(?string $status = null): array
    {
        if ($status !== null && $status !== '' && in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $stmt = $this->connection->prepare(
                'SELECT r.id, r.question_id, r.user_id, r.reason, r.status, r.admin_note, r.created_at,
                        q.question_content, u.name AS reporter_name, z.title AS quiz_title, z.id AS quiz_id
                 FROM question_reports r
                 INNER JOIN questions q ON q.id = r.question_id
                 INNER JOIN users u ON u.id = r.user_id
                 INNER JOIN quizzes z ON z.id = q.quiz_id
                 WHERE r.status = :st
                 ORDER BY r.id DESC'
            );
            $stmt->execute([':st' => $status]);
        } else {
            $stmt = $this->connection->query(
                'SELECT r.id, r.question_id, r.user_id, r.reason, r.status, r.admin_note, r.created_at,
                        q.question_content, u.name AS reporter_name, z.title AS quiz_title, z.id AS quiz_id
                 FROM question_reports r
                 INNER JOIN questions q ON q.id = r.question_id
                 INNER JOIN users u ON u.id = r.user_id
                 INNER JOIN quizzes z ON z.id = q.quiz_id
                 ORDER BY r.id DESC'
            );
        }

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findQuestionReportById(int $reportId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT r.id, r.question_id, r.user_id, r.reason, r.status, r.admin_note, r.created_at
             FROM question_reports r WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $reportId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function updateQuestionReportStatus(int $reportId, string $status, string $adminNote = ''): void
    {
        if (!in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            return;
        }

        $stmt = $this->connection->prepare(
            'UPDATE question_reports SET status = :st, admin_note = :note WHERE id = :id'
        );
        $stmt->execute([
            ':st' => $status,
            ':note' => mb_substr($adminNote, 0, 500, 'UTF-8'),
            ':id' => $reportId,
        ]);
    }

    public function createDocument(
        int $userId,
        string $title,
        string $originalFileName,
        string $storedFilePath,
        string $mimeType,
        string $extractedContent
    ): int {
        $stmt = $this->connection->prepare(
            'INSERT INTO documents (user_id, title, original_file_name, stored_file_path, mime_type, extracted_content, created_at)
             VALUES (:user_id, :title, :original_file_name, :stored_file_path, :mime_type, :extracted_content, :created_at)'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':original_file_name' => $originalFileName,
            ':stored_file_path' => $storedFilePath,
            ':mime_type' => $mimeType,
            ':extracted_content' => $extractedContent,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listDocuments(?int $ownerUserId = null): array
    {
        if ($ownerUserId === null) {
            $stmt = $this->connection->query(
                'SELECT d.id, d.title, d.original_file_name, d.stored_file_path, d.mime_type, d.created_at, u.name AS owner_name, u.email AS owner_email
                 FROM documents d
                 INNER JOIN users u ON u.id = d.user_id
                 ORDER BY d.id DESC'
            );
        } else {
            $stmt = $this->connection->prepare(
                'SELECT d.id, d.title, d.original_file_name, d.stored_file_path, d.mime_type, d.created_at, u.name AS owner_name, u.email AS owner_email
                 FROM documents d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE d.user_id = :user_id
                 ORDER BY d.id DESC'
            );
            $stmt->execute([':user_id' => $ownerUserId]);
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findDocumentById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT d.*, u.name AS owner_name
             FROM documents d
             INNER JOIN users u ON u.id = d.user_id
             WHERE d.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public function createQuiz(
        int $documentId,
        int $createdBy,
        string $title,
        string $difficulty,
        array $questions
    ): int {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO quizzes (document_id, created_by, title, difficulty, created_at)
                 VALUES (:document_id, :created_by, :title, :difficulty, :created_at)'
            );

            $stmt->execute([
                ':document_id' => $documentId > 0 ? $documentId : null,
                ':created_by' => $createdBy,
                ':title' => $title,
                ':difficulty' => $difficulty,
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $quizId = (int) $this->connection->lastInsertId();
            $questionStmt = $this->connection->prepare(
                'INSERT INTO questions (quiz_id, position, question_content, answer_a, answer_b, answer_c, answer_d, correct_answer, evidence_quote, reasoning, explanation, confidence_score, grounding_status, source, created_at)
                 VALUES (:quiz_id, :position, :question_content, :answer_a, :answer_b, :answer_c, :answer_d, :correct_answer, :evidence_quote, :reasoning, :explanation, :confidence_score, :grounding_status, :source, :created_at)'
            );

            foreach ($questions as $index => $question) {
                $answers = $question['answers'] ?? [];
                if (!is_array($answers)) {
                    throw new RuntimeException('Dinh dang answers khong hop le.');
                }

                $src = strtolower((string) ($question['source'] ?? 'extract'));
                if (!in_array($src, ['ai', 'extract', 'manual'], true)) {
                    $src = 'extract';
                }

                $questionStmt->execute([
                    ':quiz_id' => $quizId,
                    ':position' => $index + 1,
                    ':question_content' => (string) ($question['question_content'] ?? ''),
                    ':answer_a' => (string) ($answers['A'] ?? ''),
                    ':answer_b' => (string) ($answers['B'] ?? ''),
                    ':answer_c' => (string) ($answers['C'] ?? ''),
                    ':answer_d' => (string) ($answers['D'] ?? ''),
                    ':correct_answer' => strtoupper((string) ($question['correct_answer'] ?? 'A')),
                    ':evidence_quote' => (string) ($question['evidence_quote'] ?? ''),
                    ':reasoning' => (string) ($question['reasoning'] ?? ''),
                    ':explanation' => (string) ($question['explanation'] ?? ''),
                    ':confidence_score' => (int) ($question['confidence_score'] ?? 0),
                    ':grounding_status' => (string) ($question['grounding_status'] ?? 'unknown'),
                    ':source' => $src,
                    ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);
            }

            $this->connection->commit();
            return $quizId;
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw new RuntimeException('Khong the tao quiz: ' . $throwable->getMessage(), 0, $throwable);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function listQuizzes(): array
    {
        $stmt = $this->connection->query(
            'SELECT q.id, q.title, q.difficulty, q.created_at,
                    d.title AS document_title, u.name AS creator_name,
                    COUNT(qq.id) AS total_questions
             FROM quizzes q
             LEFT JOIN documents d ON d.id = q.document_id
             INNER JOIN users u ON u.id = q.created_by
             LEFT JOIN questions qq ON qq.quiz_id = q.id
             GROUP BY q.id
             ORDER BY q.id DESC'
        );

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findQuizById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT q.*, d.title AS document_title, d.id AS doc_id, u.name AS creator_name
             FROM quizzes q
             LEFT JOIN documents d ON d.id = q.document_id
             INNER JOIN users u ON u.id = q.created_by
             WHERE q.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function deleteQuiz(int $quizId): void
    {
        $stmt = $this->connection->prepare('DELETE FROM quizzes WHERE id = :id');
        $stmt->execute([':id' => $quizId]);
    }

    /** @return array<int, array<string, mixed>> */
    public function findQuestionsByQuizId(int $quizId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT *
             FROM questions
             WHERE quiz_id = :quiz_id
             ORDER BY position ASC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listQuestions(?int $quizId = null, ?string $sourceFilter = null): array
    {
        $sourceOk = $sourceFilter !== null
            && in_array($sourceFilter, ['ai', 'extract', 'manual'], true);

        if ($quizId === null) {
            if ($sourceOk) {
                $stmt = $this->connection->prepare(
                    'SELECT q.id, q.quiz_id, q.position, q.question_content, q.answer_a, q.answer_b, q.answer_c, q.answer_d, q.correct_answer, q.source,
                            z.title AS quiz_title
                     FROM questions q
                     INNER JOIN quizzes z ON z.id = q.quiz_id
                     WHERE q.source = :src
                     ORDER BY q.id DESC'
                );
                $stmt->execute([':src' => $sourceFilter]);
            } else {
                $stmt = $this->connection->query(
                    'SELECT q.id, q.quiz_id, q.position, q.question_content, q.answer_a, q.answer_b, q.answer_c, q.answer_d, q.correct_answer, q.source,
                            z.title AS quiz_title
                     FROM questions q
                     INNER JOIN quizzes z ON z.id = q.quiz_id
                     ORDER BY q.id DESC'
                );
            }
        } else {
            if ($sourceOk) {
                $stmt = $this->connection->prepare(
                    'SELECT q.id, q.quiz_id, q.position, q.question_content, q.answer_a, q.answer_b, q.answer_c, q.answer_d, q.correct_answer, q.source,
                            z.title AS quiz_title
                     FROM questions q
                     INNER JOIN quizzes z ON z.id = q.quiz_id
                     WHERE q.quiz_id = :quiz_id AND q.source = :src
                     ORDER BY q.position ASC'
                );
                $stmt->execute([':quiz_id' => $quizId, ':src' => $sourceFilter]);
            } else {
                $stmt = $this->connection->prepare(
                    'SELECT q.id, q.quiz_id, q.position, q.question_content, q.answer_a, q.answer_b, q.answer_c, q.answer_d, q.correct_answer, q.source,
                            z.title AS quiz_title
                     FROM questions q
                     INNER JOIN quizzes z ON z.id = q.quiz_id
                     WHERE q.quiz_id = :quiz_id
                     ORDER BY q.position ASC'
                );
                $stmt->execute([':quiz_id' => $quizId]);
            }
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findQuestionById(int $questionId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT q.*, z.title AS quiz_title
             FROM questions q
             INNER JOIN quizzes z ON z.id = q.quiz_id
             WHERE q.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $questionId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function createQuestion(
        int $quizId,
        string $questionContent,
        string $answerA,
        string $answerB,
        string $answerC,
        string $answerD,
        string $correctAnswer,
        string $source = 'manual'
    ): int {
        $positionStmt = $this->connection->prepare('SELECT COALESCE(MAX(position), 0) FROM questions WHERE quiz_id = :quiz_id');
        $positionStmt->execute([':quiz_id' => $quizId]);
        $position = ((int) $positionStmt->fetchColumn()) + 1;

        $src = strtolower($source);
        if (!in_array($src, ['ai', 'extract', 'manual'], true)) {
            $src = 'manual';
        }

        $stmt = $this->connection->prepare(
            'INSERT INTO questions (quiz_id, position, question_content, answer_a, answer_b, answer_c, answer_d, correct_answer, source, created_at)
             VALUES (:quiz_id, :position, :question_content, :answer_a, :answer_b, :answer_c, :answer_d, :correct_answer, :source, :created_at)'
        );

        $stmt->execute([
            ':quiz_id' => $quizId,
            ':position' => $position,
            ':question_content' => $questionContent,
            ':answer_a' => $answerA,
            ':answer_b' => $answerB,
            ':answer_c' => $answerC,
            ':answer_d' => $answerD,
            ':correct_answer' => strtoupper($correctAnswer),
            ':source' => $src,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateQuestion(
        int $questionId,
        string $questionContent,
        string $answerA,
        string $answerB,
        string $answerC,
        string $answerD,
        string $correctAnswer
    ): void {
        $stmt = $this->connection->prepare(
            'UPDATE questions
             SET question_content = :question_content,
                 answer_a = :answer_a,
                 answer_b = :answer_b,
                 answer_c = :answer_c,
                 answer_d = :answer_d,
                 correct_answer = :correct_answer
             WHERE id = :id'
        );

        $stmt->execute([
            ':question_content' => $questionContent,
            ':answer_a' => $answerA,
            ':answer_b' => $answerB,
            ':answer_c' => $answerC,
            ':answer_d' => $answerD,
            ':correct_answer' => strtoupper($correctAnswer),
            ':id' => $questionId,
        ]);
    }

    public function deleteQuestion(int $questionId): void
    {
        $stmt = $this->connection->prepare('DELETE FROM questions WHERE id = :id');
        $stmt->execute([':id' => $questionId]);
    }

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
    ): int {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO submissions (quiz_id, user_id, score, total_questions, total_correct, created_at)
                 VALUES (:quiz_id, :user_id, :score, :total_questions, :total_correct, :created_at)'
            );

            $stmt->execute([
                ':quiz_id' => $quizId,
                ':user_id' => $userId,
                ':score' => $score,
                ':total_questions' => $totalQuestions,
                ':total_correct' => $totalCorrect,
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $submissionId = (int) $this->connection->lastInsertId();
            $answerStmt = $this->connection->prepare(
                'INSERT INTO submission_answers (submission_id, question_id, selected_answer, is_correct)
                 VALUES (:submission_id, :question_id, :selected_answer, :is_correct)'
            );

            foreach ($answerRows as $row) {
                $answerStmt->execute([
                    ':submission_id' => $submissionId,
                    ':question_id' => (int) ($row['question_id'] ?? 0),
                    ':selected_answer' => strtoupper((string) ($row['selected_answer'] ?? '')),
                    ':is_correct' => !empty($row['is_correct']) ? 1 : 0,
                ]);
            }

            $this->connection->commit();
            return $submissionId;
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw new RuntimeException('Khong the luu submission: ' . $throwable->getMessage(), 0, $throwable);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function listSubmissions(?int $userId = null): array
    {
        if ($userId === null) {
            $stmt = $this->connection->query(
                'SELECT s.id, s.user_id, s.score, s.total_questions, s.total_correct, s.created_at,
                        q.title AS quiz_title, u.name AS user_name
                 FROM submissions s
                 INNER JOIN quizzes q ON q.id = s.quiz_id
                 INNER JOIN users u ON u.id = s.user_id
                 ORDER BY s.id DESC'
            );
        } else {
            $stmt = $this->connection->prepare(
                'SELECT s.id, s.user_id, s.score, s.total_questions, s.total_correct, s.created_at,
                        q.title AS quiz_title, u.name AS user_name
                 FROM submissions s
                 INNER JOIN quizzes q ON q.id = s.quiz_id
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.user_id = :user_id
                 ORDER BY s.id DESC'
            );
            $stmt->execute([':user_id' => $userId]);
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findSubmissionById(int $submissionId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT s.*, q.title AS quiz_title, u.name AS user_name
             FROM submissions s
             INNER JOIN quizzes q ON q.id = s.quiz_id
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $submissionId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findSubmissionAnswers(int $submissionId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT sa.question_id, sa.selected_answer, sa.is_correct, q.question_content, q.answer_a, q.answer_b, q.answer_c, q.answer_d, q.correct_answer
             FROM submission_answers sa
             INNER JOIN questions q ON q.id = sa.question_id
             WHERE sa.submission_id = :submission_id
             ORDER BY q.position ASC'
        );
        $stmt->execute([':submission_id' => $submissionId]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }
}
