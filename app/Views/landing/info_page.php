<?php
/** @var string|null $metaTitle */
/** @var string|null $activePage */
/** @var string|null $pageLabel */
/** @var string|null $pageTitle */
/** @var string|null $pageDescription */
/** @var array<int,array<string,mixed>>|null $sections */
/** @var array<int,array<string,string>>|null $contactChannels */
/** @var string|null $supportHours */

$metaTitle = (string) ($metaTitle ?? 'LivQuiz Learning');
$activePage = (string) ($activePage ?? '');
$pageLabel = (string) ($pageLabel ?? 'Thông tin');
$pageTitle = (string) ($pageTitle ?? '');
$pageDescription = (string) ($pageDescription ?? '');
$sections = is_array($sections ?? null) ? $sections : [];
$contactChannels = is_array($contactChannels ?? null) ? $contactChannels : [];
$supportHours = trim((string) ($supportHours ?? ''));

$pageLinks = [
    'privacy' => ['/privacy-policy', 'Chính sách bảo mật', 'shield_person'],
    'terms' => ['/terms-of-use', 'Điều khoản sử dụng', 'contract'],
    'help' => ['/help-center', 'Trung tâm trợ giúp', 'help'],
    'contact' => ['/contact', 'Liên hệ', 'mark_email_unread'],
];

$heroIcon = 'article';
foreach ($pageLinks as $key => $link) {
    if ($key === $activePage && isset($link[2])) {
        $heroIcon = (string) $link[2];
        break;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&amp;family=Be+Vietnam+Pro:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                background: '#fef7ff',
                'on-background': '#1d1b20',
                'on-surface-variant': '#484556',
                primary: '#4400d7',
                'primary-ui': '#5D34F1',
                'primary-fixed': '#e5deff',
                surface: '#fef7ff',
            },
            fontFamily: {
                sans: ['Be Vietnam Pro', 'sans-serif'],
                lexend: ['Lexend', 'sans-serif'],
            },
        },
    },
};
</script>
<style>
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-block;
    white-space: nowrap;
    direction: ltr;
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.material-symbols-outlined.filled {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.mesh-gradient {
    background-color: #fef7ff;
    background-image:
        radial-gradient(at 0% 0%, hsla(253, 86%, 57%, 0.14) 0px, transparent 50%),
        radial-gradient(at 100% 0%, hsla(268, 100%, 77%, 0.12) 0px, transparent 48%),
        radial-gradient(at 100% 100%, hsla(253, 86%, 57%, 0.08) 0px, transparent 45%),
        radial-gradient(at 0% 100%, hsla(268, 100%, 77%, 0.1) 0px, transparent 50%);
}
.info-prose p {
    margin-bottom: 0.875rem;
}
.info-prose p:last-child {
    margin-bottom: 0;
}
.info-list li {
    position: relative;
    padding-left: 1.35rem;
}
.info-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.55em;
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, #5D34F1, #8b5cf6);
}
</style>
</head>
<body class="mesh-gradient min-h-screen font-sans text-on-background text-[16px] leading-relaxed selection:bg-[#5D34F1]/25 selection:text-[#1b0063] scroll-smooth">

<header class="fixed top-0 w-full z-50 bg-white/85 backdrop-blur-xl border-b border-[#ede8fa]/40 shadow-sm shadow-[#5D34F1]/5">
<nav class="flex flex-wrap justify-between items-center gap-4 px-4 sm:px-8 h-[4.5rem] max-w-7xl mx-auto w-full">
<a class="font-lexend text-xl sm:text-2xl font-black text-[#5D34F1] tracking-tighter shrink-0" href="/">LivQuiz</a>
<div class="flex items-center gap-2 sm:gap-3 ml-auto">
<a href="/login" class="hidden sm:inline-block px-4 sm:px-6 py-2 rounded-full font-lexend text-sm font-medium text-slate-600 hover:text-[#5D34F1] transition-colors">Đăng nhập</a>
<a href="/register" class="px-4 sm:px-6 py-2 rounded-full bg-[#5D34F1] text-white font-lexend text-sm font-bold shadow-md shadow-[#5D34F1]/25 hover:opacity-92 transition-opacity active:scale-[0.98]">Bắt đầu ngay</a>
</div>
</nav>
</header>

<main class="pt-[5.25rem] pb-16 sm:pb-24 px-4 sm:px-8">
<div class="max-w-7xl mx-auto">
<div class="xl:grid xl:grid-cols-[minmax(0,15.5rem)_minmax(0,1fr)] xl:gap-12 xl:items-start">

