<?php
/** @var bool $isAdmin */
/** @var int $documentsCount */
/** @var int $quizzesCount */
/** @var int $questionsCount */
/** @var int $submissionsCount */
/** @var string $displayName */
/** @var array<int, array<string,mixed>> $recentQuizzes */
/** @var array<int, array<string,mixed>> $allQuizzes */
/** @var array<int, array<string,mixed>> $recentSubmissions */

$fullName = trim((string) preg_replace('/\s+/', ' ', $displayName));
if ($fullName === '') {
    $fullName = 'Người dùng';
}

$firstName = explode(' ', $fullName)[0] ?? $fullName;
$avgScore = 0;
if ($recentSubmissions !== []) {
    $totalScore = 0;
    foreach ($recentSubmissions as $submission) {
        $totalScore += (int) ($submission['score'] ?? 0);
    }
    $avgScore = (int) round($totalScore / count($recentSubmissions));
}
?>

<section class="card">
    <div class="dashboard-hero">
        <div>
            <p class="eyebrow">Tổng quan không gian làm việc</p>
            <h1 class="section-title">Xin chào, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="section-lead">Toàn bộ chức năng quản lý bài kiểm tra được tập trung tại đây: tải lên, xem trước/sửa, tạo đề, chia sẻ, làm bài, xem kết quả và bảng xếp hạng.</p>
        </div>
        <div class="form-actions">
            <a href="/documents/create" class="btn">Tạo đề bằng AI</a>
            <a href="/quizzes/create" class="btn ghost">Tạo đề thủ công</a>
            <a href="/quizzes/preview" class="btn ghost">Xem trước/Sửa</a>
            <a href="/quizzes" class="btn ghost">Chia sẻ liên kết</a>
        </div>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <p>Tài liệu</p>
        <h2><?= (int) $documentsCount ?></h2>
    </article>
    <article class="metric-card">
        <p>Bài kiểm tra</p>
        <h2><?= (int) $quizzesCount ?></h2>
    </article>
    <article class="metric-card">
        <p>Ngân hàng câu hỏi</p>
        <h2><?= (int) $questionsCount ?></h2>
    </article>
    <article class="metric-card">
        <p>Bài nộp</p>
        <h2><?= (int) $submissionsCount ?></h2>
    </article>
</section>

<section class="dashboard-layout">
    <article class="card">
        <div class="section-header">
            <div>
                <h2 class="section-title">Thao tác nhanh</h2>
                <p class="section-lead">Chọn nhanh thao tác theo luồng xử lý của hệ thống.</p>
            </div>
            <span class="badge">Điểm trung bình: <?= $avgScore ?>%</span>
        </div>
        <div class="quick-grid">
            <a class="quick-link" href="/documents/create">
                <strong>Tạo đề bằng AI</strong>
                <span>Tải tệp và trích xuất nội dung.</span>
            </a>
            <a class="quick-link" href="/quizzes/preview">
                <strong>Xem trước + Sửa</strong>
                <span>Xem và sửa bộ câu hỏi trước khi lưu.</span>
            </a>
            <a class="quick-link" href="/quizzes/create">
                <strong>Tạo đề thủ công</strong>
                <span>Nhập hoặc dán nội dung trắc nghiệm theo định dạng có sẵn.</span>
            </a>
            <a class="quick-link" href="/quizzes">
                <strong>Chia sẻ + Làm bài</strong>
                <span>Lấy liên kết, cho học viên vào làm bài.</span>
            </a>
            <a class="quick-link" href="/submissions">
                <strong>Kết quả</strong>
                <span>Xem kết quả và chi tiết từng lần nộp.</span>
            </a>
        </div>
    </article>

    <article class="card">
        <div class="section-header">
            <h2 class="section-title">Hoạt động bài kiểm tra gần đây</h2>
            <a href="/quizzes" class="btn ghost small">Xem tất cả bài kiểm tra</a>
        </div>
        <?php if ($recentQuizzes === []): ?>
            <p class="muted">Chưa có bài kiểm tra nào được tạo.</p>
        <?php else: ?>
            <ul class="timeline-list">
                <?php foreach ($recentQuizzes as $quiz): ?>
                    <li class="timeline-item">
                        <span class="status-dot done"></span>
                        <div>
                            <strong><?= htmlspecialchars((string) ($quiz['title'] ?? 'Bài kiểm tra chưa đặt tên'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <p class="muted">
                                #<?= (int) ($quiz['id'] ?? 0) ?> |
                                <?= (int) ($quiz['total_questions'] ?? 0) ?> câu hỏi
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<section class="card">
    <div class="section-header">
        <h2 class="section-title">Ngân hàng bài kiểm tra</h2>
        <a href="/quizzes" class="btn ghost small">Mở toàn bộ ngân hàng</a>
    </div>

    <?php if ($allQuizzes === []): ?>
        <p class="muted">Chưa có bài kiểm tra nào trong hệ thống.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Tài liệu</th>
                    <th>Câu hỏi</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($allQuizzes as $quiz): ?>
                    <tr>
                        <td><?= (int) ($quiz['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($quiz['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($quiz['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($quiz['total_questions'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($quiz['creator_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($quiz['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/quizzes/<?= (int) ($quiz['id'] ?? 0) ?>">Chi tiết</a> |
                            <a href="/quizzes/<?= (int) ($quiz['id'] ?? 0) ?>/take">Làm bài</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
