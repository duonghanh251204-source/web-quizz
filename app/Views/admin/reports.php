<?php /** @var array<int, array<string, mixed>> $reports */ ?>
<?php /** @var string $statusFilter */ ?>
<?php include __DIR__ . '/_nav.php'; ?>

<section class="card admin-hub-hero">
    <div class="admin-hub-hero__text">
        <p class="admin-hub-eyebrow">
            <span class="material-symbols-outlined" aria-hidden="true">flag</span>
            Báo cáo lỗi
        </p>
        <h1 class="section-title">Báo cáo lỗi từ người dùng</h1>
        <p class="section-lead">Danh sách câu bị gắn cờ <strong>« sai kiến thức »</strong> hoặc <strong>« lỗi định dạng / hiển thị »</strong> — xử lý nhanh, rồi sửa câu tại mục « Ngân hàng câu hỏi » nếu cần.</p>
    </div>
</section>

<section class="card">
    <form method="get" action="/admin/reports" class="filter-row" style="margin-bottom: 16px;">
        <label>
            Trạng thái
            <select name="status" onchange="this.form.submit()">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Tất cả</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Đang mở</option>
                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                <option value="dismissed" <?= $statusFilter === 'dismissed' ? 'selected' : '' ?>>Đã bỏ qua</option>
            </select>
        </label>
    </form>

    <?php if ($reports === []): ?>
        <p class="muted">Chưa có báo cáo nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-report-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Quiz</th>
                    <th>Nội dung (rút gọn)</th>
                    <th>Lý do</th>
                    <th>Người gửi</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $r): ?>
                    <?php
                    $reason = (string) ($r['reason'] ?? 'other');
                    $reasonLabel = match ($reason) {
                        'knowledge' => 'Sai kiến thức',
                        'format' => 'Lỗi định dạng',
                        default => 'Khác',
                    };
                    $st = (string) ($r['status'] ?? 'open');
                    ?>
                    <tr>
                        <td class="mono"><?= (int) $r['id'] ?></td>
                        <td>
                            <a href="/quizzes/<?= (int) ($r['quiz_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($r['quiz_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        </td>
                        <td><?= htmlspecialchars(\App\Support\QuizRichContent::plainTextPreview((string) ($r['question_content'] ?? ''), 80), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($r['reporter_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge report-status report-status--<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <a class="btn ghost small" href="/questions/<?= (int) ($r['question_id'] ?? 0) ?>/edit">Sửa câu hỏi</a>
                            <form method="post" action="/admin/reports/<?= (int) $r['id'] ?>/status" class="inline-form admin-inline-status">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="admin_note" value="">
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" class="btn small">Đánh dấu đã xử lý</button>
                            </form>
                            <form method="post" action="/admin/reports/<?= (int) $r['id'] ?>/status" class="inline-form admin-inline-status">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="admin_note" value="">
                                <input type="hidden" name="status" value="dismissed">
                                <button type="submit" class="btn ghost small">Bỏ qua</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
