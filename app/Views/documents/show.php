<?php /** @var array<string,mixed> $document */ ?>
<section class="card">
    <div class="section-header">
        <h1><?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="/quizzes/create" class="btn">Tạo đề thủ công</a>
    </div>
    <p><strong>Tệp:</strong> <?= htmlspecialchars((string) $document['original_file_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Loại:</strong> <?= htmlspecialchars((string) $document['mime_type'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Chủ sở hữu:</strong> <?= htmlspecialchars((string) $document['owner_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Ngày tạo:</strong> <?= htmlspecialchars((string) $document['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
</section>

<section class="card">
    <h2>Nội dung đã trích xuất (xem trước)</h2>
    <div class="content-preview">
        <?= nl2br(htmlspecialchars((string) $preview, ENT_QUOTES, 'UTF-8')) ?>
    </div>
</section>
