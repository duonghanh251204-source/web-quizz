<?php /** @var array<string,mixed> $quiz */ ?>
<?php /** @var bool $isAdmin */ ?>
<?php
$appUrl = (string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '');
$base = rtrim($appUrl, '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $marker = '/quizzes/' . (int) $quiz['id'];
    $basePath = '';
    $markerPos = strpos($requestPath, $marker);
    if ($markerPos !== false) {
        $basePath = substr($requestPath, 0, $markerPos);
    }

    $base = $scheme . '://' . $host . rtrim($basePath, '/');
}
$shareLink = $base . '/quizzes/' . (int) $quiz['id'] . '/take';
?>
<section class="card">
    <div class="section-header">
        <div>
            <h1><?= htmlspecialchars((string) $quiz['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p>
                <strong>Tài liệu:</strong> <?= htmlspecialchars((string) ($quiz['document_title'] ?? 'Nhập trực tiếp'), ENT_QUOTES, 'UTF-8') ?> |
                <strong>Người tạo:</strong> <?= htmlspecialchars((string) $quiz['creator_name'], ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p class="muted" style="margin-top: 6px;"><strong>Liên kết chia sẻ:</strong> <span class="mono"><?= htmlspecialchars($shareLink, ENT_QUOTES, 'UTF-8') ?></span></p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn ghost" data-share-link="<?= htmlspecialchars($shareLink, ENT_QUOTES, 'UTF-8') ?>">Sao chép liên kết chia sẻ</button>
            <?php if ($isAdmin): ?>
                <a href="/questions?quiz_id=<?= (int) $quiz['id'] ?>" class="btn ghost">Quản lý câu hỏi</a>
                <a href="/quizzes/<?= (int) $quiz['id'] ?>/export?with_answers=1" class="btn ghost">Xuất tệp TXT</a>
            <?php endif; ?>
            <a href="/quizzes/<?= (int) $quiz['id'] ?>/take" class="btn">Làm bài</a>
        </div>
    </div>
</section>

<section class="card">
    <h2>Danh sách câu hỏi</h2>
    <?php foreach ($questions as $question): ?>
        <article class="question-item">
            <h3 class="question-item-title">Câu <?= (int) $question['position'] ?>:</h3>
            <div class="quiz-rich-field question-item-stem"><?= \App\Support\QuizRichContent::toHtml((string) $question['question_content']) ?></div>
            <ul class="options">
                <li class="<?= $question['correct_answer'] === 'A' ? 'correct' : '' ?>"><strong>A.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_a']) ?></span></li>
                <li class="<?= $question['correct_answer'] === 'B' ? 'correct' : '' ?>"><strong>B.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_b']) ?></span></li>
                <li class="<?= $question['correct_answer'] === 'C' ? 'correct' : '' ?>"><strong>C.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_c']) ?></span></li>
                <li class="<?= $question['correct_answer'] === 'D' ? 'correct' : '' ?>"><strong>D.</strong> <span class="quiz-rich-field"><?= \App\Support\QuizRichContent::toHtml((string) $question['answer_d']) ?></span></li>
            </ul>
        </article>
    <?php endforeach; ?>
</section>

<script>
(function () {
    var btn = document.querySelector('[data-share-link]');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        var link = btn.getAttribute('data-share-link') || '';
        if (!link) {
            return;
        }

        var done = function () {
            var old = btn.textContent;
            btn.textContent = 'Đã sao chép';
            setTimeout(function () { btn.textContent = old; }, 1500);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(done).catch(function () {});
            return;
        }

        var input = document.createElement('input');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        done();
    });
})();
</script>
