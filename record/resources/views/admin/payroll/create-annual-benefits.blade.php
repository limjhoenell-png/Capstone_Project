@extends('layouts.admin', ['title' => 'Create Annual Benefits'])

@section('content')
    <style>
        .annual-benefit-page {
            display: grid;
            gap: 16px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .annual-benefit-form {
            display: grid;
            gap: 16px;
            padding: 20px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
        }
        .annual-benefit-header,
        .annual-benefit-employees-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .annual-benefit-header h1 {
            margin: 0;
            color: var(--c-text);
            font-size: 20px;
        }
        .annual-benefit-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .annual-benefit-fields .form-group { margin: 0; }
        .annual-benefit-item {
            display: grid;
            gap: 14px;
            padding: 14px;
            border: 1px solid var(--c-border);
            border-radius: 10px;
            background: var(--c-surface-2);
        }
        .annual-benefit-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--c-text);
            font-weight: 700;
        }
        .annual-benefit-item-remove {
            min-width: 34px;
            min-height: 34px;
            padding: 4px 10px;
            color: var(--c-error-text);
        }
        .annual-benefit-add {
            justify-self: start;
        }
        .annual-benefit-fields label,
        .annual-benefit-employees > label {
            display: block;
            margin-bottom: 7px;
            color: var(--c-text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .annual-benefit-form input[type="text"],
        .annual-benefit-form input[type="number"],
        .annual-benefit-form input[type="date"],
        .annual-benefit-form select {
            width: 100%;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface-2);
            color: var(--c-text);
            font: inherit;
        }
        .annual-benefit-month-panel,
        .annual-benefit-employees {
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
        .annual-benefit-formula {
            margin: 6px 0 0;
            color: var(--c-text-secondary);
            font-size: 13px;
            line-height: 1.5;
        }
        .annual-benefit-months,
        .annual-benefit-employee-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .annual-benefit-months label,
        .annual-benefit-employee {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin: 0;
            padding: 9px;
            border: 1px solid var(--c-border);
            border-radius: 7px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }
        .annual-benefit-months input,
        .annual-benefit-employee input {
            flex: 0 0 auto;
            margin: 0;
            accent-color: var(--c-primary);
        }
        .annual-benefit-preview {
            max-height: 240px;
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
            padding: 7px;
            border-bottom: 1px solid var(--c-border);
            text-align: left;
        }
        .annual-benefit-preview th:last-child,
        .annual-benefit-preview td:nth-child(3) {
            text-align: right;
        }
        .annual-benefit-error {
            margin: 4px 0 0;
            color: var(--c-error-text);
            font-size: 12px;
        }
        .annual-benefit-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid var(--c-border);
        }
        @media (max-width: 700px) {
            .annual-benefit-fields,
            .annual-benefit-months,
            .annual-benefit-employee-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 440px) {
            .annual-benefit-fields,
            .annual-benefit-months,
            .annual-benefit-employee-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="annual-benefit-page">
        <form method="POST" action="{{ route('admin.payroll.annual-benefits.store') }}" class="annual-benefit-form">
            @csrf
            <input type="hidden" name="period_start" value="{{ $period_start }}">
            <input type="hidden" name="period_end" value="{{ $period_end }}">

            <div class="annual-benefit-header">
                <h1>Create Annual Benefits</h1>
            </div>

            @php
                $benefitMonthLabels = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                ];
                $benefitItems = old('benefit_items', [[
                    'benefit_type' => old('benefit_type', 'Other'),
                    'name' => old('name'),
                    'amount' => old('amount'),
                    'payment_date' => old('payment_date', $period_end),
                    'calculation_months' => old('calculation_months', range(1, 12)),
                ]]);
            @endphp
            <div id="annual-benefit-items">
                @foreach ($benefitItems as $benefitIndex => $benefitItem)
                    @php
                        $benefitItem = is_array($benefitItem) ? $benefitItem : [];
                        $itemMonths = array_map('intval', (array) ($benefitItem['calculation_months'] ?? range(1, 12)));
                    @endphp
                    <section class="annual-benefit-item" data-benefit-index="{{ $benefitIndex }}">
                        <div class="annual-benefit-item-header">
                            <span class="annual-benefit-item-title">Benefit {{ $benefitIndex + 1 }}</span>
                            <button class="btn annual-benefit-item-remove" type="button" aria-label="Remove benefit {{ $benefitIndex + 1 }}" @if ($loop->first) hidden @endif>&times;</button>
                        </div>
                        <div class="annual-benefit-fields">
                            <div class="form-group">
                                <label for="annual-benefit-type-{{ $benefitIndex }}">Benefit Type</label>
                                <select id="annual-benefit-type-{{ $benefitIndex }}" data-field="benefit_type" name="benefit_items[{{ $benefitIndex }}][benefit_type]" required>
                                    <option value="Other" @selected(($benefitItem['benefit_type'] ?? 'Other') === 'Other')>Other</option>
                                    <option value="13Month" @selected(($benefitItem['benefit_type'] ?? '') === '13Month')>13Month</option>
                                </select>
                                @error("benefit_items.$benefitIndex.benefit_type")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group annual-benefit-custom-fields">
                                <label for="annual-benefit-name-{{ $benefitIndex }}">Benefit Name</label>
                                <input id="annual-benefit-name-{{ $benefitIndex }}" data-field="name" type="text" name="benefit_items[{{ $benefitIndex }}][name]" maxlength="120" value="{{ $benefitItem['name'] ?? '' }}">
                                @error("benefit_items.$benefitIndex.name")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group annual-benefit-fixed-amount">
                                <label for="annual-benefit-amount-{{ $benefitIndex }}">Amount (PHP)</label>
                                <input id="annual-benefit-amount-{{ $benefitIndex }}" data-field="amount" type="number" name="benefit_items[{{ $benefitIndex }}][amount]" min="0.01" max="100000000" step="0.01" value="{{ $benefitItem['amount'] ?? '' }}">
                                @error("benefit_items.$benefitIndex.amount")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="annual-benefit-date-{{ $benefitIndex }}">Payment Date</label>
                                <input id="annual-benefit-date-{{ $benefitIndex }}" data-field="payment_date" type="date" name="benefit_items[{{ $benefitIndex }}][payment_date]" value="{{ $benefitItem['payment_date'] ?? $period_end }}" required>
                                @error("benefit_items.$benefitIndex.payment_date")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <section class="annual-benefit-month-panel" hidden aria-label="13th month calculation options">
                            <div>
                                <strong>13th Month Formula</strong>
                                <p class="annual-benefit-formula">Sum each selected month’s basic pay from saved payslips, then divide by the number of selected months worked (maximum 12). The calculation year follows the payment date.</p>
                            </div>
                            <div class="annual-benefit-months" role="group" aria-label="Select months to include">
                                @foreach ($benefitMonthLabels as $monthNumber => $monthLabel)
                                    <label>
                                        <input type="checkbox" data-field="calculation_months" name="benefit_items[{{ $benefitIndex }}][calculation_months][]" value="{{ $monthNumber }}" @checked(in_array($monthNumber, $itemMonths, true))>
                                        <span>{{ $monthLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error("benefit_items.$benefitIndex.calculation_months")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            @error("benefit_items.$benefitIndex.calculation_months.*")<p class="annual-benefit-error">{{ $message }}</p>@enderror
                            <div class="annual-benefit-preview" data-field="preview" aria-live="polite">Select a payment date, at least one month, and at least one employee to preview calculated amounts.</div>
                        </section>
                    </section>
                @endforeach
            </div>
            @error('benefit_items')<p class="annual-benefit-error">{{ $message }}</p>@enderror
            <button class="btn annual-benefit-add" type="button" id="annual-benefit-add">+ Add Another Benefit</button>

            <section class="annual-benefit-employees">
                <div class="annual-benefit-employees-head">
                    <label id="annual-benefit-employees-label">Employees</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn" type="button" id="annual-benefit-select-all">Select All</button>
                        <button class="btn" type="button" id="annual-benefit-deselect-all">Deselect All</button>
                    </div>
                </div>
                @error('employee_ids')<p class="annual-benefit-error">{{ $message }}</p>@enderror
                @error('employee_ids.*')<p class="annual-benefit-error">{{ $message }}</p>@enderror
                <div class="annual-benefit-employee-list" role="group" aria-labelledby="annual-benefit-employees-label">
                    @forelse ($employees as $employee)
                        <label class="annual-benefit-employee">
                            <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" @checked(in_array($employee->id, (array) old('employee_ids', [])))>
                            <span>{{ $employee->name }}</span>
                        </label>
                    @empty
                        <p class="annual-benefit-error">No registered employees are available.</p>
                    @endforelse
                </div>
            </section>

            <div class="annual-benefit-footer">
                <a class="btn" href="{{ route('admin.payroll.index', ['period_start' => $period_start, 'period_end' => $period_end]) }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Annual Benefits</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var form = document.querySelector('.annual-benefit-form');
            var itemContainer = document.getElementById('annual-benefit-items');
            var addBenefitButton = document.getElementById('annual-benefit-add');
            var selectAll = document.getElementById('annual-benefit-select-all');
            var deselectAll = document.getElementById('annual-benefit-deselect-all');

            function employeeCheckboxes() {
                return Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));
            }

            function formatBenefitAmount(amount) {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
            }

            function renderBenefitPreview(preview, payload) {
                preview.replaceChildren();
                var total = document.createElement('p');
                total.style.margin = '0 0 8px';
                total.style.fontWeight = '700';
                total.textContent = 'Total amount: ' + formatBenefitAmount(payload.total_amount);
                preview.appendChild(total);

                if (!payload.employees.length) {
                    var empty = document.createElement('p');
                    empty.style.margin = '0';
                    empty.textContent = 'No employees selected.';
                    preview.appendChild(empty);
                    return;
                }

                var table = document.createElement('table');
                var head = document.createElement('thead');
                head.innerHTML = '<tr><th>Employee</th><th>Months worked</th><th>Amount</th></tr>';
                table.appendChild(head);
                var body = document.createElement('tbody');
                payload.employees.forEach(function (employee) {
                    var row = document.createElement('tr');
                    var name = document.createElement('td');
                    name.textContent = employee.name;
                    var worked = document.createElement('td');
                    worked.textContent = employee.divisor + ' / 12';
                    var amount = document.createElement('td');
                    amount.textContent = formatBenefitAmount(employee.amount);
                    row.append(name, worked, amount);
                    body.appendChild(row);

                    var detailRow = document.createElement('tr');
                    var details = document.createElement('td');
                    details.colSpan = 3;
                    details.style.color = 'var(--c-text-muted)';
                    details.textContent = employee.monthly_pay.map(function (month) {
                        return month.label + ': ' + formatBenefitAmount(month.basic_pay);
                    }).join(' · ');
                    detailRow.appendChild(details);
                    body.appendChild(detailRow);
                });
                table.appendChild(body);
                preview.appendChild(table);
            }

            function refreshBenefitPreview(row) {
                if (row.querySelector('[data-field="benefit_type"]').value !== '13Month') return;
                var preview = row.querySelector('[data-field="preview"]');
                var selectedEmployees = employeeCheckboxes().filter(function (checkbox) { return checkbox.checked; });
                var selectedMonths = Array.prototype.slice.call(row.querySelectorAll('[data-field="calculation_months"]'))
                    .filter(function (checkbox) { return checkbox.checked; });
                var paymentDate = row.querySelector('[data-field="payment_date"]');
                if (!paymentDate.value || selectedEmployees.length === 0 || selectedMonths.length === 0) {
                    preview.textContent = 'Select a payment date, at least one month, and at least one employee to preview calculated amounts.';
                    return;
                }

                if (row.previewAbortController) row.previewAbortController.abort();
                row.previewAbortController = new AbortController();
                var requestId = (row.previewRequestId || 0) + 1;
                row.previewRequestId = requestId;
                preview.textContent = 'Calculating from saved payroll records…';

                var url = new URL(@json(route('admin.payroll.annual-benefits.preview')), window.location.origin);
                url.searchParams.set('payment_date', paymentDate.value);
                selectedEmployees.forEach(function (checkbox) {
                    url.searchParams.append('employee_ids[]', checkbox.value);
                });
                selectedMonths.forEach(function (checkbox) {
                    url.searchParams.append('calculation_months[]', checkbox.value);
                });

                fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: row.previewAbortController.signal
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) throw new Error(payload.message || 'Unable to calculate the 13th month preview.');
                        return payload;
                    });
                }).then(function (payload) {
                    if (requestId === row.previewRequestId) renderBenefitPreview(preview, payload);
                }).catch(function (error) {
                    if (error.name !== 'AbortError' && requestId === row.previewRequestId) {
                        preview.textContent = error.message || 'Unable to calculate the 13th month preview.';
                    }
                });
            }

            function updateBenefitTypeFields(row) {
                var isThirteenthMonth = row.querySelector('[data-field="benefit_type"]').value === '13Month';
                var name = row.querySelector('[data-field="name"]');
                var amount = row.querySelector('[data-field="amount"]');
                row.querySelector('.annual-benefit-custom-fields').hidden = isThirteenthMonth;
                name.disabled = isThirteenthMonth;
                name.required = !isThirteenthMonth;
                row.querySelector('.annual-benefit-fixed-amount').hidden = isThirteenthMonth;
                amount.disabled = isThirteenthMonth;
                amount.required = !isThirteenthMonth;
                row.querySelector('.annual-benefit-month-panel').hidden = !isThirteenthMonth;
                Array.prototype.slice.call(row.querySelectorAll('[data-field="calculation_months"]'))
                    .forEach(function (checkbox) { checkbox.disabled = !isThirteenthMonth; });
                refreshBenefitPreview(row);
            }

            function bindBenefitRow(row) {
                row.querySelector('[data-field="benefit_type"]').addEventListener('change', function () {
                    updateBenefitTypeFields(row);
                });
                row.querySelector('[data-field="payment_date"]').addEventListener('change', function () {
                    refreshBenefitPreview(row);
                });
                Array.prototype.slice.call(row.querySelectorAll('[data-field="calculation_months"]'))
                    .forEach(function (checkbox) {
                        checkbox.addEventListener('change', function () { refreshBenefitPreview(row); });
                    });
                row.querySelector('.annual-benefit-item-remove').addEventListener('click', function () {
                    row.remove();
                    renumberBenefitRows();
                });
                updateBenefitTypeFields(row);
            }

            function renumberBenefitRows() {
                Array.prototype.slice.call(itemContainer.querySelectorAll('.annual-benefit-item')).forEach(function (row, index) {
                    var oldIndex = row.dataset.benefitIndex;
                    row.dataset.benefitIndex = index;
                    row.querySelector('.annual-benefit-item-title').textContent = 'Benefit ' + (index + 1);
                    var removeButton = row.querySelector('.annual-benefit-item-remove');
                    removeButton.hidden = index === 0;
                    removeButton.setAttribute('aria-label', 'Remove benefit ' + (index + 1));
                    Array.prototype.slice.call(row.querySelectorAll('[data-field]')).forEach(function (field) {
                        if (field.name) field.name = field.name.replace('[' + oldIndex + ']', '[' + index + ']');
                        if (field.id) field.id = field.id.replace(new RegExp('-' + oldIndex + '$'), '-' + index);
                    });
                    Array.prototype.slice.call(row.querySelectorAll('label[for]')).forEach(function (label) {
                        label.htmlFor = label.htmlFor.replace(new RegExp('-' + oldIndex + '$'), '-' + index);
                    });
                });
            }

            addBenefitButton.addEventListener('click', function () {
                var rows = itemContainer.querySelectorAll('.annual-benefit-item');
                var source = rows[0];
                var sourceIndex = source.dataset.benefitIndex;
                var row = source.cloneNode(true);
                row.dataset.benefitIndex = rows.length;
                row.querySelector('.annual-benefit-item-title').textContent = 'Benefit ' + (rows.length + 1);
                var removeButton = row.querySelector('.annual-benefit-item-remove');
                removeButton.hidden = false;
                removeButton.setAttribute('aria-label', 'Remove benefit ' + (rows.length + 1));
                row.querySelectorAll('.annual-benefit-error').forEach(function (error) { error.remove(); });
                Array.prototype.slice.call(row.querySelectorAll('[data-field]')).forEach(function (field) {
                    if (field.name) field.name = field.name.replace('[' + sourceIndex + ']', '[' + rows.length + ']');
                    if (field.id) field.id = field.id.replace(new RegExp('-' + sourceIndex + '$'), '-' + rows.length);
                    if (field.type === 'checkbox') field.checked = true;
                    else if (field.tagName === 'SELECT') field.value = 'Other';
                    else if (field.dataset.field === 'payment_date') field.value = source.querySelector('[data-field="payment_date"]').value;
                    else if (field.dataset.field !== 'preview') field.value = '';
                });
                Array.prototype.slice.call(row.querySelectorAll('label[for]')).forEach(function (label) {
                    label.htmlFor = label.htmlFor.replace(new RegExp('-' + sourceIndex + '$'), '-' + rows.length);
                });
                row.querySelector('[data-field="preview"]').textContent = 'Select a payment date, at least one month, and at least one employee to preview calculated amounts.';
                itemContainer.appendChild(row);
                bindBenefitRow(row);
            });

            Array.prototype.slice.call(itemContainer.querySelectorAll('.annual-benefit-item')).forEach(bindBenefitRow);
            selectAll.addEventListener('click', function () {
                employeeCheckboxes().forEach(function (checkbox) { checkbox.checked = true; });
                Array.prototype.slice.call(itemContainer.querySelectorAll('.annual-benefit-item')).forEach(refreshBenefitPreview);
            });
            deselectAll.addEventListener('click', function () {
                employeeCheckboxes().forEach(function (checkbox) { checkbox.checked = false; });
                Array.prototype.slice.call(itemContainer.querySelectorAll('.annual-benefit-item')).forEach(refreshBenefitPreview);
            });
            employeeCheckboxes().forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    Array.prototype.slice.call(itemContainer.querySelectorAll('.annual-benefit-item')).forEach(refreshBenefitPreview);
                });
            });
        }());
    </script>
@endsection
