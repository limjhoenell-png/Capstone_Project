<style>
    .attendance-live-region .attendance-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px 20px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--c-border);
    }

    .attendance-live-region .attendance-card-title {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 5px 12px;
    }

    .attendance-live-region .attendance-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .attendance-live-region .attendance-filter label {
        color: var(--c-text-secondary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        white-space: nowrap;
    }

    .attendance-live-region .attendance-filter input[type="date"] {
        min-height: 38px;
        padding: 7px 10px;
        border: 1px solid var(--c-border-strong);
        border-radius: var(--radius-md);
        background: var(--c-surface);
        color: var(--c-text);
        color-scheme: light;
        font: inherit;
    }

    .attendance-live-region .attendance-filter select {
        min-height: 38px;
        min-width: 82px;
        padding: 7px 10px;
        border: 1px solid var(--c-border-strong);
        border-radius: var(--radius-md);
        background: var(--c-surface);
        color: var(--c-text);
        color-scheme: light;
        font: inherit;
    }

    .attendance-live-region .attendance-filter input[type="date"]:focus {
        border-color: var(--c-primary);
        outline: 2px solid var(--c-form-focus-ring);
        outline-offset: 1px;
    }

    html[data-theme="dark"] .attendance-live-region .attendance-filter input[type="date"],
    html[data-theme="dark"] .attendance-live-region .attendance-filter select {
        color-scheme: dark;
    }

    .attendance-live-region .attendance-filter-clear {
        text-decoration: none;
        white-space: nowrap;
    }

    .attendance-live-region .table-wrapper {
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-x: contain;
    }

    .attendance-live-region .table {
        width: 100%;
        min-width: 680px;
        table-layout: fixed;
    }

    .attendance-live-region .table th:nth-child(1),
    .attendance-live-region .table td:nth-child(1) {
        width: 24%;
    }

    .attendance-live-region .table th:nth-child(2),
    .attendance-live-region .table td:nth-child(2) {
        width: 14%;
    }

    .attendance-live-region .table th:nth-child(3),
    .attendance-live-region .table td:nth-child(3),
    .attendance-live-region .table th:nth-child(4),
    .attendance-live-region .table td:nth-child(4) {
        width: 15%;
    }

    .attendance-live-region .table th:nth-child(5),
    .attendance-live-region .table td:nth-child(5) {
        width: 12%;
    }

    .attendance-live-region .table th:nth-child(6),
    .attendance-live-region .table td:nth-child(6) {
        width: 20%;
    }

    .attendance-live-region .table thead th,
    .attendance-live-region .table tbody td {
        padding: 13px 18px;
    }

    .attendance-live-region .table tbody td {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .attendance-live-region .table thead th:nth-child(1),
    .attendance-live-region .table tbody td:nth-child(1),
    .attendance-live-region .table thead th:nth-child(3),
    .attendance-live-region .table tbody td:nth-child(3),
    .attendance-live-region .table thead th:nth-child(4),
    .attendance-live-region .table tbody td:nth-child(4) {
        white-space: nowrap;
    }

    .attendance-live-region .attendance-pagination {
        display: flex;
        justify-content: center;
        margin: -4px 0 22px;
        padding: 12px 16px;
        overflow-x: auto;
        border: 1px solid var(--c-border);
        border-radius: var(--radius-lg);
        background: var(--c-surface);
    }

    .attendance-live-region .attendance-pagination nav,
    .attendance-live-region .attendance-pagination nav > div {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
        width: 100%;
    }

    .attendance-live-region .attendance-pagination nav ul.pagination {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .attendance-live-region .attendance-pagination nav ul.pagination > li {
        display: inline-flex;
        flex: 0 0 auto;
        margin: 0;
        padding: 0;
    }

    .attendance-live-region .attendance-pagination nav a,
    .attendance-live-region .attendance-pagination nav span[aria-current],
    .attendance-live-region .attendance-pagination nav .pagination > li > span,
    .attendance-live-region .attendance-pagination nav button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        min-height: 38px;
        padding: 7px 12px;
        border: 1px solid var(--c-border-strong);
        border-radius: var(--radius-md);
        background: var(--c-surface);
        color: var(--c-text);
        text-decoration: none;
        white-space: nowrap;
    }

    .attendance-live-region .attendance-pagination nav .pagination > li.active > span,
    .attendance-live-region .attendance-pagination nav .pagination > li > span[aria-current] {
        border-color: var(--c-primary);
        background: var(--c-primary);
        color: #fff;
    }

    .attendance-live-region .attendance-pagination nav .pagination > li.disabled > span {
        opacity: .55;
    }

    .attendance-live-region .attendance-pagination nav a:hover {
        border-color: var(--c-primary);
        background: var(--c-primary-light);
        color: var(--c-primary-text);
    }

    @media (max-width: 640px) {
        .attendance-live-region .attendance-card-header {
            align-items: flex-start;
        }

        .attendance-live-region .attendance-filter {
            width: 100%;
            flex-wrap: wrap;
        }

        .attendance-live-region .table thead th,
        .attendance-live-region .table tbody td {
            padding: 11px 13px;
        }

        .attendance-live-region .attendance-pagination {
            padding: 10px;
        }
    }
</style>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:18px;">
    <div class="attendance-card-header">
        <div class="attendance-card-title">
            <strong>Daily Attendance</strong>
            <span style="color:var(--c-text-muted);font-size:12px;">One consolidated row per employee per day, newest first.</span>
        </div>
        <form class="attendance-filter" method="GET" action="{{ route('admin.attendances.index') }}">
            <label for="attendance-filter-date">FILTER DATE</label>
            <input
                id="attendance-filter-date"
                type="date"
                name="date"
                value="{{ $filterDate ?? '' }}"
                aria-label="Filter attendance by date"
            >
            <label for="attendance-per-page">ROWS PER PAGE</label>
            <select id="attendance-per-page" name="per_page" aria-label="Rows per page">
                @foreach ([5, 10, 15, 20, 25, 50, 75, 100] as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            @if($filterDate)
                <a class="btn btn-sm btn-danger attendance-filter-clear" href="{{ route('admin.attendances.index', ['per_page' => $perPage]) }}">Clear</a>
            @endif
        </form>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>EMPLOYEE</th>
                    <th>DATE</th>
                    <th>TIME IN</th>
                    <th>TIME OUT</th>
                    <th>PAYABLE HOURS</th>
                    <th>SOURCE</th>
                </tr>
            </thead>
            <tbody>
            @forelse($mergedDays as $row)
                <tr>
                    <td style="font-weight:600;">{{ $row['name'] ?: 'Employee' }}</td>
                    <td class="text-mono" style="font-size:13px;">{{ $row['date'] }}</td>
                    <td class="text-mono" style="font-size:13px;">{{ $row['time_in'] ? $row['time_in']->format('h:i A') : '—' }}</td>
                    <td class="text-mono" style="font-size:13px;">{{ $row['time_out'] ? $row['time_out']->format('h:i A') : '—' }}</td>
                    <td class="text-mono">{{ number_format((float) $row['hours'], 2) }}</td>
                    <td>
                        <span class="badge badge-gray">{{ $row['source'] }}</span>
                        @if ($row['status'] === 'Absent')
                            <span class="badge badge-red">Absent</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="table-empty">{{ $filterDate ? 'No attendance records for this date.' : 'No attendance records yet.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="attendance-pagination">{{ $mergedDays->links() }}</div>
</div>
