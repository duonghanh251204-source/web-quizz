<?php

declare(strict_types=1);

namespace App\Services;

final class SubmissionEvaluationService
{
    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, string> $submittedAnswers
     * @return array{score:int,total_questions:int,total_correct:int,answer_rows:array<int,array<string,mixed>>}
     */
    public function evaluate(array $questions, array $submittedAnswers): array
    {
        $totalQuestions = count($questions);
        $totalCorrect = 0;
        $answerRows = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $selected = strtoupper(trim((string) ($submittedAnswers[$questionId] ?? '')));
            $correct = strtoupper(trim((string) ($question['correct_answer'] ?? '')));
            $isCorrect = $selected !== '' && $selected === $correct;

            if ($isCorrect) {
                $totalCorrect++;
            }

            $answerRows[] = [
                'question_id' => $questionId,
                'selected_answer' => $selected,
                'is_correct' => $isCorrect,
            ];
        }

        $score = $totalQuestions === 0 ? 0 : (int) round(($totalCorrect / $totalQuestions) * 100);

        return [
            'score' => $score,
            'total_questions' => $totalQuestions,
            'total_correct' => $totalCorrect,
            'answer_rows' => $answerRows,
        ];
    }
}
