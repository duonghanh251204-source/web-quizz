<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Exceptions\AIProviderException;
use App\Support\Logger;

/**
 * ChatbotAIServiceProvider — Proxy tới microservice Python (CHATBOT-AI).
 *
 * Thay vì gọi API của OpenAI/Gemini trực tiếp từ PHP, class này forward
 * tài liệu lên FastAPI service (chạy ở localhost:8000) để được xử lý bởi
 * Python pipeline: chunking → LLM (Gemini/OpenAI/Ollama) → parse JSON.
 *
 * Luồng:
 *   1. upload($filePath, $filename)  → POST /upload  → trả về session_id
 *   2. generate($prompt)             → POST /generate (dùng session_id trong prompt)
 *
 * Cách dùng đặc biệt: $prompt phải là JSON encode của GenerateRequest payload,
 * được build bởi QuizFromDocumentPromptBuilder khi AI_PROVIDER=chatbot_ai.
 * Nhưng để tương thích ngược với AIProviderInterface (nhận string $prompt),
 * class này parse JSON từ $prompt nếu hợp lệ, hoặc dùng content text thô.
 */
final class ChatbotAIServiceProvider implements AIProviderInterface
{
    private string $baseUrl;

    public function __construct(
        private int $timeoutSeconds,
        private Logger $logger,
        string $serviceUrl = ''
    ) {
        $this->baseUrl = rtrim($serviceUrl !== '' ? $serviceUrl : 'http://localhost:8000', '/');
    }

    /**
     * Gọi FastAPI pipeline: nhận text prompt → upload temp file → /generate → trả kết quả.
     *
     * Hoạt động theo 2 chế độ:
     *   a) $prompt là JSON với "session_id" → gọi /generate trực tiếp (dùng từ code ngoài).
     *   b) $prompt là text thường (từ QuizGenerationService) → tạo temp .txt, upload, rồi generate.
     *
     * @throws AIProviderException
     */
    public function generate(string $prompt): AIResult
    {
        set_time_limit(0);
        // Chế độ a: đã có session_id (gọi trực tiếp)
        $decoded = json_decode($prompt, true);
        if (is_array($decoded) && isset($decoded['session_id'])) {
            return $this->callGenerate($decoded);
        }

        // Chế độ b: text prompt thông thường → tạo temp file và upload
        // QuizFromDocumentPromptBuilder tạo plain-text prompt có dạng:
        // "QUESTION_COUNT: 20" và "META_QUESTION_COUNT: 20"
        // Cần parse từ đó thay vì hardcode
        $numQuestions  = $this->parseNumQuestionsFromPrompt($prompt);
        $difficulty    = $this->parseDifficultyFromPrompt($prompt);
        $language      = 'vi'; // mặc định; có thể mở rộng sau

        $this->logger->info('ChatbotAI: Nhận text prompt, tạo temp file để upload', [
            'num_questions' => $numQuestions,
            'difficulty'    => $difficulty,
        ]);
        $sessionInfo = $this->uploadTextAsFile($prompt);

        $payload = [
            'session_id'    => $sessionInfo['session_id'],
            'num_questions' => $numQuestions,
            'difficulty'    => $difficulty,
            'language'      => $language,
            'auto_review'   => false,
        ];

        return $this->callGenerate($payload);
    }