<!-- Sidebar: page switcher + TOC -->
<aside class="mb-10 xl:mb-0 xl:sticky xl:top-24 space-y-8">
<a href="/" class="inline-flex items-center gap-2 text-sm font-lexend font-semibold text-slate-500 hover:text-[#5D34F1] transition-colors group">
<span class="material-symbols-outlined text-lg group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
Về trang chủ
</a>

<nav aria-label="Trang thông tin" class="space-y-2">
<p class="font-lexend text-xs font-bold uppercase tracking-[0.14em] text-slate-400 px-1">Tài liệu</p>
<div class="flex flex-row xl:flex-col gap-2 overflow-x-auto xl:overflow-visible pb-1 xl:pb-0 -mx-1 px-1 xl:mx-0 xl:px-0 snap-x snap-mandatory xl:snap-none scrollbar-thin">
<?php foreach ($pageLinks as $key => $link): ?>
<?php
$href = (string) $link[0];
$label = (string) $link[1];
$icon = (string) ($link[2] ?? 'article');
$isActive = $key === $activePage;
?>
<a
<?php if ($isActive): ?>aria-current="page"<?php endif; ?>
class="<?= $isActive
    ? 'bg-[#5D34F1] text-white shadow-lg shadow-[#5D34F1]/25 ring-2 ring-[#5D34F1]/20'
    : 'bg-white/80 text-slate-700 hover:bg-white hover:text-[#5D34F1] ring-1 ring-[#ede8fa]' ?>
flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-lexend font-semibold transition-all shrink-0 snap-start min-w-[11.5rem] xl:min-w-0"
href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
>
<span class="material-symbols-outlined text-xl <?= $isActive ? 'filled' : '' ?> opacity-90"><?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?></span>
<span class="leading-tight"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
</a>
<?php endforeach; ?>
</div>
</nav>

<?php if ($sections !== []): ?>
<nav aria-label="Mục lục trang" class="hidden xl:block space-y-2 pt-2 border-t border-[#ede8fa]/80">
<p class="font-lexend text-xs font-bold uppercase tracking-[0.14em] text-slate-400 px-1 pt-2">Trên trang này</p>
<ul class="space-y-1 text-sm">
<?php foreach ($sections as $ti => $section): ?>
<?php
$t = trim((string) ($section['title'] ?? ''));
if ($t === '') {
    continue;
}
$slug = 'sec-' . (string) $ti;
?>
<li>
<a class="block px-3 py-2 rounded-xl text-slate-600 hover:bg-[#5D34F1]/8 hover:text-[#5D34F1] font-medium transition-colors border border-transparent hover:border-[#5D34F1]/15" href="#<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
</a>
</li>
<?php endforeach; ?>
</ul>
</nav>
<?php endif; ?>
</aside>

<!-- Main -->
<div class="min-w-0 max-w-3xl xl:max-w-none">

<!-- Hero -->
<div class="relative rounded-[1.75rem] overflow-hidden mb-10 sm:mb-12 ring-1 ring-white/60 shadow-[0_24px_60px_-12px_rgba(93,52,241,0.18)]">
<div class="absolute inset-0 bg-gradient-to-br from-[#5D34F1] via-[#624bbd] to-[#8b5cf6] opacity-[0.93]"></div>
<div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.06%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-90"></div>
<div class="relative px-6 sm:px-10 py-9 sm:py-11 text-white">
<div class="flex flex-col sm:flex-row sm:items-start gap-6">
<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md ring-1 ring-white/30">
<span class="material-symbols-outlined filled text-3xl"><?= htmlspecialchars($heroIcon, ENT_QUOTES, 'UTF-8') ?></span>
</div>
<div class="space-y-3 min-w-0">
<p class="font-lexend text-xs font-bold uppercase tracking-[0.2em] text-white/75"><?= htmlspecialchars($pageLabel, ENT_QUOTES, 'UTF-8') ?></p>
<h1 class="font-lexend text-2xl sm:text-3xl md:text-[2rem] font-extrabold tracking-tight leading-tight"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
<p class="text-[15px] sm:text-base text-white/88 leading-relaxed max-w-2xl"><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
</div>
</div>
</div>
</div>

