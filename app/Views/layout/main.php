<?php
/** @var array<string, mixed>|null $_current_user */
$isLoggedIn = is_array($_current_user ?? null);
$currentRole = strtolower((string) ($_current_user['role'] ?? ''));
$isAdmin = $isLoggedIn && $currentRole === 'admin';
$roleLabel = $isAdmin ? 'Quản trị viên' : 'Thí sinh';

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$current_page = basename($_SERVER['PHP_SELF']);
$appUrl = (string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '');
$basePath = (string) parse_url($appUrl, PHP_URL_PATH);
$basePath = '/' . trim($basePath, '/');
$basePath = $basePath === '/' ? '' : $basePath;

$normalizedRequestPath = $requestPath;
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $trimmed = substr($requestPath, strlen($basePath));
    $normalizedRequestPath = ($trimmed === false || $trimmed === '') ? '/' : $trimmed;
}

$pathMatches = static function (string $candidate, string $path): bool {
    if ($candidate === '/') {
        return $path === '/';
    }

    return $path === $candidate || str_starts_with($path, $candidate . '/');
};

$sidebarNav = [
    ['id' => 'tao_bai', 'href' => '/quizzes/create', 'label' => 'Tạo đề thủ công', 'icon' => 'edit_note'],
    ['id' => 'tai_len', 'href' => '/documents/create', 'label' => 'Tạo đề bằng AI', 'icon' => 'auto_awesome'],
    ['id' => 'thu_vien_tai_lieu', 'href' => '/documents', 'label' => 'Thư viện tài liệu', 'icon' => 'folder_open'],
    ['id' => 'chia_se', 'href' => '/quizzes', 'label' => 'Danh sách bài kiểm tra', 'icon' => 'list_alt'],
    ['id' => 'ket_qua', 'href' => '/submissions', 'label' => 'Kết quả', 'icon' => 'analytics'],
];

if ($isAdmin) {
    $sidebarNav = [
        [
            'id' => 'admin_dashboard',
            'href' => '/admin/dashboard',
            'label' => 'Trang chủ',
            'icon' => 'admin_panel_settings',
        ],
        [
            'id' => 'admin_ai',
            'href' => '/admin/ai',
            'label' => 'Cấu hình AI',
            'icon' => 'smart_toy',
        ],
        [
            'id' => 'admin_questions',
            'href' => '/admin/questions',
            'label' => 'Ngân hàng câu hỏi',
            'icon' => 'quiz',
        ],
        [
            'id' => 'admin_reports',
            'href' => '/admin/reports',
            'label' => 'Báo cáo lỗi',
            'icon' => 'flag',
        ],
        [
            'id' => 'admin_documents',
            'href' => '/admin/documents',
            'label' => 'Tài liệu',
            'icon' => 'description',
        ],
        [
            'id' => 'admin_members',
            'href' => '/admin/members',
            'label' => 'Thành viên',
            'icon' => 'group',
        ],
    ];
}

$homeHref = $isAdmin ? '/admin/dashboard' : '/quizzes';
$homeSubline = $isAdmin ? 'Quản trị' : 'Luyện tập thông minh';

