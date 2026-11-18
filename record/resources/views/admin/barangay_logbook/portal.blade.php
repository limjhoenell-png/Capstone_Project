@extends('layouts.admin', ['title' => 'Logbook Reports'])

@section('content')
    @php
        $analyticsJsonUrl = route('admin.barangay-logbook.analytics');
    @endphp

    <style>
        #brgy-analytics {
            --brgy-chart-primary: rgba(37, 99, 235, 0.88);
            --brgy-chart-primary-soft: rgba(37, 99, 235, 0.18);
            --brgy-chart-grid: rgba(100, 116, 139, 0.22);
            --brgy-chart-tick: #64748b;
            --brgy-chart-legend: #475569;
            --brgy-pie-empty: #cbd5e1;
        }

        html[data-theme="dark"] #brgy-analytics {
            --brgy-chart-primary: rgba(96, 165, 250, 0.92);
            --brgy-chart-primary-soft: rgba(59, 130, 246, 0.22);
            --brgy-chart-grid: rgba(148, 163, 184, 0.12);
            --brgy-chart-tick: #94a3b8;
            --brgy-chart-legend: #cbd5e1;
            --brgy-pie-empty: #475569;
        }

        .brgy-section {
            scroll-margin-top: calc(var(--header-h) + 52px);
            margin-bottom: 36px;
        }
        .brgy-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .brgy-section-head h3 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: var(--c-text);
        }
        .brgy-section-head p {
            margin: 0;
            font-size: 13px;
            color: var(--c-text-muted);
        }
        .brgy-stat-highlight {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border-radius: var(--radius-lg);
            background: linear-gradient(120deg, #dbeafe 0%, #eff6ff 100%);
            border: 1px solid #bfdbfe;
            margin-bottom: 20px;
        }
        .brgy-stat-highlight .big {
            font-size: 28px;
            font-weight: 800;
            color: #1e40af;
            font-family: 'DM Mono', monospace;
            line-height: 1;
        }
        .brgy-stat-highlight .lbl {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #3b82f6;
            margin-bottom: 4px;
        }
        .brgy-stat-highlight .name {
            font-size: 16px;
            font-weight: 700;
            color: var(--c-text);
        }
        .brgy-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 8px;
        }
        .brgy-chart-card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 16px 16px 12px;
            box-shadow: var(--shadow-sm);
        }
        .brgy-chart-card h5 {
            margin: 0 0 4px;
            font-size: 13px;
            font-weight: 700;
            color: var(--c-text);
        }
        .brgy-chart-card .hint {
            font-size: 11px;
            color: var(--c-text-muted);
            margin-bottom: 10px;
        }
        .brgy-chart-card canvas { max-height: 220px; }
        .brgy-report-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }
        .brgy-report-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 12px;
            border-radius: var(--radius-sm);
            background: var(--c-primary);
            border: 1px solid var(--c-primary);
            color: #fff;
        }
        .brgy-report-btn svg { width: 14px; height: 14px; }
        .brgy-report-btn:hover { filter: brightness(1.05); }
        .brgy-period-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 0;
        }
        .brgy-period-tabs button {
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--c-border);
            background: var(--c-surface-2);
            color: var(--c-text-secondary);
            cursor: pointer;
            transition: background var(--transition), border-color var(--transition), color var(--transition);
        }
        .brgy-period-tabs button.active {
            background: var(--c-primary);
            border-color: var(--c-primary);
            color: #fff;
        }
        .brgy-period-tabs button:hover:not(.active) {
            border-color: var(--c-primary);
            color: var(--c-primary);
        }
    </style>

    <section id="brgy-analytics" class="brgy-section">
        <div class="brgy-section-head">
            <h3>Data analytics</h3>
        </div>
        <div class="card">
            <div id="analytics-top-slot"></div>
            <div class="brgy-report-row">
                <div class="brgy-period-tabs" role="tablist" aria-label="Report period">
                    <button type="button" data-period="day" class="active">Day</button>
                    <button type="button" data-period="week">Week</button>
                    <button type="button" data-period="month">Month</button>
                    <button type="button" data-period="year">Year</button>
                </div>
                <a id="logbook-report-download" class="brgy-report-btn" data-report-url="{{ route('admin.barangay-logbook.report') }}" href="{{ route('admin.barangay-logbook.report', ['period' => 'day']) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download report
                </a>
            </div>
            <div class="brgy-chart-grid">
                <div class="brgy-chart-card">
                    <h5>Visits by period</h5>
                    <p class="hint">Bar chart — record counts per bucket</p>
                    <canvas id="chart-bar" height="200" aria-label="Bar chart of visits"></canvas>
                </div>
                <div class="brgy-chart-card">
                    <h5>Trend</h5>
                    <p class="hint">Line chart — same data as trend</p>
                    <canvas id="chart-line" height="200" aria-label="Line chart of visits"></canvas>
                </div>
                <div class="brgy-chart-card">
                    <h5>Distribution</h5>
                    <p class="hint">Pie chart — purpose / transaction mix (top slices)</p>
                    <canvas id="chart-pie" height="200" aria-label="Pie chart of purposes"></canvas>
                </div>
            </div>
        </div>
    </section>

    @vite('resources/js/chart.js')
    <script>
        (function () {
            const analyticsUrl = @json($analyticsJsonUrl);

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
                });
            }

            /* ── Analytics & Chart.js ── */
            function readBrgyChartTheme() {
                const el = document.getElementById('brgy-analytics');
                const cs = el ? getComputedStyle(el) : getComputedStyle(document.documentElement);
                function v(name, fb) {
                    const t = cs.getPropertyValue(name).trim();
                    return t || fb;
                }
                return {
                    primary: v('--brgy-chart-primary', 'rgba(37, 99, 235, 0.85)'),
                    primarySoft: v('--brgy-chart-primary-soft', 'rgba(37, 99, 235, 0.2)'),
                    grid: v('--brgy-chart-grid', 'rgba(148, 163, 184, 0.25)'),
                    tick: v('--brgy-chart-tick', '#64748b'),
                    legend: v('--brgy-chart-legend', '#475569'),
                    pieEmpty: v('--brgy-pie-empty', '#cbd5e1'),
                };
            }
            let chartBar, chartLine, chartPie;
            let analyticsPayload = null;
            let currentPeriod = 'day';

            function destroyChart(c) {
                if (c) c.destroy();
            }

            function renderCharts() {
                if (!analyticsPayload || !window.Chart) return;
                const chartColors = readBrgyChartTheme();
                const keyMap = { day: 'by_day', week: 'by_week', month: 'by_month', year: 'by_year' };
                const series = analyticsPayload[keyMap[currentPeriod]] || [];
                const labels = series.map(function (x) { return x.label; });
                const values = series.map(function (x) { return x.count; });

                const barCtx = document.getElementById('chart-bar');
                const lineCtx = document.getElementById('chart-line');
                destroyChart(chartBar);
                destroyChart(chartLine);
                chartBar = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{ label: 'Records', data: values, backgroundColor: chartColors.primary, borderRadius: 6 }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { maxRotation: 45, minRotation: 0, color: chartColors.tick }, grid: { display: false } },
                            y: { beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.tick } },
                        },
                    },
                });
                chartLine = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Records',
                            data: values,
                            borderColor: chartColors.primary,
                            backgroundColor: chartColors.primarySoft,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { maxRotation: 45, color: chartColors.tick }, grid: { display: false } },
                            y: { beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.tick } },
                        },
                    },
                });

                const pieData = analyticsPayload.purpose_breakdown || [];
                const pieLabels = pieData.map(function (x) { return x.label; });
                const pieValues = pieData.map(function (x) { return x.count; });
                const palette = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2', '#db2777', '#4f46e5'];
                destroyChart(chartPie);
                chartPie = new Chart(document.getElementById('chart-pie'), {
                    type: 'pie',
                    data: {
                        labels: pieLabels.length ? pieLabels : ['No breakdown'],
                        datasets: [{
                            data: pieValues.length ? pieValues : [1],
                            backgroundColor: pieLabels.length ? palette : [chartColors.pieEmpty],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, color: chartColors.legend } } },
                    },
                });
            }

            document.querySelectorAll('.brgy-period-tabs button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.brgy-period-tabs button').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    currentPeriod = btn.getAttribute('data-period') || 'day';
                    syncReportLink();
                    renderCharts();
                });
            });

            function syncReportLink() {
                const link = document.getElementById('logbook-report-download');
                if (!link) return;
                const base = link.getAttribute('data-report-url') || link.href;
                const url = new URL(base, window.location.origin);
                url.searchParams.set('period', currentPeriod);
                link.href = url.toString();
            }

            syncReportLink();

            async function loadAnalytics() {
                const slot = document.getElementById('analytics-top-slot');
                try {
                    const res = await fetch(analyticsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    analyticsPayload = await res.json();

                    const top = analyticsPayload.top_visitor;
                    if (top && top.name) {
                        slot.innerHTML = '<div class="brgy-stat-highlight"><div><div class="lbl">Most frequent visitor</div><div class="name">' + escapeHtml(top.name) + '</div></div><div class="big">' + top.count + '</div></div>';
                    } else {
                        slot.innerHTML = '<div class="brgy-stat-highlight" style="background:var(--c-surface-2);border-color:var(--c-border);"><div><div class="lbl">Most frequent visitor</div><div class="name">No logbook data yet</div></div><div class="big">0</div></div>';
                    }
                    renderCharts();
                } catch (e) {
                    console.error(e);
                }
            }
            loadAnalytics();
            window.addEventListener('hris-theme-changed', function () {
                if (analyticsPayload) {
                    renderCharts();
                }
            });
        })();
    </script>
@endsection
