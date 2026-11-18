@extends('layouts.admin', ['title' => 'Payroll Settings'])

@section('content')

    <style>
        .payroll-settings-form {
            display: grid;
            gap: 18px;
        }
        .payroll-settings-section {
            padding: 18px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
        }
        .payroll-settings-section h2 {
            margin: 0 0 6px;
            color: var(--c-text);
            font-size: 15px;
        }
        .payroll-settings-section > p {
            margin: 0 0 14px;
            color: var(--c-text-muted);
            font-size: 12px;
            line-height: 1.5;
        }
        .payroll-settings-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
        }
        .payroll-settings-table {
            width: 100%;
            min-width: 620px;
            margin: 0;
            border-collapse: collapse;
        }
        .payroll-settings-table th,
        .payroll-settings-table td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--c-border);
            text-align: left;
            vertical-align: middle;
        }
        .payroll-settings-table th {
            background: var(--c-bg);
            color: var(--c-text-muted);
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .payroll-settings-table tr:last-child td { border-bottom: 0; }
        .payroll-settings-table td:first-child {
            width: 25%;
            color: var(--c-text);
            font-weight: 650;
        }
        .payroll-settings-table td:nth-child(2) { width: 22%; }
        .payroll-settings-table td:last-child {
            color: var(--c-text-muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .payroll-settings-table input {
            width: 100%;
            min-width: 115px;
        }
        .payroll-settings-actions { margin-top: 16px; }
        .payroll-activity-list {
            display: grid;
            gap: 8px;
        }
        .payroll-activity-item {
            padding: 10px 12px;
            border: 1px solid var(--c-border);
            border-radius: 8px;
            background: var(--c-surface);
        }
        .payroll-activity-pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
        }
        .payroll-activity-page-links {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .payroll-activity-page-link {
            display: inline-flex;
            min-width: 34px;
            min-height: 34px;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: 1px solid var(--c-border-strong);
            border-radius: 7px;
            background: var(--c-surface);
            color: var(--c-text);
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
        }
        .payroll-activity-page-link:hover,
        .payroll-activity-page-link[aria-current="page"] {
            border-color: var(--c-primary);
            background: var(--c-primary);
            color: #fff;
        }
        .payroll-activity-page-link[aria-disabled="true"] {
            opacity: .45;
            pointer-events: none;
        }
        .payroll-activity-summary {
            color: var(--c-text-muted);
            font-size: 12px;
        }
        .employee-payroll-card {
            padding: 18px;
        }
        .employee-deductions-section {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--c-border);
        }
        .employee-deductions-section .card-title {
            margin-bottom: 14px;
            color: var(--c-text);
            font-weight: 700;
            text-transform: uppercase;
        }
        .employee-deductions-section .employee-deduction-builder {
            max-width: none;
        }
        .employee-deductions-section .employee-deduction-builder > summary {
            color: var(--c-primary-text);
            font-weight: 700;
            text-transform: uppercase;
        }
        .employee-deductions-section .employee-deduction-table {
            margin-top: 12px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
        }
        .employee-deductions-section .employee-deduction-table th {
            background: var(--c-surface-2);
            color: var(--c-text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .employee-deductions-section .employee-deduction-table td {
            border-color: var(--c-border);
        }
        .employee-deductions-section .employee-deduction-form {
            display: grid;
            grid-template-columns: minmax(180px, 1.5fr) minmax(160px, 1fr) minmax(120px, .7fr) auto;
            gap: 12px;
            align-items: end;
        }
        .employee-deductions-section .employee-deduction-form .form-row {
            min-width: 0;
            margin: 0;
        }
        .employee-deductions-section .employee-deduction-form label {
            display: block;
            margin-bottom: 7px;
            color: var(--c-text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .employee-deductions-section .employee-deduction-form input,
        .employee-deductions-section .employee-deduction-form select {
            width: 100%;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid var(--c-border-strong);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
            outline: none;
        }
        .employee-deductions-section .employee-deduction-form input:focus,
        .employee-deductions-section .employee-deduction-form select:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .employee-deductions-section .employee-add-deduction {
            margin-top: 14px;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
        }
        .employee-deductions-section .employee-add-deduction-title {
            margin: 0 0 12px;
            color: var(--c-text);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .employee-deductions-section .employee-deduction-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 8px;
        }
        @media (max-width: 720px) {
            .employee-deductions-section .employee-deduction-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 420px) {
            .employee-deductions-section .employee-deduction-form {
                grid-template-columns: minmax(0, 1fr);
            }
        }
        .employee-payroll-form {
            display: grid;
            gap: 18px;
        }
        .employee-payroll-rate-controls {
            display: grid;
            grid-template-columns: minmax(200px, 1fr) repeat(2, minmax(220px, 1fr));
            gap: 16px;
            align-items: end;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
        }
        .employee-payroll-form .employee-payroll-rate-controls .form-row label,
        .employee-payroll-form .employee-incentive-section .form-row label {
            display: block;
            margin-bottom: 7px;
            color: var(--c-text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .employee-payroll-form .employee-payroll-rate-controls select,
        .employee-payroll-form .employee-payroll-rate-controls input[type="number"],
        .employee-payroll-form .employee-incentive-section input[type="number"] {
            width: 100%;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid var(--c-border-strong);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .employee-payroll-form .employee-payroll-rate-controls select:focus,
        .employee-payroll-form .employee-payroll-rate-controls input[type="number"]:focus,
        .employee-payroll-form .employee-incentive-section input[type="number"]:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .employee-schedule-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
        }
        .employee-schedule-section {
            min-width: 0;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
        }
        .employee-schedule-section h3 {
            margin: 0 0 12px;
            color: var(--c-text);
            font-size: 14px;
            font-weight: 700;
        }
        .employee-payroll-identity {
            min-width: 0;
            color: var(--c-text);
            font-size: 15px;
            font-weight: 700;
            overflow-wrap: anywhere;
            align-self: center;
        }
        .employee-incentive-section {
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
        }
        .employee-incentive-section h3 {
            margin: 0 0 10px;
            color: var(--c-text);
            font-size: 14px;
            font-weight: 700;
        }
        .employee-incentive-section .form-row {
            max-width: 320px;
            margin: 0;
        }
        .employee-day-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .employee-day-option {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 6px 10px;
            border: 1px solid var(--c-border-strong);
            border-radius: 999px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }
        .employee-day-option input {
            appearance: none;
            width: 17px;
            height: 17px;
            min-width: 17px;
            margin: 0;
            border: 1px solid var(--c-border-strong);
            border-radius: 50%;
            background: var(--c-surface-2);
            cursor: pointer;
        }
        .employee-day-option input:checked {
            border-color: var(--c-primary);
            background: radial-gradient(circle, var(--c-surface) 0 24%, var(--c-primary) 34%);
            box-shadow: 0 0 0 2px var(--c-form-focus-ring);
        }
        .employee-day-option input:focus-visible {
            outline: 2px solid var(--c-primary);
            outline-offset: 3px;
        }
        html[data-theme="dark"] .employee-payroll-form select,
        html[data-theme="dark"] .employee-deduction-form select {
            color-scheme: dark;
        }
        .employee-schedule-save {
            display: flex;
            justify-content: flex-start;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--c-border);
        }
        html[data-theme="dark"] .payroll-settings-section h2,
        html[data-theme="dark"] .payroll-settings-table td:first-child { color: #fff; }
        @media (max-width: 640px) {
            .payroll-settings-section { padding: 12px; }
            .payroll-settings-table th,
            .payroll-settings-table td { padding: 9px 10px; }
            .employee-payroll-rate-controls {
                grid-template-columns: minmax(0, 1fr);
                padding: 12px;
            }
        }
        @media (max-width: 420px) {
        }
    </style>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <h1 style="margin:0;font-size:20px;font-weight:700;color:var(--c-text);">Payroll Management Settings</h1>
        </div>
        @if ($selectedEmployeeId)
            <a class="btn" href="{{ route('admin.payroll.index') }}">View All Employees</a>
        @endif
    </div>

    @unless ($selectedEmployeeId)
    <form method="POST" action="{{ route('admin.payroll.settings.update', $selectedEmployeeId ? ['employee' => $selectedEmployeeId] : []) }}" class="card payroll-settings-form" style="margin-bottom:20px;">
        @csrf
        @method('PUT')
        <div class="card-title" style="margin-bottom:14px;">Global Attendance Controls</div>
        <section class="payroll-settings-section" aria-labelledby="working-hours-settings">
            <h2 id="working-hours-settings">Attendance Settings</h2>
            <p>Set the standard schedule, grace period, and late deduction rule used to evaluate employee attendance.</p>
            <div class="payroll-settings-table-wrap">
                <table class="table payroll-settings-table">
                    <thead>
                        <tr><th scope="col">Setting</th><th scope="col">Value</th><th scope="col">Explanation</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><label for="grace_minutes">Grace Minutes</label></td>
                            <td><input id="grace_minutes" name="grace_minutes" type="number" min="0" max="180" value="{{ old('grace_minutes', $settings->grace_minutes ?? 15) }}"></td>
                            <td>Late penalties begin only after this grace period has expired.</td>
                        </tr>
                        <tr>
                            <td><label for="late_penalty_increment_minutes">Late Penalty Threshold / Increment (Minutes)</label></td>
                            <td><input id="late_penalty_increment_minutes" name="late_penalty_increment_minutes" type="number" min="1" max="1440" value="{{ old('late_penalty_increment_minutes', $settings->late_penalty_increment_minutes ?? 5) }}" required></td>
                            <td>Each started increment after grace adds one configured deduction.</td>
                        </tr>
                        <tr>
                            <td><label for="late_penalty_amount">Deduction Amount (PHP)</label></td>
                            <td><input id="late_penalty_amount" name="late_penalty_amount" type="number" step="0.01" min="0" max="1000000" value="{{ old('late_penalty_amount', $settings->late_penalty_amount ?? 0) }}" required></td>
                            <td>Deduction per late increment. Set to 0.00 to disable late deductions.</td>
                        </tr>
                        <tr>
                            <td><label for="shift_start">Shift Start</label></td>
                            <td><input id="shift_start" name="shift_start" type="time" value="{{ old('shift_start', substr((string) ($settings->shift_start ?? '08:00'), 0, 5)) }}"></td>
                            <td>Official starting time of work; the standard barangay schedule is 08:00 AM–05:00 PM.</td>
                        </tr>
                        <tr>
                            <td><label for="shift_end">Shift End</label></td>
                            <td><input id="shift_end" name="shift_end" type="time" value="{{ old('shift_end', substr((string) ($settings->shift_end ?? '17:00'), 0, 5)) }}"></td>
                            <td>Official ending time of work; the standard barangay schedule is 08:00 AM–05:00 PM.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="payroll-settings-actions">
            <button class="btn btn-primary" type="submit">Save Global Settings</button>
        </div>
    </form>
    @endunless

    @if ($selectedEmployeeId)
        <div class="card employee-payroll-card" style="margin-bottom:20px;">
            <div class="card-title" style="margin-bottom:14px;">Employee Payroll Settings</div>
            @forelse ($employees as $emp)
                @php
                    $employeeDefaultRestDays = $emp->employee_rest_days ?? $defaultRestDays;
                    $employeeDefaultWorkingDays = $emp->working_days ?? array_values(array_diff(range(1, 7), $employeeDefaultRestDays));
                @endphp
                <form id="employee-payroll-{{ $emp->id }}" method="POST" action="{{ route('admin.payroll.settings.employee.update', $emp) }}" class="employee-payroll-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="working_days_present" value="1">
                    <input type="hidden" name="employee_rest_days_present" value="1">
                    <div class="employee-payroll-rate-controls">
                        <div class="employee-payroll-identity">{{ $emp->name }}</div>
                        <div class="form-row" style="margin:0;min-width:220px;">
                            <label for="pay-rate-type-{{ $emp->id }}">PAY RATE TYPE</label>
                            <select id="pay-rate-type-{{ $emp->id }}" name="pay_rate_type" required>
                                <option value="{{ \App\Models\User::PAY_RATE_DAILY }}" @selected(old('pay_rate_type', $emp->pay_rate_type ?? \App\Models\User::PAY_RATE_DAILY) === \App\Models\User::PAY_RATE_DAILY)>Daily</option>
                                <option value="{{ \App\Models\User::PAY_RATE_HOURLY }}" @selected(old('pay_rate_type', $emp->pay_rate_type) === \App\Models\User::PAY_RATE_HOURLY)>Hourly</option>
                            </select>
                        </div>
                        <div class="form-row" style="margin:0;min-width:220px;">
                            <label>PAY RATE AMOUNT</label>
                            <input type="number" name="pay_rate" value="{{ old('pay_rate', $emp->pay_rate) }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <section class="employee-incentive-section" aria-labelledby="hours-per-day-heading-{{ $emp->id }}">
                        <h3 id="hours-per-day-heading-{{ $emp->id }}">Hours Per Day</h3>
                        <div class="form-row">
                            <label
                                id="hours-per-day-label-{{ $emp->id }}"
                                for="hours-per-day-{{ $emp->id }}"
                                data-hours-per-day-label
                                data-daily-label="HOURS PER DAY (EQUIVALENT DAY BASIS)"
                                data-hourly-label="DAILY WORK HOURS LIMIT (MAX HOURS/DAY)"
                            >{{ old('pay_rate_type', $emp->pay_rate_type ?? \App\Models\User::PAY_RATE_DAILY) === \App\Models\User::PAY_RATE_HOURLY ? 'DAILY WORK HOURS LIMIT (MAX HOURS/DAY)' : 'HOURS PER DAY (EQUIVALENT DAY BASIS)' }}</label>
                            <input
                                id="hours-per-day-{{ $emp->id }}"
                                type="number"
                                name="hours_per_day"
                                value="{{ old('hours_per_day', $emp->hours_per_day ?? $settings->hours_per_full_day) }}"
                                step="0.01"
                                min="0.01"
                                max="24"
                                required
                            >
                        </div>
                    </section>
                    <section class="employee-incentive-section" aria-labelledby="monthly-incentives-{{ $emp->id }}">
                        <h3 id="monthly-incentives-{{ $emp->id }}">Monthly Incentives</h3>
                        <div class="form-row">
                            <label for="monthly-incentive-{{ $emp->id }}">MONTHLY INCENTIVE AMOUNT</label>
                            <input
                                id="monthly-incentive-{{ $emp->id }}"
                                type="number"
                                name="monthly_incentive"
                                value="{{ old('monthly_incentive', $emp->monthly_incentive ?? 0) }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            >
                        </div>
                    </section>
                    <div class="employee-schedule-grid">
                        <section class="employee-schedule-section" aria-labelledby="working-days-{{ $emp->id }}">
                            <h3 id="working-days-{{ $emp->id }}">Working Days</h3>
                            <div class="employee-day-options">
                                @foreach ($weekdays as $weekday => $label)
                                    <label class="employee-day-option">
                                        <input type="checkbox" name="working_days[]" value="{{ $weekday }}" @checked(in_array($weekday, $employeeDefaultWorkingDays, true))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                        <section class="employee-schedule-section" aria-labelledby="employee-rest-days-{{ $emp->id }}">
                            <h3 id="employee-rest-days-{{ $emp->id }}">Rest Days</h3>
                            <div class="employee-day-options">
                                @foreach ($weekdays as $weekday => $label)
                                    <label class="employee-day-option">
                                        <input type="checkbox" name="employee_rest_days[]" value="{{ $weekday }}" @checked(in_array($weekday, $employeeDefaultRestDays, true))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </form>
            @empty
                <div class="table-empty">No employees yet. Create employee accounts first.</div>
            @endforelse

        <section class="employee-deductions-section" aria-labelledby="employee-deductions-heading">
            <div id="employee-deductions-heading" class="card-title">Employee Deductions</div>
            <datalist id="deduction-name-presets">
                @foreach ($deductionPresets as $label)
                    <option value="{{ $label }}">
                @endforeach
            </datalist>
            <div class="table-wrapper" style="border:1px solid var(--c-border);border-radius:10px;overflow:hidden;">
                <table class="table" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">EMPLOYEE</th>
                            <th>DEDUCTIONS RULE BUILDER</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($employees as $emp)
                        <tr id="employee-deductions-{{ $emp->id }}">
                            <td style="vertical-align:top;padding-top:14px;">
                                <div style="font-weight:600;font-size:14px;">{{ $emp->name }}</div>
                            </td>
                            <td>
                                <details class="employee-deduction-builder">
                                    <summary style="cursor:pointer;font-weight:600;color:var(--c-primary);font-size:13.5px;padding:4px 0;user-select:none;">
                                        {{ $emp->payrollDeductions->count() }} configured deduction{{ $emp->payrollDeductions->count() === 1 ? '' : 's' }}
                                    </summary>
                                    <div style="overflow:hidden;">
                                        <table class="table employee-deduction-table" style="margin:0;font-size:13px;">
                                            <tbody>
                                            @foreach ($emp->payrollDeductions as $d)
                                                <tr>
                                                    <td style="padding:12px;background:var(--c-bg);">
                                                        <form method="POST" action="{{ route('admin.payroll.deductions.update', ['payrollDeduction' => $d, 'employee' => $emp->id]) }}" class="employee-deduction-form">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="form-row"><label>NAME</label><input type="text" name="name" value="{{ old('name', $d->name) }}" list="deduction-name-presets" required maxlength="255"></div>
                                                            <div class="form-row"><label>TYPE</label><select name="calculation_type" required><option value="{{ \App\Models\EmployeePayrollDeduction::CALCULATION_FIXED }}" @selected(old('calculation_type', $d->calculation_type) === \App\Models\EmployeePayrollDeduction::CALCULATION_FIXED)>Fixed amount</option><option value="{{ \App\Models\EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS }}" @selected(old('calculation_type', $d->calculation_type) === \App\Models\EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS)>% of gross pay</option></select></div>
                                                            <div class="form-row"><label>VALUE</label><input type="number" name="value" value="{{ old('value', $d->value) }}" step="0.01" min="0" required></div>
                                                            <button class="btn btn-primary" type="submit" style="width:auto;height:38px;">Save</button>
                                                        </form>
                                                        <div class="employee-deduction-actions">
                                                            <form method="POST" action="{{ route('admin.payroll.deductions.destroy', ['payrollDeduction' => $d, 'employee' => $emp->id]) }}" onsubmit="return confirm('Remove this deduction?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn" type="submit" style="width:auto;font-size:12px;padding:4px 12px;color:var(--c-danger);border-color:var(--c-danger);">Remove</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <form method="POST" action="{{ route('admin.payroll.deductions.store', ['employee' => $emp->id]) }}" class="employee-add-deduction">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $emp->id }}">
                                        <p class="employee-add-deduction-title">ADD DEDUCTION RULE</p>
                                        <div class="employee-deduction-form">
                                            <div class="form-row"><label>NAME</label><input type="text" name="name" value="{{ old('user_id') == $emp->id ? old('name') : '' }}" list="deduction-name-presets" required maxlength="255" placeholder="e.g. PhilHealth"></div>
                                            <div class="form-row"><label>TYPE</label><select name="calculation_type" required><option value="{{ \App\Models\EmployeePayrollDeduction::CALCULATION_FIXED }}">Fixed amount</option><option value="{{ \App\Models\EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS }}">% of gross pay</option></select></div>
                                            <div class="form-row"><label>VALUE</label><input type="number" name="value" value="{{ old('user_id') == $emp->id ? old('value') : '' }}" step="0.01" min="0" required placeholder="0.00"></div>
                                            <button class="btn btn-primary" type="submit" style="width:auto;height:38px;">Add</button>
                                        </div>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="table-empty">No employees yet. Create employee accounts first.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if ($employees->isNotEmpty())
            <div class="employee-schedule-save">
                <button class="btn btn-primary" type="submit" form="employee-payroll-{{ $selectedEmployeeId }}">Save Employee Settings</button>
            </div>
        @endif
        </div>
    @endif

    @unless ($selectedEmployeeId)
        <div class="card">
            <div class="card-title" style="margin-bottom:12px;">Payroll Settings Activity History</div>
            <div class="payroll-activity-list">
                @forelse ($activityItems as $item)
                    <div class="payroll-activity-item">
                        <div style="font-size:13px;font-weight:600;">{{ $item['title'] }}</div>
                        <div style="font-size:12px;color:var(--c-text-muted);margin-top:2px;">{{ $item['subtitle'] }}</div>
                        <div style="font-size:11px;color:var(--c-text-muted);margin-top:4px;">{{ \Illuminate\Support\Carbon::parse($item['at'])->format('M d, Y h:i A') }}</div>
                    </div>
                @empty
                    <div class="table-empty">No activity history yet.</div>
                @endforelse
            </div>
            @if ($activityItems->hasPages())
                @php
                    $firstPage = max(1, $activityItems->currentPage() - 2);
                    $lastVisiblePage = min($activityItems->lastPage(), $activityItems->currentPage() + 2);
                @endphp
                <nav class="payroll-activity-pagination" aria-label="Payroll settings activity pagination">
                    <span class="payroll-activity-summary">
                        Showing {{ $activityItems->firstItem() }}–{{ $activityItems->lastItem() }} of {{ $activityItems->total() }} activities
                    </span>
                    <div class="payroll-activity-page-links">
                        <a
                            class="payroll-activity-page-link"
                            href="{{ $activityItems->previousPageUrl() ?? '#' }}"
                            @if ($activityItems->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                            rel="prev"
                        >Previous</a>
                        @for ($page = $firstPage; $page <= $lastVisiblePage; $page++)
                            @if ($page === $activityItems->currentPage())
                                <span class="payroll-activity-page-link" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="payroll-activity-page-link" href="{{ $activityItems->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor
                        <a
                            class="payroll-activity-page-link"
                            href="{{ $activityItems->nextPageUrl() ?? '#' }}"
                            @if (! $activityItems->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                            rel="next"
                        >Next</a>
                    </div>
                </nav>
            @endif
        </div>
    @endunless

    @if ($selectedEmployeeId)
        <script>
            document.querySelectorAll('.employee-payroll-form [name="pay_rate_type"]').forEach(function (rateType) {
                var form = rateType.closest('.employee-payroll-form');
                var hoursLabel = form ? form.querySelector('[data-hours-per-day-label]') : null;
                if (!hoursLabel) return;

                var updateHoursLabel = function () {
                    hoursLabel.textContent = rateType.value === '{{ \App\Models\User::PAY_RATE_HOURLY }}'
                        ? hoursLabel.dataset.hourlyLabel
                        : hoursLabel.dataset.dailyLabel;
                };

                rateType.addEventListener('change', updateHoursLabel);
                updateHoursLabel();
            });
        </script>
    @endif

@endsection
