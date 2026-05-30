<?php /** @var array<int, array<string,mixed>> $documents */ ?>
<?php /** @var string $_csrf_token */ ?>
<style>
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
        <h1>Thư viện tài liệu</h1>
        <a href="/documents/create" class="btn">Tải tài liệu mới</a>
    </div>

    <?php if ($documents === []): ?>
        <p>Chưa có tài liệu nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tiêu đề</th>
                    <th>Tên tệp</th>
                    <th>Định dạng</th>
                    <th>Chủ sở hữu</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?= (int) $doc['id'] ?></td>
                        <td><?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $doc['original_file_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $doc['mime_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $doc['owner_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $doc['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="form-actions">
                                <a href="/documents/<?= (int) $doc['id'] ?>" class="btn ghost small">Chi tiết</a>
                                <form
                                    method="post"
                                    action="/documents/<?= (int) $doc['id'] ?>/delete"
                                    class="inline-form"
                                    data-no-submit-lock
                                    data-delete-confirm
                                    data-delete-title="Xóa tài liệu"
                                    data-delete-message="Bạn có chắc muốn xóa tài liệu này? Các bài kiểm tra liên quan cũng sẽ bị xóa."
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
    <?php endif; ?>
</section>

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
        const title = form.dataset.deleteTitle || 'Xác nhận xóa';
        const message = form.dataset.deleteMessage || 'Bạn có chắc muốn thực hiện hành động này không?';
        document.getElementById('delete-confirm-title').textContent = title;
        messageEl.textContent = message;
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
