<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Printable Logbook Records</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            color: #172033;
            font: 14px/1.45 Arial, Helvetica, sans-serif;
        }
        .print-actions { display: flex; justify-content: flex-end; gap: 8px; margin: 0 auto 18px; max-width: 900px; }
        .print-actions button {
            padding: 9px 14px;
            border: 1px solid #334155;
            border-radius: 4px;
            background: #fff;
            color: #172033;
            font: inherit;
            cursor: pointer;
        }
        .record-sheet { max-width: 900px; margin: 0 auto 28px; }
        .record-sheet + .record-sheet { break-before: page; page-break-before: always; }
        .document-header { padding-bottom: 14px; border-bottom: 2px solid #172033; text-align: center; }
        .document-header h1 { margin: 0; font-size: 21px; letter-spacing: .04em; }
        .document-header p { margin: 3px 0 0; font-size: 12px; }
        .document-header h2 { margin: 13px 0 0; font-size: 17px; }
        .person-details { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; margin: 16px 0; }
        .detail { overflow-wrap: anywhere; }
        .detail strong { display: inline-block; min-width: 125px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 8px; border: 1px solid #64748b; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #e2e8f0; font-size: 12px; }
        td { font-size: 12px; }
        .document-footer { margin-top: 20px; color: #475569; font-size: 10px; text-align: right; }
        @page { size: A4 portrait; margin: 14mm; }
        @media print {
            body { padding: 0; }
            .print-actions { display: none; }
            .record-sheet { max-width: none; margin: 0; }
            .record-sheet + .record-sheet { margin-top: 0; }
            th { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
        @media (max-width: 600px) {
            body { padding: 14px; }
            .person-details { grid-template-columns: 1fr; }
            .detail strong { min-width: 112px; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>
    @foreach ($groups as $group)
        <main class="record-sheet">
            <header class="document-header">
                <h1>BARANGAY SAN JOSE</h1>
                <p>Official Logbook Transaction Records</p>
                <h2>PERSONAL LOGBOOK HISTORY</h2>
            </header>
            <section class="person-details" aria-label="Person details">
                <div class="detail"><strong>Full Name:</strong> {{ $group['name'] ?: '—' }}</div>
                <div class="detail"><strong>Classification:</strong> {{ $group['classification'] ?: '—' }}</div>
                <div class="detail"><strong>Address:</strong> {{ $group['address'] ?: '—' }}</div>
                <div class="detail"><strong>Contact Number:</strong> {{ $group['contact_number'] ?: '—' }}</div>
                <div class="detail"><strong>Period:</strong> {{ $periodLabel }}</div>
                <div class="detail"><strong>Records Date Range:</strong> {{ $group['date_range'] }}</div>
            </section>
            <table>
                <thead>
                    <tr>
                        <th style="width: 38%;">Purpose of Transaction</th>
                        <th style="width: 28%;">Date/Time In</th>
                        <th style="width: 18%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['entries'] as $entry)
                        <tr>
                            <td>{{ $entry['purpose'] ?: '—' }}</td>
                            <td>{{ $entry['timestamp'] ?: '—' }}</td>
                            <td>{{ $entry['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <footer class="document-footer">Generated {{ $printedAt }}</footer>
        </main>
    @endforeach
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
