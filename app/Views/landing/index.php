<?php
/** @var string|null $_flash_success */
/** @var string|null $_flash_error */
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>LivQuiz — Học ít hiểu nhiều, ôn tập hiệu quả với AI</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&amp;family=Be+Vietnam+Pro:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-primary-fixed-variant": "#4500d8",
                        "on-tertiary": "#ffffff",
                        "on-primary-container": "#dbd2ff",
                        "inverse-on-surface": "#f5eff6",
                        "on-surface-variant": "#484556",
                        "surface": "#fef7ff",
                        "tertiary-container": "#5d5d68",
                        "surface-container": "#f2ecf3",
                        "tertiary-fixed": "#e3e1ee",
                        "surface-container-highest": "#e6e1e8",
                        "error": "#ba1a1a",
                        "surface-container-lowest": "#ffffff",
                        "primary-container": "#5d34f1",
                        "background": "#fef7ff",
                        "surface-container-high": "#ece6ed",
                        "inverse-primary": "#c9bfff",
                        "on-secondary-fixed": "#1d0061",
                        "on-surface": "#1d1b20",
                        "primary-fixed-dim": "#c9bfff",
                        "tertiary-fixed-dim": "#c6c5d2",
                        "on-secondary-fixed-variant": "#4a30a3",
                        "on-secondary": "#ffffff",
                        "on-primary": "#ffffff",
                        "surface-container-low": "#f8f2f9",
                        "inverse-surface": "#322f35",
                        "on-background": "#1d1b20",
                        "on-error": "#ffffff",
                        "surface-dim": "#ded8df",
                        "surface-bright": "#fef7ff",
                        "tertiary": "#454650",
                        "secondary": "#624bbd",
                        "on-error-container": "#93000a",
                        "secondary-container": "#a08aff",
                        "surface-variant": "#e6e1e8",
                        "primary-fixed": "#e5deff",
                        "on-tertiary-container": "#d8d6e3",
                        "secondary-fixed-dim": "#cbbeff",
                        "primary": "#4400d7",
                        "on-tertiary-fixed-variant": "#464650",
                        "outline-variant": "#c9c3d9",
                        "error-container": "#ffdad6",
                        "outline": "#797588",
                        "on-primary-fixed": "#1b0063",
                        "secondary-fixed": "#e7deff",
                        "on-tertiary-fixed": "#1a1b24",
                        "on-secondary-container": "#36158f",
                        "surface-tint": "#5d35f1"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "label-md": ["Be Vietnam Pro", "sans-serif"],
                        "body-lg": ["Be Vietnam Pro", "sans-serif"],
                        "body-md": ["Be Vietnam Pro", "sans-serif"],
                        "headline-lg": ["Lexend", "sans-serif"],
                        "headline-md": ["Lexend", "sans-serif"]
                    },
                    "fontSize": {
                        "label-md": ["14px", { lineHeight: "1.4", fontWeight: "600" }],
                        "body-lg": ["18px", { lineHeight: "1.6", fontWeight: "400" }],
                        "body-md": ["16px", { lineHeight: "1.6", fontWeight: "400" }],
                        "headline-lg": ["48px", { lineHeight: "1.2", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "headline-md": ["32px", { lineHeight: "1.3", fontWeight: "600" }]
                    }
                },
            },
        }
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
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .mesh-gradient {
            background-color: #fef7ff;
            background-image:
                radial-gradient(at 0% 0%, hsla(253, 86%, 57%, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, hsla(268, 100%, 77%, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, hsla(253, 86%, 57%, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(268, 100%, 77%, 0.1) 0px, transparent 50%);
        }
    </style>
</head>
<body class="bg-background font-body-md text-on-background selection:bg-primary-container selection:text-on-primary-container scroll-smooth">
<header class="fixed top-0 w-full z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-[#ede8fa]/30 dark:border-slate-800 shadow-sm shadow-[#5D34F1]/5">
<nav class="flex justify-between items-center px-8 h-20 max-w-7xl mx-auto w-full">
<div class="flex items-center gap-12">
<a class="text-2xl font-black text-[#5D34F1] tracking-tighter" href="/">LivQuiz</a>
<div class="hidden md:flex items-center gap-8 font-['Lexend'] text-sm font-medium tracking-tight">
<a class="text-slate-600 dark:text-slate-400 hover:text-[#5D34F1] transition-colors" href="#features">Tính năng</a>
<a class="text-slate-600 dark:text-slate-400 hover:text-[#5D34F1] transition-colors" href="#process">Quy trình</a>
<a class="text-slate-600 dark:text-slate-400 hover:text-[#5D34F1] transition-colors" href="#cta">Đăng ký</a>
<a class="text-slate-600 dark:text-slate-400 hover:text-[#5D34F1] transition-colors" href="/contact">Liên hệ</a>
</div>
</div>
<div class="flex items-center gap-4">
<a href="/login" class="hidden sm:inline-block px-6 py-2.5 rounded-full font-['Lexend'] text-sm font-medium text-slate-600 hover:text-[#5D34F1] transition-all active:scale-95">Đăng nhập</a>
<a href="/register" class="px-6 py-2.5 rounded-full bg-[#5D34F1] text-white font-['Lexend'] text-sm font-bold shadow-lg shadow-[#5D34F1]/20 hover:opacity-90 transition-all active:scale-95 inline-block text-center">Bắt đầu ngay</a>
</div>
</nav>
</header>

<?php if (!empty($_flash_success) || !empty($_flash_error)): ?>
<div class="fixed top-24 left-0 right-0 z-40 px-8 pointer-events-none">
<div class="max-w-7xl mx-auto pointer-events-auto space-y-2">
<?php if (!empty($_flash_success)): ?>
<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm font-medium shadow-sm"><?= htmlspecialchars((string) $_flash_success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($_flash_error)): ?>
<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm font-medium shadow-sm"><?= htmlspecialchars((string) $_flash_error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<main class="mesh-gradient min-h-screen pt-20">
<section class="relative overflow-hidden pt-16 pb-24 md:pt-32 md:pb-40">
<div class="max-w-4xl mx-auto px-8 flex flex-col gap-8 text-center items-center z-10 relative">
<div class="inline-flex items-center px-4 py-2 rounded-full bg-primary-fixed text-[#5D34F1] font-label-md text-label-md">
<span class="material-symbols-outlined mr-2 text-sm filled">auto_awesome</span>
Ôn tập chủ động — không học vẹt
</div>
<h1 class="font-headline-lg text-[clamp(1.75rem,5vw,3rem)] leading-tight text-on-background max-w-4xl mx-auto">
Học Ít Hiểu Nhiều - Ôn Tập Hiệu Quả Với Trí Tuệ Nhân Tạo
</h1>
<p class="text-body-lg font-body-lg text-on-surface-variant max-w-2xl mx-auto">
Đừng đọc đi đọc lại tài liệu một cách thụ động. Hãy để AI biến giáo trình của bạn thành những thử thách trắc nghiệm, giúp bạn ghi nhớ sâu và nắm vững kiến thức chỉ sau một lần ôn.
</p>
<div class="flex flex-col items-center gap-3 pt-4">
<div class="flex flex-wrap gap-4 justify-center">
<a href="/register" class="px-8 py-4 rounded-full bg-[#5D34F1] text-white font-['Lexend'] text-lg font-bold shadow-xl shadow-[#5D34F1]/30 hover:translate-y-[-2px] transition-all active:scale-95 inline-block text-center">
Bắt đầu hành trình chinh phục điểm A
</a>
<a href="/login" class="px-8 py-4 rounded-full border-2 border-[#ede8fa]/60 bg-white/50 backdrop-blur-sm text-on-surface-variant font-['Lexend'] text-lg font-semibold hover:bg-white transition-all active:scale-95 inline-block text-center">
Đăng nhập
</a>
</div>
<p class="text-sm text-on-surface-variant/90 font-['Lexend'] max-w-md text-center">
Tham gia cùng cộng đồng học tập thông minh ngay hôm nay.
</p>
</div>
</div>
<div class="absolute top-1/4 left-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -translate-x-1/2 pointer-events-none" aria-hidden="true"></div>
<div class="absolute bottom-0 right-0 w-96 h-96 bg-secondary/5 rounded-full blur-3xl translate-x-1/4 translate-y-1/4 pointer-events-none" aria-hidden="true"></div>
</section>

<section class="py-24 bg-white/40 scroll-mt-24" id="features">
<div class="max-w-7xl mx-auto px-8">
<div class="text-center max-w-2xl mx-auto mb-16">
<h2 class="font-headline-md text-headline-md text-on-background mb-4">Học và ghi nhớ — không còn ôn vô định</h2>
<p class="text-body-md font-body-md text-on-surface-variant">Ba trụ cột giúp bạn ôn đúng trọng tâm, nhớ lâu và biết mình đang mạnh–yếu ở đâu.</p>
</div>
<div class="grid md:grid-cols-3 gap-8">
<div class="glass-card p-8 rounded-lg transition-all hover:translate-y-[-8px] hover:shadow-xl hover:shadow-primary/5 group">
<div class="w-14 h-14 rounded-full bg-primary-fixed flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">psychology</span>
</div>
<h3 class="font-headline-md text-xl mb-4">Chống Quên Kiến Thức</h3>
<p class="text-body-md text-on-surface-variant">AI tự động nhận diện các ý chính trong bài học để đặt câu hỏi, giúp bạn kiểm tra ngay những gì vừa đọc, tăng khả năng ghi nhớ dài hạn.</p>
</div>
<div class="glass-card p-8 rounded-lg transition-all hover:translate-y-[-8px] hover:shadow-xl hover:shadow-primary/5 group">
<div class="w-14 h-14 rounded-full bg-secondary-fixed flex items-center justify-center text-secondary mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">center_focus_strong</span>
</div>
<h3 class="font-headline-md text-xl mb-4">Ôn Tập Đúng Trọng Tâm</h3>
<p class="text-body-md text-on-surface-variant">Không cần làm lan man. Hệ thống tập trung vào các khái niệm quan trọng nhất trong tài liệu của bạn, giúp tối ưu hóa thời gian chuẩn bị cho kỳ thi.</p>
</div>
<div class="glass-card p-8 rounded-lg transition-all hover:translate-y-[-8px] hover:shadow-xl hover:shadow-primary/5 group">
<div class="w-14 h-14 rounded-full bg-[#E5DEFF] flex items-center justify-center text-[#5D34F1] mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">analytics</span>
</div>
<h3 class="font-headline-md text-xl mb-4">Đánh Giá Năng Lực</h3>
<p class="text-body-md text-on-surface-variant">Sau mỗi bộ câu hỏi, bạn sẽ biết mình đang yếu ở chương nào, đoạn nào để tập trung đọc lại, giúp lộ trình ôn tập trở nên rõ ràng và khoa học.</p>
</div>
</div>

<div id="process" class="scroll-mt-24 mt-20 pt-16 border-t border-[#ede8fa]/80">
<div class="text-center max-w-2xl mx-auto mb-12">
<h2 class="font-headline-md text-headline-md text-on-background mb-3">Quy trình học tập 3 bước</h2>
<p class="text-body-md text-on-surface-variant">Đơn giản để bạn thấy việc bắt đầu ôn với AI là nhẹ nhàng.</p>
</div>
<div class="grid md:grid-cols-3 gap-8 md:gap-6 relative">
<div class="hidden md:block absolute top-[2.25rem] left-[12%] right-[12%] h-0.5 bg-gradient-to-r from-primary/20 via-primary/40 to-primary/20 pointer-events-none" aria-hidden="true"></div>
<div class="relative flex flex-col items-center text-center gap-4 glass-card p-8 rounded-lg md:pt-10">
<div class="flex-shrink-0 w-14 h-14 rounded-full bg-[#5D34F1] text-white flex items-center justify-center font-headline-md text-xl font-bold shadow-lg shadow-[#5D34F1]/25">1</div>
<h4 class="font-headline-md text-xl">Tải tài liệu</h4>
<p class="text-body-md text-on-surface-variant">Đưa giáo trình hoặc ghi chú bài giảng lên.</p>
</div>
<div class="relative flex flex-col items-center text-center gap-4 glass-card p-8 rounded-lg md:pt-10">
<div class="flex-shrink-0 w-14 h-14 rounded-full bg-[#5D34F1] text-white flex items-center justify-center font-headline-md text-xl font-bold shadow-lg shadow-[#5D34F1]/25">2</div>
<h4 class="font-headline-md text-xl">Luyện tập</h4>
<p class="text-body-md text-on-surface-variant">Làm các câu hỏi trắc nghiệm do AI vừa &quot;đúc kết&quot; riêng cho bạn.</p>
</div>
<div class="relative flex flex-col items-center text-center gap-4 glass-card p-8 rounded-lg md:pt-10">
<div class="flex-shrink-0 w-14 h-14 rounded-full bg-[#5D34F1] text-white flex items-center justify-center font-headline-md text-xl font-bold shadow-lg shadow-[#5D34F1]/25">3</div>
<h4 class="font-headline-md text-xl">Làm chủ</h4>
<p class="text-body-md text-on-surface-variant">Tự tin bước vào phòng thi với khối lượng kiến thức đã được kiểm chứng.</p>
</div>
</div>
</div>
</div>
</section>

<section class="py-24 relative overflow-hidden scroll-mt-24" id="cta">
<div class="max-w-5xl mx-auto px-8 relative z-10">
<div class="bg-white rounded-lg p-12 md:p-20 text-center shadow-2xl border border-[#ede8fa] flex flex-col items-center gap-6">
<div class="w-20 h-20 bg-primary-fixed rounded-2xl flex items-center justify-center text-primary rotate-12 mb-2">
<span class="material-symbols-outlined text-4xl filled">school</span>
</div>
<h2 class="font-headline-lg text-3xl md:text-4xl text-on-background max-w-2xl">Biến mỗi buổi ôn thành bước tiến rõ rệt</h2>
<p class="text-body-lg text-on-surface-variant max-w-xl">
Đăng ký để AI đồng hành cùng lộ trình của bạn — ôn ít hơn, nhớ sâu hơn, vào thi tự tin hơn.
</p>
<div class="flex flex-col items-center gap-3 pt-4 w-full">
<a href="/register" class="px-10 py-4 rounded-full bg-[#5D34F1] text-white font-['Lexend'] text-lg md:text-xl font-bold shadow-xl shadow-[#5D34F1]/30 hover:scale-[1.02] transition-all active:scale-95 inline-block text-center max-w-full">
Bắt đầu hành trình chinh phục điểm A
</a>
<p class="text-sm text-on-surface-variant font-['Lexend']">
Tham gia cùng cộng đồng học tập thông minh ngay hôm nay.
</p>
<a href="#features" class="text-sm font-['Lexend'] font-semibold text-[#5D34F1] hover:underline mt-1">Xem tính năng và quy trình</a>
</div>
</div>
</div>
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] bg-primary/5 rounded-full blur-[120px] -z-10 pointer-events-none" aria-hidden="true"></div>
</section>
</main>

<footer id="contact" class="w-full py-12 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 scroll-mt-20">
<div class="max-w-7xl mx-auto px-8 flex flex-col md:flex-row justify-between items-center gap-8">
<div class="flex flex-col gap-4 items-center md:items-start">
<span class="text-lg font-bold text-[#5D34F1]">LivQuiz Learning</span>
<p class="font-['Lexend'] text-xs text-slate-500 dark:text-slate-400">© <?= date('Y') ?> LivQuiz Learning. Tất cả quyền được bảo lưu.</p>
</div>
<div class="flex flex-wrap justify-center gap-8 font-['Lexend'] text-xs text-slate-500 dark:text-slate-400">
<a class="hover:text-[#5D34F1] hover:underline transition-all" href="/privacy-policy">Bảo mật</a>
<a class="hover:text-[#5D34F1] hover:underline transition-all" href="/terms-of-use">Điều khoản</a>
<a class="hover:text-[#5D34F1] hover:underline transition-all" href="/help-center">Chính sách &amp; trợ giúp</a>
<a class="hover:text-[#5D34F1] hover:underline transition-all" href="/contact">Liên hệ</a>
</div>
<div class="flex gap-4">
<div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400" title="Chia sẻ" aria-hidden="true">
<span class="material-symbols-outlined text-lg">share</span>
</div>
<div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400" title="Web" aria-hidden="true">
<span class="material-symbols-outlined text-lg">public</span>
</div>
</div>
</div>
</footer>
</body>
</html>
