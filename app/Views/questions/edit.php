<?php /** @var array<string,mixed> $question */ ?>
<section class="card">
    <h1>Chỉnh sửa câu hỏi #<?= (int) $question['id'] ?></h1>
    <p><strong>Bài kiểm tra:</strong> <?= htmlspecialchars((string) $question['quiz_title'], ENT_QUOTES, 'UTF-8') ?></p>

    <form method="POST" action="/questions/<?= (int) $question['id'] ?>/update" class="form-grid single">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label class="ai-question-field">
            Nội dung câu hỏi
            <div data-rich-field class="rich-field-stack">
                <textarea name="question_content" rows="4" required data-rich-paste="1" data-question-live-preview="1"><?= htmlspecialchars((string) $question['question_content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="quiz-question-live-preview" data-question-img-preview hidden>
                    <p class="muted" style="margin:0 0 8px;">Xem trước ảnh (ô trên vẫn là mã — khi lưu, thí sinh sẽ thấy ảnh)</p>
                    <div data-question-img-preview-body class="quiz-rich-field"></div>
                </div>
                <div class="rich-image-row">
                    <button type="button" class="btn ghost small" data-rich-image-pick>Chèn ảnh</button>
                    <span class="muted rich-image-hint"><strong>Ctrl+V</strong> · kéo thả</span>
                    <input type="file" accept="image/*" hidden data-rich-image-input="1" aria-hidden="true">
                </div>
            </div>
        </label>
        <p class="muted" style="margin: -6px 0 10px;">Ô <strong>Câu hỏi</strong>: công thức và ảnh (Ctrl+V / Chèn ảnh). <strong>Đáp án</strong> chỉ chữ hoặc công thức.</p>

        <label>Đáp án A <textarea name="answer_a" rows="2" required><?= htmlspecialchars((string) $question['answer_a'], ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <label>Đáp án B <textarea name="answer_b" rows="2" required><?= htmlspecialchars((string) $question['answer_b'], ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <label>Đáp án C <textarea name="answer_c" rows="2" required><?= htmlspecialchars((string) $question['answer_c'], ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <label>Đáp án D <textarea name="answer_d" rows="2" required><?= htmlspecialchars((string) $question['answer_d'], ENT_QUOTES, 'UTF-8') ?></textarea></label>

        <label>
            Đáp án đúng
            <select name="correct_answer" required>
                <option value="A" <?= (string) $question['correct_answer'] === 'A' ? 'selected' : '' ?>>A</option>
                <option value="B" <?= (string) $question['correct_answer'] === 'B' ? 'selected' : '' ?>>B</option>
                <option value="C" <?= (string) $question['correct_answer'] === 'C' ? 'selected' : '' ?>>C</option>
                <option value="D" <?= (string) $question['correct_answer'] === 'D' ? 'selected' : '' ?>>D</option>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit">Cập nhật</button>
            <a href="/questions?quiz_id=<?= (int) $question['quiz_id'] ?>" class="btn ghost">Quay lại</a>
        </div>
    </form>
</section>
