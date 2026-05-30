<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Exceptions\AIProviderException;
use App\Exceptions\ValidationException;
use App\Services\AI\AIProviderInterface;
use App\Services\AI\ConcurrentAIProviderInterface;
use App\Services\Prompt\QuizFromDocumentPromptBuilder;
use App\Support\Logger;

final class QuizGenerationService
{
    /** @var array<int, string> */
    private const OPTION_LABELS = ['A', 'B', 'C', 'D'];

    public function __construct(
        private QuizFromDocumentPromptBuilder $promptBuilder,
        private AIProviderInterface $aiProvider,
        private Logger $logger
    ) {
    }

    /**
     * @return array{title:string, questions:array<int,array<string,mixed>>, source:string}
     */
    public function generateFromDocument(
        string $documentTitle,
        string $documentContent,
        ?int $questionCount,
        string $difficulty
    ): array {
        // Backward-compatible entrypoint: import flow must stay parse-only.
        // AI generation is reserved exclusively for explicit suggestion actions.
        return $this->extractQuestionsFromDocument($documentTitle, $documentContent);
    }

    /**
     * @return array{title:string, questions:array<int,array<string,mixed>>, source:string}
     */
    public function extractQuestionsFromDocument(string $documentTitle, string $documentContent): array
    {
        $parsedQuestions = $this->parseQuestionsFromDocument($documentContent);
        if ($parsedQuestions === []) {
            throw new ValidationException([
                'questions' => 'Khong nhan dien duoc bo cau hoi co cau truc tu tep import. Hay kiem tra dinh dang tai lieu.',
            ]);
        }

        foreach ($parsedQuestions as $i => $row) {
            $parsedQuestions[$i]['source'] = 'extract';
        }

        return [
            'title' => "Bai kiem tra tu {$documentTitle}",
            'questions' => $parsedQuestions,
            'source' => 'document',
        ];
    }

    /**
     * @return array{title:string, questions:array<int,array<string,mixed>>, source:string}
     */
    public function generateAiSuggestions(
        string $documentTitle,
        string $documentContent,
        ?int $questionCount,
        string $difficulty
    ): array {
        $safeQuestionCount = $this->resolveAiQuestionCount($questionCount, 10, 50);

        return $this->generateQuestionsWithAi(
            documentTitle: $documentTitle,
            documentContent: $documentContent,
            questionCount: $safeQuestionCount,
            difficulty: $difficulty,
            source: 'ai_suggestion',
            allowPartialCount: true
        );
    }

    private function resolveAiQuestionCount(?int $questionCount, int $default, int $max): int
    {
        $raw = $questionCount ?? $default;

        return max(1, min($raw, $max));
    }

    /**
     * @return array{title:string, questions:array<int,array<string,mixed>>, source:string}
     */
    private function generateQuestionsWithAi(
        string $documentTitle,
        string $documentContent,
        int $questionCount,
        string $difficulty,
        string $source,
        bool $allowPartialCount
    ): array {
        $safeDifficulty = $this->normalizeDifficulty($difficulty);
        $preparedContent = $this->prepareDocumentContent($documentContent);

        if ($this->shouldUseParallelAi($questionCount)) {
            return $this->generateQuestionsWithAiParallel(
                documentTitle: $documentTitle,
                preparedContent: $preparedContent,
                questionCount: $questionCount,
                safeDifficulty: $safeDifficulty,
                source: $source,
                allowPartialCount: $allowPartialCount
            );
        }

        $prompt = $this->promptBuilder->build($documentTitle, $preparedContent, $questionCount, $safeDifficulty);

        try {
            $result = $this->aiProvider->generate($prompt);
        } catch (\Throwable $throwable) {
            $this->logger->error('Loi sinh cau hoi AI', ['message' => $throwable->getMessage()]);
            throw new AIProviderException($throwable->getMessage());
        }

        $decoded = $this->decodeJson($result->content);
        $questions = $this->normalizeQuestions($decoded['questions'] ?? []);

        if ($allowPartialCount) {
            if ($questions === []) {
                throw new ValidationException([
                    'questions' => 'AI khong tao duoc cau hoi goi y hop le.',
                ]);
            }
        } elseif (count($questions) < $questionCount) {
            throw new ValidationException([
                'question_count' => 'AI khong tra du so cau hoi hop le theo yeu cau.',
            ]);
        }

        $questions = array_slice($questions, 0, $questionCount);
        $title = trim((string) ($decoded['title'] ?? ''));

        return [
            'title' => $title !== '' ? $title : "Bai kiem tra tu {$documentTitle}",
            'questions' => $questions,
            'source' => $source,
        ];
    }

