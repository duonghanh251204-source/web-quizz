<?php
/** @var string $formProvider */
/** @var string $effectiveProvider */
/** @var string $aiModelValue */
/** @var bool $openaiKeySet */
/** @var bool $geminiKeySet */
/** @var bool $deepseekKeySet */

$effectiveLabels = [
    'openai' => 'OpenAI',
    'gemini' => 'Google Gemini',
    'deepseek' => 'DeepSeek',
    'chatbot_ai' => 'Chatbot AI (Python)',
    'mock' => 'Giả lập',
];
$effectiveLabel = $effectiveLabels[$effectiveProvider] ?? $effectiveProvider;

/** @var array<string, string> $openaiModelChoices id => nhãn hiển thị */
$openaiModelChoices = [
    'gpt-4o' => 'GPT-4o',
    'gpt-4o-mini' => 'GPT-4o mini',
    'gpt-4-turbo' => 'GPT-4 Turbo',
    'gpt-4.1' => 'GPT-4.1',
    'gpt-4.1-mini' => 'GPT-4.1 mini',
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'o1' => 'o1',
    'o1-mini' => 'o1-mini',
    'o3-mini' => 'o3-mini',
];
/** @var array<string, string> $geminiModelChoices */
$geminiModelChoices = [
    'gemini-2.5-flash' => 'Gemini 2.5 Flash',
    'gemini-2.5-pro' => 'Gemini 2.5 Pro',
    'gemini-2.0-flash' => 'Gemini 2.0 Flash',
    'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash-Lite',
    'gemini-1.5-flash' => 'Gemini 1.5 Flash',
    'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash-8B',
    'gemini-1.5-pro' => 'Gemini 1.5 Pro',
];
/** @var array<string, string> $deepseekModelChoices */
$deepseekModelChoices = [
    'deepseek-v4-flash' => 'DeepSeek V4 Flash',
    'deepseek-chat' => 'DeepSeek Chat',
    'deepseek-reasoner' => 'DeepSeek Reasoner',
];

$modelChoicesNow = match ($formProvider) {
    'gemini' => $geminiModelChoices,
    'deepseek' => $deepseekModelChoices,
    default => $openaiModelChoices,
};
$firstModelKey = $modelChoicesNow !== [] ? (string) array_key_first($modelChoicesNow) : '';

include __DIR__ . '/_nav.php';
?>

