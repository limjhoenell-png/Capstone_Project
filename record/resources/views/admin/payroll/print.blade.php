@extends('layouts.admin', ['title' => 'Payroll Report'])

@section('content')
    <style>
        .payroll-report-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }
        .payroll-report {
            padding: 28px;
            background: #fff;
            color: #111827;
            border: 1px solid #cbd5e1;
            font-family: Arial, Helvetica, sans-serif;
        }
        .payroll-report-header {
            padding-bottom: 16px;
            border-bottom: 2px solid #111827;
            text-align: center;
        }
        .payroll-report-header h1 {
            margin: 0;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .payroll-report-header p {
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: 600;
        }
        .payroll-report-title {
            margin: 16px 0 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .payroll-report-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            margin-bottom: 14px;
            font-size: 12px;
        }
        .payroll-report-meta strong { text-transform: uppercase; }
        .payroll-report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }
        .payroll-report-table th,
        .payroll-report-table td {
            padding: 8px 7px;
            border: 1px solid #334155;
            text-align: left;
            vertical-align: middle;
        }
        .payroll-report-table thead th {
            background: #e2e8f0;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }
        .payroll-report-table .amount { text-align: right; white-space: nowrap; }
        .payroll-report-table .signature-cell { height: 36px; }
        .payroll-report-table tfoot td {
            background: #f1f5f9;
            font-weight: 800;
        }
        .payroll-report-certifications {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 28px 18px;
            margin-top: 30px;
            font-size: 11px;
            page-break-inside: avoid;
        }
        .payroll-certification {
            grid-column: span 2;
            min-height: 84px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .payroll-certification:nth-child(4) { grid-column: 2 / span 2; }
        .payroll-certification:nth-child(5) { grid-column: 4 / span 2; }
        .payroll-certification p {
            margin: 0 0 28px;
            line-height: 1.5;
        }
        .payroll-certification strong {
            display: block;
            padding-top: 5px;
            border-top: 1px solid #111827;
            text-align: center;
            text-transform: uppercase;
            line-height: 1.4;
        }
        .payroll-signatory-name {
            min-height: 16px;
            margin-bottom: 4px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }
        @media (max-width: 720px) {
            .payroll-report-certifications {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .payroll-certification,
            .payroll-certification:nth-child(4),
            .payroll-certification:nth-child(5) {
                grid-column: auto;
            }
        }
        @media print {
            @page { size: landscape; margin: 12mm; }
            html, body { background: #fff !important; color: #111827 !important; }
            .sidebar, .topbar, .payroll-report-actions, .flash { display: none !important; }
            .layout { display: block !important; min-height: 0 !important; }
            .main { margin: 0 !important; padding: 0 !important; }
            .page-body { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .payroll-report { padding: 0; border: 0; }
            .payroll-report-table tr { page-break-inside: avoid; }
        }
    </style>

    <div class="payroll-report-actions">
        <a class="btn" href="{{ route('admin.payroll.index') }}">Back to Payroll</a>
        <button class="btn btn-primary" type="button" onclick="window.print()">Print Payroll</button>
    </div>

    <article class="payroll-report">
        <header class="payroll-report-header">
            <h1>{{ $printSettings->barangay_name }}</h1>
            <p>{{ $printSettings->city_name }}</p>
        </header>

        <h2 class="payroll-report-title">{{ $printSettings->report_title }}</h2>

        <div class="payroll-report-meta">
            <div><strong>{{ $printSettings->period_covered_label }}:</strong> {{ $periodStart->format('F 1, Y') }} – {{ $periodEnd->format('F j, Y') }}</div>
            <div><strong>{{ $printSettings->payroll_number_label }}:</strong> {{ $payrollNumber }}</div>
            <div><strong>{{ $printSettings->source_fund_label }}:</strong> {{ $sourceFund !== '' ? $sourceFund : '____________________________' }}</div>
            <div><strong>{{ $printSettings->date_prepared_label }}:</strong> {{ now()->format('F j, Y') }}</div>
        </div>

        <table class="payroll-report-table">
            <colgroup>
                <col style="width:24%">
                <col style="width:20%">
                <col style="width:17%">
                <col style="width:13%">
                <col style="width:15%">
                <col style="width:11%">
            </colgroup>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Honoraria / Salary</th>
                    <th>Deductions</th>
                    <th>Total (Net Pay)</th>
                    <th>Signature</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td>{{ $line['employee']->name }}</td>
                        <td>{{ $line['employee']->position ?: 'Unassigned' }}</td>
                        <td class="amount">{{ number_format($line['gross_pay'], 2) }}</td>
                        <td class="amount">{{ number_format($line['deductions_total'], 2) }}</td>
                        <td class="amount">{{ number_format($line['net_pay'], 2) }}</td>
                        <td class="signature-cell"></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">No registered employees.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($lines->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="2">Grand Total</td>
                        <td class="amount">{{ number_format($grossTotal, 2) }}</td>
                        <td class="amount">{{ number_format($deductionsTotal, 2) }}</td>
                        <td class="amount">{{ number_format($netTotal, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <section class="payroll-report-certifications" aria-label="Certifications and approvals">
            <div class="payroll-certification">
                <p>Certified correct and services duly rendered.</p>
                <div class="payroll-signatory-name">{{ $printSettings->punong_barangay_name }}</div>
                <strong>{{ $printSettings->punong_barangay_designation }}</strong>
            </div>
            <div class="payroll-certification">
                <p>Certified for appropriation.</p>
                <div class="payroll-signatory-name">{{ $printSettings->appropriations_chair_name }}</div>
                <strong>{{ $printSettings->appropriations_chair_designation }}</strong>
            </div>
            <div class="payroll-certification">
                <p>Funds available for this payroll.</p>
                <div class="payroll-signatory-name">{{ $printSettings->treasurer_name }}</div>
                <strong>{{ $printSettings->treasurer_designation }}</strong>
            </div>
            <div class="payroll-certification">
                <p>Approved for payment.</p>
                <div class="payroll-signatory-name">{{ $printSettings->approved_by_name }}</div>
                <strong>{{ $printSettings->approved_by_designation }}</strong>
            </div>
            <div class="payroll-certification">
                <p>{{ $preparedDateDescription }}</p>
                <div class="payroll-signatory-name">{{ $printSettings->prepared_by_name }}</div>
                <strong>{{ $printSettings->prepared_by_designation }}</strong>
            </div>
        </section>
    </article>
@endsection
