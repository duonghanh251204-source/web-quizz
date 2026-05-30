<?php /** @var array<string,mixed> $submission */ ?>
<?php
$score = (int) $submission['score'];
$totalQuestions = (int) $submission['total_questions'];
$totalCorrect = (int) $submission['total_correct'];
$totalIncorrect = max(0, $totalQuestions - $totalCorrect);
$isPass = $score >= 60;
$quizId = (int) ($submission['quiz_id'] ?? 0);
?>
<section class="card">
    <div class="result-summary">
        <div class="score-ring" style="--score: <?= $score ?>;"><?= $score ?>%</div>

        <div>
            <p class="eyebrow">Kết quả bài làm</p>
            <h1 class="section-title">Bài nộp #<?= (int) $submission['id'] ?></h1>
            <p class="section-lead">
                Bài kiểm tra: <strong><?= htmlspecialchars((string) $submission['quiz_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                | Thí sinh: <strong><?= htmlspecialchars((string) $submission['user_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            </p>

            <div class="form-actions" style="margin-top: 10px;">
                <span class="badge <?= $isPass ? 'success' : 'danger' ?>"><?= $isPass ? 'Đạt' : 'Cần cải thiện' ?></span>
                <span class="badge">Đã nộp: <?= htmlspecialchars((string) $submission['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                <a href="/submissions" class="btn ghost small">Quay lại danh sách kết quả</a>
                <?php if ($quizId > 0): ?>
                    <a href="/quizzes/<?= $quizId ?>" class="btn ghost small">Chi tiết bài kiểm tra</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="result-kpis">
    <article class="kpi-panel">
        <p>Tổng số câu hỏi</p>
        <strong class="mono"><?= $totalQuestions ?></strong>
    </article>

    <article class="kpi-panel">
        <p>Số câu đúng</p>
        <strong class="mono"><?= $totalCorrect ?></strong>
    </article>

    <article class="kpi-panel">
        <p>Số câu sai</p>
        <strong class="mono"><?= $totalIncorrect ?></strong>
    </article>
</section>

<section class="card">
    <h2 class="section-title">Xem lại đáp án</h2>
    <p class="section-lead">Đáp án đúng được tô xanh lá. Đáp án bạn chọn được tô xanh dương.</p>

    <?php foreach ($answers as $index => $answer): ?>
        <?php
        $selectedAnswer = (string) ($answer['selected_answer'] ?? '');
        $correctAnswer = (string) $answer['correct_answer'];
        ?>
        <article class="answer-review">
            <div class="answer-head">
                <h3 style="margin: 0;">Câu <?= $index + 1 ?></h3>
                <?php if ((int) $answer['is_correct'] === 1): ?>
                    <span class="badge success">Đúng</span>
                <?php else: ?>
                    <span class="badge danger">Sai</span>
                <?php endif; ?>
            </div>

            <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $answer['question_content']) ?></div>

            <ul class="options">
                <?php foreach (['A', 'B', 'C', 'D'] as $key): ?>
                    <?php
                    $value = (string) ($answer['answer_' . strtolower($key)] ?? '');
                    $classes = ['option-item'];
                    if ($key === $correctAnswer) {
                        $classes[] = 'option-correct';
                    }
                    if ($selectedAnswer !== '' && $key === $selectedAnswer) {
                        $classes[] = 'option-selected';
                    }
                    ?>
                    <li class="<?= implode(' ', $classes) ?>">
                        <strong><?= $key ?>.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml($value) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="muted" style="margin-top: 10px;">
                Bạn chọn: <strong><?= htmlspecialchars($selectedAnswer !== '' ? $selectedAnswer : 'Chưa trả lời', ENT_QUOTES, 'UTF-8') ?></strong>
                | Đáp án đúng: <strong><?= htmlspecialchars($correctAnswer, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </article>
    <?php endforeach; ?>
</section>