<section class="card admin-ai-hero">
    <div class="admin-ai-hero__inner">
        <div class="admin-ai-hero__copy">
            <p class="admin-ai-hero__eyebrow">
                <span class="material-symbols-outlined" aria-hidden="true">tune</span>
                Cấu hình AI
            </p>
            <h1 class="admin-ai-hero__title">Nhà cung cấp &amp; model</h1>
        </div>
        <div class="admin-ai-hero__badge-wrap">
            <span class="admin-ai-hero__badge-label">Đang hoạt động</span>
            <span class="admin-ai-hero__badge"><?= htmlspecialchars($effectiveLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</section>

<section class="card admin-ai-panel">
    <form method="POST" action="/admin/ai" class="admin-ai-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($_csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <div class="admin-ai-section">
            <h2 class="admin-ai-section__title">Nhà cung cấp</h2>
            <div class="admin-ai-provider-grid" role="radiogroup" aria-label="Nhà cung cấp AI">
                <label class="admin-ai-provider-card">
                    <input type="radio" name="ai_provider" value="openai" <?= $formProvider === 'openai' ? 'checked' : '' ?>>
                    <span class="admin-ai-provider-card__inner">
                        <span class="material-symbols-outlined admin-ai-provider-card__icon" aria-hidden="true">smart_toy</span>
                        <span class="admin-ai-provider-card__name">OpenAI</span>
                    </span>
                </label>
                <label class="admin-ai-provider-card">
                    <input type="radio" name="ai_provider" value="gemini" <?= $formProvider === 'gemini' ? 'checked' : '' ?>>
                    <span class="admin-ai-provider-card__inner">
                        <span class="material-symbols-outlined admin-ai-provider-card__icon" aria-hidden="true">auto_awesome</span>
                        <span class="admin-ai-provider-card__name">Google Gemini</span>
                    </span>
                </label>
                <label class="admin-ai-provider-card">
                    <input type="radio" name="ai_provider" value="deepseek" <?= $formProvider === 'deepseek' ? 'checked' : '' ?>>
                    <span class="admin-ai-provider-card__inner">
                        <span class="material-symbols-outlined admin-ai-provider-card__icon" aria-hidden="true">bolt</span>
                        <span class="admin-ai-provider-card__name">DeepSeek</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="admin-ai-section admin-ai-section--border">
            <h2 class="admin-ai-section__title">Model</h2>
            <label class="admin-ai-model-block">
                <select name="ai_model" id="admin_ai_model_select" class="admin-ai-model-select" required aria-label="Chọn model">
                    <?php foreach ($modelChoicesNow as $mid => $mlabel):
                        $isSel = ($aiModelValue !== '' && $aiModelValue === $mid)
                            || ($aiModelValue === '' && $mid === $firstModelKey);
                        ?>
                        <option value="<?= htmlspecialchars($mid, ENT_QUOTES, 'UTF-8') ?>"<?= $isSel ? ' selected' : '' ?>>
                            <?= htmlspecialchars($mlabel . ' — ' . $mid, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($aiModelValue !== '' && !array_key_exists($aiModelValue, $modelChoicesNow)): ?>
                        <option value="<?= htmlspecialchars($aiModelValue, ENT_QUOTES, 'UTF-8') ?>" selected>
                            <?= htmlspecialchars($aiModelValue, ENT_QUOTES, 'UTF-8') ?> (khác)
                        </option>
                    <?php endif; ?>
                </select>
            </label>
        </div>

        <script type="application/json" id="admin-ai-model-presets"><?= json_encode([
            'openai' => $openaiModelChoices,
            'gemini' => $geminiModelChoices,
            'deepseek' => $deepseekModelChoices,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?></script>
        <script>
        (function () {
            var presetsEl = document.getElementById("admin-ai-model-presets");
            var sel = document.getElementById("admin_ai_model_select");
            if (!presetsEl || !sel) return;
            var presets;
            try {
                presets = JSON.parse(presetsEl.textContent || "{}");
            } catch (e) {
                return;
            }

            function rebuild(provider) {
                var map = presets[provider] || {};
                var prev = (sel.value || "").trim();
                sel.innerHTML = "";
                Object.keys(map).forEach(function (id) {
                    var opt = document.createElement("option");
                    opt.value = id;
                    opt.textContent = map[id] + " — " + id;
                    sel.appendChild(opt);
                });
                if (prev && Object.prototype.hasOwnProperty.call(map, prev)) {
                    sel.value = prev;
                } else {
                    var keys = Object.keys(map);
                    if (keys.length) {
                        sel.value = keys[0];
                    }
                }
            }

            document.querySelectorAll('input[name="ai_provider"]').forEach(function (r) {
                r.addEventListener("change", function () {
                    if (!r.checked) return;
                    rebuild(r.value);
                });
            });
        })();
        </script>

        <div class="admin-ai-section admin-ai-section--border">
            <div class="admin-ai-section__head">
                <h2 class="admin-ai-section__title">API Key</h2>
                <div class="admin-ai-key-chips" aria-label="Key đã lưu trong CSDL">
                    <span class="admin-ai-key-chip <?= $openaiKeySet ? 'admin-ai-key-chip--on' : 'admin-ai-key-chip--off' ?>">OpenAI</span>
                    <span class="admin-ai-key-chip <?= $geminiKeySet ? 'admin-ai-key-chip--on' : 'admin-ai-key-chip--off' ?>">Gemini</span>
                    <span class="admin-ai-key-chip <?= $deepseekKeySet ? 'admin-ai-key-chip--on' : 'admin-ai-key-chip--off' ?>">DeepSeek</span>
                </div>
            </div>
            <label class="admin-ai-key-field">
                <input type="password" name="api_key" autocomplete="off" placeholder="Dán key mới cho nhà cung cấp đang chọn">
            </label>

            <?php if ($openaiKeySet || $geminiKeySet || $deepseekKeySet): ?>
                <div class="admin-ai-clear-row">
                    <?php if ($openaiKeySet): ?>
                        <label class="admin-ai-clear-item">
                            <input type="checkbox" name="clear_openai_key" value="1">
                            <span>Xóa key OpenAI</span>
                        </label>
                    <?php endif; ?>
                    <?php if ($geminiKeySet): ?>
                        <label class="admin-ai-clear-item">
                            <input type="checkbox" name="clear_gemini_key" value="1">
                            <span>Xóa key Gemini</span>
                        </label>
                    <?php endif; ?>
                    <?php if ($deepseekKeySet): ?>
                        <label class="admin-ai-clear-item">
                            <input type="checkbox" name="clear_deepseek_key" value="1">
                            <span>Xóa key DeepSeek</span>
                        </label>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-ai-actions">
            <button type="submit" class="btn">Lưu cấu hình</button>
            <a href="/admin/dashboard" class="btn ghost">Về thống kê</a>
        </div>
    </form>
</section>
