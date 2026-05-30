<?php /** @var array<int, array<string,mixed>> $questions */ ?>
<?php
$totalQuestions = count($questions);
$totalQuizzes = count($quizzes);
$selectedSource = $selectedSource ?? '';
$filterAction = $filterAction ?? '/questions';
$adminContext = !empty($adminContext);
?>
<?php if ($adminContext): ?>
    <?php include __DIR__ . '/../admin/_nav.php'; ?>
<?php endif; ?>

<section class="card qh-bank<?= $adminContext ? ' qh-bank--admin' : '' ?>">
    <header class="qh-bank__hero">
        <div class="qh-bank__hero-text">
            <?php if ($adminContext): ?>
                <p class="qh-bank__crumb"><a href="/admin/dashboard">Quản trị</a> / Ngân hàng câu hỏi</p>
            <?php else: ?>
                <p class="qh-bank__crumb">Ngân hàng</p>
            <?php endif; ?>
            <h1 class="qh-bank__title"><?= $adminContext ? 'Ngân hàng câu hỏi' : 'Quản lý câu hỏi' ?></h1>
            <?php if (!$adminContext): ?>
                <p class="qh-bank__tagline">Lọc theo đề thi và nguồn tạo câu.</p>
            <?php endif; ?>
        </div>
        <div class="qh-bank__stats">
            <span class="qh-stat"><strong><?= $totalQuestions ?></strong><span>câu</span></span>
            <span class="qh-stat"><strong><?= $totalQuizzes ?></strong><span>đề</span></span>
            <?php if (!$adminContext): ?>
                <a href="/questions/create<?= $selectedQuizId > 0 ? '?quiz_id=' . (int) $selectedQuizId : '' ?>" class="btn qh-bank__cta">Thêm câu</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="qh-bank__filters">
        <form method="GET" action="<?= htmlspecialchars($filterAction, ENT_QUOTES, 'UTF-8') ?>" class="qh-filter-form">
            <div class="qh-filter-field">
                <label for="qh_quiz">Đề thi</label>
                <select name="quiz_id" id="qh_quiz">
                    <option value="0">Tất cả đề</option>
                    <?php foreach ($quizzes as $quiz): ?>
                        <option value="<?= (int) $quiz['id'] ?>" <?= (int) $selectedQuizId === (int) $quiz['id'] ? 'selected' : '' ?>>
                            #<?= (int) $quiz['id'] ?> · <?= htmlspecialchars((string) $quiz['title'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="qh-filter-field">
                <label for="qh_source">Nguồn</label>
                <select name="source" id="qh_source">
                    <option value="" <?= $selectedSource === '' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="ai" <?= $selectedSource === 'ai' ? 'selected' : '' ?>>AI</option>
                    <option value="extract" <?= $selectedSource === 'extract' ? 'selected' : '' ?>>Trích xuất</option>
                </select>
            </div>
            <div class="qh-filter-actions">
                <button type="submit" class="btn">Áp dụng</button>
                <?php if ($selectedQuizId > 0 || $selectedSource !== ''): ?>
                    <a href="<?= htmlspecialchars($filterAction, ENT_QUOTES, 'UTF-8') ?>" class="btn ghost">Đặt lại</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="qh-bank__body">
        <?php if ($questions === []): ?>
            <div class="qh-empty">
                <p class="qh-empty__title">Không có câu hỏi</p>
                <p class="qh-empty__hint">Thử đổi bộ lọc hoặc chọn đề khác.</p>
            </div>
        <?php else: ?>
            <div class="qh-table-scroll">
                <table class="qh-table">
                    <thead>
                    <tr>
                        <th class="qh-th-id">ID</th>
                        <th>Đề thi</th>
                        <th class="qh-th-src">Nguồn</th>
                        <th class="qh-th-num">#</th>
                        <th>Nội dung</th>
                        <th class="qh-th-ans">Đúng</th>
                        <th class="qh-th-act"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($questions as $question): ?>
                        <?php
                        $src = (string) ($question['source'] ?? 'extract');
                        $srcLabel = match ($src) {
                            'ai' => 'AI',
                            'extract' => 'Trích xuất',
                            default => 'Khác',
                        };
                        $srcClass = in_array($src, ['ai', 'extract'], true) ? $src : 'other';
                        ?>
                        <tr class="qh-tr">
                            <td class="qh-td-id mono">#<?= (int) $question['id'] ?></td>
                            <td class="qh-td-quiz" title="<?= htmlspecialchars((string) $question['quiz_title'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) $question['quiz_title'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <span class="qh-src qh-src--<?= htmlspecialchars($srcClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($srcLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="qh-td-num"><?= (int) $question['position'] ?></td>
                            <td class="qh-td-q"><?= htmlspecialchars(\App\Support\QuizRichContent::plainTextPreview((string) $question['question_content'], 160), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="qh-ans"><?= htmlspecialchars((string) $question['correct_answer'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="qh-td-actions">
                                <div class="qh-actions">
                                    <button
                                        type="button"
                                        class="btn ghost small qh-btn-expand"
                                        data-expand-target="question-extra-<?= (int) $question['id'] ?>"
                                        aria-expanded="false"
                                    >Chi tiết</button>
                                    <a href="/questions/<?= (int) $question['id'] ?>/edit" class="btn ghost small">Sửa</a>
                                    <form method="POST" action="/questions/<?= (int) $question['id'] ?>/delete" class="inline-form" onsubmit="return confirm('Xóa câu hỏi này?');">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn danger small" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="question-extra-<?= (int) $question['id'] ?>" class="question-expand-row qh-expand-row" hidden>
                            <td colspan="7">
                                <div class="question-expand-panel qh-expand-panel">
                                    <div class="question-answer-grid">
                                        <article class="question-answer-item <?= (string) $question['correct_answer'] === 'A' ? 'is-correct' : '' ?>">
                                            <h4>A</h4>
                                            <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_a']) ?></div>
                                        </article>
                                        <article class="question-answer-item <?= (string) $question['correct_answer'] === 'B' ? 'is-correct' : '' ?>">
                                            <h4>B</h4>
                                            <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_b']) ?></div>
                                        </article>
                                        <article class="question-answer-item <?= (string) $question['correct_answer'] === 'C' ? 'is-correct' : '' ?>">
                                            <h4>C</h4>
                                            <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_c']) ?></div>
                                        </article>
                                        <article class="question-answer-item <?= (string) $question['correct_answer'] === 'D' ? 'is-correct' : '' ?>">
                                            <h4>D</h4>
                                            <div class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_d']) ?></div>
                                        </article>
                                    </div>

                                    <form method="POST" action="/questions/<?= (int) $question['id'] ?>/correct" class="expand-inline-form qh-correct-form">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <label>
                                            Đáp án đúng
                                            <select name="correct_answer" required>
                                                <option value="A" <?= (string) $question['correct_answer'] === 'A' ? 'selected' : '' ?>>A</option>
                                                <option value="B" <?= (string) $question['correct_answer'] === 'B' ? 'selected' : '' ?>>B</option>
                                                <option value="C" <?= (string) $question['correct_answer'] === 'C' ? 'selected' : '' ?>>C</option>
                                                <option value="D" <?= (string) $question['correct_answer'] === 'D' ? 'selected' : '' ?>>D</option>
                                            </select>
                                        </label>
                                        <button type="submit" class="btn small">Lưu đáp án</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