$workspaceTopbarSub = 'Trang bài kiểm tra';
if ($isAdmin) {
    if ($normalizedRequestPath === '/admin' || $normalizedRequestPath === '/admin/dashboard') {
        $workspaceTopbarSub = 'Thống kê & tổng quan';
    } elseif (str_starts_with($normalizedRequestPath, '/admin/ai')) {
        $workspaceTopbarSub = 'Cấu hình AI';
    } elseif (str_starts_with($normalizedRequestPath, '/admin/questions')) {
        $workspaceTopbarSub = 'Ngân hàng câu hỏi';
    } elseif (str_starts_with($normalizedRequestPath, '/admin/reports')) {
        $workspaceTopbarSub = 'Báo cáo lỗi';
    } elseif (str_starts_with($normalizedRequestPath, '/admin/documents')) {
        $workspaceTopbarSub = 'Tài liệu';
    } elseif (str_starts_with($normalizedRequestPath, '/admin/members') || str_starts_with($normalizedRequestPath, '/admin/users')) {
        $workspaceTopbarSub = 'Thành viên';
    } elseif (str_starts_with($normalizedRequestPath, '/questions')) {
        $workspaceTopbarSub = 'Ngân hàng câu hỏi';
    } else {
        $workspaceTopbarSub = 'Trang chủ';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LivQuiz Learning — Luyện đề &amp; kiểm tra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/app.css?v=<?= htmlspecialchars((string) (is_file(dirname(__DIR__, 3) . '/public/assets/css/app.css') ? filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') : '1'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" crossorigin="anonymous">
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body.workspace-layout {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(165deg, #faf7ff 0%, #f3ecfc 40%, #eef6fb 100%);
            color: #36274e;
            font-family: "Manrope", "Segoe UI", sans-serif;
        }
        .ws-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 250px;
            background: rgba(255, 255, 255, 0.78);
            border-right: 1px solid #e8def7;
            backdrop-filter: blur(18px);
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            z-index: 50;
            box-shadow: 4px 0 32px rgba(82, 22, 227, 0.04);
        }
        .ws-brand {
            padding: 0 10px 18px;
        }
        .ws-brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        .ws-brand-lockup:focus-visible {
            outline: 2px solid #6d2de1;
            outline-offset: 3px;
            border-radius: 12px;
        }
        .ws-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: #fff;
            background: linear-gradient(145deg, #5e2eef 0%, #5216e3 55%, #8b5cf6 100%);
            box-shadow: 0 8px 22px rgba(94, 46, 239, 0.28);
            flex-shrink: 0;
        }
        .ws-brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .ws-brand-name {
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #36274e;
            line-height: 1.12;
        }
        .ws-brand-tag {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.11em;
            color: #8d7aa8;
        }
        .ws-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }
        .ws-menu a {
            display: flex;
            align-items: center;
            gap: 11px;
            border-radius: 12px;
            padding: 10px 12px;
            text-decoration: none;
            color: #66789a;
            font-size: 15px;
            font-weight: 700;
            transition: background 0.18s ease, color 0.18s ease;
        }
        .ws-menu a:hover {
            background: #ede4fb;
            color: #4f28a8;
        }
        .ws-menu a.active {
            background: #ede4fb;
            color: #6d2de1;
            position: relative;
        }
        .ws-menu a.active::after {
            content: "";
            position: absolute;
            right: -1px;
            top: 9px;
            bottom: 9px;
            width: 4px;
            border-radius: 999px;
            background: #7d35f2;
        }
        .ws-menu .material-symbols-outlined {
            font-size: 22px;
        }
        .ws-menu--admin a {
            font-size: 13.5px;
            font-weight: 700;
            align-items: flex-start;
            line-height: 1.3;
        }
        .ws-menu--admin a > span:last-of-type {
            min-width: 0;
        }
        .ws-menu--admin .material-symbols-outlined {
            margin-top: 1px;
            flex-shrink: 0;
        }
        .ws-profile {
            margin-top: auto;
            border-top: 1px solid #e4d8f7;
            padding: 16px 10px 0;
        }
        .ws-profile strong {
            display: block;
            font-size: 18px;
            line-height: 1.2;
            color: #36274e;
        }
        .ws-profile span {
            display: block;
            font-size: 13px;
            color: #7a6b92;
            margin-top: 2px;
            margin-bottom: 12px;
        }
        .ws-profile button {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #d7c7f1;
            background: #fff;
            color: #6d2de1;
            font-weight: 700;
            font-size: 13px;
            padding: 9px 12px;
            cursor: pointer;
        }
        .ws-main {
            margin-left: 250px;
            min-height: 100vh;
        }
        body.workspace-layout:has(.ws-sidebar--admin) .ws-sidebar--admin {
            width: 272px;
        }
        body.workspace-layout:has(.ws-sidebar--admin) .ws-main {
            margin-left: 272px;
        }
        .ws-topbar {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            border-bottom: 1px solid #e8def7;
            background: rgba(252, 248, 255, 0.75);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .ws-topbar-leading h2 {
            margin: 0;
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: #36274e;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .ws-topbar-sub {
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: 600;
            color: #8d7aa8;
        }
        .ws-topbar .role {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #decef5;
            background: #fff;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 800;
            color: #6b5e84;
            text-transform: uppercase;
            letter-spacing: 0.09em;
        }
        .ws-page {
            padding: 20px 24px 30px;
        }
        .ws-page .alert {
            margin-bottom: 16px;
        }
        @media (max-width: 980px) {
            .ws-sidebar {
                position: static;
                width: 100%;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid #e8def7;
            }
            .ws-main {
                margin-left: 0;
            }
            .ws-topbar {
                position: static;
            }
        }
    </style>
</head>
<body class="workspace-layout">
<?php if (!$isLoggedIn): ?>
    <main class="ws-page">
        <?php if (!empty($_flash_success)): ?>
            <div class="alert success"><?= htmlspecialchars((string) $_flash_success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($_flash_error)): ?>
            <div class="alert error"><?= htmlspecialchars((string) $_flash_error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
<?php else: ?>
    <aside class="ws-sidebar<?= $isAdmin ? ' ws-sidebar--admin' : '' ?>">
        <div class="ws-brand">
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="ws-brand-lockup">
                <span class="ws-brand-mark" aria-hidden="true">L</span>
                <span class="ws-brand-text">
                    <span class="ws-brand-name">LivQuiz Learning</span>
                    <span class="ws-brand-tag"><?= $homeSubline ?></span>
                </span>
            </a>
        </div>

        <nav class="ws-menu<?= $isAdmin ? ' ws-menu--admin' : '' ?>">
            <?php foreach ($sidebarNav as $item): ?>
                <?php
                $id = (string) ($item['id'] ?? '');
                if ($isAdmin) {
                    $isActive = match ($id) {
                        'admin_dashboard' => $normalizedRequestPath === '/admin' || $normalizedRequestPath === '/admin/dashboard',
                        'admin_ai' => str_starts_with($normalizedRequestPath, '/admin/ai'),
                        'admin_questions' => str_starts_with($normalizedRequestPath, '/admin/questions')
                            || $normalizedRequestPath === '/questions'
                            || str_starts_with($normalizedRequestPath, '/questions/'),
                        'admin_reports' => str_starts_with($normalizedRequestPath, '/admin/reports'),
                        'admin_documents' => str_starts_with($normalizedRequestPath, '/admin/documents'),
                        'admin_members' => $normalizedRequestPath === '/admin/members' || $normalizedRequestPath === '/admin/users',
                        default => false,
                    };
                } else {
                    $isActive = false;
                    if ($id === 'tao_bai') {
                        $isActive = $normalizedRequestPath === '/quizzes/create';
                    } elseif ($id === 'tai_len') {
                        $isActive = $normalizedRequestPath === '/documents/create';
                    } elseif ($id === 'thu_vien_tai_lieu') {
                        $isActive = ($current_page === 'documents.php') || $normalizedRequestPath === '/documents';
                    } elseif ($id === 'chia_se') {
                        $isActive = ($current_page === 'quizzes.php')
                            || $normalizedRequestPath === '/quizzes'
                            || preg_match('#^/quizzes/\d+$#', $normalizedRequestPath) === 1
                            || preg_match('#^/quizzes/\d+/export$#', $normalizedRequestPath) === 1;
                    } else {
                        $isActive = $pathMatches((string) $item['href'], $normalizedRequestPath);
                    }
                }
                ?>
                <a href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <span class="material-symbols-outlined"><?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ws-profile">
            <strong><?= htmlspecialchars((string) ($_current_user['name'] ?? 'Người dùng'), ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars((string) $roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <form method="POST" action="/logout" class="inline-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Đăng xuất</button>
            </form>
        </div>
    </aside>

    <div class="ws-main">
        <header class="ws-topbar">
            <div class="ws-topbar-leading">
                <h2>LivQuiz Learning</h2>
                <p class="ws-topbar-sub"><?= htmlspecialchars($isAdmin ? (string) $workspaceTopbarSub : 'Trang bài kiểm tra', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="role"><?= htmlspecialchars((string) $roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </header>

        <main class="ws-page">
            <?php if (!empty($_flash_success)): ?>
                <div class="alert success"><?= htmlspecialchars((string) $_flash_success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($_flash_error)): ?>
                <div class="alert error"><?= htmlspecialchars((string) $_flash_error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    function renderQuizMath() {
        if (typeof renderMathInElement === 'undefined') {
            return;
        }
        document.querySelectorAll('.quiz-rich-field').forEach(function (el) {
            try {
                renderMathInElement(el, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '\\(', right: '\\)', display: false },
                        { left: '\\[', right: '\\]', display: true }
                    ],
                    throwOnError: false,
                    strict: false,
                    ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
                });
            } catch (e) {}
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderQuizMath);
    } else {
        renderQuizMath();
    }
})();
</script>
<script src="/public/assets/js/app.js?v=<?= htmlspecialchars((string) (is_file(dirname(__DIR__, 3) . '/public/assets/js/app.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/app.js') : '1'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
