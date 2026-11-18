@php
    $clock = function ($value): string {
        if ($value === null || $value === '' || $value === '—') {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('g:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $sourceLabel = function (?string $source): string {
        return match ($source) {
            'attendance' => 'Attendance',
            default => 'Record',
        };
    };
    $days = collect($logDailySummaries ?? []);
    $attendanceRows = $attendancePage ?? $days;
@endphp

<style>
    .att-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: baseline;
        padding: 14px 18px;
        border-bottom: 1px solid var(--c-border);
    }
    .att-head h2 { margin: 0; font-size: 15px; }
    .emp-self-attendance-viewport {
        max-width: 100%;
        max-height: min(70vh, 36rem);
        overflow: auto;
    }
    .emp-self-attendance-viewport .table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: var(--c-surface-2);
        box-shadow: 0 1px 0 var(--c-border);
    }
    .employee-attendance-toolbar {
        display: flex;
        justify-content: flex-end;
        padding: 12px 16px;
        border-bottom: 1px solid var(--c-border);
        background: var(--c-surface-2);
    }
    .employee-attendance-toolbar label {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--c-text-secondary);
        font-size: 12px;
        font-weight: 700;
    }
    .employee-attendance-toolbar select {
        width: 78px;
        min-height: 36px;
        padding: 0 8px;
        border: 1px solid var(--c-border-strong);
        border-radius: var(--radius-sm);
        background: var(--c-surface);
        color: var(--c-text);
        font: inherit;
    }
    .employee-attendance-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        padding: 0 16px;
    }
    .employee-attendance-pagination nav { margin: 0; }
    .employee-attendance-pagination nav .pagination {
        justify-content: center;
        border-top: 0;
    }
</style>

<section class="card card-compact" style="padding:0;">
    <div class="att-head">
        <div>
            <h2>Daily attendance</h2>
        </div>
    </div>
    <form method="GET" action="{{ route('employee.attendances.index') }}" class="employee-attendance-toolbar">
        <label for="employee-attendance-per-page">
            Rows per page
            <select id="employee-attendance-per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page">
                @foreach ([10, 15, 20, 30] as $option)
                    <option value="{{ $option }}" @selected(($perPage ?? 10) === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <div class="table-wrapper emp-self-attendance-viewport">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Payable Hours</th>
                    <th>Source</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($attendanceRows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->attendance_date)->format('D, M j, Y') }}</td>
                    <td class="text-mono">{{ $clock($row->time_in ?? null) }}</td>
                    <td class="text-mono">{{ $clock($row->time_out ?? null) }}</td>
                    <td class="text-mono">{{ number_format((float) ($row->hours ?? 0), 2) }}</td>
                    <td>{{ $sourceLabel($row->source ?? null) }}</td>
                    <td>
                        @if (! empty($row->time_in) && empty($row->valid_time_in))
                            <span class="badge badge-red">Absent</span>
                        @elseif (! empty($row->late))
                            <span class="badge badge-red">Late</span>
                        @elseif ($clock($row->time_in ?? null) !== '—')
                            <span class="badge badge-green">On time</span>
                        @else
                            <span class="badge badge-gray">Open</span>
                        @endif
                        @if (! empty($row->holiday_name))
                            <span class="badge badge-gray">{{ $row->holiday_name }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="table-empty">No attendance for the past year. A device punch or a manual entry will appear here.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if (isset($attendancePage))
        <div class="employee-attendance-pagination">
            @if ($attendancePage->hasPages())
                {{ $attendancePage->links() }}
            @endif
        </div>
    @endif
</section>
