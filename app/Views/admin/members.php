<?php
/** @var array<int, array<string, mixed>> $users */
/** @var int $totalUsers */
/** @var int $adminCount */
/** @var int $learnerCount */
?>
<?php include __DIR__ . '/_nav.php'; ?>

<section class="card adm-dash-hero">
    <div class="adm-dash-hero__inner">
        <div class="adm-dash-hero__copy">
            <p class="adm-dash-hero__eyebrow">
                <span class="material-symbols-outlined" aria-hidden="true">group</span>
                Quản trị / Thành viên
            </p>
            <h1 class="adm-dash-hero__title">Quản lý thành viên</h1>
            <p class="adm-dash-hero__lead">Vai trò, khóa tài khoản và tìm kiếm nhanh.</p>
        </div>
        <div class="adm-dash-hero__aside">
            <a href="/quizzes" class="adm-hero-link-btn">Danh sách đề</a>
        </div>
    </div>
</section>

<section class="adm-dash-metrics adm-mem-metrics">
    <article class="adm-dash-metric">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">badge</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= (int) $totalUsers ?></span>
            <span class="adm-dash-metric__label">Tổng tài khoản</span>
        </div>
    </article>
    <article class="adm-dash-metric adm-dash-metric--accent adm-mem-metric--admin">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">admin_panel_settings</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= (int) $adminCount ?></span>
            <span class="adm-dash-metric__label">Quản trị</span>
        </div>
    </article>
    <article class="adm-dash-metric adm-dash-metric--accent adm-mem-metric--learner">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">school</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= (int) $learnerCount ?></span>
            <span class="adm-dash-metric__label">Thí sinh</span>
        </div>
    </article>
</section>

<section class="card adm-data-panel adm-mem-panel">
    <div class="adm-mem-toolbar">
        <div>
            <h2 class="adm-mem-toolbar__title">Danh sách</h2>
            <p class="adm-mem-toolbar__sub">Lọc theo tên hoặc email.</p>
        </div>
        <label class="adm-mem-search">
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            <input type="search" data-admin-user-filter placeholder="Tìm người dùng…" autocomplete="off" aria-label="Tìm người dùng">
        </label>
    </div>

    <div class="adm-data-scroll adm-mem-table-scroll">
        <table class="adm-data-table adm-mem-table admin-user-table">
            <thead>
            <tr>
                <th class="adm-th-id">ID</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th class="adm-th-muted">Ngày tạo</th>
                <th class="adm-th-act-wide"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <?php
                $uid = (int) $u['id'];
                $role = strtolower((string) ($u['role'] ?? 'user'));
                $isAdminRow = $role === 'admin';
                $isLocked = !empty($u['is_locked']);
                ?>
                <tr
                    class="adm-data-tr"
                    data-admin-user-row
                    data-search="<?= htmlspecialchars(
                        mb_strtolower((string) $u['name'] . ' ' . (string) $u['email'], 'UTF-8'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <td class="mono adm-td-id">#<?= $uid ?></td>
                    <td class="adm-td-strong"><?= htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="mono adm-td-email"><?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="role-badge <?= $isAdminRow ? 'role-badge--admin' : 'role-badge--user' ?>">
                            <?= $isAdminRow ? 'Quản trị' : 'Thí sinh' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($isLocked): ?>
                            <span class="adm-status adm-status--locked">Đã khóa</span>
                        <?php else: ?>
                            <span class="adm-status adm-status--ok">Hoạt động</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono adm-td-date"><?= htmlspecialchars((string) $u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="adm-td-actions adm-mem-actions">
                        <form method="POST" action="/admin/users/<?= $uid ?>/role" class="admin-role-form adm-mem-role-form">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <select name="role" class="admin-role-select" aria-label="Đổi vai trò">
                                <option value="user" <?= ! $isAdminRow ? 'selected' : '' ?>>Thí sinh</option>
                                <option value="admin" <?= $isAdminRow ? 'selected' : '' ?>>Quản trị</option>
                            </select>
                            <button type="submit" class="btn ghost small">Lưu</button>
                        </form>
                        <?php if (! $isAdminRow): ?>
                            <form method="POST" action="/admin/users/<?= $uid ?>/lock" class="admin-lock-form adm-mem-lock-form">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="locked" value="<?= $isLocked ? '0' : '1' ?>">
                                <button type="submit" class="btn ghost small <?= $isLocked ? 'success-outline' : 'danger-outline' ?>">
                                    <?= $isLocked ? 'Mở khóa' : 'Khóa' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
