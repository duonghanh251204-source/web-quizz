<?php

declare(strict_types=1);

namespace App\Services\Prompt;

use App\Core\Env;
use App\Repositories\PlatformRepositoryInterface;

final class QuizFromDocumentPromptBuilder
{
    public function __construct(private PlatformRepositoryInterface $repository)
    {
    }

    /**
     * @param string|null $docFocus null | 'first' | 'second' — gợi ý phần tài liệu khi tách song song 2 request
     */
    public function build(
        string $documentTitle,
        string $documentContent,
        int $questionCount,
        string $difficulty,
        ?string $docFocus = null,
    ): string {
        $safeDifficulty = $this->normalizeDifficulty($difficulty);
        $safeQuestionCount = max(1, min($questionCount, 50));
        $maxChars = max(2500, min(50000, (int) Env::get('AI_DOCUMENT_CONTEXT_CHARS', '9000')));
        $clippedContent = mb_substr($documentContent, 0, $maxChars, 'UTF-8');

        /** Giúp provider tính max_tokens an toàn kể cả prompt tùy chỉnh không chứa QUESTION_COUNT: */
        $metaFooter = "\nMETA_QUESTION_COUNT: {$safeQuestionCount}\n";

        $focusSuffix = match ($docFocus) {
            'first' => "\nDOC_FOCUS: Ưu tiên ý chính ở nửa đầu văn bản nguồn.\n",
            'second' => "\nDOC_FOCUS: Ưu tiên ý chính ở nửa sau văn bản nguồn.\n",
            default => '',
        };
        $tail = $metaFooter . $focusSuffix;

        $custom = trim($this->repository->getSetting('ai_quiz_prompt_template', ''));
        if ($custom !== '') {
            return str_replace(
                ['{DOC_TITLE}', '{DIFFICULTY}', '{QUESTION_COUNT}', '{DOC_CONTENT}'],
                [$documentTitle, $safeDifficulty, (string) $safeQuestionCount, $clippedContent],
                $custom
            ) . $tail;
        }

        return $this->defaultTemplate($documentTitle, $safeDifficulty, $safeQuestionCount, $clippedContent) . $tail;
    }

    private function defaultTemplate(
        string $documentTitle,
        string $safeDifficulty,
        int $safeQuestionCount,
        string $clippedContent
    ): string {
        return <<<PROMPT
AI_MODULE: document_to_mcq
DOC_TITLE: {$documentTitle}
DIFFICULTY: {$safeDifficulty}
QUESTION_COUNT: {$safeQuestionCount}
OUTPUT_SCHEMA_VERSION: 1.0

TAO: Đúng {$safeQuestionCount} câu trắc nghiệm 4 đáp án từ nguồn; bám sát dữ kiện; 1 đáp đúng; không trùng stem; không kiểu "tất cả đúng/sai".
DANG_RA: JSON thuần (không markdown):
{"title":"string","questions":[{"question":"string","options":["","","",""],"correct":"A|B|C|D"}]}

NOI_DUNG:
"""
{$clippedContent}
"""
PROMPT;
    }

    private function normalizeDifficulty(string $difficulty): string
    {
        $normalized = strtolower(trim($difficulty));

        return in_array($normalized, ['easy', 'medium', 'hard'], true)
            ? $normalized
            : 'medium';
    }
}
