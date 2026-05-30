<?php /** @var string $_csrf_token */ ?>

<section class="card">
    <div class="section-header">
        <div>
            <p class="eyebrow">Tạo đề bằng AI</p>
            <h1 class="section-title">Nhập tài liệu</h1>
            <p class="section-lead">Tải PDF, DOCX hoặc TXT chứa nội dung kiến thức. Hệ thống trích xuất văn bản và dùng AI để
                soạn bộ câu hỏi trắc nghiệm theo tùy chọn của bạn.</p>
        </div>
        <div class="form-actions">
            <a href="/documents" class="btn ghost small">Thư viện tài liệu</a>
            <a href="/quizzes/create" class="btn ghost small">Đi tới tạo đề thủ công</a>
        </div>
    </div>
</section>

<form
    method="POST"
    action="/documents"
    enctype="multipart/form-data"
    data-ai-quiz-form
    data-no-submit-lock
>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="upload_mode" value="ai">
    <section class="upload-layout">
        <article class="card">
            <div class="dropzone">
                <h3>Tài liệu nguồn</h3>
                <p>Chọn tệp chứa nội dung cần dựng đề. AI sẽ đọc văn bản đã trích xuất và tạo câu hỏi.</p>
                <input type="file" name="document_file" accept=".pdf,.docx,.txt" required>
                <p class="field-hint">Có thể mất thêm thời gian do xử lý AI. Giới hạn đề xuất: 15MB/tệp.</p>
            </div>

            <div class="form-grid" style="margin-top: 12px;">
                <label>
                    Tiêu đề tài liệu kiến thức
                    <input type="text" name="title" required placeholder="Ví dụ: Chương 5 - Đạo hàm">
                </label>
                <label>
                    Số lượng câu hỏi
                    <input type="number" name="question_count" min="1" max="30" value="10" required>
                </label>
                <label>
                    Mức độ
                    <select name="difficulty" required>
                        <option value="easy">Dễ</option>
                        <option value="medium" selected>Trung bình</option>
                        <option value="hard">Khó</option>
                    </select>
                </label>
                <label>
                    Ngôn ngữ
                    <select name="language" required>
                        <option value="vi" selected>Tiếng Việt</option>
                        <option value="en">English</option>
                    </select>
                </label>
            </div>

            <div class="form-actions" style="margin-top: 12px;">
                <button type="submit" data-ai-quiz-submit>Bắt đầu tạo đề bằng AI</button>
                <a href="/quizzes/preview" class="btn ghost">Mở trang xem trước</a>
            </div>
        </article>

        <aside class="card">
            <h2 class="section-title">Tiến trình (tham khảo)</h2>
            <p class="section-lead">Tác vụ gồm ba bước chính:</p>
            <ul class="rule-list">
                <li>1) Trích xuất nội dung tài liệu.</li>
                <li>2) Gửi nội dung đến AI để soạn câu hỏi.</li>
                <li>3) Tạo bản nháp và chuyển sang trang xem trước.</li>
            </ul>
            <div class="progress-track" style="margin-top: 12px;">
                <span style="width: 100%;"></span>
            </div>
            <p class="field-hint" style="margin-top: 8px;">Khi nhấn «Bắt đầu», vui lòng chờ đến khi hệ thống chuyển trang
                tự động.</p>
        </aside>
    </section>
</form>

<div class="ai-gen-loading" data-ai-gen-loading hidden aria-hidden="true">
    <div class="ai-gen-loading__backdrop" aria-hidden="true"></div>
    <div
        class="ai-gen-loading__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ai-gen-loading-title"
        aria-describedby="ai-gen-loading-desc"
        aria-live="polite"
    >
        <div class="ai-gen-loading__ring" aria-hidden="true">
            <span class="ai-gen-loading__orbit"></span>
            <span class="material-symbols-outlined ai-gen-loading__spark" aria-hidden="true">auto_awesome</span>
        </div>
        <h2 id="ai-gen-loading-title" class="ai-gen-loading__title" tabindex="-1">Đang tạo đề bằng AI</h2>
        <p id="ai-gen-loading-desc" class="ai-gen-loading__lead">
            Đang trích xuất tài liệu và soạn câu hỏi — có thể mất một vài phút tùy độ dài nội dung.
        </p>
        <ul class="ai-gen-loading__steps" aria-label="Tiến trình">
            <li class="ai-gen-loading__step">
                <span class="ai-gen-loading__step-dot"></span>
                <span>Đọc &amp; trích xuất văn bản từ tệp</span>
            </li>
            <li class="ai-gen-loading__step">
                <span class="ai-gen-loading__step-dot"></span>
                <span>Gửi nội dung tới AI để sinh câu hỏi</span>
            </li>
            <li class="ai-gen-loading__step">
                <span class="ai-gen-loading__step-dot"></span>
                <span>Chuẩn bị bản nháp đề — trang sẽ chuyển khi xong</span>
            </li>
        </ul>
        <div class="ai-gen-loading__bar" aria-hidden="true">
            <span class="ai-gen-loading__bar-fill"></span>
        </div>
        <p class="ai-gen-loading__hint">Vui lòng không đóng hoặc làm mới trình duyệt.</p>
    </div>
</div>
