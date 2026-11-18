<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logbook Report — {{ $periodLabel }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5pt;
            color: #0f172a;
            margin: 28px 32px;
        }
        h1 { font-size: 16pt; margin: 0 0 2px; }
        h2 {
            font-size: 11pt;
            margin: 18px 0 6px;
            padding-bottom: 3px;
            border-bottom: 2px solid #0d1426;
        }
        .sub { color: #64748b; font-size: 8.5pt; margin: 0; }
        .header {
            border-bottom: 2px solid #0d1426;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        table { width: 100%; border-collapse: collapse; margin: 0 0 8px; }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #0d1426;
            color: #fff;
            font-size: 8pt;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .kpi td { width: 50%; }
        .kpi .label { display: block; color: #64748b; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
        .kpi .value { display: block; font-size: 12pt; font-weight: 700; margin-top: 2px; }
        .note { color: #475569; font-size: 8.5pt; margin: 0 0 8px; }
        .footer { margin-top: 22px; color: #64748b; font-size: 8pt; }
        .right { text-align: right; }
    </style>
</head>
<body>
@php
    $totals = $summary['totals'] ?? [];
    $top = $summary['top_visitor'] ?? null;
    $series = array_slice($summary[$seriesKey] ?? [], -24);
    $purposes = $summary['purpose_breakdown'] ?? [];
@endphp

    <div class="header">
        <h1>Logbook Report</h1>
        <p class="sub">{{ $periodLabel }} view · generated {{ now()->format('M j, Y g:i A') }}</p>
    </div>

    <h2>Summary</h2>
    <table class="kpi">
        <tr>
            <td>
                <span class="label">Logbook records</span>
                <span class="value">{{ number_format((int) ($totals['records'] ?? 0)) }}</span>
            </td>
            <td>
                <span class="label">Most frequent visitor</span>
                <span class="value">{{ $top['name'] ?? 'No logbook data yet' }}</span>
                <span class="label">{{ isset($top['count']) ? number_format((int) $top['count']).' visits' : '0 visits' }}</span>
            </td>
        </tr>
    </table>

    <h2>Visits by {{ strtolower($periodLabel) }}</h2>
    <p class="note">Record counts for each {{ strtolower($periodLabel) }} bucket. The latest 24 buckets are listed.</p>
    <table>
        <thead>
            <tr><th>Bucket</th><th class="right">Records</th></tr>
        </thead>
        <tbody>
            @forelse ($series as $row)
                <tr>
                    <td>{{ $row['label'] ?? '—' }}</td>
                    <td class="right">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No logbook rows in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Purpose of Transaction mix</h2>
    <p class="note">Top purposes or descriptions on the logbook.</p>
    <table>
        <thead>
            <tr><th>Purpose of Transaction</th><th class="right">Records</th></tr>
        </thead>
        <tbody>
            @forelse ($purposes as $row)
                <tr>
                    <td>{{ $row['label'] ?? '—' }}</td>
                    <td class="right">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No purpose breakdown yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">ReCORD · Confidential logbook report · {{ $periodLabel }} snapshot.</p>
</body>
</html>