<?php if ($contactChannels !== []): ?>
<div class="mb-10 sm:mb-12 space-y-4">
<?php if ($supportHours !== ''): ?>
<div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white/75 backdrop-blur-sm border border-[#ede8fa] px-4 py-3 shadow-sm">
<span class="material-symbols-outlined text-[#5D34F1]">schedule</span>
<p class="text-sm text-slate-600"><span class="font-lexend font-semibold text-slate-800">Thời gian phản hồi:</span> <?= htmlspecialchars($supportHours, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<div class="grid sm:grid-cols-2 gap-4">
<?php foreach ($contactChannels as $ch): ?>
<?php
$cIcon = (string) ($ch['icon'] ?? 'mail');
$cTitle = (string) ($ch['title'] ?? '');
$cEmail = (string) ($ch['email'] ?? '');
$cDesc = (string) ($ch['description'] ?? '');
if ($cEmail === '') {
    continue;
}
$mailto = 'mailto:' . $cEmail;
?>
<a href="<?= htmlspecialchars($mailto, ENT_QUOTES, 'UTF-8') ?>" class="group relative flex flex-col gap-3 rounded-2xl bg-white p-6 shadow-[0_14px_40px_-12px_rgba(93,52,241,0.15)] ring-1 ring-[#ede8fa] hover:ring-[#5D34F1]/35 hover:shadow-[0_20px_50px_-12px_rgba(93,52,241,0.22)] transition-all active:scale-[0.99]">
<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-fixed text-[#5D34F1] group-hover:scale-105 transition-transform">
<span class="material-symbols-outlined filled"><?= htmlspecialchars($cIcon, ENT_QUOTES, 'UTF-8') ?></span>
</span>
<div>
<h2 class="font-lexend font-bold text-lg text-slate-900"><?= htmlspecialchars($cTitle, ENT_QUOTES, 'UTF-8') ?></h2>
<?php if ($cDesc !== ''): ?>
<p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($cDesc, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
</div>
<p class="font-lexend text-[#5D34F1] font-semibold text-sm mt-auto pt-2 border-t border-[#f3efff]"><?= htmlspecialchars($cEmail, ENT_QUOTES, 'UTF-8') ?></p>
<span class="absolute top-5 right-5 material-symbols-outlined text-slate-300 group-hover:text-[#5D34F1] transition-colors text-xl" aria-hidden="true">mail</span>
</a>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<!-- Articles -->
<div class="space-y-6">
<?php foreach ($sections as $si => $section): ?>
<?php
$sectionTitle = trim((string) ($section['title'] ?? ''));
$paragraphs = is_array($section['paragraphs'] ?? null) ? $section['paragraphs'] : [];
$list = is_array($section['list'] ?? null) ? $section['list'] : [];
$slug = 'sec-' . (string) $si;
$isHelp = $activePage === 'help';
?>
<article id="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="scroll-mt-28 rounded-2xl bg-white/90 backdrop-blur-sm border border-[#ede8fa] shadow-[0_12px_40px_-16px_rgba(29,27,32,0.12)] overflow-hidden">
<div class="flex flex-col sm:flex-row sm:items-stretch">
<div class="hidden sm:block w-1.5 shrink-0 bg-gradient-to-b from-[#5D34F1] via-[#7c3aed] to-[#a78bfa]"></div>
<div class="sm:border-l border-[#f5f3fa] flex-1 p-6 sm:p-8">
<?php if ($sectionTitle !== ''): ?>
<div class="flex items-start gap-4 mb-5">
<?php if ($isHelp): ?>
<span class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-xl bg-primary-fixed font-lexend text-sm font-bold text-[#5D34F1]"><?= (string) ($si + 1) ?></span>
<?php endif; ?>
<h2 class="font-lexend text-xl sm:text-2xl font-bold text-slate-900 leading-snug"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
</div>
<?php endif; ?>

<div class="info-prose text-slate-600 text-[15px] sm:text-base leading-relaxed">
<?php foreach ($paragraphs as $paragraph): ?>
<p><?= htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
</div>

<?php if ($list !== []): ?>
<ul class="info-list mt-5 space-y-3 text-slate-600 text-[15px] sm:text-base">
<?php foreach ($list as $item): ?>
<li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
</div>
</article>
<?php endforeach; ?>
</div>

</div>
</div>
</div>
</main>

<footer class="border-t border-[#ede8fa]/80 bg-white/50 backdrop-blur-sm mt-4">
<div class="max-w-7xl mx-auto px-4 sm:px-8 py-10 flex flex-col sm:flex-row flex-wrap justify-between items-center gap-6">
<span class="font-lexend font-bold text-[#5D34F1]">LivQuiz Learning</span>
<div class="flex flex-wrap justify-center gap-x-8 gap-y-2 text-sm text-slate-500 font-medium">
<a class="hover:text-[#5D34F1] transition-colors" href="/privacy-policy">Bảo mật</a>
<a class="hover:text-[#5D34F1] transition-colors" href="/terms-of-use">Điều khoản</a>
<a class="hover:text-[#5D34F1] transition-colors" href="/help-center">Trợ giúp</a>
<a class="hover:text-[#5D34F1] transition-colors" href="/contact">Liên hệ</a>
</div>
<p class="text-xs text-slate-400">© <?= date('Y') ?> LivQuiz Learning</p>
</div>
</footer>

</body>
</html>
