<div class="card" style="padding:0;overflow:hidden;">
    <form method="GET" action="{{ route('admin.payslips.index') }}" class="payslip-pagination-toolbar">
        @if ($filter_period_start)
            <input type="hidden" name="period_start" value="{{ $filter_period_start }}">
        @endif
        @if ($filter_period_end)
            <input type="hidden" name="period_end" value="{{ $filter_period_end }}">
        @endif
        @if ($filter_employee_id)
            <input type="hidden" name="employee_id" value="{{ $filter_employee_id }}">
        @endif
        <label for="payslips-per-page">
            Rows per page
            <select id="payslips-per-page" name="per_page" aria-label="Rows per page">
                @foreach ([5, 10, 15, 20, 25, 50, 75, 100] as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>Employee</th><th>Period</th><th>Gross</th><th>Net Pay</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($payslips as $slip)
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $slip->employee_display_name }}</div>
                        @if ($slip->employee_identifier)
                            <div style="font-size:12px;color:var(--c-text-muted);">ID {{ $slip->employee_identifier }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="text-mono" style="font-size:13px;">{{ $slip->period_start->format('Y-m-d') }}</span>
                        <span style="color:var(--c-text-muted);"> — </span>
                        <span class="text-mono" style="font-size:13px;">{{ $slip->period_end->format('Y-m-d') }}</span>
                    </td>
                    <td class="text-mono" style="font-size:14px;">{{ number_format((float) $slip->gross_pay, 2) }}</td>
                    <td class="text-mono" style="font-size:14px;font-weight:700;color:var(--c-primary-text);">{{ number_format((float) $slip->net_pay, 2) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a class="btn btn-sm" href="{{ route('admin.payslips.show', $slip) }}">View</a>
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.payslips.pdf', $slip) }}" target="_blank">PDF</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="table-empty">No payslips yet. Run payroll processing from the Payroll page to generate payslips.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($payslips->hasPages())
    <div class="payslip-pagination-footer">{{ $payslips->links() }}</div>
@endif
