<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\QuizRichContent;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

final class QuizDocxExportService
{
    /**
     * @param array<string, mixed> $quiz
     * @param array<int, array<string, mixed>> $questions
     */
    public function build(array $quiz, array $questions, bool $withAnswers): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText('BÀI KIỂM TRA', ['bold' => true, 'size' => 18]);
        $section->addTextBreak(1);
        $section->addText('Bài kiểm tra: ' . (string) ($quiz['title'] ?? ''));
        $section->addText('Tài liệu: ' . (string) ($quiz['document_title'] ?? 'Nhập trực tiếp'));
        $section->addText('Tổng số câu: ' . (string) count($questions));
        $section->addText('Ngày xuất: ' . date('Y-m-d H:i:s'));
        $section->addTextBreak(2);

        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $pos = (string) ($question['position'] ?? '');
            $qText = QuizRichContent::toPlainTextForExport((string) ($question['question_content'] ?? ''));
            $section->addText('Câu ' . $pos . ': ' . $qText, ['bold' => true]);
            $section->addText('A. ' . QuizRichContent::toPlainTextForExport((string) ($question['answer_a'] ?? '')));
            $section->addText('B. ' . QuizRichContent::toPlainTextForExport((string) ($question['answer_b'] ?? '')));
            $section->addText('C. ' . QuizRichContent::toPlainTextForExport((string) ($question['answer_c'] ?? '')));
            $section->addText('D. ' . QuizRichContent::toPlainTextForExport((string) ($question['answer_d'] ?? '')));

            if ($withAnswers) {
                $section->addText('Đáp án đúng: ' . (string) ($question['correct_answer'] ?? ''));
            }

            $section->addTextBreak(1);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'lq_docx_');
        if ($tmp === false) {
            throw new RuntimeException('Không tạo được file tạm để xuất DOCX.');
        }

        try {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tmp);
            $binary = file_get_contents($tmp);
            if ($binary === false || $binary === '') {
                throw new RuntimeException('Không đọc được nội dung file DOCX đã tạo.');
            }
        } finally {
            @unlink($tmp);
        }

        return $binary;
    }
}
