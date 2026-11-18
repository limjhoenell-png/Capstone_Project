@extends(auth()->user()?->isAdmin() ? 'layouts.admin' : 'layouts.employee', ['title' => $title])

@section('content')

    <style>
        @page {
            size: auto;
            margin: 0;
        }

        @media print {
            html,
            body {
                width: 100%;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
            }

            .sidebar,
            .topbar,
            .actions-bar {
                display: none !important;
            }

            .layout,
            .main,
            .page-body {
                display: block !important;
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .payslip-card {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .payslip-header,
            .payslip-detail .ps-table th,
            .payslip-detail .ps-table td,
            .payslip-detail .muted,
            .payslip-detail .deduction-list {
                color: #000 !important;
            }

            .payslip-detail .ps-table th,
            .payslip-detail .ps-table td,
            .payslip-header {
                border-color: #999 !important;
            }

            .payslip-badge {
                border: 1px solid #777 !important;
                background: #fff !important;
                color: #000 !important;
            }

            .payslip-detail .net-amount {
                color: #000 !important;
            }
        }

        .payslip-card {
            max-width: 740px;
        }

        .payslip-detail .ps-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14.5px;
        }

        .payslip-detail .ps-table th {
            text-align: left;
            width: 36%;
            padding: 12px 16px 12px 0;
            vertical-align: top;
            color: var(--c-text-muted);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .payslip-detail .ps-table td {
            padding: 12px 0;
            border-bottom: 1px solid var(--c-border);
            color: var(--c-text);
        }

        .payslip-detail .ps-table th {
            border-bottom: 1px solid var(--c-border);
        }

        .payslip-detail .ps-table tr:last-child td,
        .payslip-detail .ps-table tr:last-child th {
            border-bottom: none;
        }

        .payslip-detail .net-row th,
        .payslip-detail .net-row td {
            border-top: 2px solid var(--c-border);
            padding-top: 16px;
            font-size: 15px;
        }

        .payslip-detail .net-amount {
            font-size: 22px;
            color: var(--c-primary);
        }

        .payslip-detail .muted { color: var(--c-text-muted); }
        .payslip-detail .small { font-size: 12.5px; }
        .payslip-detail .text-mono { font-family: 'DM Mono', monospace; }

        .payslip-detail .deduction-list {
            margin: 0 0 8px 1.1em;
            padding: 0;
            font-size: 13.5px;
            color: var(--c-text-secondary);
        }

        .payslip-detail .deduction-list li {
            margin-bottom: 2px;
        }

        .actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .payslip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--c-border);
        }

        .payslip-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--c-primary-light, rgba(37,99,235,0.1));
            color: var(--c-primary);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>

    <div class="actions-bar">
        @if(auth()->user()?->isAdmin() && $payslip->status === 'draft')
            <form method="POST" action="{{ route('admin.payslips.approve', $payslip) }}">@csrf<button class="btn btn-primary" type="submit">Publish to employee</button></form>
        @endif
        @if(auth()->user()?->isAdmin() && $payslip->status !== 'paid')
            <form method="POST" action="{{ route('admin.payslips.paid', $payslip) }}">@csrf<button class="btn" type="submit">Mark paid</button></form>
        @endif
        <a class="btn btn-primary" href="{{ auth()->user()?->isAdmin() ? route('admin.payslips.pdf', $payslip) : route('employee.payslips.pdf', $payslip) }}" style="display:inline-flex;align-items:center;gap:6px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PDF
        </a>
        <button class="btn" type="button" onclick="window.print()" style="display:inline-flex;align-items:center;gap:6px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>

    <div class="card payslip-card">
        <div class="payslip-header">
            <div>
                <h2 style="margin:0 0 4px;font-size:20px;font-weight:700;">Official Payslip</h2>
                <div style="font-size:13px;color:var(--c-text-muted);">
                    Period: {{ $payslip->period_start->format('M j') }} — {{ $payslip->period_end->format('M j, Y') }}
                </div>
            </div>
            <span class="payslip-badge">
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Processed
            </span>
        </div>

        @include('admin.payslips.partials.detail', ['payslip' => $payslip])
    </div>

@endsection
