<?php /** @var array<string,mixed> $quiz */ ?>
<?php
$totalQuestions = count($questions);
$estimatedMinutes = max(15, $totalQuestions * 2);
$quizId = (int) $quiz['id'];
?>
<section class="exam-shell" data-exam-root data-exam-minutes="<?= $estimatedMinutes ?>">
    <article class="card">
        <div class="exam-header">
            <div>
                <p class="eyebrow">Phiên làm bài</p>
                <h1 class="section-title"><?= htmlspecialchars((string) $quiz['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="section-lead">
                    Nguồn: <strong><?= htmlspecialchars((string) ($quiz['document_title'] ?? 'Nhập trực tiếp'), ENT_QUOTES, 'UTF-8') ?></strong>
                </p>
            </div>

            <div class="form-actions">
                <span class="timer-chip">Thời gian còn lại: <span data-exam-timer><?= str_pad((string) $estimatedMinutes, 2, '0', STR_PAD_LEFT) ?>:00</span></span>
                <button type="submit" form="exam_form">Nộp bài</button>
            </div>
        </div>

        <p class="muted" data-progress-label>0 / <?= $totalQuestions ?> đã trả lời</p>
        <div class="progress-track" aria-hidden="true"><span data-progress-fill></span></div>
    </article>

    <section class="exam-layout">
        <aside class="card navigator-card">
            <h2 class="section-title">Điều hướng câu hỏi</h2>
            <p class="section-lead">Nhảy đến bất kỳ câu nào. Màu xanh là đã trả lời.</p>

            <div class="navigator-grid">
                <?php foreach ($questions as $index => $question): ?>
                    <button type="button" class="nav-question <?= $index === 0 ? 'current' : '' ?>" data-nav-question="<?= (int) $question['id'] ?>">
                        <?= (int) $question['position'] ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="stack" style="margin-top: 12px;">
                <span class="badge success">Đã trả lời</span>
                <span class="badge">Chưa trả lời</span>
            </div>
        </aside>

        <article class="card exam-take-card">
            <form id="exam_form" method="POST" action="/quizzes/<?= $quizId ?>/submit" class="exam-form-ghost">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </form>

            <?php foreach ($questions as $question): ?>
                <?php
                $qid = (int) $question['id'];
                $qSource = strtolower((string) ($question['source'] ?? 'extract'));
                $isAi = $qSource === 'ai';
                ?>
                <article id="question-<?= $qid ?>" class="exam-question" data-question-id="<?= $qid ?>">
                    <div class="exam-question__head">
                        <h3>Câu <?= (int) $question['position'] ?></h3>
                        <?php if ($isAi): ?>
                            <span class="badge source-badge source-badge--ai" title="Câu hỏi do AI sinh từ tài liệu">Câu từ AI</span>
                        <?php endif; ?>
                    </div>
                    <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['question_content']) ?></div>

                    <?php if ($isAi): ?>
                        <p class="question-report-hint">Nếu phát hiện câu hỏi do AI tạo ra bị sai, hãy nhấn <strong>Báo lỗi</strong> — phản hồi sẽ được gửi tới quản trị viên.</p>
                    <?php else: ?>
                        <p class="question-report-hint">Phát hiện lỗi nội dung? Nhấn <strong>Báo lỗi</strong> để gửi phản hồi cho quản trị viên.</p>
                    <?php endif; ?>

                    <div class="question-report-box">
                        <details class="question-report-details">
                            <summary class="question-report-summary">Báo lỗi câu hỏi</summary>
                            <form method="POST" action="/questions/<?= $qid ?>/report" class="question-report-form">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="return" value="/quizzes/<?= $quizId ?>/take">
                                <div class="question-report-row">
                                    <label>
                                        Lý do
                                        <select name="reason" required class="question-report-reason">
                                            <option value="knowledge">Sai nội dung / kiến thức</option>
                                            <option value="format">Lỗi hiển thị, định dạng</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </label>
                                    <button type="submit" class="btn small">Gửi cho quản trị</button>
                                </div>
                            </form>
                        </details>
                    </div>

                    <ul class="options options-exam" role="list">
                        <?php
                        $options = [
                            ['key' => 'A', 'text' => (string) $question['answer_a']],
                            ['key' => 'B', 'text' => (string) $question['answer_b']],
                            ['key' => 'C', 'text' => (string) $question['answer_c']],
                            ['key' => 'D', 'text' => (string) $question['answer_d']],
                        ];
                        shuffle($options);
                        ?>
                        <?php foreach ($options as $option): ?>
                            <?php
                            $optionKey = (string) ($option['key'] ?? '');
                            $optionText = (string) ($option['text'] ?? '');
                            ?>
                            <li class="option-choice">
                                <label>
                                    <input
                                        type="radio"
                                        form="exam_form"
                                        name="answers[<?= $qid ?>]"
                                        value="<?= htmlspecialchars($optionKey, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                    <span><strong><?= htmlspecialchars($optionKey, ENT_QUOTES, 'UTF-8') ?>.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml($optionText) ?></span></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>

            <div class="form-actions exam-submit-row">
                <button type="submit" class="btn" form="exam_form">Nộp bài</button>
                <a href="/quizzes/<?= $quizId ?>" class="btn ghost">Quay lại chi tiết bài kiểm tra</a>
            </div>
        </article>
    </section>
</section>
