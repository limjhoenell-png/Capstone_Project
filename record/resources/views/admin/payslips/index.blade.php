@extends('layouts.admin', ['title' => 'Payslips'])

@section('content')

    <style>
        .payslip-pagination-toolbar {
            display: flex;
            justify-content: flex-end;
            padding: 14px 16px;
            border-bottom: 1px solid var(--c-border);
            background: var(--c-surface-2);
            transition: background-color var(--transition), border-color var(--transition);
        }
        .payslip-pagination-toolbar label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 700;
        }
        .payslip-pagination-toolbar select {
            width: 92px;
            min-height: 36px;
            padding: 0 8px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface);
            color: var(--c-text);
            font: inherit;
            transition: background-color var(--transition), color var(--transition), border-color var(--transition);
        }
        .payslip-pagination-footer {
            display: flex;
            justify-content: center;
            padding: 12px 16px 16px;
        }
        .payslip-pagination-footer nav { margin: 0; }
        .payslip-pagination-footer ul.pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .payslip-pagination-footer .pagination li { display: flex; }
        .payslip-pagination-footer .pagination a,
        .payslip-pagination-footer .pagination li > span {
            display: inline-flex;
            min-width: 36px;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 6px 10px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            transition: background-color var(--transition), color var(--transition), border-color var(--transition);
        }
        .payslip-pagination-footer .pagination a:hover,
        .payslip-pagination-footer .pagination li.active > span {
            border-color: var(--c-primary);
            background: var(--c-primary);
            color: #fff;
        }
        .payslip-pagination-footer .pagination li.disabled > span {
            background: var(--c-surface-2);
            color: var(--c-text-muted);
        }
        .payslip-pagination-footer .pagination a:focus-visible {
            outline: 2px solid var(--c-primary);
            outline-offset: 2px;
        }
    </style>

    <div class="action-bar">
        <a class="btn" href="{{ route('admin.payroll.index') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
            </svg>
            Payroll Preview
        </a>
    </div>

    <section class="card" style="margin-bottom:20px;">
        <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <span>Past Payslip Requests</span>
            <span class="badge">{{ $payslipRequests->count() }} open</span>
        </div>
        @if ($payslipRequests->isEmpty())
            <p style="margin:0;color:var(--c-text-muted);font-size:13px;">There are no open past payslip requests.</p>
        @else
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr><th>Employee</th><th>Requested month</th><th>Submitted</th><th>Status</th><th>Review</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($payslipRequests as $payslipRequest)
                            <tr>
                                <td>{{ $payslipRequest->user?->name ?? 'Employee account removed' }}</td>
                                <td>{{ $payslipRequest->period_start->format('F Y') }}</td>
                                <td>{{ $payslipRequest->created_at->format('M j, Y') }}</td>
                                <td>{{ ucfirst($payslipRequest->status) }}</td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                        <a class="btn btn-sm" href="{{ route('admin.payroll.index', [
                                            'period_start' => $payslipRequest->period_start->toDateString(),
                                            'period_end' => $payslipRequest->period_end->toDateString(),
                                        ]) }}">Review payroll</a>
                                        @if ($payslipRequest->status === 'pending')
                                            <form method="POST" action="{{ route('admin.payslip-requests.status', $payslipRequest) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="reviewing">
                                                <button class="btn btn-sm" type="submit">Mark in review</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.payslip-requests.status', $payslipRequest) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="fulfilled">
                                            <button class="btn btn-sm btn-primary" type="submit">Mark fulfilled</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.payslip-requests.status', $payslipRequest) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="dismissed">
                                            <button class="btn btn-sm btn-danger" type="submit">Dismiss</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.payslips.index') }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        <div class="card" style="margin-bottom:20px;">
            <div class="card-title" style="margin-bottom:12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                Filter Payslips
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
                <div class="form-group" style="margin:0;min-width:160px;">
                    <label class="form-label">Period Start</label>
                    <input class="form-control" type="date" name="period_start" value="{{ $filter_period_start }}">
                </div>
                <div class="form-group" style="margin:0;min-width:160px;">
                    <label class="form-label">Period End</label>
                    <input class="form-control" type="date" name="period_end" value="{{ $filter_period_end }}">
                </div>
                <div class="form-group" style="margin:0;min-width:200px;">
                    <label class="form-label">Employee</label>
                    <select class="form-control" name="employee_id">
                        <option value="">All employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                    @selected((string) $filter_employee_id === (string) $emp->id)>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:8px;padding-bottom:1px;">
                    <button class="btn btn-primary" type="submit">Apply Filter</button>
                    <a class="btn btn-danger" href="{{ route('admin.payslips.index') }}">Clear</a>
                </div>
            </div>

            <p style="margin:10px 0 0;font-size:12.5px;color:var(--c-text-muted);">
                When both period dates are set, results match that payroll window exactly.
            </p>
        </div>
    </form>

    <div id="payslips-table-region">
        @include('admin.payslips.partials.table-region', [
            'filter_period_start' => $filter_period_start,
            'filter_period_end' => $filter_period_end,
            'filter_employee_id' => $filter_employee_id,
        ])
    </div>

    <script>
        (function () {
            const tableRegion = document.getElementById('payslips-table-region');
            if (!tableRegion) {
                return;
            }

            const liveSyncUrl = @json(route('admin.payslips.live-sync'));
            let isLoading = false;

            function updateUrl(params) {
                const url = new URL(window.location.href);
                ['period_start', 'period_end', 'employee_id', 'per_page', 'page'].forEach((key) => {
                    url.searchParams.delete(key);
                });
                params.forEach((value, key) => {
                    if (key !== 'page' || value !== '1') {
                        url.searchParams.set(key, value);
                    }
                });
                window.history.pushState({}, '', url.pathname + url.search + url.hash);
            }

            async function loadPayslips(params, force = true) {
                if (isLoading && !force) {
                    return;
                }
                isLoading = true;

                try {
                    const syncUrl = new URL(liveSyncUrl, window.location.origin);
                    params.forEach((value, key) => syncUrl.searchParams.set(key, value));

                    const response = await fetch(syncUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });

                    if (!response.ok) {
                        console.error('Payslips AJAX refresh failed with status:', response.status);
                        return;
                    }

                    const payload = await response.json();
                    if (payload.changed === true && typeof payload.html === 'string') {
                        tableRegion.innerHTML = payload.html;
                    }
                } catch (error) {
                    console.error('Payslips AJAX refresh failed:', error);
                } finally {
                    isLoading = false;
                }
            }

            tableRegion.addEventListener('change', (event) => {
                if (!event.target.matches('#payslips-per-page')) {
                    return;
                }

                const params = new URLSearchParams(window.location.search);
                params.set('per_page', event.target.value);
                params.delete('page');
                params.set('page', '1');
                updateUrl(params);
                loadPayslips(params);
            });

            tableRegion.addEventListener('click', (event) => {
                const link = event.target.closest('.payslip-pagination-footer a');
                if (!link) {
                    return;
                }

                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const params = new URLSearchParams(targetUrl.search);
                params.set('per_page', new URLSearchParams(window.location.search).get('per_page') || '10');
                updateUrl(params);
                loadPayslips(params);
            });

            const initialParams = new URLSearchParams(window.location.search);
            if (!initialParams.has('per_page')) {
                initialParams.set('per_page', '10');
                window.history.replaceState(
                    {},
                    '',
                    window.location.pathname + '?' + initialParams.toString() + window.location.hash
                );
            }
        })();
    </script>

@endsection
