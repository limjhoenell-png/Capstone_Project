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
    $todayKey = now()->toDateString();
    $rows = collect($latestAttendance ?? []);
    $monthlySummary = $monthlySummary ?? ['days' => 0, 'hours' => 0, 'late' => 0, 'absences' => 0];
    $today = $rows->first(fn ($row) => (string) ($row->attendance_date ?? '') === $todayKey);
    $recent = collect($recentAttendance ?? $rows->take(7));
    $weekPage = (int) ($weekPage ?? 1);
    $weekPages = (int) ($weekPages ?? 1);
    $weekMonth = $weekMonth ?? now()->format('Y-m');
    $notices = collect($notices ?? []);
    $unreadNoticeCount = (int) ($unreadNoticeCount ?? $notices->whereNull('read_at')->count());
    $inLabel = 'Not in yet';
    $inClass = 'me-status me-status--out';
    if ($today && $clock($today->time_in ?? null) !== '—') {
        if (empty($today->valid_time_in)) {
            $inLabel = 'Absent';
            $inClass = 'me-status me-status--late';
        } else {
            $inLabel = ! empty($today->late) ? 'In, late' : 'In today';
            $inClass = ! empty($today->late) ? 'me-status me-status--late' : 'me-status me-status--in';
        }
    }
@endphp

<section class="me-today" aria-label="Today">
    <article class="me-card">
        <h2>Today</h2>
        <b class="{{ $inClass }}">{{ $inLabel }}</b>
        <span>
            @if ($today)
                In {{ $clock($today->time_in ?? null) }} · Out {{ $clock($today->time_out ?? null) }}
            @else
                No punch recorded for today
            @endif
        </span>
    </article>
    <article class="me-card">
        <h2>Days Worked This Month</h2>
        <b>{{ $monthlySummary['days'] }}</b>
        <span>Monthly attendance</span>
    </article>
    <article class="me-card">
        <h2>Payable Hours This Month</h2>
        <b>{{ number_format($monthlySummary['hours'], 1) }}</b>
        <span>Attendance at the 8-hour daily cap</span>
    </article>
    <article class="me-card">
        <h2>Late Days This Month</h2>
        <b>{{ $monthlySummary['late'] }}</b>
        <span>After scheduled start and grace period</span>
    </article>
    <article class="me-card">
        <h2>Monthly Absences</h2>
        <b>{{ $monthlySummary['absences'] }}</b>
        <span>Scheduled workdays without attendance</span>
    </article>
    <button
        class="me-card me-notification-card"
        type="button"
        data-notification-open
        aria-label="Notifications and activity, {{ $unreadNoticeCount }} unread"
        aria-haspopup="dialog"
        aria-controls="employee-notification-modal"
    >
        <span class="me-notification-topline">
            <span class="me-notification-icon" aria-hidden="true">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75a2.25 2.25 0 0 1-4.5 0m9-3H5.25l1.5-1.5v-4.5a5.25 5.25 0 1 1 10.5 0v4.5l1.5 1.5Z" />
                </svg>
            </span>
            <span class="me-notification-unread" data-notification-unread @if ($unreadNoticeCount === 0) hidden @endif>{{ $unreadNoticeCount > 99 ? '99+' : $unreadNoticeCount }}</span>
        </span>
        <strong class="me-notification-count">Notifications &amp; Activity</strong>
        <span class="me-notification-hint" data-notification-summary>{{ $notificationPayload['total'] }} {{ \Illuminate\Support\Str::plural('notification', $notificationPayload['total']) }}</span>
    </button>
</section>

<section class="me-card me-table-card">
    <header>
        <h2>Recent days</h2>
        <a href="{{ route('employee.attendances.index') }}">Full attendance</a>
    </header>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            @forelse($recent as $attendance)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($attendance->attendance_date)->format('D, M j') }}</td>
                    <td class="text-mono">{{ $clock($attendance->time_in ?? null) }}</td>
                    <td class="text-mono">{{ $clock($attendance->time_out ?? null) }}</td>
                    <td>
                        @if (! empty($attendance->time_in) && empty($attendance->valid_time_in))
                            <span class="badge badge-red">Absent</span>
                        @elseif (! empty($attendance->late))
                            <span class="badge badge-red">Late</span>
                        @endif
                        {{ $attendance->remarks ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="table-empty">No attendance yet. Punches from the device and manual entries both show up here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <nav class="me-week-pagination" aria-label="Recent attendance weeks">
        @for ($page = 1; $page <= $weekPages; $page++)
            @if ($page === $weekPage)
                <span class="is-active" aria-current="page">Week {{ $page }}</span>
            @else
                <a href="{{ route('employee.dashboard', ['week_month' => $weekMonth, 'week_page' => $page]) }}">Week {{ $page }}</a>
            @endif
        @endfor
    </nav>
</section>
