<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Exceptions\AIProviderException;
use App\Support\Logger;

/**
 * GeminiAIProvider — Gọi Google Gemini API trực tiếp từ PHP.
 * Implement AIProviderInterface, tương thích hoàn toàn với hệ thống hiện tại.
 *
 * Cấu hình .env:
 *   AI_PROVIDER=gemini
 *   GEMINI_API_KEY=AIza...
 *   GEMINI_MODEL=gemini-1.5-flash   (hoặc gemini-2.5-flash, gemini-1.5-pro, v.v.)
 *   GEMINI_TIMEOUT=60
 */
final class GeminiAIProvider implements AIProviderInterface
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(
        private string $apiKey,
        private string $model,
        private int $timeoutSeconds,
        private Logger $logger
    ) {
    }

    public function generate(string $prompt): AIResult
    {
        if ($this->apiKey === '') {
            throw new AIProviderException('Thiếu API key Gemini. Cấu hình trong Quản trị → AI hoặc GEMINI_API_KEY trong .env.');
        }

        $endpoint = self::BASE_URL . urlencode($this->model) . ':generateContent?key=' . urlencode($this->apiKey);

        $maxOut = $this->inferMaxOutputTokens($prompt);

        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'temperature'     => 0.25,
                'maxOutputTokens' => $maxOut,
                'responseMimeType' => 'application/json',
            ],
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'Bạn là AI chuyên tạo câu hỏi trắc nghiệm từ văn bản nguồn. '
                            . 'Chỉ trả về JSON đúng schema: {"title": "...", "questions": [...]}. '
                            . 'Mỗi câu hỏi có dạng: {"question": "...", "options": {"A": "...", "B": "...", "C": "...", "D": "..."}, "correct_answer": "A|B|C|D", "explanation": "..."}.',
                    ],
                ],
            ],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
        ]);

        $responseBody = curl_exec($ch);
        $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError !== '') {
            throw new AIProviderException('Lỗi kết nối Gemini: ' . $curlError);
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new AIProviderException('Gemini trả dữ liệu không hợp lệ.');
        }

        if ($statusCode >= 400) {
            $this->logger->error('Yêu cầu Gemini thất bại', ['status' => $statusCode, 'response' => $decoded]);
            $message = $decoded['error']['message'] ?? 'Gemini trả về lỗi không xác định.';
            throw new AIProviderException((string) $message);
        }

        // Lấy text từ response Gemini
        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            throw new AIProviderException('Gemini không trả nội dung câu hỏi.');
        }

        $modelUsed = $decoded['modelVersion'] ?? $this->model;

        return new AIResult(
            content: $content,
            model: (string) $modelUsed,
            rawResponse: (string) $responseBody
        );
    }

    /** Giới hạn đầu ra theo số câu trong prompt — tránh ceiling cố định quá cao làm model “dài dòng”. */
    private function inferMaxOutputTokens(string $prompt): int
    {
        $qc = 15;
        if (preg_match('/META_QUESTION_COUNT:\s*(\d+)/i', $prompt, $m) === 1) {
            $qc = max(1, min(30, (int) $m[1]));
        } elseif (preg_match('/QUESTION_COUNT:\s*(\d+)/i', $prompt, $m) === 1) {
            $qc = max(1, min(30, (int) $m[1]));
        }

        $estimated = 480 + ($qc * 280);

        return max(2048, min(16384, $estimated));
    }
}
