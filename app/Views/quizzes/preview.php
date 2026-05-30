<?php /** @var array<string,mixed> $draft */ ?>
<?php /** @var array<int, array<string,mixed>> $questions */ ?>
<?php /** @var array<int, array<string,mixed>> $suggestedQuestions */ ?>
<?php /** @var array<int, int> $selectedSuggestionIndexes */ ?>
<?php /** @var string $_csrf_token */ ?>
<?php
$title = (string) ($draft['title'] ?? 'Bài kiểm tra chưa đặt tên');
$documentTitle = (string) ($draft['document_title'] ?? '');
$generatedAt = (string) ($draft['generated_at'] ?? '');
?>

<section class="card">
    <div class="section-header">
        <div>
            <p class="eyebrow">Xem trước/Sửa</p>
            <h1 class="section-title">Rà soát câu hỏi đã tạo</h1>
            <p class="section-lead">
                Nguồn: <strong><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($generatedAt !== ''): ?>
                    | Thời điểm tạo: <span class="mono"><?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="form-actions">
            <a href="/quizzes/create" class="btn ghost small">Quay lại tạo đề</a>
            <form method="POST" action="/quizzes/preview/discard" class="inline-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn ghost small">Hủy bản nháp</button>
            </form>
        </div>
    </div>
</section>

<form method="POST" action="/quizzes/preview/save" data-preview-form>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="questions_payload" value="" data-preview-payload>

    <section class="card">
        <div class="form-grid">
            <label>
                Tiêu đề bài kiểm tra
                <input type="text" name="title" value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
        </div>
        <p class="section-lead rich-field-hint" style="margin-top: 8px;">
            <strong>Công thức / cực trị:</strong> gõ LaTeX trong <code>\( … \)</code> hoặc <code>\[ … \]</code>, ví dụ khoảng <code>\( (0;+\infty) \)</code>, cực tiểu <code>\(m=-2\)</code>, cực đại <code>\(M=3\)</code>.
            <strong>Ảnh đồ thị / bảng biến thiên:</strong> chỉ trong ô <strong>Câu hỏi</strong> — <strong>Ctrl+V</strong>, <strong>Chèn ảnh</strong>, hoặc kéo thả file. <strong>Đáp án A–D</strong> chỉ nhập chữ (không chèn ảnh).
        </p>
        <div class="form-actions" style="margin-top: 12px;">
            <button type="button" class="btn ghost" data-add-question>Tạo câu hỏi mới</button>
            <button type="button" class="btn ghost" data-shuffle-answers>Đảo đáp án</button>
        </div>
    </section>

    <section class="ai-question-list" data-preview-question-list>
        <?php foreach ($questions as $index => $question): ?>
            <?php
            $questionContent = (string) ($question['question_content'] ?? '');
            $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
            $answerA = (string) ($answers['A'] ?? '');
            $answerB = (string) ($answers['B'] ?? '');
            $answerC = (string) ($answers['C'] ?? '');
            $answerD = (string) ($answers['D'] ?? '');
            $correct = (string) ($question['correct_answer'] ?? 'A');
            $qSource = strtolower((string) ($question['source'] ?? 'extract'));
            if (!in_array($qSource, ['ai', 'extract', 'manual'], true)) {
                $qSource = 'extract';
            }
            ?>
            <article class="card ai-question-card" data-preview-question data-source="<?= htmlspecialchars($qSource, ENT_QUOTES, 'UTF-8') ?>">
                <div class="ai-question-head">
                    <p><strong data-question-number><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></strong> | TRẮC NGHIỆM</p>
                    <div class="ai-question-tools">
                        <span class="badge">1 điểm</span>
                        <button type="button" class="btn ghost small danger-outline" data-remove-question>Xóa câu hỏi</button>
                    </div>
                </div>

                <label class="ai-question-field">
                    Câu hỏi
                    <div data-rich-field class="rich-field-stack">
                        <textarea
                            name="questions[<?= $index ?>][question_content]"
                            rows="4"
                            required
                            data-preview-input="1"
                            data-field="question_content"
                            data-rich-paste="1"
                            data-question-live-preview="1"
                        ><?= $questionContent ?></textarea>
                        <div class="quiz-question-live-preview" data-question-img-preview hidden>
                            <p class="muted" style="margin:0 0 8px;">Xem trước ảnh (ô trên vẫn là mã — khi lưu, thí sinh sẽ thấy ảnh)</p>
                            <div data-question-img-preview-body class="quiz-rich-field"></div>
                        </div>
                        <div class="rich-image-row">
                            <button type="button" class="btn ghost small" data-rich-image-pick>Chèn ảnh</button>
                            <span class="muted rich-image-hint">Ctrl+V · kéo thả</span>
                            <input type="file" accept="image/*" hidden data-rich-image-input="1" aria-hidden="true">
                        </div>
                    </div>
                </label>

                <div class="ai-option-grid">
                    <label class="ai-option-row">
                        <input
                            type="radio"
                            name="questions[<?= $index ?>][correct_answer]"
                            value="A"
                            <?= $correct === 'A' ? 'checked' : '' ?>
                            data-preview-input="1"
                            data-field="correct_answer"
                        >
                        <span class="mono">A.</span>
                        <textarea
                            name="questions[<?= $index ?>][answers][A]"
                            rows="2"
                            required
                            data-preview-input="1"
                            data-field="answer"
                            data-option="A"
                        ><?= htmlspecialchars($answerA, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input
                            type="radio"
                            name="questions[<?= $index ?>][correct_answer]"
                            value="B"
                            <?= $correct === 'B' ? 'checked' : '' ?>
                            data-preview-input="1"
                            data-field="correct_answer"
                        >
                        <span class="mono">B.</span>
                        <textarea
                            name="questions[<?= $index ?>][answers][B]"
                            rows="2"
                            required
                            data-preview-input="1"
                            data-field="answer"
                            data-option="B"
                        ><?= htmlspecialchars($answerB, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input
                            type="radio"
                            name="questions[<?= $index ?>][correct_answer]"
                            value="C"
                            <?= $correct === 'C' ? 'checked' : '' ?>
                            data-preview-input="1"
                            data-field="correct_answer"
                        >
                        <span class="mono">C.</span>
                        <textarea
                            name="questions[<?= $index ?>][answers][C]"
                            rows="2"
                            required
                            data-preview-input="1"
                            data-field="answer"
                            data-option="C"
                        ><?= htmlspecialchars($answerC, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input
                            type="radio"
                            name="questions[<?= $index ?>][correct_answer]"
                            value="D"
                            <?= $correct === 'D' ? 'checked' : '' ?>
                            data-preview-input="1"
                            data-field="correct_answer"
                        >
                        <span class="mono">D.</span>
                        <textarea
                            name="questions[<?= $index ?>][answers][D]"
                            rows="2"
                            required
                            data-preview-input="1"
                            data-field="answer"
                            data-option="D"
                        ><?= htmlspecialchars($answerD, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <div class="form-actions">
            <button type="submit">Lưu bài kiểm tra</button>
            <a href="/quizzes/create" class="btn ghost">Quay lại tạo đề</a>
        </div>
    </section>
</form>
