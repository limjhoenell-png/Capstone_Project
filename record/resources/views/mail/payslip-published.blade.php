Your {{ $payslip->kind === 'thirteenth_month' ? '13th month' : 'payslip' }} for {{ $payslip->period_start->format('M j, Y') }} to {{ $payslip->period_end->format('M j, Y') }} is ready.

Net pay: {{ number_format((float) $payslip->net_pay, 2) }}

Sign in to the employee portal to view or download it.
