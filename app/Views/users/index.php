<?php /** @var array<int, array<string,mixed>> $users */ ?>
<section class="card">
    <h1>Danh sách người dùng</h1>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Mã</th>
                <th>Họ và tên</th>
                <th>Thư điện tử</th>
                <th>Vai trò</th>
                <th>Ngày tạo</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <?php
                $roleRaw = strtolower((string) ($user['role'] ?? ''));
                $roleVi = $roleRaw === 'admin' ? 'Quản trị viên' : 'Thí sinh';
                ?>
                <tr>
                    <td><?= (int) $user['id'] ?></td>
                    <td><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($roleVi, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
