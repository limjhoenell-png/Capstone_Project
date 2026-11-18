@extends('layouts.admin', ['title' => 'Daily Attendance Breakdown'])

@section('content')
    <style>
        .payroll-breakdown-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .payroll-breakdown-title {
            margin: 0;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
        }
        .payroll-breakdown-subtitle {
            margin-top: 6px;
            color: var(--c-text-secondary);
            font-size: 13px;
        }
        .payroll-breakdown-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1px;
            overflow: hidden;
            margin-bottom: 18px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-border);
        }
        .payroll-breakdown-summary-item {
            min-width: 0;
            padding: 14px 16px;
            background: var(--c-surface-2);
        }
        .payroll-breakdown-summary-item span {
            display: block;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .payroll-breakdown-summary-item strong {
            display: block;
            margin-top: 6px;
            color: #fff;
            font-size: 18px;
            font-variant-numeric: tabular-nums;
        }
        .payroll-breakdown-table-wrap {
            overflow-x: auto;
        }
        .payroll-breakdown-table {
            width: 100%;
            min-width: 820px;
        }
        .payroll-breakdown-table th {
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .payroll-breakdown-table td { vertical-align: top; }
        .payroll-breakdown-notes {
            min-width: 180px;
            color: var(--c-text-secondary);
            white-space: normal;
        }
        @media (max-width: 560px) {
            .payroll-breakdown-summary { grid-template-columns: minmax(0, 1fr); }
            .payroll-breakdown-title { font-size: 19px; }
        }
    </style>

    <div class="payroll-breakdown-header">
        <div>
            <h1 class="payroll-breakdown-title">Daily Attendance Breakdown</h1>
            <div class="payroll-breakdown-subtitle">
                {{ $employee->name }} · {{ \Illuminate\Support\Carbon::parse($period_start)->format('M j, Y') }}
                – {{ \Illuminate\Support\Carbon::parse($period_end)->format('M j, Y') }}
            </div>
        </div>
        <a class="btn" href="{{ route('admin.payroll.index', ['period_start' => $period_start, 'period_end' => $period_end]) }}">Back to Payroll Preview</a>
    </div>

    <div class="payroll-breakdown-summary">
        <div class="payroll-breakdown-summary-item">
            <span>Total Regular Hours</span>
            <strong>{{ number_format($regular_hours, 2) }}</strong>
        </div>
    </div>

    <section class="card" aria-label="Daily attendance records">
        <div class="card-title">Attendance Records</div>
        <div class="payroll-breakdown-table-wrap">
            <table class="table payroll-breakdown-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th class="text-right">Payable Hours</th>
                        <th>Source / Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($days as $day)
                        @php
                            $hours = (float) ($day['payable_hours'] ?? $day['hours'] ?? 0);
                        @endphp
                        <tr>
                            <td class="text-mono">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M j, Y') }}</td>
                            <td class="text-mono">{{ $day['time_in']?->format('g:i A') ?? '—' }}</td>
                            <td class="text-mono">{{ $day['time_out']?->format('g:i A') ?? '—' }}</td>
                            <td class="text-mono text-right">{{ number_format($hours, 2) }}</td>
                            <td class="payroll-breakdown-notes">
                                <div>{{ ucfirst($day['source'] ?? 'attendance') }}</div>
                                @if (! empty($day['time_in']) && empty($day['valid_time_in']))
                                    <div style="color:var(--c-danger, #ef4444);">Absent — Time-In outside valid window</div>
                                @endif
                                @if (! empty($day['attendance_id']))
                                    <div class="payroll-breakdown-subtitle">Attendance record #{{ $day['attendance_id'] }}</div>
                                @endif
                                @if (! empty($day['late']))
                                    <div style="color:var(--c-warning-text, #fbbf24);">Late arrival</div>
                                @endif
                                @if (! empty($day['holiday_name']))
                                    <div>{{ $day['holiday_name'] }} ({{ ucfirst($day['holiday']) }} holiday)</div>
                                @elseif (! empty($day['rest_day']))
                                    <div>Rest day</div>
                                @endif
                                @if (! empty($day['remarks']))
                                    <div>{{ $day['remarks'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">No attendance records for this employee during the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
