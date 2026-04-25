<?php
declare(strict_types=1);

/**
 * Revenue analytics (Chart.js): expects $revenueChartDatasets from getRevenueChartDatasets()
 * and $chartAccent 'super' | 'admin' for theme.
 *
 * @var array $revenueChartDatasets
 * @var string $chartAccent
 */
if (!isset($revenueChartDatasets) || !is_array($revenueChartDatasets)) {
    return;
}
$accent = ($chartAccent ?? 'admin') === 'super' ? 'super' : 'admin';
$primaryRgb = $accent === 'super' ? '220, 38, 38' : '124, 58, 237';
$canvasId = $accent === 'super' ? 'revenueAnalyticsChartSuper' : 'revenueAnalyticsChartAdmin';
$currency = (string)getSystemSetting('currency', 'IQD ');
$usdToIqdRate = (float)getSystemSetting('usd_to_iqd_rate', 1300);
$isIqdCurrency = strtoupper(trim($currency)) === 'IQD' || str_starts_with(strtoupper(trim($currency)), 'IQD');
?>
                <div class="revenue-analytics-widget bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8 lg:mb-12">
                    <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center <?= $accent === 'super' ? 'bg-red-100 text-red-600' : 'bg-purple-100 text-purple-600' ?>">
                                <i class="fas fa-chart-area"></i>
                            </span>
                            <?= e(t('sales_trend')) ?>
                        </h2>
                        <div class="revenue-analytics-tabs flex flex-wrap gap-2 p-1 rounded-xl bg-gray-100/80" role="tablist" aria-label="<?= e(t('sales_trend')) ?>">
                            <button type="button" data-range="daily" class="revenue-range-btn px-4 py-2 rounded-lg text-sm font-bold transition-all bg-white text-gray-700 shadow-sm ring-1 ring-gray-200/80">
                                <?= e(t('daily')) ?>
                            </button>
                            <button type="button" data-range="weekly" class="revenue-range-btn px-4 py-2 rounded-lg text-sm font-bold text-gray-600 hover:bg-white/80 transition-all">
                                <?= e(t('weekly')) ?>
                            </button>
                            <button type="button" data-range="monthly" class="revenue-range-btn px-4 py-2 rounded-lg text-sm font-bold text-gray-600 hover:bg-white/80 transition-all">
                                <?= e(t('monthly')) ?>
                            </button>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <p class="text-sm text-gray-500 mb-4"><?= e(t('reports_desc')) ?></p>
                        <div class="h-72 sm:h-80 w-full min-h-[16rem]">
                            <canvas id="<?= e($canvasId) ?>"></canvas>
                        </div>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
                <script>
                (function () {
                    const datasets = <?= json_encode($revenueChartDatasets, JSON_THROW_ON_ERROR) ?>;
                    const canvas = document.getElementById('<?= e($canvasId) ?>');
                    if (!canvas || typeof Chart === 'undefined') return;

                    const primaryRgb = '<?= e($primaryRgb) ?>';
                    const accent = '<?= e($accent) ?>';
                    const currency = <?= json_encode($currency) ?>;
                    const isIqd = <?= $isIqdCurrency ? 'true' : 'false' ?>;
                    const usdToIqdRate = <?= json_encode($usdToIqdRate) ?>;
                    const tabRoot = canvas.closest('.revenue-analytics-widget') || document;
                    let chart = null;

                    const activeClasses = accent === 'super'
                        ? ['bg-red-600', 'text-white', 'shadow-md', 'ring-0']
                        : ['bg-purple-600', 'text-white', 'shadow-md', 'ring-0'];
                    const inactiveClasses = ['bg-transparent', 'text-gray-600', 'shadow-none', 'ring-0'];

                    function setActiveButton(range) {
                        tabRoot.querySelectorAll('.revenue-range-btn').forEach(function (btn) {
                            activeClasses.forEach(function (c) { btn.classList.remove(c); });
                            inactiveClasses.forEach(function (c) { btn.classList.add(c); });
                            btn.classList.add('hover:bg-white/80');
                        });
                        const cur = tabRoot.querySelector('.revenue-range-btn[data-range="' + range + '"]');
                        if (cur) {
                            inactiveClasses.forEach(function (c) { cur.classList.remove(c); });
                            cur.classList.remove('hover:bg-white/80');
                            activeClasses.forEach(function (c) { cur.classList.add(c); });
                        }
                    }

                    function render(range) {
                        const d = datasets[range];
                        if (!d || !d.labels) return;

                        if (chart) chart.destroy();

                        chart = new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: d.labels,
                                datasets: [{
                                    label: <?= json_encode(t('revenue')) ?>,
                                    data: d.revenue.map(function (v) { return parseFloat(v); }),
                                    borderColor: 'rgb(' + primaryRgb + ')',
                                    backgroundColor: 'rgba(' + primaryRgb + ', 0.08)',
                                    borderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 6,
                                    tension: 0.35,
                                    fill: true,
                                    yAxisID: 'y'
                                }, {
                                    label: <?= json_encode(t('orders')) ?>,
                                    data: d.orders.map(function (v) { return parseInt(v, 10); }),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.06)',
                                    borderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 6,
                                    tension: 0.35,
                                    fill: true,
                                    yAxisID: 'y1'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        align: 'end',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 18,
                                            font: { family: "'Inter', sans-serif", size: 12 }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0,0,0,0.82)',
                                        padding: 12,
                                        cornerRadius: 8
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: { maxRotation: 45, minRotation: 0, font: { size: 10 } }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        position: 'left',
                                        grid: { color: 'rgba(0,0,0,0.05)' },
                                        ticks: {
                                            callback: function (value) {
                                                const v = Number(value);
                                                const converted = isIqd ? (v * (usdToIqdRate || 1300)) : v;
                                                const formatted = Number(converted).toLocaleString(undefined, { maximumFractionDigits: 0 });
                                                return (isIqd ? (currency.trim() + ' ') : currency) + formatted;
                                            }
                                        }
                                    },
                                    y1: {
                                        beginAtZero: true,
                                        position: 'right',
                                        grid: { display: false }
                                    }
                                }
                            }
                        });
                    }

                    tabRoot.querySelectorAll('.revenue-range-btn').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            const range = btn.getAttribute('data-range');
                            setActiveButton(range);
                            render(range);
                        });
                    });

                    setActiveButton('daily');
                    render('daily');
                })();
                </script>
