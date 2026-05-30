<?php /** @var array<int, array<string,mixed>> $rows */ ?>
<?php /** @var array<int, array<string,mixed>> $quizzes */ ?>
<?php /** @var int $selectedQuizId */ ?>
<section class="card">
    <div class="section-header">
        <div>
            <h1>Bảng xếp hạng</h1>
            <p class="section-lead">Xếp hạng theo điểm, sau đó đến số câu đúng, rồi thời gian nộp sớm hơn.</p>
        </div>
        <a href="/quizzes" class="btn ghost">Quay lại danh sách bài kiểm tra</a>
    </div>

    <form method="GET" action="/leaderboard" class="form-grid" style="margin-top: 10px;">
        <label>
            Lọc theo bài kiểm tra
            <select name="quiz_id">
                <option value="0">Tất cả bài kiểm tra</option>
                <?php foreach ($quizzes as $quiz): ?>
                    <option value="<?= (int) $quiz['id'] ?>" <?= (int) $selectedQuizId === (int) $quiz['id'] ? 'selected' : '' ?>>
                        #<?= (int) $quiz['id'] ?> - <?= htmlspecialchars((string) $quiz['title'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit">Áp dụng bộ lọc</button>
        </div>
    </form>
</section>

<section class="card">
    <?php if ($rows === []): ?>
        <p>Chưa có dữ liệu bảng xếp hạng.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Hạng</th>
                    <th>Thí sinh</th>
                    <th>Bài kiểm tra</th>
                    <th>Điểm</th>
                    <th>Đúng</th>
                    <th>Thời điểm nộp</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td>#<?= $index + 1 ?></td>
                        <td><?= htmlspecialchars((string) ($row['user_name'] ?? 'Không xác định'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['quiz_title'] ?? 'Không xác định'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($row['score'] ?? 0) ?>%</td>
                        <td><?= (int) ($row['total_correct'] ?? 0) ?>/<?= (int) ($row['total_questions'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/submissions/<?= (int) ($row['id'] ?? 0) ?>">Kết quả</a> |
                            <a href="/quizzes/<?= (int) ($row['quiz_id'] ?? 0) ?>/take">Làm lại</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
