@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    @php
        $hrisS = $hrisFigures['summary'] ?? [];
        $present = (int) ($hrisS['present_today'] ?? 0);
        $absent = (int) ($hrisS['absent_today'] ?? 0);
        $roster = max($present + $absent, $employeeCount);
        $rate = (float) ($hrisS['attendance_rate_pct'] ?? 0);
        $periodStart = $hrisS['period_start'] ?? null;
        $periodEnd = $hrisS['period_end'] ?? null;
        $attendancePunches = collect($attendancePunches)->values();
        $logbookTransactions = collect($logbookTransactions)->values();
        $covered = $roster > 0 ? min(100, round($present / $roster * 100)) : 0;
    @endphp

    <style>
        .ops-hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .ops-kicker {
            margin: 0 0 4px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--c-primary-text);
        }
        .ops-hero h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: -.03em;
            line-height: 1.15;
        }
        .ops-hero p { margin: 6px 0 0; max-width: 46rem; color: var(--c-text-secondary); font-size: 14px; }
        .ops-kpis {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .ops-kpi {
            display: block;
            text-decoration: none;
            color: inherit;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 16px 16px 14px;
            box-shadow: var(--shadow-xs);
        }
        .ops-kpi:hover { border-color: var(--c-border-strong); box-shadow: var(--shadow-sm); }
        .ops-kpi__label { font-size: 12px; font-weight: 650; color: var(--c-text-secondary); }
        .ops-kpi__value {
            margin-top: 6px;
            font-family: 'DM Mono', ui-monospace, monospace;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -.03em;
            line-height: 1;
        }
        .ops-kpi__hint { margin-top: 6px; font-size: 12px; color: var(--c-text-muted); }
        .ops-kpi--good { border-top: 3px solid #059669; }
        .ops-kpi--warn { border-top: 3px solid #d97706; }
        .ops-kpi--bad { border-top: 3px solid #e11d48; }
        .ops-kpi--info { border-top: 3px solid var(--c-primary); }
        .ops-kpi--late { border-top: 3px solid #f97316; }
        .ops-meter { height: 6px; border-radius: 99px; background: var(--c-surface-2); margin-top: 12px; overflow: hidden; }
        .ops-meter > span { display: block; height: 100%; background: var(--c-primary); border-radius: inherit; }
        .ops-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 14px; margin-bottom: 14px; }
        .ops-panel {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xs);
            min-width: 0;
        }
        .ops-panel__head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: baseline;
            padding: 14px 16px 10px;
        }
        .ops-panel__head h2 { margin: 0; font-size: 15px; letter-spacing: -.02em; }
        .ops-panel__head p, .ops-panel__head a { margin: 0; font-size: 12px; color: var(--c-text-muted); }
        .ops-panel__head a { color: var(--c-primary-text); font-weight: 650; text-decoration: none; }
        .ops-list { list-style: none; margin: 0; padding: 0 8px 8px; }
        .ops-list li {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px 12px;
            align-items: center;
            padding: 9px 8px;
            border-top: 1px solid var(--c-border);
        }
        .ops-list strong { display: block; font-size: 14px; font-weight: 650; }
        .ops-list span { display: block; font-size: 12px; color: var(--c-text-muted); }
        .ops-pill {
            font-size: 11px;
            font-weight: 700;
            border-radius: 99px;
            padding: 3px 8px;
            white-space: nowrap;
        }
        .ops-pill--in { background: var(--c-success-bg); color: var(--c-success-text); }
        .ops-pill--out { background: var(--c-surface-2); color: var(--c-text-secondary); }
        .ops-pill--bio { background: var(--c-primary-light); color: var(--c-primary-text); }
        .ops-pill--manual { background: var(--c-surface-2); color: var(--c-text-secondary); }
        .ops-empty { padding: 22px 16px 18px; color: var(--c-text-muted); font-size: 13px; }
        @media (max-width: 980px) {
            .ops-kpis, .ops-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 680px) {
            .ops-kpis, .ops-grid { grid-template-columns: 1fr; }
        }
    </style>

    <header class="ops-hero">
        <div>
            <p class="ops-kicker">{{ now()->format('l, F j') }}</p>
            <h1>{{ $present }} of {{ $roster }} in today</h1>
        </div>
    </header>

    <section class="ops-kpis" aria-label="Today">
        <a class="ops-kpi ops-kpi--good" href="{{ route('admin.attendances.index') }}">
            <div class="ops-kpi__label">Present today</div>
            <div class="ops-kpi__value">{{ $present }}</div>
            <div class="ops-meter" aria-hidden="true"><span style="width: {{ $covered }}%"></span></div>
            <div class="ops-kpi__hint">{{ $covered }}% of the roster has a punch</div>
        </a>
        <a class="ops-kpi ops-kpi--bad" href="{{ route('admin.attendances.index') }}">
            <div class="ops-kpi__label">Absent today</div>
            <div class="ops-kpi__value">{{ $absent }}</div>
            <div class="ops-kpi__hint">No time in recorded yet</div>
        </a>
        <a class="ops-kpi ops-kpi--late" href="{{ route('admin.attendances.index', ['date' => now()->toDateString()]) }}">
            <div class="ops-kpi__label">Late Today</div>
            <div class="ops-kpi__value">{{ $lateTodayCount }}</div>
            <div class="ops-kpi__hint">After {{ $lateCutoffLabel }}</div>
        </a>
        <a class="ops-kpi ops-kpi--info" href="{{ route('admin.employees.index') }}">
            <div class="ops-kpi__label">Employees</div>
            <div class="ops-kpi__value">{{ number_format($employeeCount) }}</div>
            <div class="ops-kpi__hint">Registered employees</div>
        </a>
        <a class="ops-kpi ops-kpi--warn" href="{{ route('admin.visitors.index') }}">
            <div class="ops-kpi__label">Logbook Accounts</div>
            <div class="ops-kpi__value">{{ number_format($logbookCount) }}</div>
            <div class="ops-kpi__hint">Biometric and non-biometric profiles</div>
        </a>
    </section>

    <div class="ops-grid">
        <section class="ops-panel" aria-labelledby="ops-attendance-punches">
            <div class="ops-panel__head">
                <div>
                    <h2 id="ops-attendance-punches">Attendance Latest Punches</h2>
                    <p>Recent employee attendance records and punch times.</p>
                </div>
                <a href="{{ route('admin.attendances.index') }}">All attendance</a>
            </div>
            @if ($attendancePunches->isEmpty())
                <p class="ops-empty">No attendance punches have been recorded.</p>
            @else
                <ul class="ops-list">
                    @foreach ($attendancePunches as $row)
                        <li>
                            <div>
                                <strong>{{ $row['name'] }}</strong>
                                <span>{{ $row['date'] }} · IN {{ $row['time_in'] }} · OUT {{ $row['time_out'] }}</span>
                            </div>
                            <span class="ops-pill {{ $row['time_out'] === '—' ? 'ops-pill--in' : 'ops-pill--out' }}">
                                {{ $row['time_out'] === '—' ? 'On duty' : 'Completed' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="ops-panel" aria-labelledby="ops-logbook-latest">
            <div class="ops-panel__head">
                <div>
                    <h2 id="ops-logbook-latest">Logbook Latest Transaction</h2>
                    <p>Recent transactions, including biometric and non-biometric entries.</p>
                </div>
                <a href="{{ route('admin.barangay-logbook.logs') }}">All transactions</a>
            </div>
            @if ($logbookTransactions->isEmpty())
                <p class="ops-empty">No logbook transactions have been recorded.</p>
            @else
                <ul class="ops-list">
                    @foreach ($logbookTransactions as $row)
                        <li>
                            <div>
                                <strong>{{ $row['name'] }}</strong>
                                <span>{{ $row['date'] }} · {{ $row['purpose'] }}</span>
                            </div>
                            <span class="ops-pill {{ $row['biometric'] ? 'ops-pill--bio' : 'ops-pill--manual' }}">
                                {{ $row['classification'] }} · {{ $row['biometric'] ? 'Biometric' : 'No biometric' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
