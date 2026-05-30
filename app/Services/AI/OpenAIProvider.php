<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Exceptions\AIProviderException;
use App\Support\Logger;

final class OpenAIProvider implements AIProviderInterface, ConcurrentAIProviderInterface
{
    private const DEFAULT_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /** OpenAI-compatible Chat Completions URL của DeepSeek */
    public const DEEPSEEK_ENDPOINT = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct(
        private string $apiKey,
        private string $model,
        private int $timeoutSeconds,
        private Logger $logger,
        private string $endpoint = self::DEFAULT_ENDPOINT,
        private string $vendorLabel = 'OpenAI',
    ) {
    }

    public function generate(string $prompt): AIResult
    {
        $this->assertHasApiKey();

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, $this->curlOptionsForPayload($this->buildPayload($prompt)));

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return $this->parseChatCompletionResponse((string) $responseBody, $curlError, $statusCode);
    }

    public function generateConcurrent(array $prompts): array
    {
        $this->assertHasApiKey();

        if ($prompts === []) {
            throw new AIProviderException('Danh sách prompt cho ' . $this->vendorLabel . ' đang rỗng.');
        }

        $multiHandle = curl_multi_init();
        if ($multiHandle === false) {
            throw new AIProviderException('Không khởi tạo được curl_multi.');
        }

        /** @var array<int, \CurlHandle> $handles */
        $handles = [];

        try {
            foreach ($prompts as $index => $prompt) {
                if (!is_string($prompt)) {
                    throw new AIProviderException('Prompt không hợp lệ.');
                }

                $ch = curl_init($this->endpoint);
                if ($ch === false) {
                    throw new AIProviderException('Không khởi tạo được CURL.');
                }

                curl_setopt_array($ch, $this->curlOptionsForPayload($this->buildPayload($prompt)));
                curl_multi_add_handle($multiHandle, $ch);
                $handles[(int) $index] = $ch;
            }

            $running = null;
            do {
                $exec = curl_multi_exec($multiHandle, $running);
                if ($exec === CURLM_CALL_MULTI_PERFORM) {
                    continue;
                }

                if ($running > 0) {
                    $select = curl_multi_select($multiHandle, 1.0);
                    if ($select === -1) {
                        usleep(1000);
                    }
                }
            } while ($running > 0);

            $results = [];
            foreach ($handles as $index => $ch) {
                $curlError = curl_error($ch);
                $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $body = curl_multi_getcontent($ch);
                $results[(int) $index] = $this->parseChatCompletionResponse(
                    $body !== false ? (string) $body : '',
                    $curlError,
                    $statusCode
                );
            }

            ksort($results);

            return array_values($results);
        } finally {
            foreach ($handles as $ch) {
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }

            curl_multi_close($multiHandle);
        }
    }

    private function assertHasApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new AIProviderException(
                'Thiếu API key ' . $this->vendorLabel . '. Cấu hình trong Quản trị → AI hoặc biến môi trường tương ứng trong .env.'
            );
        }
    }

    /** @return array<string, mixed> */
    private function buildPayload(string $prompt): array
    {
        return [
            'model' => $this->model,
            'temperature' => 0.25,
            'max_tokens' => $this->inferMaxCompletionTokens($prompt),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tạo MCQ từ văn bản. Chỉ JSON gồm title và questions; mỗi phần tử có question, options (đúng 4 chuỗi), correct A-D.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function curlOptionsForPayload(array $payload): array
    {
        return [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ];
    }

    private function parseChatCompletionResponse(string $responseBody, string $curlError, int $statusCode): AIResult
    {
        if ($curlError !== '') {
            throw new AIProviderException('Lỗi kết nối ' . $this->vendorLabel . ': ' . $curlError);
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new AIProviderException($this->vendorLabel . ' trả dữ liệu không hợp lệ.');
        }

        if ($statusCode >= 400) {
            $this->logger->error('Yêu cầu ' . $this->vendorLabel . ' thất bại', ['status' => $statusCode, 'response' => $decoded]);
            $message = $decoded['error']['message'] ?? ($this->vendorLabel . ' trả về lỗi không xác định.');
            throw new AIProviderException((string) $message);
        }

        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            throw new AIProviderException($this->vendorLabel . ' không trả nội dung câu hỏi.');
        }

        $model = $decoded['model'] ?? $this->model;

        return new AIResult(
            content: $content,
            model: (string) $model,
            rawResponse: $responseBody
        );
    }

    /**
     * Giới hạn token đầu ra theo số câu hỏi (parse từ prompt) để model không sinh quá dài → thường phản hồi nhanh hơn.
     */
    private function inferMaxCompletionTokens(string $prompt): int
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
