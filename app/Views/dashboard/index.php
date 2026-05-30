<?php /** @var bool $isAdmin */ ?>
<section class="card">
    <div class="dashboard-hero">
        <div>
            <p class="eyebrow">Tổng quan hệ thống</p>
            <h1 class="section-title">Bảng điều khiển</h1>
            <p class="section-lead">Theo dõi hoạt động nền tảng và mở nhanh từng phân hệ từ một trang trung tâm.</p>
        </div>

        <div class="form-actions">
            <?php if ($isAdmin): ?>
                <span class="badge success">Đã bật quyền quản trị</span>
            <?php else: ?>
                <span class="badge">Chế độ thí sinh</span>
            <?php endif; ?>
            <a href="/quizzes" class="btn ghost">Mở ngân hàng bài kiểm tra</a>
        </div>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <p>Tài liệu</p>
        <h2 class="mono"><?= (int) $documentsCount ?></h2>
    </article>

    <article class="metric-card">
        <p>Bài kiểm tra</p>
        <h2 class="mono"><?= (int) $quizzesCount ?></h2>
    </article>

    <article class="metric-card">
        <p>Bài nộp</p>
        <h2 class="mono"><?= (int) $submissionsCount ?></h2>
    </article>

    <article class="metric-card">
        <p>Vai trò hiện tại</p>
        <h2 class="mono"><?= $isAdmin ? 'QUẢN TRỊ' : 'THÍ SINH' ?></h2>
    </article>
</section>

<section class="dashboard-layout">
    <article class="card">
        <div class="section-header">
            <div>
                <h2 class="section-title">Thao tác nhanh</h2>
                <p class="section-lead">Các bước thường dùng trong vận hành hằng ngày.</p>
            </div>
        </div>

        <div class="quick-grid">
            <?php if ($isAdmin): ?>
                <a class="quick-link" href="/admin/dashboard">
                    <strong>Trang chủ</strong>
                    <span>Thống kê, cấu hình AI, ngân hàng câu, báo cáo, tài liệu, thành viên (đúng chức năng yêu cầu)</span>
                </a>
                <a class="quick-link" href="/documents/create">
                    <strong>Tải lên tài liệu</strong>
                    <span>Thêm tài liệu nguồn (luồng tạo đề cho giảng viên)</span>
                </a>
                <a class="quick-link" href="/quizzes/create">
                    <strong>Tạo đề thủ công</strong>
                    <span>Nhập trực tiếp nội dung trắc nghiệm theo định dạng.</span>
                </a>
            <?php endif; ?>

            <a class="quick-link" href="/quizzes">
                <strong>Ngân hàng bài kiểm tra</strong>
                <span>Mở danh sách đề và bắt đầu làm bài</span>
            </a>
            <a class="quick-link" href="/submissions">
                <strong>Danh sách kết quả</strong>
                <span>Xem lịch sử nộp bài và điểm số</span>
            </a>
        </div>
    </article>

    <article class="card">
        <h2 class="section-title">Trình tự gợi ý</h2>
        <p class="section-lead">Thực hiện theo thứ tự sau để có bộ đề chất lượng tốt nhất.</p>

        <ul class="timeline-list">
            <li class="timeline-item">
                <span class="status-dot done" aria-hidden="true"></span>
                <div>
                    <strong>Bước 1: Chuẩn bị tài liệu nguồn</strong>
                    <p class="muted">Tải lên PDF, DOCX hoặc TXT và kiểm tra phần trích xuất văn bản.</p>
                </div>
            </li>
            <li class="timeline-item">
                <span class="status-dot" aria-hidden="true"></span>
                <div>
                    <strong>Bước 2: Dựng ngân hàng câu hỏi</strong>
                    <p class="muted">Tạo bản xem trước, chỉnh đáp án và câu yếu trước khi phát hành.</p>
                </div>
            </li>
            <li class="timeline-item">
                <span class="status-dot" aria-hidden="true"></span>
                <div>
                    <strong>Bước 3: Tổ chức thi &amp; xem kết quả</strong>
                    <p class="muted">Chia sẻ liên kết, thu bài nộp và phân tích sai sót.</p>
                </div>
            </li>
        </ul>
    </article>
</section>
