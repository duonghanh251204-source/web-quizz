<?php

declare(strict_types=1);

namespace App\Services\AI;

final class MockAIProvider implements AIProviderInterface, ConcurrentAIProviderInterface
{
    public function generate(string $prompt): AIResult
    {
        $docTitle = $this->extractField($prompt, 'DOC_TITLE');
        $difficulty = $this->extractField($prompt, 'DIFFICULTY');
        $questionCount = (int) $this->extractField($prompt, 'QUESTION_COUNT');

        $safeQuestionCount = max(1, min($questionCount, 30));
        $topicText = $docTitle !== '' ? $docTitle : 'Nội dung tài liệu';
        $safeDifficulty = $difficulty !== '' ? $difficulty : 'medium';

        $questions = [];

        for ($i = 1; $i <= $safeQuestionCount; $i++) {
            $correct = ['A', 'B', 'C', 'D'][($i + 1) % 4];

            $questions[] = [
                'question' => "Câu {$i}: Phát biểu nào phù hợp nhất với {$topicText} ở mức {$safeDifficulty}?",
                'options' => [
                    "Phương án A cho câu {$i}",
                    "Phương án B cho câu {$i}",
                    "Phương án C cho câu {$i}",
                    "Phương án D cho câu {$i}",
                ],
                'correct' => $correct,
            ];
        }

        $payload = json_encode(
            [
                'title' => "Bộ câu hỏi - {$topicText}",
                'questions' => $questions,
            ],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        return new AIResult(
            content: $payload ?: '{"title":"Mẫu","questions":[]}',
            model: 'mock-generator-v2',
            rawResponse: $payload ?: '{"title":"Mẫu","questions":[]}'
        );
    }

    public function generateConcurrent(array $prompts): array
    {
        $out = [];
        foreach ($prompts as $prompt) {
            if (!is_string($prompt)) {
                throw new \InvalidArgumentException('Prompt không hợp lệ.');
            }

            $out[] = $this->generate($prompt);
        }

        return $out;
    }

    private function extractField(string $prompt, string $field): string
    {
        if (preg_match('/' . preg_quote($field, '/') . ':\s*(.+)/', $prompt, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return '';
    }
}
