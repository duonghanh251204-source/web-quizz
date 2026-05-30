<?php /** @var array<int, array<string,mixed>> $submissions */ ?>
<section class="card">
    <div class="section-header">
        <h1>Kết quả bài nộp</h1>
    </div>

    <?php if ($submissions === []): ?>
        <p>Chưa có bài nộp nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Bài kiểm tra</th>
                    <th>Thí sinh</th>
                    <th>Điểm</th>
                    <th>Đúng</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= (int) $submission['id'] ?></td>
                        <td><?= htmlspecialchars((string) $submission['quiz_title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $submission['user_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $submission['score'] ?>%</td>
                        <td><?= (int) $submission['total_correct'] ?>/<?= (int) $submission['total_questions'] ?></td>
                        <td><?= htmlspecialchars((string) $submission['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/submissions/<?= (int) $submission['id'] ?>">Kết quả</a>
                            |
                            <a href="/quizzes/<?= (int) ($submission['quiz_id'] ?? 0) ?>">Chi tiết đề</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
