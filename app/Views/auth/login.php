<!DOCTYPE html>
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Đăng nhập | LivQuiz Learning</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Manrope:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              surface: "#fcf4ff",
              background: "#fcf4ff",
              primary: "#5e2eef",
              "primary-dim": "#5216e3",
              "surface-container-low": "#f7edff",
              "surface-container": "#f0e3ff",
              "surface-container-lowest": "#ffffff",
              "surface-container-high": "#ecdcff",
              "surface-container-highest": "#e7d5ff",
              "on-surface": "#36274e",
              "on-surface-variant": "#64547e",
              outline: "#806f9a",
              "outline-variant": "#b8a5d4"
            },
            borderRadius: {
              DEFAULT: "1rem",
              lg: "2rem",
              xl: "3rem",
              full: "9999px"
            },
            fontFamily: {
              headline: ["Plus Jakarta Sans"],
              body: ["Manrope"]
            }
          }
        }
      }
    </script>
<style>
      .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      }
      .main-gradient {
        background: linear-gradient(135deg, #5e2eef 0%, #5216e3 100%);
      }
      .glass-panel {
        background: rgba(255, 255, 255, 0.74);
        backdrop-filter: blur(24px);
      }
    </style>
</head>
<body class="bg-background text-on-surface antialiased font-body min-h-screen">
<header class="fixed top-0 w-full z-40 bg-[#fcf4ff]/70 backdrop-blur-xl">
  <div class="max-w-7xl mx-auto h-20 px-8 flex items-center justify-between">
    <a href="/" class="font-headline text-2xl font-black tracking-tighter bg-gradient-to-r from-primary to-primary-dim bg-clip-text text-transparent">LivQuiz Learning</a>
    <div class="flex items-center gap-6">
      <a class="font-headline font-bold text-sm text-on-surface/70 hover:text-primary transition-colors" href="/register">Đăng ký</a>
      <span class="font-headline font-bold text-sm text-primary border-b-2 border-primary">Đăng nhập</span>
    </div>
  </div>
</header>

<main class="min-h-screen pt-24 px-6 pb-12 flex items-center justify-center">
  <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-8 items-center">
    <section class="hidden lg:flex flex-col gap-8 pr-10">
      <div class="space-y-4">
        <span class="inline-block rounded-full bg-surface-container-highest text-primary text-xs font-bold uppercase tracking-wider px-4 py-1.5">Chào mừng quay lại</span>
        <h1 class="font-headline text-[3.4rem] leading-[1.08] font-extrabold tracking-tight text-on-surface">Tiếp tục hành trình <span class="text-primary italic">học tập</span> của bạn.</h1>
        <p class="text-on-surface-variant text-lg max-w-md">Đăng nhập để truy cập bài kiểm tra, bài nộp và không gian làm việc theo vai trò.</p>
      </div>
      <div class="relative overflow-hidden rounded-2xl bg-surface-container shadow-[0_20px_40px_rgba(54,39,78,0.08)]">
        <img class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAbRpJwmMO8Z58oHn5qKqtQRkW0RfgJFw9-Q7lv5x6bt40zLXSydHhPLsBTBE9N8KR5Gwv3l9vhOQz4SeZy7CoTNJkVXvgG2U9UiceIXquatHaSA_rTRlm7zwe1k1FNDcUmMXzvZZf1CVmx9ukPa0t5xDAFi96qQODcyWH205zL3Y6h_9xpZ1LuabE_7bJ50JEl_2GamK2Y6hZD4IHhjcXdXmyG2XTHbUEGmVjnh7WtrI0vpr1IDiOmFL9nMd5soQ8D8fXCuaA4y40" alt="Hình minh họa hệ thống"/>
      </div>
    </section>

    <section class="w-full max-w-md mx-auto">
      <div class="glass-panel rounded-3xl p-10 shadow-[0_20px_40px_rgba(54,39,78,0.08)] border border-outline-variant/20">
        <div class="mb-8">
          <h2 class="font-headline text-3xl font-bold">Đăng nhập</h2>
          <p class="text-on-surface-variant mt-2">Tiếp tục đến không gian làm việc của bạn.</p>
        </div>

        <?php if (!empty($_flash_success)): ?>
          <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm font-semibold">
            <?= htmlspecialchars((string) $_flash_success, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($_flash_error)): ?>
          <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm font-semibold">
            <?= htmlspecialchars((string) $_flash_error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/login" class="space-y-5">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">

          <div class="space-y-2">
            <label for="login_email" class="text-xs uppercase tracking-widest font-bold text-on-surface-variant">Địa chỉ email</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
              <input id="login_email" name="email" type="email" autocomplete="email" required placeholder="vidu@truong.edu.vn" class="w-full rounded-2xl bg-surface-container-low border-none pl-12 pr-4 py-4 text-on-surface placeholder:text-outline/80 focus:ring-2 focus:ring-primary/30">
            </div>
          </div>

          <div class="space-y-2">
            <label for="login_password" class="text-xs uppercase tracking-widest font-bold text-on-surface-variant">Mật khẩu</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
              <input id="login_password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••" class="w-full rounded-2xl bg-surface-container-low border-none pl-12 pr-12 py-4 text-on-surface placeholder:text-outline/80 focus:ring-2 focus:ring-primary/30">
              <button type="button" data-toggle-target="#login_password" class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors" title="Hiện hoặc ẩn mật khẩu" aria-label="Hiện hoặc ẩn mật khẩu">
                <span class="material-symbols-outlined">visibility</span>
              </button>
            </div>
          </div>

          <button type="submit" class="w-full main-gradient text-white font-headline font-bold py-4 rounded-full shadow-lg shadow-primary/25 hover:scale-[1.01] active:scale-95 transition-transform">Đăng nhập</button>
        </form>

        <p class="mt-7 text-sm text-on-surface-variant text-center">
          Chưa có tài khoản?
          <a href="/register" class="text-primary font-bold hover:underline">Đăng ký miễn phí</a>
        </p>
      </div>
    </section>
  </div>
</main>

<footer class="w-full bg-[#f7edff] mt-10">
  <div class="max-w-7xl mx-auto px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
    <span class="font-headline font-bold text-on-surface">LivQuiz Learning</span>
    <p class="text-sm text-on-surface/60">© 2026 LivQuiz Learning. Học tập năng động.</p>
  </div>
</footer>

<script>
  document.querySelectorAll('[data-toggle-target]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const selector = btn.getAttribute('data-toggle-target');
      if (!selector) return;
      const input = document.querySelector(selector);
      if (!(input instanceof HTMLInputElement)) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });
</script>
</body>
</html>