    /**
     * Gọi POST /generate với payload đã có session_id.
     *
     * @param array<string, mixed> $payload
     * @throws AIProviderException
     */
    private function callGenerate(array $payload): AIResult
    {
        $this->logger->info('ChatbotAI: Gọi /generate', ['session_id' => $payload['session_id'] ?? '']);

        $ch = curl_init($this->baseUrl . '/generate');
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS      => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT         => $this->timeoutSeconds,
        ]);

        $responseBody = curl_exec($ch);
        $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError !== '') {
            throw new AIProviderException('Không kết nối được CHATBOT-AI service: ' . $curlError);
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new AIProviderException('CHATBOT-AI trả dữ liệu không hợp lệ.');
        }

        if ($statusCode >= 400) {
            $detail = $decoded['detail'] ?? 'Lỗi không xác định.';
            $this->logger->error('ChatbotAI /generate thất bại', ['status' => $statusCode, 'detail' => $detail]);
            throw new AIProviderException('CHATBOT-AI lỗi: ' . (string) $detail);
        }

        // Map questions từ format Python → format PHP (question_content, answers, correct_answer)
        $rawQuestions = $decoded['questions'] ?? [];
        $mapped = $this->mapQuestions($rawQuestions);
        $content = json_encode(['questions' => $mapped], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return new AIResult(
            content: (string) $content,
            model: 'chatbot-ai-fastapi',
            rawResponse: (string) $responseBody
        );
    }

    /**
     * Map format câu hỏi từ Python sang format PHP.
     * Python: {question, options: {A,B,C,D}, correct_answer, explanation}
     * PHP:    {question_content, answers: {A,B,C,D}, correct_answer}
     *
     * @param array<int, mixed> $rawQuestions
     * @return array<int, array<string, mixed>>
     */
    private function mapQuestions(array $rawQuestions): array
    {
        $result = [];
        foreach ($rawQuestions as $q) {
            if (!is_array($q)) {
                continue;
            }
            $rawOptions = $q['options'] ?? $q['answers'] ?? null;
            $options = $this->normalizeOptions($rawOptions);
            $correct = strtoupper((string) ($q['correct_answer'] ?? $q['correct'] ?? 'A'));
            $result[] = [
                'question_content' => $q['question'] ?? ($q['question_content'] ?? ''),
                'answers' => [
                    'A' => $options['A'] ?? '',
                    'B' => $options['B'] ?? '',
                    'C' => $options['C'] ?? '',
                    'D' => $options['D'] ?? '',
                ],
                'correct_answer' => in_array($correct, ['A', 'B', 'C', 'D'], true) ? $correct : 'A',
                'evidence_quote' => $q['evidence_quote'] ?? $q['source_hint'] ?? '',
                'reasoning' => $q['reasoning'] ?? '',
                'explanation' => $q['explanation'] ?? '',
                'confidence_score' => (int)($q['confidence_score'] ?? 0),
                'grounding_status' => $q['grounding_status'] ?? 'unknown',
            ];
        }
        return $result;
    }

    /**
     * @param mixed $rawOptions
     * @return array{A:string,B:string,C:string,D:string}
     */
    private function normalizeOptions(mixed $rawOptions): array
    {
        $normalized = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];

        if (!is_array($rawOptions)) {
            return $normalized;
        }

        if (array_keys($rawOptions) === range(0, count($rawOptions) - 1)) {
            $list = array_values($rawOptions);
            $normalized['A'] = trim((string) ($list[0] ?? ''));
            $normalized['B'] = trim((string) ($list[1] ?? ''));
            $normalized['C'] = trim((string) ($list[2] ?? ''));
            $normalized['D'] = trim((string) ($list[3] ?? ''));
            return $normalized;
        }

        $normalized['A'] = trim((string) ($rawOptions['A'] ?? $rawOptions['a'] ?? $rawOptions[0] ?? ''));
        $normalized['B'] = trim((string) ($rawOptions['B'] ?? $rawOptions['b'] ?? $rawOptions[1] ?? ''));
        $normalized['C'] = trim((string) ($rawOptions['C'] ?? $rawOptions['c'] ?? $rawOptions[2] ?? ''));
        $normalized['D'] = trim((string) ($rawOptions['D'] ?? $rawOptions['d'] ?? $rawOptions[3] ?? ''));

        return $normalized;
    }

    /**
     * Parse số câu hỏi từ plain-text prompt (do QuizFromDocumentPromptBuilder tạo ra).
     * Tìm các pattern: "QUESTION_COUNT: 20" hoặc "META_QUESTION_COUNT: 20"
     */
    private function parseNumQuestionsFromPrompt(string $prompt): int
    {
        // Tìm META_QUESTION_COUNT trước (footer được thêm sau)
        if (preg_match('/META_QUESTION_COUNT:\s*(\d+)/i', $prompt, $m)) {
            return max(1, min(50, (int) $m[1]));
        }
        // Tìm QUESTION_COUNT trong header template
        if (preg_match('/QUESTION_COUNT:\s*(\d+)/i', $prompt, $m)) {
            return max(1, min(50, (int) $m[1]));
        }
        // Tìm dạng "Đúng X câu" hoặc "tạo X câu"
        if (preg_match('/(?:đúng|tạo|create|generate)\s+(\d+)\s+câu/iu', $prompt, $m)) {
            return max(1, min(50, (int) $m[1]));
        }
        return 10; // fallback mặc định
    }

    /**
     * Parse độ khó từ plain-text prompt.
     * Tìm pattern: "DIFFICULTY: medium"
     */
    private function parseDifficultyFromPrompt(string $prompt): string
    {
        if (preg_match('/DIFFICULTY:\s*(easy|medium|hard)/i', $prompt, $m)) {
            return strtolower($m[1]);
        }
        return 'medium';
    }

    /**
     * Tạo temp file .txt từ text content và upload lên FastAPI.
     *
     * @return array{session_id: string, total_chunks: int, title: string}
     * @throws AIProviderException
     */
    private function uploadTextAsFile(string $textContent): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'chatbot_ai_') . '.txt';
        $written = file_put_contents($tmpFile, $textContent);
        if ($written === false) {
            throw new AIProviderException('Không thể tạo temp file để upload lên CHATBOT-AI.');
        }

        try {
            return $this->upload($tmpFile, 'document.txt');
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Tải lên nội dung thô (không có prompt wrapper)
     *
     * @return array{session_id: string, total_chunks: int, title: string}
     * @throws AIProviderException
     */
    public function uploadRawContent(string $content, string $filename = 'document.txt'): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'chatbot_ai_raw_') . '.txt';
        $written = file_put_contents($tmpFile, $content);
        if ($written === false) {
            throw new AIProviderException('Không thể tạo temp file để upload lên CHATBOT-AI.');
        }

        try {
            return $this->upload($tmpFile, $filename);
        } finally {
            @unlink($tmpFile);
        }
    }


    /**
     * Upload file lên FastAPI /upload, nhận về session_id.
     * Gọi method này trước generate() để có session_id.
     *
     * @return array{session_id: string, total_chunks: int, title: string}
     * @throws AIProviderException
     */
    public function upload(string $filePath, string $filename): array
    {
        set_time_limit(0);

        if (!file_exists($filePath)) {
            throw new AIProviderException("File không tồn tại: {$filePath}");
        }

        $this->logger->info('ChatbotAI: Upload file', ['file' => $filename]);

        $ch = curl_init($this->baseUrl . '/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => $this->timeoutSeconds,
            CURLOPT_POSTFIELDS      => [
                'file' => new \CURLFile($filePath, mime_content_type($filePath) ?: 'application/octet-stream', $filename),
            ],
        ]);

        $responseBody = curl_exec($ch);
        $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError !== '') {
            throw new AIProviderException('Không kết nối được CHATBOT-AI /upload: ' . $curlError);
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new AIProviderException('CHATBOT-AI /upload trả dữ liệu không hợp lệ.');
        }

        if ($statusCode >= 400) {
            $detail = $decoded['detail'] ?? 'Upload thất bại.';
            throw new AIProviderException('CHATBOT-AI upload lỗi: ' . (string) $detail);
        }

        $sessionId = (string) ($decoded['session_id'] ?? '');
        if ($sessionId === '') {
            throw new AIProviderException('CHATBOT-AI không trả về session_id.');
        }

        $this->logger->info('ChatbotAI: Upload thành công', ['session_id' => $sessionId]);

        return [
            'session_id'   => $sessionId,
            'total_chunks' => (int) ($decoded['total_chunks'] ?? 0),
            'title'        => (string) ($decoded['title'] ?? $filename),
        ];
    }

    /**
     * Kiểm tra xem service Python có đang chạy không.
     */
    public function isAvailable(): bool
    {
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
        ]);
        curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $statusCode === 200;
    }
}
