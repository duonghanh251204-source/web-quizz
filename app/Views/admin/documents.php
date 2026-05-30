<?php /** @var array<int, array<string, mixed>> $documents */ ?>
<?php
$docCount = count($documents);
?>
<?php include __DIR__ . '/_nav.php'; ?>

<section class="card adm-dash-hero">
    <div class="adm-dash-hero__inner">
        <div class="adm-dash-hero__copy">
            <p class="adm-dash-hero__eyebrow">
                <span class="material-symbols-outlined" aria-hidden="true">description</span>
                Quản trị / Tài liệu
            </p>
            <h1 class="adm-dash-hero__title">Quản lý tài liệu</h1>
            <p class="adm-dash-hero__lead">Tệp PDF, DOCX, TXT người dùng đã tải lên.</p>
        </div>
        <div class="adm-hero-pills" aria-label="Tổng số">
            <span class="adm-hero-pill"><strong><?= $docCount ?></strong><span>tài liệu</span></span>
        </div>
    </div>
</section>

<section class="card adm-data-panel">
    <?php if ($documents === []): ?>
        <div class="adm-data-empty">
            <p class="adm-data-empty__title">Chưa có tài liệu</p>
            <p class="adm-data-empty__hint">Người dùng tải lên sẽ hiển thị tại đây.</p>
        </div>
    <?php else: ?>
        <div class="adm-data-scroll">
            <table class="adm-data-table adm-doc-table">
                <thead>
                <tr>
                    <th class="adm-th-id">ID</th>
                    <th>Tiêu đề</th>
                    <th>Tệp gốc</th>
                    <th>Chủ sở hữu</th>
                    <th class="adm-th-muted">Loại</th>
                    <th class="adm-th-num">Dung lượng</th>
                    <th class="adm-th-muted">Ngày</th>
                    <th class="adm-th-act"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <?php
                    $bytes = (int) ($d['file_size'] ?? 0);
                    if ($bytes >= 1048576) {
                        $sizeStr = number_format($bytes / 1048576, 1) . ' MB';
                    } elseif ($bytes >= 1024) {
                        $sizeStr = number_format($bytes / 1024, 1) . ' KB';
                    } else {
                        $sizeStr = $bytes . ' B';
                    }
                    ?>
                    <tr class="adm-data-tr">
                        <td class="mono adm-td-id">#<?= (int) $d['id'] ?></td>
                        <td class="adm-td-strong"><?= htmlspecialchars((string) $d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono adm-td-file"><?= htmlspecialchars((string) $d['original_file_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="adm-td-owner">
                            <span class="adm-owner-name"><?= htmlspecialchars((string) $d['owner_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="adm-owner-mail"><?= htmlspecialchars((string) ($d['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="adm-td-mime"><?= htmlspecialchars((string) $d['mime_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono adm-td-size"><?= htmlspecialchars($sizeStr, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono adm-td-date"><?= htmlspecialchars((string) $d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="adm-td-actions">
                            <div class="adm-row-actions">
                                <a href="/documents/<?= (int) $d['id'] ?>" class="btn ghost small">Xem</a>
                                <form method="post" action="/admin/documents/<?= (int) $d['id'] ?>/delete" class="inline-form" onsubmit="return confirm('Xóa tài liệu này và mọi bài kiểm tra liên quan?');">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn danger small">Xóa</button>
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