    private function shouldUseParallelAi(int $questionCount): bool
    {
        if (!$this->aiProvider instanceof ConcurrentAIProviderInterface) {
            return false;
        }

        $flag = strtolower(trim((string) Env::get('AI_PARALLEL_SPLIT', '1')));
        if (in_array($flag, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        $minQ = max(4, min(30, (int) Env::get('AI_PARALLEL_MIN_QUESTIONS', '12')));

        return $questionCount >= $minQ;
    }

    /**
     * Hai request song song (OpenAI/DeepSeek): mỗi nhánh ít câu hơn → thường hết thời gian chờ nhanh hơn một nhánh duy nhất.
     *
     * @return array{title:string, questions:array<int,array<string,mixed>>, source:string}
     */
    private function generateQuestionsWithAiParallel(
        string $documentTitle,
        string $preparedContent,
        int $questionCount,
        string $safeDifficulty,
        string $source,
        bool $allowPartialCount
    ): array {
        $n1 = intdiv($questionCount, 2);
        $n2 = $questionCount - $n1;

        $promptFirst = $this->promptBuilder->build($documentTitle, $preparedContent, $n1, $safeDifficulty, 'first');
        $promptSecond = $this->promptBuilder->build($documentTitle, $preparedContent, $n2, $safeDifficulty, 'second');

        /** @var ConcurrentAIProviderInterface $provider */
        $provider = $this->aiProvider;

        try {
            $batch = $provider->generateConcurrent([$promptFirst, $promptSecond]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Loi sinh cau hoi AI (song song)', ['message' => $throwable->getMessage()]);
            throw new AIProviderException($throwable->getMessage());
        }

        $decodedA = $this->decodeJson($batch[0]->content);
        $decodedB = $this->decodeJson($batch[1]->content);

        $mergedRaw = array_merge(
            is_array($decodedA['questions'] ?? null) ? $decodedA['questions'] : [],
            is_array($decodedB['questions'] ?? null) ? $decodedB['questions'] : [],
        );

        $questions = $this->normalizeQuestions($mergedRaw);

        if ($allowPartialCount) {
            if ($questions === []) {
                throw new ValidationException([
                    'questions' => 'AI khong tao duoc cau hoi goi y hop le.',
                ]);
            }
        } elseif (count($questions) < $questionCount) {
            throw new ValidationException([
                'question_count' => 'AI khong tra du so cau hoi hop le theo yeu cau.',
            ]);
        }

        $questions = array_slice($questions, 0, $questionCount);
        $title = trim((string) ($decodedA['title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($decodedB['title'] ?? ''));
        }

        return [
            'title' => $title !== '' ? $title : "Bai kiem tra tu {$documentTitle}",
            'questions' => $questions,
            'source' => $source,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $content): array
    {
        $normalized = trim($content);
        $normalized = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $normalized) ?? $normalized;

        $decoded = json_decode($normalized, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $normalized, $matches) === 1) {
            $decoded = json_decode((string) $matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new ValidationException([
            'payload' => 'Không thể phân tích JSON từ phản hồi AI.',
        ]);
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeQuestions(mixed $items): array
    {
        if (!is_array($items)) {
            throw new ValidationException([
                'questions' => 'Trường "questions" phải là một mảng.',
            ]);
        }

        $result = [];
        $fingerprints = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $content = $this->extractQuestionText($item);
            if ($content === '') {
                continue;
            }

            $normalized = $this->normalizeOptions($item['options'] ?? $item['answers'] ?? null);
            if ($normalized === null) {
                continue;
            }

            $options = $normalized['options'];
            if ($this->hasDuplicateOptions($options)) {
                continue;
            }

            $correct = strtoupper(trim((string) ($item['correct'] ?? $item['correct_answer'] ?? '')));
            if ($correct === '' && $normalized['correct_from_marker'] !== '') {
                $correct = $normalized['correct_from_marker'];
            }

            if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                continue;
            }

            $fingerprint = $this->fingerprintQuestion($content);
            if (isset($fingerprints[$fingerprint])) {
                continue;
            }
            $fingerprints[$fingerprint] = true;

            $normalizedAnswers = [
                'A' => $options[0],
                'B' => $options[1],
                'C' => $options[2],
                'D' => $options[3],
            ];

            if ($normalizedAnswers[$correct] === '') {
                continue;
            }

            $result[] = [
                'question_content' => $content,
                'answers' => $normalizedAnswers,
                'correct_answer' => $correct,
                'source' => 'ai',
            ];
        }

        if ($result === []) {
            throw new ValidationException([
                'questions' => 'AI không trả về câu hỏi hợp lệ nào.',
            ]);
        }

        return $result;
    }

    private function extractQuestionText(array $item): string
    {
        $candidates = [
            $item['question'] ?? null,
            $item['question_content'] ?? null,
            $item['text'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array{options: array<int, string>, correct_from_marker: string}|null
     */
    private function normalizeOptions(mixed $rawOptions): ?array
    {
        if (!is_array($rawOptions)) {
            return null;
        }

        if ($this->isListArray($rawOptions)) {
            if (count($rawOptions) !== 4) {
                return null;
            }

            $list = array_values($rawOptions);
            $normalized = [
                trim((string) $list[0]),
                trim((string) $list[1]),
                trim((string) $list[2]),
                trim((string) $list[3]),
            ];
        } else {
            $normalized = [
                trim((string) ($rawOptions['A'] ?? $rawOptions['a'] ?? $rawOptions[0] ?? '')),
                trim((string) ($rawOptions['B'] ?? $rawOptions['b'] ?? $rawOptions[1] ?? '')),
                trim((string) ($rawOptions['C'] ?? $rawOptions['c'] ?? $rawOptions[2] ?? '')),
                trim((string) ($rawOptions['D'] ?? $rawOptions['d'] ?? $rawOptions[3] ?? '')),
            ];
        }

        if (in_array('', $normalized, true)) {
            return null;
        }

        $correctFromMarker = $this->extractCorrectFromOptionsMarker($normalized);
        $cleaned = array_map([$this, 'stripCorrectMarker'], $normalized);

        return [
            'options' => $cleaned,
            'correct_from_marker' => $correctFromMarker,
        ];
    }

    /** @param array<int, string> $options */
    private function hasDuplicateOptions(array $options): bool
    {
        $seen = [];

        foreach ($options as $option) {
            $normalized = preg_replace('/\s+/u', ' ', trim($option));
            $normalized = mb_strtolower((string) $normalized, 'UTF-8');

            if ($normalized === '') {
                continue;
            }

            if (isset($seen[$normalized])) {
                return true;
            }

            $seen[$normalized] = true;
        }

        return false;
    }

    private function fingerprintQuestion(string $question): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($question));
        $normalized = mb_strtolower((string) $normalized, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', (string) $normalized) ?? $normalized;
        $normalized = trim((string) $normalized);

        return $normalized !== '' ? $normalized : md5($question);
    }

    private function prepareDocumentContent(string $documentContent): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $documentContent);
        $normalized = preg_replace('/[ \t]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace("/\n{3,}/u", "\n\n", $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            throw new ValidationException([
                'document_content' => 'Nội dung tài liệu trống sau khi tiền xử lý.',
            ]);
        }

        return $this->buildContextWindow($normalized);
    }

    private function buildContextWindow(string $content): string
    {
        $maxChars = max(2500, min(50000, (int) Env::get('AI_DOCUMENT_CONTEXT_CHARS', '9000')));
        $totalChars = mb_strlen($content, 'UTF-8');

        if ($totalChars <= $maxChars) {
            return $content;
        }

        $sliceLength = max(1200, intdiv($maxChars, 3) - 100);
        $middleStart = max(0, intdiv($totalChars, 2) - intdiv($sliceLength, 2));
        $endStart = max(0, $totalChars - $sliceLength);

        $segments = [
            'DAU' => mb_substr($content, 0, $sliceLength, 'UTF-8'),
            'GIUA' => mb_substr($content, $middleStart, $sliceLength, 'UTF-8'),
            'CUOI' => mb_substr($content, $endStart, $sliceLength, 'UTF-8'),
        ];

        $result = [];
        $seen = [];

        foreach ($segments as $label => $segment) {
            $safeSegment = trim($segment);
            if ($safeSegment === '') {
                continue;
            }

            $hash = md5($safeSegment);
            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $result[] = "[{$label}_SEGMENT]\n{$safeSegment}";
        }

        return implode("\n\n", $result);
    }

    private function normalizeDifficulty(string $difficulty): string
    {
        $normalized = strtolower(trim($difficulty));

        return in_array($normalized, ['easy', 'medium', 'hard'], true)
            ? $normalized
            : 'medium';
    }

    /** @param array<int, string> $options */
    private function extractCorrectFromOptionsMarker(array $options): string
    {
        $indexes = [];

        foreach ($options as $index => $value) {
            if ($this->hasCorrectMarker($value)) {
                $indexes[] = $index;
            }
        }

        if (count($indexes) !== 1) {
            return '';
        }

        return self::OPTION_LABELS[$indexes[0]] ?? '';
    }

    private function stripCorrectMarker(string $value): string
    {
        $clean = preg_replace('/\(\s*(?:đúng|dung)\s*\)/iu', '', $value) ?? $value;
        $clean = preg_replace('/^\s*\*\s*(.*?)\s*\*\s*$/u', '$1', $clean) ?? $clean;
        $clean = preg_replace('/^\s*\*\s*/u', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*\*\s*$/u', '', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/u', ' ', trim($clean)) ?? trim($clean);

        return $clean;
    }

    /** @param array<mixed> $items */
    private function isListArray(array $items): bool
    {
        if ($items === []) {
            return true;
        }

        return array_keys($items) === range(0, count($items) - 1);
    }

    /** @return array<int, array<string, mixed>> */
    private function parseQuestionsFromDocument(string $documentContent): array
    {
        $normalizedContent = $this->normalizeRawDocumentContent($documentContent);
        if ($normalizedContent === '') {
            return [];
        }

        $regexParsed = $this->parseQuizWithRegex($normalizedContent);
        if ($regexParsed !== []) {
            return $regexParsed;
        }

        $lines = $this->splitLinesWithInlineOptions($normalizedContent);
        if ($lines === []) {
            return [];
        }

        $result = [];
        $state = $this->newParseState();

        foreach ($lines as $line) {
            $isQuestionStart = $this->isQuestionStartLine($line);
            if ($isQuestionStart && ($state['question_lines'] !== [] || $state['options'] !== [])) {
                if ($state['options'] !== []) {
                    $this->pushParsedQuestion($result, $state);
                }
                $state = $this->newParseState();
            }

            $correctAnswerFromLine = $this->parseCorrectAnswerLine($line);
            if ($correctAnswerFromLine !== '') {
                if ($state['question_lines'] !== []) {
                    $state['explicit_correct'] = $correctAnswerFromLine;
                }
                continue;
            }

            $allowNumericOption = $state['question_lines'] !== [] && !$isQuestionStart;
            $parsedOption = $this->parseOptionLine($line, $allowNumericOption);
            if ($parsedOption !== null) {
                if ($state['question_lines'] === []) {
                    continue;
                }

                $optionLetter = (string) $parsedOption['letter'];
                if (isset($state['options'][$optionLetter])) {
                    $this->pushParsedQuestion($result, $state);
                    $state = $this->newParseState();
                    continue;
                }

                $state['options'][$optionLetter] = (string) $parsedOption['text'];
                if ($state['explicit_correct'] === '' && $parsedOption['embedded_correct'] !== '') {
                    $state['explicit_correct'] = (string) $parsedOption['embedded_correct'];
                }
                continue;
            }

            if ($state['options'] !== []) {
                if ($this->hasAllOptions($state['options'])) {
                    $this->pushParsedQuestion($result, $state);
                    $state = $this->newParseState();
                } else {
                    $lastOptionKey = $this->lastOptionKey($state['options']);
                    if ($lastOptionKey !== '') {
                        $state['options'][$lastOptionKey] = trim($state['options'][$lastOptionKey] . ' ' . $line);
                        continue;
                    }
                }
            }

            if ($state['options'] === [] && $state['question_lines'] === [] && $this->isSectionHeadingLine($line)) {
                continue;
            }

            $questionLine = $this->cleanupQuestionLine($line);
            if ($questionLine !== '') {
                $state['question_lines'][] = $questionLine;
                if ($isQuestionStart) {
                    $state['has_question_marker'] = true;
                }
            }
        }

        $this->pushParsedQuestion($result, $state);

        $explicitBlockQuestions = $this->parseQuestionsByExplicitMarkers($lines);

        return $this->mergeQuestionCollections($result, $explicitBlockQuestions);
    }

    /** @return array<int, array<string, mixed>> */
    private function parseQuizWithRegex(string $text): array
    {
        $questionPattern = '/(?:^|\n)\s*(?:câu|cau)\s*(\d+)\s*[:.)\/-]?\s*|(?:^|\n)\s*(\d{1,3})\s*[.\/]\s*/iu';
        $matched = preg_match_all($questionPattern, $text, $questionMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        if ($matched === false || $matched === 0) {
            return [];
        }

        $questions = [];
        $total = count($questionMatches);

        for ($index = 0; $index < $total; $index++) {
            $startOffset = (int) $questionMatches[$index][0][1];
            $endOffset = $index + 1 < $total
                ? (int) $questionMatches[$index + 1][0][1]
                : strlen($text);

            $block = trim(substr($text, $startOffset, max(0, $endOffset - $startOffset)));
            if ($block === '') {
                continue;
            }

            $block = preg_replace('/^\s*(?:(?:câu|cau)\s*\d+\s*[:.)\/-]?|\d{1,3}\s*[.\/])\s*/iu', '', $block) ?? $block;
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $optionPattern = '/(?:^|\n)\s*(\*)?\s*([ABCD])\s*[.)]\s*(.*?)(?=(?:\n\s*\*?\s*[ABCD]\s*[.)]\s*)|\z)/isu';
            $foundOptions = preg_match_all($optionPattern, $block, $optionMatches, PREG_SET_ORDER);
            if ($foundOptions === false || $foundOptions < 4) {
                continue;
            }

            $firstOptionPos = null;
            if (preg_match('/(?:^|\n)\s*\*?\s*[ABCD]\s*[.)]\s*/iu', $block, $firstOptionHit, PREG_OFFSET_CAPTURE) === 1) {
                $firstOptionPos = (int) $firstOptionHit[0][1];
            }

            $questionText = $firstOptionPos !== null ? substr($block, 0, $firstOptionPos) : $block;
            $questionText = trim((string) preg_replace('/\s+/u', ' ', $questionText));
            $questionText = trim($questionText, " \t\n\r\0\x0B*");
            if ($questionText === '') {
                continue;
            }

            $options = [];
            foreach ($optionMatches as $match) {
                $label = strtoupper((string) ($match[2] ?? ''));
                if (!in_array($label, self::OPTION_LABELS, true)) {
                    continue;
                }

                $rawOption = trim((string) ($match[3] ?? ''));
                $isCorrect = ((string) ($match[1] ?? '')) === '*' || preg_match('/\*\s*$/u', $rawOption) === 1;

                $rawOption = preg_replace('/^\s*\*\s*/u', '', $rawOption) ?? $rawOption;
                $rawOption = preg_replace('/\s*\*\s*$/u', '', $rawOption) ?? $rawOption;
                $rawOption = trim((string) preg_replace('/[ \t]+\n/u', "\n", $rawOption));
                $rawOption = trim((string) preg_replace('/\n{3,}/u', "\n\n", $rawOption));
                $rawOption = trim($rawOption, " \t\n\r\0\x0B*");

                $options[$label] = [
                    'text' => $rawOption,
                    'is_correct' => $isCorrect,
                ];
            }

            if (!$this->hasAllOptionLabels($options)) {
                continue;
            }

            $normalizedAnswers = [
                'A' => $options['A']['text'],
                'B' => $options['B']['text'],
                'C' => $options['C']['text'],
                'D' => $options['D']['text'],
            ];

            if (in_array('', $normalizedAnswers, true)) {
                continue;
            }

            if ($this->hasDuplicateOptions(array_values($normalizedAnswers))) {
                continue;
            }

            $correctAnswer = 'A';
            foreach (self::OPTION_LABELS as $label) {
                if (($options[$label]['is_correct'] ?? false) === true) {
                    $correctAnswer = $label;
                    break;
                }
            }

            $questions[] = [
                'question_content' => $questionText,
                'answers' => $normalizedAnswers,
                'correct_answer' => $correctAnswer,
            ];
        }

        return $questions;
    }

    /** @param array<string, array{text:string, is_correct:bool}> $options */
    private function hasAllOptionLabels(array $options): bool
    {
        foreach (self::OPTION_LABELS as $label) {
            if (!array_key_exists($label, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Secondary exhaustive parser:
     * - focuses on explicit question markers (Cau/Bai/Question + number)
     * - accepts partial/messy option layouts
     * - fills missing options with placeholders to avoid dropping questions
     *
     * @param array<int, string> $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseQuestionsByExplicitMarkers(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $blocks = [];
        $current = [];
        $currentNumber = 0;

        foreach ($lines as $line) {
            $markerNumber = $this->extractExplicitQuestionNumber($line);
            if ($markerNumber > 0) {
                if ($current !== []) {
                    $blocks[] = [
                        'number' => $currentNumber,
                        'lines' => $current,
                    ];
                }
                $current = [$line];
                $currentNumber = $markerNumber;
                continue;
            }

            if ($current !== []) {
                $current[] = $line;
            }
        }

        if ($current !== []) {
            $blocks[] = [
                'number' => $currentNumber,
                'lines' => $current,
            ];
        }

        if ($blocks === []) {
            return [];
        }

        $questions = [];
        $questionByNumber = [];
        $orderedNumbers = [];

        foreach ($blocks as $blockLines) {
            $linesInBlock = is_array($blockLines['lines'] ?? null) ? $blockLines['lines'] : [];
            $parsed = $this->buildQuestionFromExplicitBlock($linesInBlock);
            if ($parsed === null) {
                continue;
            }

            $number = (int) ($blockLines['number'] ?? 0);
            if ($number <= 0) {
                $questions[] = $parsed;
                continue;
            }

            if (!isset($questionByNumber[$number])) {
                $questionByNumber[$number] = $parsed;
                $orderedNumbers[] = $number;
                continue;
            }

            $oldScore = $this->scoreParsedQuestionQuality($questionByNumber[$number]);
            $newScore = $this->scoreParsedQuestionQuality($parsed);
            if ($newScore > $oldScore) {
                $questionByNumber[$number] = $parsed;
            }
        }

        foreach ($orderedNumbers as $number) {
            if (isset($questionByNumber[$number])) {
                $questions[] = $questionByNumber[$number];
            }
        }

        return $questions;
    }

    private function isExplicitQuestionMarkerLine(string $line): bool
    {
        return $this->extractExplicitQuestionNumber($line) > 0;
    }

    private function extractExplicitQuestionNumber(string $line): int
    {
        if (preg_match('/^\s*(?:c\S{0,4}u(?:\s*h\S{0,4}i)?|b\S{0,4}i|question|q)\s*(\d+)\b/iu', $line, $matches) !== 1) {
            return 0;
        }

        $value = (int) ($matches[1] ?? 0);

        return $value > 0 ? $value : 0;
    }

    /**
     * @param array<int, string> $blockLines
     * @return array<string, mixed>|null
     */
    private function buildQuestionFromExplicitBlock(array $blockLines): ?array
    {
        if ($blockLines === []) {
            return null;
        }

        $questionLines = [];
        $options = [];
        $explicitCorrect = '';

        foreach ($blockLines as $line) {
            $correctFromLine = $this->parseCorrectAnswerLine($line);
            if ($correctFromLine !== '') {
                $explicitCorrect = $correctFromLine;
                continue;
            }

            $parsedOption = $this->parseOptionLine($line, true);
            if ($parsedOption !== null) {
                $optionLetter = (string) $parsedOption['letter'];
                $optionText = trim((string) $parsedOption['text']);

                if ($optionText === '') {
                    $optionText = "[Bo sung dap an {$optionLetter}]";
                }

                if (isset($options[$optionLetter])) {
                    $options[$optionLetter] = trim($options[$optionLetter] . ' ' . $optionText);
                } else {
                    $options[$optionLetter] = $optionText;
                }

                if ($explicitCorrect === '' && $parsedOption['embedded_correct'] !== '') {
                    $explicitCorrect = (string) $parsedOption['embedded_correct'];
                }
                continue;
            }

            if ($options !== []) {
                $lastOption = $this->lastOptionKey($options);
                if ($lastOption !== '') {
                    $options[$lastOption] = trim($options[$lastOption] . ' ' . $line);
                    continue;
                }
            }

            $questionLine = $this->cleanupQuestionLine($line);
            if ($questionLine !== '') {
                $questionLines[] = $questionLine;
            }
        }

        if (count($options) < 3) {
            return null;
        }

        $questionContent = trim(preg_replace('/\s+/u', ' ', implode(' ', $questionLines)) ?? implode(' ', $questionLines));
        if ($questionContent === '') {
            return null;
        }

        if (!$this->isLikelyQuestionContent($questionContent, false)) {
            return null;
        }

        $rawOptions = [
            $this->normalizeOptionValue((string) ($options['A'] ?? '')),
            $this->normalizeOptionValue((string) ($options['B'] ?? '')),
            $this->normalizeOptionValue((string) ($options['C'] ?? '')),
            $this->normalizeOptionValue((string) ($options['D'] ?? '')),
        ];
        $correctFromMarker = $this->extractCorrectFromOptionsMarker($rawOptions);
        $cleanedOptions = array_map([$this, 'stripCorrectMarker'], $rawOptions);

        foreach ($cleanedOptions as $index => $value) {
            if (trim($value) !== '') {
                continue;
            }
            $label = self::OPTION_LABELS[$index] ?? 'A';
            $cleanedOptions[$index] = "[Bo sung dap an {$label}]";
        }

        $cleanedOptions = $this->ensureDistinctOptions($cleanedOptions);

        $correctAnswer = strtoupper(trim($explicitCorrect));
        if (!in_array($correctAnswer, self::OPTION_LABELS, true)) {
            $correctAnswer = $correctFromMarker !== '' ? $correctFromMarker : 'A';
        }

        return [
            'question_content' => $questionContent,
            'answers' => [
                'A' => $cleanedOptions[0],
                'B' => $cleanedOptions[1],
                'C' => $cleanedOptions[2],
                'D' => $cleanedOptions[3],
            ],
            'correct_answer' => $correctAnswer,
        ];
    }

    /**
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function ensureDistinctOptions(array $options): array
    {
        $seen = [];

        foreach ($options as $index => $option) {
            $base = trim($option);
            if ($base === '') {
                $base = '[Bo sung dap an]';
            }

            $candidate = $base;
            $counter = 2;
            while (isset($seen[mb_strtolower($candidate, 'UTF-8')])) {
                $candidate = $base . ' (' . $counter . ')';
                $counter++;
            }

            $seen[mb_strtolower($candidate, 'UTF-8')] = true;
            $options[$index] = $candidate;
        }

        return $options;
    }

    /** @param array<string, mixed> $question */
    private function scoreParsedQuestionQuality(array $question): int
    {
        $score = 0;
        $questionContent = trim((string) ($question['question_content'] ?? ''));
        if ($questionContent !== '') {
            $score += min(200, mb_strlen($questionContent, 'UTF-8'));
        }

        $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
        foreach (self::OPTION_LABELS as $label) {
            $option = trim((string) ($answers[$label] ?? ''));
            if ($option === '') {
                continue;
            }

            $score += 20;
            if (!str_starts_with($option, '[Bo sung dap an')) {
                $score += min(80, mb_strlen($option, 'UTF-8'));
            }
        }

        $correct = strtoupper(trim((string) ($question['correct_answer'] ?? '')));
        if (in_array($correct, self::OPTION_LABELS, true)) {
            $score += 10;
        }

        return $score;
    }

    /**
     * @param array<int, array<string, mixed>> $primary
     * @param array<int, array<string, mixed>> $secondary
     * @return array<int, array<string, mixed>>
     */
    private function mergeQuestionCollections(array $primary, array $secondary): array
    {
        if ($secondary === []) {
            return $primary;
        }

        $merged = $primary;
        $seen = [];

        foreach ($merged as $question) {
            if (!is_array($question)) {
                continue;
            }
            $seen[$this->fingerprintQuestion((string) ($question['question_content'] ?? ''))] = true;
        }

        foreach ($secondary as $question) {
            if (!is_array($question)) {
                continue;
            }

            $fingerprint = $this->fingerprintQuestion((string) ($question['question_content'] ?? ''));
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $merged[] = $question;
        }

        return $merged;
    }

    private function normalizeRawDocumentContent(string $content): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $normalized = preg_replace('/[ \t]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace("/\n{3,}/u", "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
    }

    /** @return array<int, string> */
    private function splitLinesWithInlineOptions(string $content): array
    {
        $rawLines = preg_split('/\n/u', $content);
        if (!is_array($rawLines)) {
            return [];
        }

        $result = [];

        foreach ($rawLines as $rawLine) {
            $line = trim((string) $rawLine);
            if ($line === '') {
                continue;
            }

            foreach ($this->explodeInlineOptions($line) as $segment) {
                $segment = trim((string) $segment);
                if ($segment !== '') {
                    $result[] = $segment;
                }
            }
        }

        return $result;
    }

    /** @return array<int, string> */
    private function explodeInlineOptions(string $line): array
    {
        $matches = [];
        $count = preg_match_all('/(?<![\p{L}\p{N}])([ABCD])[\.\):\/\]-]\s*(?=\S)/u', $line, $matches, PREG_OFFSET_CAPTURE);

        if ($count === false || $count < 2) {
            return [$line];
        }

        $segments = [];
        $firstOffset = (int) $matches[0][0][1];
        $questionPrefix = trim(substr($line, 0, $firstOffset));

        if ($questionPrefix !== '') {
            $segments[] = $questionPrefix;
        }

        for ($index = 0; $index < $count; $index++) {
            $token = (string) $matches[0][$index][0];
            $letter = strtoupper((string) $matches[1][$index][0]);

            $valueStart = (int) $matches[0][$index][1] + strlen($token);
            $valueEnd = $index + 1 < $count
                ? (int) $matches[0][$index + 1][1]
                : strlen($line);

            $valueLength = max(0, $valueEnd - $valueStart);
            $value = trim(substr($line, $valueStart, $valueLength));
            $segments[] = $letter . '. ' . $value;
        }

        return $segments === [] ? [$line] : $segments;
    }

    /**
     * @return array{letter:string, text:string, embedded_correct:string}|null
     */
    private function parseOptionLine(string $line, bool $allowNumeric): ?array
    {
        if (preg_match('/^\s*(?:\(?\d+\)?\s+)?([ABCD])\s*[\.\):\/\]-]\s*(.*)$/iu', $line, $matches) === 1) {
            $payload = $this->extractOptionPayload((string) $matches[2]);

            return [
                'letter' => strtoupper((string) $matches[1]),
                'text' => $payload['text'],
                'embedded_correct' => $payload['embedded_correct'],
            ];
        }

        if ($allowNumeric && preg_match('/^\s*\(?([1-4])\)?\s*[\.\):\/\]-]\s*(.*)$/u', $line, $matches) === 1) {
            $mapped = $this->normalizeAnswerTokenToLetter((string) $matches[1]);
            if ($mapped === '') {
                return null;
            }

            $payload = $this->extractOptionPayload((string) $matches[2]);

            return [
                'letter' => $mapped,
                'text' => $payload['text'],
                'embedded_correct' => $payload['embedded_correct'],
            ];
        }

        return null;
    }

    private function parseCorrectAnswerLine(string $line): string
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return '';
        }

        // Prevent consuming option lines that also contain "dap an/answer" at the tail,
        // e.g. "D. ... Dap an: D."
        if (preg_match('/^\s*(?:\(?\d+\)?\s+)?[ABCD]\s*[\.\):\/\]-]/iu', $trimmed) === 1) {
            return '';
        }

        if (preg_match('/(?:answer|ans|da|dap\s*an|d\S{0,4}p\s*a\S{0,4}n)(?:\s*d\S{0,4}ng)?(?:\s*(?:cho|cua)\s*(?:c\S{0,4}u)?\s*\d+)?\s*(?:la|is)?\s*[:=\->]*\s*(?:c\S{0,4}u\s*)?([ABCD1-4])\b/iu', $trimmed, $directMatch) === 1) {
            return $this->normalizeAnswerTokenToLetter((string) ($directMatch[1] ?? ''));
        }

        $hasAnswerPrefix = preg_match('/^(?:answer|ans|da|dap\s*an|d\S{0,4}p\s*a\S{0,4}n)(?:\s*d\S{0,4}ng)?/iu', $trimmed) === 1;
        $hasAnswerSeparator = preg_match('/(?:[:=\->]|->)\s*(?:c\S{0,4}u\s*)?[ABCD1-4]/iu', $trimmed) === 1;

        if (!$hasAnswerPrefix && !$hasAnswerSeparator) {
            return '';
        }

        if (preg_match('/(?:[:=\->]|->)\s*(?:c\S{0,4}u\s*)?([ABCD1-4])(?:\s*[\.\,\;\:\)\]\}\!\?])*$/iu', $trimmed, $tokenFromSeparator) === 1) {
            return $this->normalizeAnswerTokenToLetter((string) ($tokenFromSeparator[1] ?? ''));
        }

        if (preg_match('/(?:c\S{0,4}u\s*)?([ABCD])(?:\s*[\.\,\;\:\)\]\}\!\?])*$/iu', $trimmed, $tokenFromTail) === 1) {
            return $this->normalizeAnswerTokenToLetter((string) ($tokenFromTail[1] ?? ''));
        }

        return '';
    }

    private function cleanupQuestionLine(string $line): string
    {
        $clean = trim($line);
        $clean = preg_replace('/^\s*(?:c\S{0,4}u(?:\s*h\S{0,4}i)?|b\S{0,4}i(?:\s*t\S{0,4}n)?|question|q)\s*\d+\s*(?:[:\.\)\-\]]+\s*|\s+)/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/^\s*\(?\d+\)?\s*(?:[:\.\)\-\]]+\s*|\s+)(?=(?:giai|tim|cho|nghi\S{0,4}m|what|which|find|solve|determine|calculate)\b)/iu', '', $clean) ?? $clean;

        return trim($clean);
    }

    /** @return array{question_lines:array<int,string>, options:array<string,string>, explicit_correct:string, has_question_marker:bool} */
    private function newParseState(): array
    {
        return [
            'question_lines' => [],
            'options' => [],
            'explicit_correct' => '',
            'has_question_marker' => false,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array{question_lines:array<int,string>, options:array<string,string>, explicit_correct:string, has_question_marker:bool} $state
     */
    private function pushParsedQuestion(array &$questions, array $state): void
    {
        $parsed = $this->finalizeParsedQuestion($state);
        if ($parsed === null) {
            return;
        }

        $questions[] = $parsed;
    }

    /**
     * @param array{question_lines:array<int,string>, options:array<string,string>, explicit_correct:string, has_question_marker:bool} $state
     * @return array<string,mixed>|null
     */
    private function finalizeParsedQuestion(array $state): ?array
    {
        if ($state['question_lines'] === [] || !$this->hasAllOptions($state['options'])) {
            return null;
        }

        $questionContent = implode(' ', $state['question_lines']);
        $questionContent = preg_replace('/\s+/u', ' ', trim($questionContent)) ?? trim($questionContent);

        if ($questionContent === '') {
            return null;
        }

        $hasQuestionMarker = (bool) ($state['has_question_marker'] ?? false);
        if (!$this->isLikelyQuestionContent($questionContent, $hasQuestionMarker)) {
            return null;
        }

        $rawOptions = [
            $this->normalizeOptionValue((string) ($state['options']['A'] ?? '')),
            $this->normalizeOptionValue((string) ($state['options']['B'] ?? '')),
            $this->normalizeOptionValue((string) ($state['options']['C'] ?? '')),
            $this->normalizeOptionValue((string) ($state['options']['D'] ?? '')),
        ];

        $correctFromMarker = $this->extractCorrectFromOptionsMarker($rawOptions);
        $cleanedOptions = array_map([$this, 'stripCorrectMarker'], $rawOptions);
        if (in_array('', $cleanedOptions, true)) {
            if (!$this->shouldUseOptionPlaceholders($questionContent, $hasQuestionMarker, $cleanedOptions)) {
                return null;
            }
            $cleanedOptions = $this->fillMissingOptionPlaceholders($cleanedOptions);
        }

        if ($this->hasDuplicateOptions($cleanedOptions)) {
            return null;
        }

        $correctAnswer = strtoupper(trim($state['explicit_correct']));
        if (!in_array($correctAnswer, self::OPTION_LABELS, true)) {
            $correctAnswer = $correctFromMarker !== '' ? $correctFromMarker : 'A';
        }

        return [
            'question_content' => $questionContent,
            'answers' => [
                'A' => $cleanedOptions[0],
                'B' => $cleanedOptions[1],
                'C' => $cleanedOptions[2],
                'D' => $cleanedOptions[3],
            ],
            'correct_answer' => $correctAnswer,
        ];
    }

    /** @param array<string,string> $options */
    private function hasAllOptions(array $options): bool
    {
        foreach (self::OPTION_LABELS as $label) {
            if (!array_key_exists($label, $options)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string,string> $options */
    private function lastOptionKey(array $options): string
    {
        $key = array_key_last($options);

        return is_string($key) ? $key : '';
    }

    private function normalizeOptionValue(string $option): string
    {
        $normalized = trim($option);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^[\.\,\;\:\-\_]+$/u', $normalized) === 1) {
            return '';
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function fillMissingOptionPlaceholders(array $options): array
    {
        foreach ($options as $index => $value) {
            if (trim($value) !== '') {
                continue;
            }

            $label = self::OPTION_LABELS[$index] ?? 'A';
            $options[$index] = "[Bo sung dap an {$label}]";
        }

        return $options;
    }

    private function isQuestionStartLine(string $line): bool
    {
        if (preg_match('/^\s*(?:c\S{0,4}u(?:\s*h\S{0,4}i)?|b\S{0,4}i(?:\s*t\S{0,4}n)?|question|q)\s*\d+\s*[:\.\)\-\]]/iu', $line) === 1) {
            return true;
        }

        if (preg_match('/^\s*(?:c\S{0,4}u(?:\s*h\S{0,4}i)?|b\S{0,4}i(?:\s*t\S{0,4}n)?|question|q)\s*\d+\s+(?:giai|tim|cho|nghi\S{0,4}m|what|which|find|solve|determine|calculate)\b/iu', $line) === 1) {
            return true;
        }

        return preg_match('/^\s*\(?\d+\)?\s*(?:[:\.\)\-\]]\s*|\s+)(?:giai|tim|cho|nghi\S{0,4}m|what|which|find|solve|determine|calculate)\b/iu', $line) === 1;
    }

    /** @return array{text:string, embedded_correct:string} */
    private function extractOptionPayload(string $rawText): array
    {
        $trimmed = trim($rawText);
        if ($trimmed === '') {
            return [
                'text' => '',
                'embedded_correct' => '',
            ];
        }

        $trimmed = $this->trimInlineTrailingOptionMarkers($trimmed);

        if (preg_match(
            '/^(.*?)(?:[\\|;,\\-]\s*)?(?:answer|ans|đáp\s*án|dap\s*an|da|d\S{0,4}p\s*a\S{0,4}n)(?:\s*d\S{0,4}ng)?\s*(?:la|là|is)?\s*[:\-]?\s*(?:c\S{0,4}u)?\s*([ABCD1-4])(?:\s*[\.\,\;\:\)\]\}\!\?])*\s*$/iu',
            $trimmed,
            $matches
        ) === 1) {
            $cleanText = trim((string) ($matches[1] ?? ''));
            if ($cleanText === '') {
                $cleanText = $trimmed;
            }

            return [
                'text' => $cleanText,
                'embedded_correct' => $this->normalizeAnswerTokenToLetter((string) ($matches[2] ?? '')),
            ];
        }

        return [
            'text' => $trimmed,
            'embedded_correct' => '',
        ];
    }

    private function hasCorrectMarker(string $value): bool
    {
        if (preg_match('/\(\s*(?:đúng|dung)\s*\)/iu', $value) === 1) {
            return true;
        }

        return preg_match('/^\s*\*/u', $value) === 1 || preg_match('/\*\s*$/u', $value) === 1;
    }

    private function trimInlineTrailingOptionMarkers(string $text): string
    {
        if (preg_match('/^(.*?)\s+(?=[ABCD]\s*[\.\):\/\]-]\s*\S).+$/u', $text, $matches) === 1) {
            $candidate = trim((string) ($matches[1] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $text;
    }

    private function normalizeAnswerTokenToLetter(string $token): string
    {
        $normalized = strtoupper(trim($token));
        if (in_array($normalized, self::OPTION_LABELS, true)) {
            return $normalized;
        }

        if (in_array($normalized, ['1', '2', '3', '4'], true)) {
            $index = (int) $normalized - 1;

            return self::OPTION_LABELS[$index] ?? '';
        }

        return '';
    }

    private function isLikelyQuestionContent(string $questionContent, bool $hasQuestionMarker): bool
    {
        if ($hasQuestionMarker) {
            return true;
        }

        if (preg_match('/\b(?:l\S{0,4}i\s*giai|ch\S{0,4}n\s*[ABCD1-4])\b/iu', $questionContent) === 1) {
            return false;
        }

        if (mb_strpos($questionContent, '?', 0, 'UTF-8') !== false) {
            return true;
        }

        if (preg_match('/\b(?:giai|tim|cho|nghi\S{0,4}m|what|which|find|solve|determine|calculate)\b/iu', $questionContent) === 1) {
            return true;
        }

        if (preg_match('/\b(?:nguy\S{0,4}n\s*h\S{0,4}m|t\S{0,4}ch\s*ph\S{0,4}n|h\S{0,4}m\s*s\S{0,4}|ph\S{0,4}ng\s*tr\S{0,4}nh|m\S{0,4}t\s*ph\S{0,4}ng|di\S{0,4}n\s*t\S{0,4}ch|th\S{0,4}\s*t\S{0,4}ch|vect\S{0,4}\s*ph\S{0,4}p\s*tuy\S{0,4}n|kh\S{0,4}ng\s*d\S{0,4}ng|m\S{0,4}nh\s*d\S{0,4}|bao\s*nhieu|c\S{0,4}ng\s*th\S{0,4}c)\b/iu', $questionContent) === 1) {
            return true;
        }

        return preg_match('/\b(?:la|l\S{0,2}|bang|b\S{0,4}ng|dung|d\S{0,4}ng)\s*[:\?]?$/iu', trim($questionContent)) === 1;
    }

    /**
     * @param array<int, string> $options
     */
    private function shouldUseOptionPlaceholders(string $questionContent, bool $hasQuestionMarker, array $options): bool
    {
        $emptyCount = 0;
        foreach ($options as $value) {
            if (trim($value) === '') {
                $emptyCount++;
            }
        }

        if ($emptyCount < 2) {
            return false;
        }

        if ($hasQuestionMarker) {
            return true;
        }

        return preg_match('/\b(?:nguy\S{0,4}n\s*h\S{0,4}m|t\S{0,4}ch\s*ph\S{0,4}n|h\S{0,4}m\s*s\S{0,4}|ph\S{0,4}ng\s*tr\S{0,4}nh|m\S{0,4}t\s*ph\S{0,4}ng|di\S{0,4}n\s*t\S{0,4}ch|th\S{0,4}\s*t\S{0,4}ch|kh\S{0,4}ng\s*d\S{0,4}ng|m\S{0,4}nh\s*d\S{0,4}|bao\s*nhieu)\b/iu', $questionContent) === 1;
    }

    private function isSectionHeadingLine(string $line): bool
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return true;
        }

        if (preg_match('/\b(?:gk\s*\d+|o\S{0,3}n\s*gk)\b/iu', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^\s*(?:ph\S{0,4}n|ch\S{0,4}\s*d\S{0,4}|m\S{0,4}t\s*ph\S{0,4}ng|t\S{0,4}ch\s*ph\S{0,4}n|u\S{0,4}ng\s*d\S{0,4}ng|on|[0-9]+\.)\b/iu', $trimmed) === 1) {
            if (preg_match('/\b(?:cau|c\S{0,4}u|question)\s*\d+\b/iu', $trimmed) === 1) {
                return false;
            }

            if (preg_match('/\b(?:cho|giai|tim|nghi\S{0,4}m|what|which|find|solve)\b/iu', $trimmed) === 1) {
                return false;
            }

            return true;
        }

        return false;
    }
}
