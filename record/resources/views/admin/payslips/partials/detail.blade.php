@php
    $rateLabel = $payslip->pay_rate_type === \App\Models\User::PAY_RATE_HOURLY ? 'Hourly' : 'Daily';
@endphp

<div class="payslip-detail">
    <table class="ps-table">
        <tbody>
        <tr>
            <th scope="row">Employee Name</th>
            <td>{{ $payslip->employee_display_name }}</td>
        </tr>
        <tr>
            <th scope="row">Employee ID</th>
            <td>
                @if ($payslip->employee_identifier !== null && $payslip->employee_identifier !== '')
                    {{ $payslip->employee_identifier }}
                @else
                    <span class="muted">—</span> <span class="muted small">(system user #{{ $payslip->user_id }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">Payroll Period</th>
            <td>{{ $payslip->period_start->format('M j, Y') }} — {{ $payslip->period_end->format('M j, Y') }}</td>
        </tr>
        <tr>
            <th scope="row">Attendance</th>
            <td>
                @if ($payslip->pay_rate_type === \App\Models\User::PAY_RATE_HOURLY)
                    <strong>{{ number_format((float) $payslip->regular_hours, 2) }}</strong> payable hours
                    @if ($payslip->days_worked > 0)
                        <span class="muted">({{ $payslip->days_worked }} day(s) with time logged)</span>
                    @endif
                @else
                    <strong>{{ number_format((float) $payslip->equivalent_days, 4) }}</strong> equivalent days
                    @if ($payslip->days_worked > 0)
                        <span class="muted"> · {{ $payslip->days_worked }} calendar day(s) with attendance</span>
                    @endif
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">Pay Rate</th>
            <td>{{ $rateLabel }}
                @if ($payslip->pay_rate_amount !== null)
                    · {{ number_format((float) $payslip->pay_rate_amount, 2) }}
                @else
                    <span class="muted">—</span>
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">Payable Hours</th>
            <td>{{ number_format((float) $payslip->regular_hours, 2) }}</td>
        </tr>
        <tr>
            <th scope="row">Regular Pay</th>
            <td class="text-mono">{{ number_format((float) $payslip->regular_pay, 2) }}</td>
        </tr>
        @php($earningItems = $payslip->earning_items)
        @if (is_array($earningItems) && $earningItems !== [])
            <tr>
                <th scope="row">Annual Benefits</th>
                <td>
                    <ul class="deduction-list">
                        @foreach ($earningItems as $item)
                            <li>
                                {{ $item['name'] ?? 'Annual Benefit' }}
                                @if (! empty($item['payment_date']))
                                    <span class="muted">({{ \Illuminate\Support\Carbon::parse($item['payment_date'])->format('M j, Y') }})</span>
                                @endif
                                <span class="text-mono">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endif
        <tr>
            <th scope="row">Gross Pay</th>
            <td class="text-mono"><strong>{{ number_format((float) $payslip->gross_pay, 2) }}</strong></td>
        </tr>
        <tr>
            <th scope="row">Deductions</th>
            <td>
                @php($items = $payslip->deduction_items)
                @if (is_array($items) && $items !== [])
                    <ul class="deduction-list">
                        @foreach ($items as $item)
                            <li>
                                {{ $item['name'] ?? $item['label'] ?? 'Deduction' }}:
                                <span class="text-mono">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div style="margin-top:4px;"><strong>Total: <span class="text-mono">{{ number_format((float) $payslip->deductions_total, 2) }}</span></strong></div>
                @else
                    <span class="text-mono">{{ number_format((float) $payslip->deductions_total, 2) }}</span>
                @endif
            </td>
        </tr>
        <tr class="net-row">
            <th scope="row">Net Pay</th>
            <td><strong class="text-mono net-amount">{{ number_format((float) $payslip->net_pay, 2) }}</strong></td>
        </tr>
        <tr>
            <th scope="row">Processed</th>
            <td class="muted text-mono small">{{ $payslip->processed_at?->format('Y-m-d H:i') }}</td>
        </tr>
        </tbody>
    </table>
</div>
