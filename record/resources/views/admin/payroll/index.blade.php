@extends('layouts.admin', ['title' => 'Payroll'])

@section('content')

    <style>
        .payroll-preview-head {
            display: flex;
            flex-wrap: wrap;
            gap: 14px 20px;
            align-items: flex-end;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--c-border);
        }
        .payroll-preview-title {
            font-size: 14px;
            font-weight: 700;
        }
        .payroll-preview-count {
            margin-top: 4px;
            font-size: 12.5px;
            color: var(--c-text-secondary);
            font-weight: 600;
        }
        .payroll-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            flex: 1;
            justify-content: flex-end;
            min-width: min(100%, 280px);
        }
        .payroll-search {
            position: relative;
            flex: 1 1 220px;
            max-width: 320px;
        }
        .payroll-search svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-text-muted);
            pointer-events: none;
        }
        .payroll-search .form-control {
            padding-left: 32px;
        }
        .payroll-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .payroll-filter {
            border: 1px solid var(--c-border-strong);
            background: var(--c-surface);
            color: var(--c-text-secondary);
            border-radius: 99px;
            padding: 5px 10px;
            font: inherit;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .payroll-filter:hover { background: var(--c-surface-2); color: var(--c-text); }
        .payroll-filter.is-active {
            background: var(--c-primary);
            border-color: var(--c-primary);
            color: #fff;
        }
        .payroll-table-viewport {
            max-height: min(68vh, 720px);
            overflow: auto;
        }
        .payroll-table {
            width: 100%;
            min-width: 1180px;
            table-layout: fixed;
        }
        .payroll-table th,
        .payroll-table td {
            padding: 12px 14px;
            vertical-align: middle;
        }
        .payroll-table thead th {
            white-space: normal;
            line-height: 1.35;
        }
        .payroll-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--c-surface-2);
            box-shadow: 0 1px 0 var(--c-border);
        }
        .payroll-col-employee {
            position: sticky;
            left: 0;
            z-index: 2;
            min-width: 250px;
            background: var(--c-surface);
            box-shadow: 1px 0 0 var(--c-border);
        }
        .payroll-employee-name {
            font-weight: 700;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }
        .payroll-breakdown-link {
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid #7dd3fc;
            border-radius: 7px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .payroll-breakdown-link:hover {
            border-color: #0284c7;
            background: #bae6fd;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, .12);
            color: #075985;
            text-decoration: none;
        }
        .payroll-breakdown-link:focus-visible {
            outline: 2px solid var(--c-primary-text);
            outline-offset: 2px;
        }
        html[data-theme="dark"] .payroll-breakdown-link {
            border-color: #0e7490;
            background: #164e63;
            color: #cffafe;
        }
        html[data-theme="dark"] .payroll-breakdown-link:hover {
            border-color: #22d3ee;
            background: #155e75;
            color: #ecfeff;
        }
        .payroll-table thead th.payroll-col-employee {
            z-index: 4;
            background: var(--c-surface-2);
        }
        .payroll-table tbody tr:hover td.payroll-col-employee {
            background: var(--c-surface-2);
        }
        .payroll-num { text-align: right; font-variant-numeric: tabular-nums; }
        .payroll-table th.payroll-num .payroll-sort { width: 100%; justify-content: flex-end; }
        .payroll-sort {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            letter-spacing: inherit;
            text-transform: inherit;
            cursor: pointer;
        }
        .payroll-num .payroll-sort { margin-left: auto; }
        .payroll-sort:hover { color: var(--c-primary); }
        .payroll-table th[aria-sort="ascending"] .payroll-sort::after { content: "↑"; }
        .payroll-table th[aria-sort="descending"] .payroll-sort::after { content: "↓"; }
        .payroll-period-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
            gap: 16px;
            align-items: end;
        }
        .payroll-period-field {
            min-width: 0;
            margin: 0;
        }
        .payroll-period-field .form-label {
            display: block;
            margin-bottom: 7px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .payroll-period-field .form-control {
            width: 100%;
            min-width: 0;
        }
        .payroll-period-fields > .btn {
            min-width: 150px;
            min-height: 40px;
            justify-content: center;
        }
        .payroll-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .payroll-action-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0;
            min-height: 0;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
        }
        .payroll-action-form .form-control {
            width: 110px;
            min-height: 34px;
        }
        input:not([type]),
        input[type="text"],
        input[type="search"],
        textarea {
            text-transform: uppercase;
        }
        .payroll-action-dialog {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            max-width: none;
            max-height: none;
            margin: 0;
            padding: 24px;
            display: none;
            align-items: center;
            justify-content: center;
            overflow: auto;
            border: 0;
            background: transparent;
            color: var(--c-text);
        }
        .payroll-action-dialog[open] {
            display: flex;
        }
        .payroll-action-dialog::backdrop { background: var(--c-modal-backdrop); }
        .annual-benefit-form {
            width: min(680px, 100%);
            max-height: min(86vh, 760px);
            overflow-y: auto;
            padding: 20px;
            border: 1px solid var(--c-border);
            border-radius: 14px;
            background: var(--c-surface);
            color: var(--c-text);
            box-shadow: var(--shadow-lg);
        }
        .payroll-print-form {
            display: grid;
            gap: 24px;
            width: min(560px, 100%);
            max-height: min(86vh, 520px);
            padding: 26px;
        }
        .payroll-print-form .annual-benefit-header {
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--c-border);
        }
        .payroll-print-form .annual-benefit-header h2 {
            font-size: 20px;
            line-height: 1.3;
        }
        .payroll-print-form .annual-benefit-header p {
            margin-top: 6px;
            line-height: 1.5;
        }
        .payroll-print-form .annual-benefit-fields {
            gap: 18px;
        }
        .payroll-print-form .form-group {
            display: grid;
            align-content: start;
            gap: 8px;
        }
        .payroll-print-form .annual-benefit-fields label {
            margin: 0;
            font-size: 11px;
        }
        .payroll-print-form .annual-benefit-footer {
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--c-border);
        }
        .payroll-layout-form {
            display: grid;
            gap: 18px;
            width: min(760px, 100%);
        }
        .payroll-layout-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 16px;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: 10px;
            background: var(--c-surface-2);
        }
        .payroll-layout-group h3 {
            grid-column: 1 / -1;
            margin: 0;
            color: var(--c-primary-text);
            font-size: 12px;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .payroll-layout-group .form-group {
            display: grid;
            align-content: start;
            gap: 7px;
            margin: 0;
        }
        .payroll-layout-group .form-group label {
            color: var(--c-text-secondary);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .payroll-layout-group input[type="text"] {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
            outline: none;
        }
        .payroll-layout-group input[type="text"]:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .payroll-layout-hint {
            grid-column: 1 / -1;
            margin: -4px 0 0;
            color: var(--c-text-muted);
            font-size: 11px;
        }
        .payroll-layout-signatories {
            display: grid;
            grid-column: 1 / -1;
            gap: 10px;
        }
        .payroll-layout-signatory {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            padding: 12px;
            border: 1px solid var(--c-border);
            border-radius: 9px;
            background: var(--c-surface);
        }
        .payroll-layout-signatory strong {
            grid-column: 1 / -1;
            color: var(--c-text-secondary);
            font-size: 11px;
        }
        .payroll-layout-signatory .form-group {
            display: grid;
            gap: 6px;
        }
        .payroll-layout-signatory .form-group label {
            color: var(--c-text-muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .payroll-layout-signatory input[type="text"] {
            width: 100%;
            min-height: 36px;
            padding: 7px 9px;
            border: 1px solid var(--c-border-strong);
            border-radius: 7px;
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
            outline: none;
        }
        .payroll-layout-signatory input[type="text"]:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .annual-benefit-form {
            display: grid;
            gap: 16px;
        }
        .payroll-action-dialog > .payroll-layout-form {
            width: min(760px, 100%);
        }
        .payroll-action-dialog > .payroll-print-form {
            width: min(560px, 100%);
        }
        .annual-benefit-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }
        .annual-benefit-header h2 {
            margin: 0 0 5px;
            color: var(--c-text);
            font-size: 18px;
        }
        .annual-benefit-header p {
            margin: 0;
            color: var(--c-text-muted);
            font-size: 13px;
        }
        .annual-benefit-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .annual-benefit-fields .form-group { margin: 0; }
        .annual-benefit-fields label,
        .annual-benefit-employees label {
            display: block;
            margin-bottom: 7px;
            color: var(--c-text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .payroll-action-dialog input[type="text"],
        .payroll-action-dialog input[type="number"],
        .payroll-action-dialog input[type="date"],
        .payroll-action-dialog select {
            width: 100%;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface-2);
            color: var(--c-text);
            font: inherit;
            outline: none;
        }
        .payroll-action-dialog input:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .annual-benefit-employees {
            display: grid;
            gap: 10px;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: 10px;
            background: var(--c-surface-2);
        }
        .annual-benefit-month-panel {
            display: grid;
            gap: 10px;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: 10px;
            background: var(--c-surface-2);
        }
        .annual-benefit-month-panel[hidden],
        .annual-benefit-custom-fields[hidden],
        .annual-benefit-fixed-amount[hidden] {
            display: none;
        }
        .annual-benefit-months {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .annual-benefit-months label {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin: 0;
            padding: 8px;
            border: 1px solid var(--c-border);
            border-radius: 7px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
        }
        .annual-benefit-months input {
            margin: 0;
            accent-color: var(--c-primary);
        }
        .annual-benefit-formula {
            margin: 0;
            color: var(--c-text-secondary);
            font-size: 13px;
            line-height: 1.5;
        }
        .annual-benefit-preview {
            max-height: 180px;
            overflow: auto;
            padding: 10px;
            border: 1px solid var(--c-border);
            border-radius: 8px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 12px;
        }
        .annual-benefit-preview table {
            width: 100%;
            border-collapse: collapse;
        }
        .annual-benefit-preview th,
        .annual-benefit-preview td {
            padding: 5px 7px;
            border-bottom: 1px solid var(--c-border);
            text-align: left;
        }
        .annual-benefit-preview td:last-child,
        .annual-benefit-preview th:last-child {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .annual-benefit-employees-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .annual-benefit-employees-head strong {
            color: var(--c-text);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .annual-benefit-employee-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            max-height: 220px;
            overflow-y: auto;
        }
        .annual-benefit-employee {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            padding: 9px 10px;
            border: 1px solid var(--c-border);
            border-radius: 8px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 13px;
        }
        .annual-benefit-employee input { accent-color: var(--c-primary); }
        .annual-benefit-footer {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 4px;
        }
        .annual-benefit-error {
            margin: 0;
            color: var(--c-error-text);
            font-size: 12px;
        }
        @media (max-width: 560px) {
            .payroll-actions { align-items: stretch; }
            .payroll-action-form { width: 100%; }
            .annual-benefit-fields,
            .annual-benefit-employee-list { grid-template-columns: minmax(0, 1fr); }
            .payroll-layout-group,
            .payroll-layout-signatory { grid-template-columns: minmax(0, 1fr); }
            .payroll-layout-group h3,
            .payroll-layout-signatory strong,
            .payroll-layout-hint { grid-column: auto; }
            .payroll-action-dialog { padding: 16px; }
            .annual-benefit-form { padding: 16px; }
            .payroll-print-form { gap: 18px; padding: 20px; }
            .payroll-print-form .annual-benefit-header { align-items: flex-start; }
            .payroll-print-form .annual-benefit-footer {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .payroll-print-form .annual-benefit-footer .btn { justify-content: center; }
        }
        @media (max-width: 800px) {
            .payroll-toolbar { justify-content: flex-start; }
            .payroll-search { max-width: none; }
        }
        @media (max-width: 560px) {
            .payroll-period-fields {
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
            }
            .payroll-period-fields > .btn { width: 100%; }
            .payroll-preview-head { padding: 14px; }
            .payroll-toolbar { min-width: 0; width: 100%; }
            .payroll-search { flex-basis: 100%; }
            .payroll-filters { width: 100%; }
        }
    </style>

    <div class="action-bar payroll-actions">
        <a class="btn" href="{{ route('admin.payroll.settings') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            Settings
        </a>
        <a class="btn" href="{{ route('admin.payslips.index', ['period_start' => $period_start, 'period_end' => $period_end]) }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            Payslips
        </a>
        <button
            class="btn admin-request-button"
            id="admin-payslip-request-open"
            type="button"
            aria-label="Pending payslip requests"
            aria-haspopup="dialog"
            aria-controls="admin-payslip-request-modal"
        >
            Payslip Request
            <span class="admin-request-count" id="admin-payslip-request-count" hidden>0</span>
        </button>
        <form method="POST" action="{{ route('admin.payslips.process') }}" class="payroll-action-form">
            @csrf
            <input type="hidden" name="period_start" value="{{ $period_start }}">
            <input type="hidden" name="period_end" value="{{ $period_end }}">
            @if ($employee_id)
                <input type="hidden" name="employee_id" value="{{ $employee_id }}">
            @endif
            <button class="btn" type="submit">Generate Payslips</button>
        </form>
        <a class="btn" href="{{ route('admin.payroll.annual-benefits.create', ['period_start' => $period_start, 'period_end' => $period_end]) }}">Create Annual Benefits</a>
        <button class="btn" type="button" id="payroll-print-open">Print Payroll</button>
        <button class="btn" type="button" id="payroll-layout-open">Customize Print Layout</button>
    </div>

    <dialog class="payroll-action-dialog" id="payroll-layout-dialog" aria-labelledby="payroll-layout-title">
        <form method="POST" action="{{ route('admin.payroll.print-layout.update') }}" class="annual-benefit-form payroll-layout-form">
            @csrf
            <div class="annual-benefit-header">
                <div>
                    <h2 id="payroll-layout-title">Customize Print Layout</h2>
                </div>
                <button class="btn" type="button" id="payroll-layout-close" aria-label="Close layout customizer">Close</button>
            </div>

            <section class="payroll-layout-group" aria-labelledby="payroll-layout-header-fields">
                <h3 id="payroll-layout-header-fields">Report Header and Labels</h3>
                @foreach ([
                    'barangay_name' => 'Barangay Name',
                    'city_name' => 'City',
                    'report_title' => 'Report Title',
                    'source_fund' => 'Default Source of Fund',
                    'payroll_number_format' => 'Payroll Number Format',
                    'period_covered_label' => 'Period Covered Label',
                    'payroll_number_label' => 'Payroll Number Label',
                    'source_fund_label' => 'Source of Fund Label',
                    'date_prepared_label' => 'Date Prepared Label',
                    'prepared_date_description' => 'Prepared Date Description',
                ] as $field => $label)
                    <div class="form-group">
                        <label for="print-layout-{{ $field }}">{{ $label }}</label>
                        <input
                            id="print-layout-{{ $field }}"
                            type="text"
                            name="{{ $field }}"
                            maxlength="{{ $field === 'prepared_date_description' ? 180 : ($field === 'payroll_number_format' ? 64 : 120) }}"
                            value="{{ old($field, $printSettings->{$field}) }}"
                            required
                        >
                    </div>
                @endforeach
            </section>

            <section class="payroll-layout-group" aria-labelledby="payroll-layout-signatory-fields">
                <h3 id="payroll-layout-signatory-fields">Signatory Names and Designations</h3>
                <div class="payroll-layout-signatories">
                    @foreach ([
                        ['name' => 'punong_barangay_name', 'designation' => 'punong_barangay_designation', 'role' => 'Punong Barangay'],
                        ['name' => 'appropriations_chair_name', 'designation' => 'appropriations_chair_designation', 'role' => 'Chairman Committee on Appropriations'],
                        ['name' => 'treasurer_name', 'designation' => 'treasurer_designation', 'role' => 'Barangay Treasurer'],
                        ['name' => 'approved_by_name', 'designation' => 'approved_by_designation', 'role' => 'Approved By'],
                        ['name' => 'prepared_by_name', 'designation' => 'prepared_by_designation', 'role' => 'Prepared By'],
                    ] as $signatory)
                        <div class="payroll-layout-signatory">
                            <strong>{{ $signatory['role'] }}</strong>
                            <div class="form-group">
                                <label for="print-layout-{{ $signatory['name'] }}">Name</label>
                                <input
                                    id="print-layout-{{ $signatory['name'] }}"
                                    type="text"
                                    name="{{ $signatory['name'] }}"
                                    maxlength="120"
                                    value="{{ old($signatory['name'], $printSettings->{$signatory['name']}) }}"
                                >
                            </div>
                            <div class="form-group">
                                <label for="print-layout-{{ $signatory['designation'] }}">Title / Designation</label>
                                <input
                                    id="print-layout-{{ $signatory['designation'] }}"
                                    type="text"
                                    name="{{ $signatory['designation'] }}"
                                    maxlength="{{ $signatory['designation'] === 'appropriations_chair_designation' ? 160 : 120 }}"
                                    value="{{ old($signatory['designation'], $printSettings->{$signatory['designation']}) }}"
                                    required
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="annual-benefit-footer">
                <button class="btn" type="button" id="payroll-layout-cancel">Cancel</button>
                <button class="btn btn-primary" type="submit">Save Print Layout</button>
            </div>
        </form>
    </dialog>

    <dialog class="payroll-action-dialog payroll-print-dialog" id="payroll-print-dialog" aria-labelledby="payroll-print-title">
        <form method="GET" action="{{ route('admin.payroll.print') }}" target="_blank" class="annual-benefit-form payroll-print-form">
            <div class="annual-benefit-header">
                <div>
                    <h2 id="payroll-print-title">Print Payroll Report</h2>
                </div>
                <button class="btn" type="button" id="payroll-print-close" aria-label="Close print options">Close</button>
            </div>
            <div class="annual-benefit-fields">
                <div class="form-group">
                    <label for="payroll-print-period">Payroll Month</label>
                    <input id="payroll-print-period" type="month" name="period" value="{{ substr($period_start, 0, 7) }}" required>
                </div>
                <div class="form-group">
                    <label for="payroll-print-source-fund">Source of Fund</label>
                    <input id="payroll-print-source-fund" type="text" name="source_fund" maxlength="120" value="{{ $printSettings->source_fund }}" placeholder="Optional">
                </div>
            </div>
            <div class="annual-benefit-footer">
                <button class="btn" type="button" id="payroll-print-cancel">Cancel</button>
                <button class="btn btn-primary" type="submit">Open Print Report</button>
            </div>
        </form>
    </dialog>

    {{-- Period filter --}}
    <form method="GET" action="{{ route('admin.payroll.index') }}">
        <div class="card" style="margin-bottom:16px;">
            <div class="card-title" style="margin-bottom:12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Payroll Period
            </div>
            <div class="payroll-period-fields">
                <div class="form-group payroll-period-field">
                    <label class="form-label" for="payroll-period-start">Period Start</label>
                    <input id="payroll-period-start" class="form-control" type="date" name="period_start" value="{{ $period_start }}" required>
                </div>
                <div class="form-group payroll-period-field">
                    <label class="form-label" for="payroll-period-end">Period End</label>
                    <input id="payroll-period-end" class="form-control" type="date" name="period_end" value="{{ $period_end }}" required>
                </div>
                <button class="btn btn-primary" type="submit">Apply Period</button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var uppercaseTextInput = function (target) {
                if (!target.matches('input:not([type]), input[type="text"], input[type="search"], textarea')) {
                    return;
                }

                var start = target.selectionStart;
                var end = target.selectionEnd;
                var value = target.value;
                var uppercaseValue = value.toUpperCase();
                if (value === uppercaseValue) {
                    return;
                }

                target.value = uppercaseValue;
                if (start !== null && end !== null) {
                    target.setSelectionRange(
                        value.slice(0, start).toUpperCase().length,
                        value.slice(0, end).toUpperCase().length
                    );
                }
            };
            document.addEventListener('input', function (event) {
                if (!event.isComposing) {
                    uppercaseTextInput(event.target);
                }
            });
            document.addEventListener('compositionend', function (event) {
                uppercaseTextInput(event.target);
            });

            var layoutDialog = document.getElementById('payroll-layout-dialog');
            var layoutCloseButtons = [
                document.getElementById('payroll-layout-close'),
                document.getElementById('payroll-layout-cancel')
            ];
            document.getElementById('payroll-layout-open').addEventListener('click', function () {
                layoutDialog.showModal();
            });
            layoutCloseButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    layoutDialog.close();
                });
            });
            layoutDialog.addEventListener('click', function (event) {
                if (event.target === layoutDialog) {
                    layoutDialog.close();
                }
            });

            var printDialog = document.getElementById('payroll-print-dialog');
            var printCloseButtons = [
                document.getElementById('payroll-print-close'),
                document.getElementById('payroll-print-cancel')
            ];
            document.getElementById('payroll-print-open').addEventListener('click', function () {
                printDialog.showModal();
            });
            printCloseButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    printDialog.close();
                });
            });
            printDialog.addEventListener('click', function (event) {
                if (event.target === printDialog) {
                    printDialog.close();
                }
            });

            @if ($errors->hasAny([
                'barangay_name', 'city_name', 'report_title', 'source_fund', 'payroll_number_format',
                'period_covered_label', 'payroll_number_label', 'source_fund_label', 'date_prepared_label',
                'prepared_date_description', 'punong_barangay_name', 'punong_barangay_designation',
                'appropriations_chair_name', 'appropriations_chair_designation', 'treasurer_name',
                'treasurer_designation', 'approved_by_name', 'approved_by_designation',
                'prepared_by_name', 'prepared_by_designation'
            ]))
                layoutDialog.showModal();
            @endif
        }());
    </script>

    {{-- Payroll table --}}
    <div class="card" id="payroll-preview" style="padding:0;overflow:hidden;">
        <div class="payroll-preview-head">
            <div>
                <div class="payroll-preview-title">Payroll Preview</div>
                <div class="payroll-preview-count" id="payroll-count" aria-live="polite">
                    {{ count($lines) }} {{ count($lines) === 1 ? 'employee' : 'employees' }}
                </div>
            </div>
            <div class="payroll-toolbar">
                <div class="payroll-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input id="payroll-search" class="form-control" type="search" placeholder="Find name, department, or ID" autocomplete="off" aria-label="Find an employee in payroll">
                </div>
                <div class="payroll-filters" role="group" aria-label="Filter payroll rows">
                    <button class="payroll-filter is-active" type="button" data-payroll-filter="all">All</button>
                    <button class="payroll-filter" type="button" data-payroll-filter="daily">Daily</button>
                    <button class="payroll-filter" type="button" data-payroll-filter="hourly">Hourly</button>
                    <button class="payroll-filter" type="button" data-payroll-filter="needs-rate">Needs rate</button>
                </div>
            </div>
        </div>
        <div class="table-wrapper payroll-table-viewport">
            <table class="table payroll-table">
                <colgroup>
                    <col style="width:22%;">
                    <col style="width:10%;">
                    <col style="width:9%;">
                    <col style="width:14%;">
                    <col style="width:10%;">
                    <col style="width:11%;">
                    <col style="width:13%;">
                    <col style="width:11%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="payroll-col-employee" aria-sort="ascending"><button class="payroll-sort" type="button" data-sort="name">Employee</button></th>
                        <th aria-sort="none"><button class="payroll-sort" type="button" data-sort="type">Rate Type</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="rate">Rate</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="hours">Payable Hours</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="days">Equiv. Days</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="gross">Gross</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="deductions">Deductions</button></th>
                        <th class="payroll-num" aria-sort="none"><button class="payroll-sort" type="button" data-sort="net">Net Pay</button></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($lines as $line)
                    @php($emp = $line['employee'])
                    @php($rateType = $emp->pay_rate_type === \App\Models\User::PAY_RATE_HOURLY ? 'hourly' : 'daily')
                    @php($searchBits = array_filter([$emp->name, $emp->department, $emp->position, $emp->employee_id, $rateType]))
                    <tr class="payroll-row"
                        data-search="{{ \Illuminate\Support\Str::lower(implode(' ', $searchBits)) }}"
                        data-rate-type="{{ $rateType }}"
                        data-has-rate="{{ $emp->pay_rate !== null ? '1' : '0' }}"
                        data-sort-name="{{ \Illuminate\Support\Str::lower($emp->name) }}"
                        data-sort-type="{{ $rateType }}"
                        data-sort-rate="{{ $emp->pay_rate !== null ? $emp->pay_rate : '' }}"
                        data-sort-hours="{{ $line['regular_hours'] }}"
                        data-sort-days="{{ $line['equivalent_days'] }}"
                        data-sort-gross="{{ $emp->pay_rate !== null ? $line['gross_pay'] : '' }}"
                        data-sort-deductions="{{ $emp->pay_rate !== null ? ($line['deductions_total'] ?? 0) : '' }}"
                        data-sort-net="{{ $emp->pay_rate !== null ? $line['net_pay'] : '' }}">
                        <td class="payroll-col-employee">
                            <div class="payroll-employee-name">
                                <a class="link-primary" href="{{ route('admin.payroll.settings', ['employee' => $emp->id]) }}#employee-payroll-{{ $emp->id }}">{{ $emp->name }}</a>
                            </div>
                            @if ($line['attendance_records'] !== [])
                                <a class="payroll-breakdown-link" href="{{ route('admin.payroll.breakdown', ['employee' => $emp, 'period_start' => $period_start, 'period_end' => $period_end]) }}">
                                    Daily breakdown
                                </a>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray">
                                {{ $rateType === 'hourly' ? 'Hourly' : 'Daily' }}
                            </span>
                        </td>
                        <td class="text-mono payroll-num" style="font-size:13px;">
                            @if ($emp->pay_rate !== null)
                                {{ number_format((float) $emp->pay_rate, 2) }}
                            @else
                                <span style="color:var(--c-text-muted);">—</span>
                            @endif
                        </td>
                        <td class="text-mono payroll-num" style="font-size:13px;">
                            {{ number_format($line['regular_hours'], 2) }}
                        </td>
                        <td class="text-mono payroll-num" style="font-size:13px;">{{ number_format($line['equivalent_days'], 4) }}</td>
                        <td class="payroll-num">
                            @if ($emp->pay_rate !== null || $line['earning_total'] > 0)
                                <div class="text-mono" style="font-size:14px;font-weight:700;">{{ number_format($line['gross_pay'], 2) }}</div>
                                @if ($line['earning_lines'] !== [])
                                    <details style="margin-top:4px;">
                                        <summary style="cursor:pointer;font-size:12px;color:var(--c-primary);font-weight:600;list-style:none;">Annual benefits</summary>
                                        <ul style="margin:6px 0 0 12px;padding:0;font-size:12px;color:var(--c-text-secondary);">
                                            @foreach ($line['earning_lines'] as $earning)
                                                <li>{{ $earning['name'] }} · {{ \Illuminate\Support\Carbon::parse($earning['payment_date'])->format('M j') }}: {{ number_format($earning['amount'], 2) }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                                @if ($emp->pay_rate === null)
                                    <div style="font-size:12px;color:var(--c-warning-text, #92400e);">Set rate in payroll settings</div>
                                @endif
                            @else
                                <span style="font-size:12px;color:var(--c-warning-text, #92400e);">Set rate in payroll settings</span>
                            @endif
                        </td>
                        <td class="payroll-num">
                            @if ($emp->pay_rate !== null)
                                <div class="text-mono" style="font-size:14px;font-weight:700;">{{ number_format($line['deductions_total'] ?? 0, 2) }}</div>
                                @if (($line['deduction_lines'] ?? []) !== [])
                                    <details style="margin-top:4px;">
                                        <summary style="cursor:pointer;font-size:12px;color:var(--c-primary);font-weight:600;list-style:none;display:inline-flex;align-items:center;gap:4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                            Breakdown
                                        </summary>
                                        <ul style="margin:6px 0 0 12px;padding:0;font-size:12px;color:var(--c-text-secondary);">
                                            @foreach ($line['deduction_lines'] as $row)
                                                <li style="margin-bottom:3px;">
                                                    {{ $row['name'] }}
                                                    @if (($row['calculation_type'] ?? '') === \App\Models\EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS)
                                                        <span style="color:var(--c-text-muted);">({{ rtrim(rtrim(number_format((float) $row['value'], 4), '0'), '.') }}%)</span>
                                                    @endif
                                                    — {{ number_format((float) $row['amount'], 2) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                                <div style="margin-top:4px;font-size:11.5px;">
                                    <a class="link-primary" href="{{ route('admin.payroll.settings', ['employee' => $emp->id]) }}#employee-deductions-{{ $emp->id }}">Edit deductions</a>
                                </div>
                            @else
                                <span style="color:var(--c-text-muted);">—</span>
                            @endif
                        </td>
                        <td class="payroll-num">
                            @if ($emp->pay_rate !== null)
                                <div class="text-mono" style="font-size:14px;font-weight:700;color:var(--c-primary-text);">{{ number_format($line['net_pay'], 2) }}</div>
                            @else
                                <span style="color:var(--c-text-muted);">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($lines === [])
                    <tr><td colspan="8" class="table-empty">No employees found.</td></tr>
                @else
                    <tr id="payroll-empty-filter" hidden>
                        <td colspan="8" class="table-empty">No employees match this view.</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('payroll-preview');
            if (!root) return;

            const rows = Array.from(root.querySelectorAll('tbody tr.payroll-row'));
            const tbody = root.querySelector('tbody');
            const search = document.getElementById('payroll-search');
            const count = document.getElementById('payroll-count');
            const empty = document.getElementById('payroll-empty-filter');
            const filters = root.querySelectorAll('[data-payroll-filter]');
            const sortButtons = root.querySelectorAll('.payroll-sort');
            let filter = 'all';
            let sortKey = 'name';
            let sortDir = 'asc';

            const sortAttr = {
                name: 'data-sort-name',
                type: 'data-sort-type',
                rate: 'data-sort-rate',
                hours: 'data-sort-hours',
                days: 'data-sort-days',
                gross: 'data-sort-gross',
                deductions: 'data-sort-deductions',
                net: 'data-sort-net',
            };

            function numberOrNull(row, attr) {
                const raw = row.getAttribute(attr);
                if (raw === null || raw === '') return null;
                const value = Number(raw);
                return Number.isFinite(value) ? value : null;
            }

            function matches(row) {
                const query = search.value.trim().toLowerCase();
                if (query && !(row.getAttribute('data-search') || '').includes(query)) return false;
                if (filter === 'daily' && row.getAttribute('data-rate-type') !== 'daily') return false;
                if (filter === 'hourly' && row.getAttribute('data-rate-type') !== 'hourly') return false;
                if (filter === 'needs-rate' && row.getAttribute('data-has-rate') !== '0') return false;
                return true;
            }

            function apply() {
                const shown = rows.filter(matches);
                const hidden = rows.filter(function (row) { return shown.indexOf(row) === -1; });

                shown.sort(function (a, b) {
                    const attr = sortAttr[sortKey];
                    if (sortKey === 'name' || sortKey === 'type') {
                        return (a.getAttribute(attr) || '').localeCompare(b.getAttribute(attr) || '') * (sortDir === 'asc' ? 1 : -1);
                    }
                    const av = numberOrNull(a, attr);
                    const bv = numberOrNull(b, attr);
                    if (av === null && bv === null) return 0;
                    if (av === null) return 1;
                    if (bv === null) return -1;
                    return (av - bv) * (sortDir === 'asc' ? 1 : -1);
                });

                shown.forEach(function (row) {
                    row.hidden = false;
                    tbody.appendChild(row);
                });
                hidden.forEach(function (row) {
                    row.hidden = true;
                    tbody.appendChild(row);
                });
                if (empty) {
                    empty.hidden = shown.length !== 0 || rows.length === 0;
                    tbody.appendChild(empty);
                }

                const noun = rows.length === 1 ? 'employee' : 'employees';
                count.textContent = shown.length === rows.length
                    ? rows.length + ' ' + noun
                    : 'Showing ' + shown.length + ' of ' + rows.length;

                sortButtons.forEach(function (button) {
                    const header = button.closest('th');
                    const active = button.dataset.sort === sortKey;
                    header.setAttribute('aria-sort', active ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none');
                });
            }

            search.addEventListener('input', apply);
            filters.forEach(function (button) {
                button.addEventListener('click', function () {
                    filter = button.dataset.payrollFilter;
                    filters.forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    apply();
                });
            });
            sortButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const key = button.dataset.sort;
                    if (sortKey === key) {
                        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortKey = key;
                        sortDir = (key === 'name' || key === 'type') ? 'asc' : 'desc';
                    }
                    apply();
                });
            });
        })();
    </script>

@endsection
