@extends('layouts.admin', ['title' => 'Attendance — '.$employee->name])

@section('content')

    <style>
        .admin-emp-attendance-viewport {
            max-width: 100%;
            max-height: min(82vh, calc(3rem + 12 * 3.35rem));
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .admin-emp-attendance-viewport .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--c-surface-2);
            box-shadow: 0 1px 0 var(--c-border);
        }
    </style>

    {{-- Employee info card --}}
    <div class="card" style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding:16px 20px;">
        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr($employee->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-size:16px;font-weight:700;">{{ $employee->name }}</div>
            <div style="font-size:13px;color:var(--c-text-secondary);">{{ $employee->email }}</div>
        </div>
        @if($employee->position)
            <span class="badge badge-blue" style="margin-left:auto;">{{ $employee->position }}</span>
        @endif
    </div>

    <div class="card" style="padding:0;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--c-border);">
            <span style="font-size:14px;font-weight:700;">Daily Attendance</span>
        </div>
        <div class="table-wrapper admin-emp-attendance-viewport">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Payable Hours</th><th>Status</th><th>Remarks</th></tr>
                </thead>
                <tbody>
                @forelse($attendances as $attendance)
                    @php
                        $validTimeIn = $attendance->time_in !== null
                            && \App\Support\AttendanceTimeWindow::allows($attendance->time_in);
                    @endphp
                    <tr>
                        <td class="text-mono" style="font-size:13px;">{{ $attendance->attendance_date?->format('Y-m-d') }}</td>
                        <td class="text-mono" style="font-size:13px;">{{ $attendance->time_in?->format('Y-m-d h:i:s A') ?? '—' }}</td>
                        <td class="text-mono" style="font-size:13px;">{{ $attendance->time_out?->format('Y-m-d h:i:s A') ?? '—' }}</td>
                        <td class="text-mono">{{ number_format($validTimeIn ? min((float) $attendance->total_hours, \App\Support\AttendanceTimeWindow::MAX_PAYABLE_HOURS) : 0, 2) }}</td>
                        <td>
                            @if ($attendance->time_in !== null && ! $validTimeIn)
                                <span class="badge badge-red">Absent</span>
                            @elseif ($validTimeIn)
                                <span class="badge badge-green">Present</span>
                            @else
                                <span class="badge badge-gray">Open</span>
                            @endif
                        </td>
                        <td style="color:var(--c-text-secondary);">{{ $attendance->remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">No attendance records for this employee.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
