<?php /** @var array<int, array<string,mixed>> $quizzes */ ?>
<section class="card">
    <h1>Tạo câu hỏi mới</h1>
    <form method="POST" action="/questions" class="form-grid single">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label>
            Bài kiểm tra
            <select name="quiz_id" required>
                <?php foreach ($quizzes as $quiz): ?>
                    <option value="<?= (int) $quiz['id'] ?>" <?= (int) $selectedQuizId === (int) $quiz['id'] ? 'selected' : '' ?>>
                        #<?= (int) $quiz['id'] ?> - <?= htmlspecialchars((string) $quiz['title'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="ai-question-field">
            Nội dung câu hỏi
            <div data-rich-field class="rich-field-stack">
                <textarea name="question_content" rows="4" required data-rich-paste="1" data-question-live-preview="1"></textarea>
                <div class="quiz-question-live-preview" data-question-img-preview hidden>
                    <p class="muted" style="margin:0 0 8px;">Xem trước ảnh (ô trên vẫn là mã — khi lưu, thí sinh sẽ thấy ảnh)</p>
                    <div data-question-img-preview-body class="quiz-rich-field"></div>
                </div>
                <div class="rich-image-row">
                    <button type="button" class="btn ghost small" data-rich-image-pick>Chèn ảnh</button>
                    <span class="muted rich-image-hint"><strong>Ctrl+V</strong> · kéo thả file</span>
                    <input type="file" accept="image/*" hidden data-rich-image-input="1" aria-hidden="true">
                </div>
            </div>
        </label>
        <p class="muted" style="margin: -6px 0 10px;">Ô <strong>Câu hỏi</strong>: công thức <code>\( … \)</code>, đồ thị — chụp màn hình <strong>Ctrl+V</strong> / <strong>Chèn ảnh</strong>. <strong>Đáp án</strong> chỉ gõ chữ hoặc công thức (không ảnh).</p>

        <label>Đáp án A <textarea name="answer_a" rows="2" required></textarea></label>
        <label>Đáp án B <textarea name="answer_b" rows="2" required></textarea></label>
        <label>Đáp án C <textarea name="answer_c" rows="2" required></textarea></label>
        <label>Đáp án D <textarea name="answer_d" rows="2" required></textarea></label>

        <label>
            Đáp án đúng
            <select name="correct_answer" required>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit">Lưu câu hỏi</button>
            <a href="/questions<?= $selectedQuizId > 0 ? '?quiz_id=' . (int) $selectedQuizId : '' ?>" class="btn ghost">Quay lại</a>
        </div>
    </form>
</section>
