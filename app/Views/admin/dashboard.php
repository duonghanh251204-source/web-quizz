<?php
/** @var array<string, int> $stats */
/** @var int $chartDays */
/** @var array<int, string> $chartLabels */
/** @var array<int, int> $chartDoc */
/** @var array<int, int> $chartAi */
/** @var array<int, int> $chartExtract */

$stUsers = (int) ($stats['users'] ?? 0);
$stDocs = (int) ($stats['documents'] ?? 0);
$stQuestions = (int) ($stats['questions'] ?? 0);
$stAi = (int) ($stats['questions_ai'] ?? 0);
$stExtract = (int) ($stats['questions_extract'] ?? 0);
?>
<?php include __DIR__ . '/_nav.php'; ?>

<section class="card adm-dash-hero">
    <div class="adm-dash-hero__inner">
        <div class="adm-dash-hero__copy">
            <p class="adm-dash-hero__eyebrow">
                <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                Quản trị
            </p>
            <h1 class="adm-dash-hero__title">Bảng điều khiển</h1>
            <p class="adm-dash-hero__lead">Theo dõi người dùng, tài liệu và câu hỏi theo nguồn.</p>
        </div>
        <form method="get" action="/admin/dashboard" class="adm-dash-range">
            <label class="adm-dash-range__label" for="adm_chart_days">Khung thời gian biểu đồ</label>
            <select name="days" id="adm_chart_days" class="adm-dash-range__select" onchange="this.form.submit()">
                <option value="7" <?= (int) $chartDays === 7 ? 'selected' : '' ?>>7 ngày</option>
                <option value="14" <?= (int) $chartDays === 14 ? 'selected' : '' ?>>14 ngày</option>
                <option value="30" <?= (int) $chartDays === 30 ? 'selected' : '' ?>>30 ngày</option>
            </select>
        </form>
    </div>
</section>

<section class="adm-dash-metrics" aria-label="Thống kê tổng">
    <article class="adm-dash-metric">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">group</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= $stUsers ?></span>
            <span class="adm-dash-metric__label">Người dùng</span>
        </div>
    </article>
    <article class="adm-dash-metric">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">folder_open</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= $stDocs ?></span>
            <span class="adm-dash-metric__label">Tài liệu</span>
        </div>
    </article>
    <article class="adm-dash-metric">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">quiz</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= $stQuestions ?></span>
            <span class="adm-dash-metric__label">Tổng câu hỏi</span>
        </div>
    </article>
    <article class="adm-dash-metric adm-dash-metric--accent adm-dash-metric--ai">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">smart_toy</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= $stAi ?></span>
            <span class="adm-dash-metric__label">Từ AI</span>
        </div>
    </article>
    <article class="adm-dash-metric adm-dash-metric--accent adm-dash-metric--extract">
        <span class="adm-dash-metric__icon" aria-hidden="true"><span class="material-symbols-outlined">text_snippet</span></span>
        <div class="adm-dash-metric__body">
            <span class="adm-dash-metric__value mono"><?= $stExtract ?></span>
            <span class="adm-dash-metric__label">Trích xuất</span>
        </div>
    </article>
</section>

<section class="card adm-dash-chart-section">
    <div class="adm-dash-chart-head">
        <div>
            <h2 class="adm-dash-chart-title">Hoạt động theo ngày</h2>
            <p class="adm-dash-chart-sub">Tải tệp · Câu AI · Câu trích xuất</p>
        </div>
    </div>
    <div class="adm-dash-chart-canvas">
        <canvas id="adminActivityChart" aria-label="Biểu đồ hoạt động" role="img"></canvas>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    const doc = <?= json_encode($chartDoc, JSON_UNESCAPED_UNICODE) ?>;
    const ai = <?= json_encode($chartAi, JSON_UNESCAPED_UNICODE) ?>;
    const ext = <?= json_encode($chartExtract, JSON_UNESCAPED_UNICODE) ?>;
    const el = document.getElementById("adminActivityChart");
    if (!el || typeof Chart === "undefined") return;

    const font = '"Manrope", "Segoe UI", sans-serif';

    new Chart(el, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Tải tệp / ngày",
                    data: doc,
                    borderColor: "#5e2eef",
                    backgroundColor: "rgba(94, 46, 239, 0.14)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: "Câu từ AI",
                    data: ai,
                    borderColor: "#c026d3",
                    backgroundColor: "rgba(192, 38, 211, 0.08)",
                    fill: true,
                    tension: 0.35,
                    borderDash: [6, 4],
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: "Câu trích xuất",
                    data: ext,
                    borderColor: "#0d9488",
                    backgroundColor: "rgba(13, 148, 136, 0.12)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: {
                    position: "bottom",
                    align: "start",
                    labels: {
                        font: { family: font, size: 12, weight: "600" },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: "circle",
                    },
                },
                tooltip: {
                    backgroundColor: "rgba(45, 31, 71, 0.92)",
                    titleFont: { family: font, size: 13, weight: "700" },
                    bodyFont: { family: font, size: 13 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: true,
                },
            },
            scales: {
                x: {
                    grid: { color: "rgba(232, 222, 247, 0.8)" },
                    ticks: {
                        font: { family: font, size: 11 },
                        maxRotation: 45,
                        minRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: "rgba(232, 222, 247, 0.9)" },
                    ticks: {
                        font: { family: font, size: 11 },
                        precision: 0,
                    },
                },
            },
        },
    });
})();
</script>
