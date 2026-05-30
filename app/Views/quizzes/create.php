<?php /** @var bool $hasDraft */ ?>
<?php /** @var string $_csrf_token */ ?>

<section class="card">
    <div class="section-header">
        <div>
            <p class="eyebrow">Tạo đề thủ công</p>
            <h1 class="section-title">Tạo đề thủ công</h1>
            <p class="section-lead">Nhập hoặc dán đoạn văn bản trắc nghiệm đúng định dạng để lưu đề trực tiếp.</p>
        </div>
        <div class="form-actions">
            <a href="/quizzes" class="btn ghost small">Quay lại danh sách</a>
        </div>
    </div>
</section>

<?php if ($hasDraft): ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2 class="section-title">Đang có bản nháp xem trước</h2>
                <p class="section-lead">Bạn đang có bản nháp chưa lưu. Có thể tiếp tục xem lại hoặc hủy bản nháp này.</p>
            </div>
            <div class="form-actions">
                <a href="/quizzes/preview" class="btn">Tiếp tục xem trước</a>
                <form method="POST" action="/quizzes/preview/discard" class="inline-form">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn ghost">Hủy bản nháp</button>
                </form>
            </div>
        </div>
    </section>
<?php endif; ?>

<form method="POST" action="/quizzes" class="wizard-shell">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">

    <section class="builder-grid">
        <article class="builder-main card">
            <h2 class="section-title">Nhập đề trắc nghiệm</h2>
            <div class="form-grid single">
                <label>
                    Tiêu đề bài kiểm tra
                    <input type="text" name="title" required placeholder="Ví dụ: Giữa kỳ - Cấu trúc dữ liệu">
                </label>
            </div>

            <div class="form-grid single" style="margin-top: 14px;">
                <label>
                    Nội dung đề trắc nghiệm
                    <textarea name="raw_content" rows="16" required placeholder="Dán đề trắc nghiệm vào đây. Ví dụ:

Câu 1: Thủ đô của Việt Nam là gì?
A. Hồ Chí Minh
B. Đà Nẵng
C. Hà Nội
*D. Hà Nội

Câu 2: 2 + 2 = ?
A. 3
*B. 4
C. 5
D. 6

(Đánh dấu * trước đáp án đúng, hoặc dùng dòng 'Đáp án: X')"></textarea>
                </label>
            </div>

            <div class="form-actions" style="margin-top: 14px;">
                <button type="submit">Tạo bản xem trước</button>
                <a href="/quizzes" class="btn ghost">Hủy</a>
            </div>
        </article>

        <aside class="builder-side card">
            <h2 class="section-title">Hướng dẫn định dạng</h2>
            <ul class="settings-list">
                <li>Mỗi câu hỏi bắt đầu bằng tiền tố như <strong>Câu 1:</strong>, <strong>1.</strong>, hoặc <strong>1/</strong></li>
                <li>Mỗi đáp án bắt đầu bằng <strong>A.</strong>, <strong>B.</strong>, <strong>C.</strong>, <strong>D.</strong></li>
                <li>Đánh dấu đáp án đúng bằng dấu <strong>*</strong> trước chữ cái (ví dụ: <strong>*B.</strong> Đáp án đúng)</li>
                <li>Hoặc thêm dòng riêng: <strong>Đáp án: B</strong> sau mỗi câu</li>
                <li>Nếu không đánh dấu, hệ thống sẽ mặc định đáp án A</li>
                <li>Cần ít nhất đủ 4 đáp án A/B/C/D cho mỗi câu</li>
            </ul>

            <h2 class="section-title" style="margin-top: 18px;">Ví dụ</h2>
            <pre style="background:#f5f0ff;padding:12px;border-radius:8px;font-size:13px;line-height:1.6;white-space:pre-wrap;overflow-x:auto;">Câu 1: Ngôn ngữ lập trình nào phổ biến nhất cho web?
A. Java
B. C++
*C. JavaScript
D. Python

Câu 2: HTML là viết tắt của?
A. Hyper Text Markup Language
B. High Tech Modern Language
C. Home Tool Markup Language
D. Hyperlinks Text Mark Language
Đáp án: A</pre>
        </aside>
    </section>
</form>
