<?php /** @var array<int, array<string,mixed>> $quizzes */ ?>
<?php /** @var bool $isAdmin */ ?>
<?php /** @var int $currentUserId */ ?>
<?php /** @var string $_csrf_token */ ?>
<?php
$totalQuizzes = count($quizzes);
?>

<style>
.quiz-list-table-wrap {
    overflow-x: auto;
}
.quiz-list-table {
    width: 100%;
    border-collapse: collapse;
}
.quiz-list-table th,
.quiz-list-table td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5eaf5;
    vertical-align: middle;
}
.quiz-list-table thead th {
    background: #f6f8ff;
    color: #334b82;
    font-size: 13px;
}
.quiz-title {
    margin: 0;
    font-weight: 700;
    color: #1e2d4f;
}
.quiz-muted {
    margin: 4px 0 0;
    font-size: 12px;
    color: #6b7a99;
}
.quiz-list-actions {
    flex-wrap: wrap;
    gap: 8px;
}
.delete-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 12, 41, 0.22);
    backdrop-filter: blur(5px) saturate(105%);
}
.delete-confirm-overlay.is-open {
    display: flex;
}
.delete-confirm-dialog {
    width: min(460px, 100%);
    border-radius: 22px;
    border: 1px solid rgba(167, 139, 250, 0.28);
    background: linear-gradient(160deg, rgba(15, 12, 41, 0.98), rgba(36, 36, 62, 0.98));
    box-shadow: 0 30px 80px rgba(15, 12, 41, 0.45);
    color: #f8fafc;
    overflow: hidden;
}
.delete-confirm-dialog__top {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 24px 24px 8px;
}
.delete-confirm-dialog__icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    color: #f87171;
    background: radial-gradient(circle at top, rgba(248, 113, 113, 0.18), rgba(167, 139, 250, 0.14));
    border: 1px solid rgba(248, 113, 113, 0.22);
    flex: 0 0 auto;
}
.delete-confirm-dialog__title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffffff;
}
.delete-confirm-dialog__text {
    margin: 8px 0 0;
    color: #cbd5e1;
    line-height: 1.6;
}
.delete-confirm-dialog__body {
    padding: 0 24px 24px;
}
.delete-confirm-dialog__actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding: 0 24px 24px;
}
.delete-confirm-dialog__actions .btn {
    min-width: 110px;
}
.btn-danger-solid {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #fff;
    border: none;
}
.btn-danger-solid:hover {
    filter: brightness(1.05);
}
</style>

<section class="card">
    <div class="section-header">
        <div>
            <p class="eyebrow">Bài kiểm tra</p>
            <h1 class="section-title">Danh sách bài kiểm tra của tôi</h1>
            <p class="section-lead">Danh sách tất cả bài kiểm tra được lấy từ bảng quizzes trong cơ sở dữ liệu.</p>
        </div>
        <div class="form-actions">
            <a href="/quizzes/create" class="btn">Tạo đề thủ công mới</a>
        </div>
    </div>
</section>

<?php if ($totalQuizzes === 0): ?>
    <section class="card">
        <h2 class="section-title">Chưa có bài kiểm tra</h2>
        <p class="section-lead">Bạn chưa có bài kiểm tra nào trong hệ thống.</p>
        <div class="form-actions">
            <a href="/quizzes/create" class="btn">Tạo đề thủ công đầu tiên</a>
        </div>
    </section>
<?php else: ?>
    <section class="card">
        <div class="section-header">
            <h2 class="section-title">Tổng số: <?= $totalQuizzes ?> bài kiểm tra</h2>
        </div>
        <div class="quiz-list-table-wrap">
            <table class="quiz-list-table">
                <thead>
                    <tr>
                        <th>Tên bài thi</th>
                        <th>Ngày tạo</th>
                        <th>Số lượng câu hỏi</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizzes as $quiz): ?>
                        <?php $quizId = (int) ($quiz['id'] ?? 0); ?>
                        <tr>
                            <td>
                                <p class="quiz-title"><?= htmlspecialchars((string) ($quiz['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="quiz-muted">Tài liệu: <?= htmlspecialchars((string) ($quiz['document_title'] ?? 'Nhập trực tiếp'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td><?= htmlspecialchars((string) ($quiz['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) ($quiz['total_questions'] ?? 0) ?> câu</td>
                            <td>
                                <div class="form-actions quiz-list-actions">
                                    <a href="/quizzes/<?= $quizId ?>/take" class="btn small">Làm bài</a>
                                    <a href="/quizzes/<?= $quizId ?>/export" class="btn ghost small">Xuất bài kiểm tra</a>
                                    <a href="/quizzes/<?= $quizId ?>" class="btn ghost small">Xem chi tiết</a>
                                    <form
                                        method="post"
                                        action="/quizzes/<?= $quizId ?>/delete"
                                        class="inline-form"
                                        data-no-submit-lock
                                        data-delete-confirm
                                        data-delete-title="Xóa bài kiểm tra"
                                        data-delete-message="Bạn có chắc muốn xóa bài kiểm tra này?"
                                    >
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn ghost small danger-outline">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<div class="delete-confirm-overlay" data-delete-modal hidden aria-hidden="true">
    <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-desc">
        <div class="delete-confirm-dialog__top">
            <div class="delete-confirm-dialog__icon" aria-hidden="true">
                <span class="material-symbols-outlined">delete_forever</span>
            </div>
            <div>
                <h2 class="delete-confirm-dialog__title" id="delete-confirm-title">Xác nhận xóa</h2>
                <p class="delete-confirm-dialog__text" id="delete-confirm-desc">Hành động này sẽ xóa dữ liệu vĩnh viễn và không thể hoàn tác.</p>
            </div>
        </div>
        <div class="delete-confirm-dialog__body">
            <p class="delete-confirm-dialog__text" data-delete-modal-message></p>
        </div>
        <div class="delete-confirm-dialog__actions">
            <button type="button" class="btn ghost small" data-delete-cancel>Hủy</button>
            <button type="button" class="btn btn-danger-solid small" data-delete-confirm-btn>Xóa ngay</button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.querySelector('[data-delete-modal]');
    if (!modal) {
        return;
    }

    const titleEl = modal.querySelector('#delete-confirm-title');
    const messageEl = modal.querySelector('[data-delete-modal-message]');
    const cancelBtn = modal.querySelector('[data-delete-cancel]');
    const confirmBtn = modal.querySelector('[data-delete-confirm-btn]');
    let pendingForm = null;

    function resetFormButton(form) {
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        if (submitBtn instanceof HTMLButtonElement) {
            const originalText = submitBtn.dataset.originalText || submitBtn.dataset.original || '';
            if (originalText) {
                submitBtn.textContent = originalText;
            }
            submitBtn.disabled = false;
            delete submitBtn.dataset.locked;
        }
    }

    function openModal(form) {
        pendingForm = form;
        titleEl.textContent = form.dataset.deleteTitle || 'Xác nhận xóa';
        messageEl.textContent = form.dataset.deleteMessage || 'Bạn có chắc muốn thực hiện hành động này không?';
        modal.hidden = false;
        modal.classList.add('is-open');
        confirmBtn.focus();
    }

    function closeModal() {
        resetFormButton(pendingForm);
        modal.classList.remove('is-open');
        modal.hidden = true;
        pendingForm = null;
    }

    document.querySelectorAll('form[data-delete-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            openModal(form);
        });
    });

    cancelBtn.addEventListener('click', closeModal);
    confirmBtn.addEventListener('click', () => {
        if (pendingForm) {
            HTMLFormElement.prototype.submit.call(pendingForm);
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
