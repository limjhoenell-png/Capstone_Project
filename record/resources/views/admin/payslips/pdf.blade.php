<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $payslip->employee_display_name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            color: #0f172a;
            background: #fff;
            padding: 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0d1426;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .brand { font-size: 18pt; font-weight: 700; color: #0d1426; }
        .brand-sub { font-size: 9pt; color: #64748b; margin-top: 4px; }

        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 14pt; font-weight: 700; margin-bottom: 4px; }
        .doc-title p { font-size: 9pt; color: #64748b; }

        .ps-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ps-table th {
            text-align: left;
            width: 36%;
            padding: 8px 12px 8px 0;
            font-weight: 600;
            color: #475569;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .ps-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10pt;
            color: #0f172a;
        }

        .net-row th, .net-row td {
            border-top: 2px solid #0d1426;
            border-bottom: none;
            padding-top: 12px;
            font-size: 12pt;
        }

        .net-amount { font-size: 14pt; font-weight: 700; color: #2563eb; }

        .muted { color: #64748b; }
        .small { font-size: 8.5pt; }

        .deduction-list {
            margin: 4px 0 6px 1.2em;
            padding: 0;
            font-size: 9pt;
            color: #475569;
        }

        .deduction-list li { margin-bottom: 2px; }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer .sig-line {
            width: 200px;
            border-top: 1px solid #0f172a;
            margin-top: 40px;
            padding-top: 6px;
            font-size: 9pt;
            color: #475569;
            text-align: center;
        }

        .payslip-page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    @for ($copy = 0; $copy < 2; $copy++)
    <div class="{{ $copy === 0 ? 'payslip-page-break' : '' }}">
    <div class="header">
        <div>
            <div class="brand">HR Management System</div>
            <div class="brand-sub">Official Payslip Document</div>
        </div>
        <div class="doc-title">
            <h1>Payslip</h1>
            <p>Processed: {{ $payslip->processed_at?->format('M j, Y H:i') }}</p>
        </div>
    </div>

    @include('admin.payslips.partials.detail', ['payslip' => $payslip])

    <div class="footer">
        <div class="sig-line">Authorized Signature</div>
    </div>
    </div>
    @endfor

</body>
</html>
