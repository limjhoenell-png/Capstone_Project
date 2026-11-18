@extends('layouts.employee', ['title' => 'Payslips'])

@section('content')
    <style>
        .employee-payslips-card { padding: 0; overflow: hidden; }
        .employee-payslip-request {
            margin-bottom: 18px;
        }
        .employee-payslip-request-form {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 12px;
            margin-top: 12px;
        }
        .employee-payslip-request-form .form-group { margin: 0; }
        .employee-payslip-request-history {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
            font-size: 13px;
        }
        .employee-payslip-request-history th,
        .employee-payslip-request-history td {
            padding: 9px 10px;
            border-top: 1px solid var(--c-border);
            text-align: left;
        }
        .employee-payslips-toolbar {
            display: flex;
            justify-content: flex-end;
            padding: 14px 16px;
            border-bottom: 1px solid var(--c-border);
            background: var(--c-surface-2);
        }
        .employee-payslips-toolbar label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 700;
        }
        .employee-payslips-toolbar select {
            width: 78px;
            min-height: 36px;
            padding: 0 8px;
            border: 1px solid var(--c-border-strong);
            border-radius: var(--radius-sm);
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
        }
        .employee-payslips-table-wrap { overflow-x: auto; }
        .employee-payslips-table { min-width: 620px; }
        .employee-payslips-pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 0 16px;
            color: var(--c-text-muted);
            font-size: 12px;
        }
        .employee-payslips-pagination nav { margin: 0; }
        .employee-payslips-pagination nav .pagination {
            justify-content: center;
            border-top: 0;
        }
        @media (prefers-reduced-motion: no-preference) {
            .employee-payslips-toolbar,
            .employee-payslips-toolbar select {
                transition: background-color var(--transition), color var(--transition), border-color var(--transition);
            }
        }
    </style>
    <section class="card employee-payslip-request">
        <h2 class="card-title">Request Past Payslip</h2>
        <p style="margin:0;color:var(--c-text-secondary);font-size:13px;">
            Choose a past month for the admin to review and prepare. Requests are available from your employment start month.
        </p>
        @if ($earliestRequestMonth <= $latestRequestMonth)
            <form method="POST" action="{{ route('employee.payslips.request') }}" class="employee-payslip-request-form">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="payslip-request-period">Month</label>
                    <input
                        class="form-control"
                        id="payslip-request-period"
                        name="period"
                        type="month"
                        min="{{ $earliestRequestMonth }}"
                        max="{{ $latestRequestMonth }}"
                        value="{{ old('period', $latestRequestMonth) }}"
                        required
                    >
                    @error('period')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <button class="btn btn-primary" type="submit">Submit request</button>
            </form>
        @else
            <p style="margin:12px 0 0;color:var(--c-text-muted);font-size:13px;">
                Past-month requests will be available after your first full month.
            </p>
        @endif
        @if ($payslipRequests->isNotEmpty())
            <table class="employee-payslip-request-history">
                <thead><tr><th>Requested month</th><th>Status</th><th>Submitted</th></tr></thead>
                <tbody>
                    @foreach ($payslipRequests as $payslipRequest)
                        <tr>
                            <td>{{ $payslipRequest->period_start->format('F Y') }}</td>
                            <td>{{ ucfirst($payslipRequest->status) }}</td>
                            <td>{{ $payslipRequest->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    <div class="card employee-payslips-card">
        <form method="GET" action="{{ route('employee.payslips.index') }}" class="employee-payslips-toolbar">
            <label for="employee-payslips-per-page">
                Rows per page
                <select id="employee-payslips-per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page">
                    @foreach ([10, 15, 20, 30] as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
        </form>
        <div class="employee-payslips-table-wrap">
        <table class="table employee-payslips-table">
            <thead><tr><th>Period</th><th>Kind</th><th>Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($payslips as $payslip)
                <tr>
                    <td>{{ $payslip->period_start->format('M j, Y') }} – {{ $payslip->period_end->format('M j, Y') }}</td>
                    <td>{{ str_replace('_', ' ', $payslip->kind) }}</td>
                    <td>{{ number_format((float) $payslip->net_pay, 2) }}</td>
                    <td>{{ $payslip->status }}</td>
                    <td>
                        <a class="btn btn-sm" href="{{ route('employee.payslips.show', $payslip) }}">View</a>
                        <a class="btn btn-sm btn-primary" href="{{ route('employee.payslips.pdf', $payslip) }}">PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="table-empty">Published payslips will appear here.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="employee-payslips-pagination">
        @if ($payslips->hasPages())
            {{ $payslips->links() }}
        @endif
    </div>
@endsection
