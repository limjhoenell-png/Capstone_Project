@if($accounts !== null || $legacyEntries !== null)
    @php
        $requestedLogbookTab = request('tab');
        $activeLogbookTab = $requestedLogbookTab === 'transactions' && $legacyEntries !== null
            ? 'employee-legacy-logbook'
            : ($accounts !== null ? 'employee-logbook-accounts' : 'employee-legacy-logbook');
    @endphp
    <style>
        .employee-logbook-header {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            min-height: 68px;
            margin-bottom: 1rem;
            padding: 12px 14px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            color: var(--c-text);
            box-shadow: var(--shadow-sm);
        }
        .employee-logbook-tabs,
        .employee-logbook-page-size,
        .employee-logbook-pagination {
            flex-shrink: 0;
        }
        .employee-logbook-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            width: auto;
            min-width: 0;
            margin-left: 0;
        }
        .employee-logbook-pagination [hidden] {
            display: none;
        }
        .employee-logbook-tabs {
            display: flex !important;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin: 0;
            min-width: 270px;
            visibility: visible;
            opacity: 1;
        }
        .employee-logbook-tabs [data-logbook-tab] {
            display: inline-flex;
            visibility: visible;
            opacity: 1;
            min-height: 38px;
            padding-inline: 14px;
            border: 1px solid var(--c-border-strong);
            border-radius: 999px;
            background: var(--c-surface);
            color: var(--c-text);
            box-shadow: none;
        }
        .employee-logbook-tabs .is-active {
            border: 2px solid var(--c-primary-text);
            background: var(--c-primary-light);
            color: var(--c-primary-text);
            font-weight: 700;
            box-shadow: none;
        }
        .employee-logbook-tabs [data-logbook-tab]:focus-visible {
            outline: 2px solid var(--c-primary-text);
            outline-offset: 2px;
        }
        .employee-logbook-pagination .pagination-shell {
            margin: 0;
        }
        .employee-logbook-page-size {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            min-width: 150px;
            color: var(--c-text-secondary);
            font-size: 13px;
        }
        .employee-logbook-page-size label {
            color: var(--c-text-secondary);
            font-weight: 600;
        }
        .employee-logbook-page-size select {
            width: auto;
            min-width: 82px;
            border-color: var(--c-border-strong);
            background: var(--c-surface);
            color: var(--c-text);
            color-scheme: light;
        }
        html[data-theme="dark"] .employee-logbook-page-size select {
            color-scheme: dark;
        }
        .employee-logbook-page-size select:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .employee-logbook-pagination nav[role="navigation"] a,
        .employee-logbook-pagination nav[role="navigation"] span {
            border-color: var(--c-border-strong);
            background: var(--c-surface);
            color: var(--c-text-secondary);
        }
        .employee-logbook-pagination nav[role="navigation"] a:hover {
            background: var(--c-surface-2);
            color: var(--c-text);
        }
        .employee-logbook-pagination nav[role="navigation"] span[aria-current="page"] > span {
            border-color: var(--c-primary);
            background: var(--c-primary);
            color: #fff;
        }
        .employee-logbook-panel[hidden] {
            display: none !important;
        }
        .employee-logbook-panel {
            margin: 0;
        }
        @media (max-width: 1100px) {
            .employee-logbook-header {
                align-items: flex-start;
                flex-wrap: wrap;
            }
            .employee-logbook-pagination {
                width: auto;
                justify-content: flex-start;
                margin-left: 0;
            }
        }
    </style>
    <div class="employee-logbook-header">
        <nav class="employee-logbook-tabs" aria-label="Logbook sections">
            @if($accounts !== null)
                <button type="button" class="btn {{ $activeLogbookTab === 'employee-logbook-accounts' ? 'is-active' : '' }}" aria-expanded="{{ $activeLogbookTab === 'employee-logbook-accounts' ? 'true' : 'false' }}" aria-controls="employee-logbook-accounts" data-logbook-tab="employee-logbook-accounts">
                    Logbook Accounts
                </button>
            @endif
            @if($legacyEntries !== null)
                <button type="button" class="btn {{ $activeLogbookTab === 'employee-legacy-logbook' ? 'is-active' : '' }}" aria-expanded="{{ $activeLogbookTab === 'employee-legacy-logbook' ? 'true' : 'false' }}" aria-controls="employee-legacy-logbook" data-logbook-tab="employee-legacy-logbook">
                    Logbook Transactions
                </button>
            @endif
        </nav>
        <div class="employee-logbook-page-size" aria-label="Global row selection">
            <label for="employee-logbook-per-page">Rows per page</label>
            <select id="employee-logbook-per-page" name="per_page" class="form-control" data-logbook-page-size>
                @foreach($pageSizes as $pageSize)
                    <option value="{{ $pageSize }}" @selected(($globalPerPage ?? 10) === $pageSize)>{{ $pageSize }}</option>
                @endforeach
            </select>
        </div>
        <div class="employee-logbook-pagination" aria-label="Table pagination">
            @if($accounts !== null)
                <div class="pagination-shell" data-logbook-pagination="employee-logbook-accounts" @if($activeLogbookTab !== 'employee-logbook-accounts') hidden @endif>
                    {!! $accounts->links() !!}
                </div>
            @endif
            @if($legacyEntries !== null)
                <div class="pagination-shell" data-logbook-pagination="employee-legacy-logbook" @if($activeLogbookTab !== 'employee-legacy-logbook') hidden @endif>
                    {!! $legacyEntries->links() !!}
                </div>
            @endif
        </div>
    </div>
@endif

@if($accounts !== null)
    <section id="employee-logbook-accounts" class="employee-logbook-panel" role="tabpanel" @if($activeLogbookTab !== 'employee-logbook-accounts') hidden @endif data-logbook-panel>
        <div class="card card-compact">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Classification</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Transactions</th>
                            <th>Latest transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->full_name ?: '—' }}</td>
                            <td>{{ $account->person_category ? \Illuminate\Support\Str::title($account->person_category) : '—' }}</td>
                            <td>{{ $account->address ?: '—' }}</td>
                            <td>{{ $account->contact_number ?: '—' }}</td>
                            <td>{{ (int) $account->transaction_count }}</td>
                            <td class="text-mono">{{ \App\Support\PortalUi::dt($account->last_seen) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">No logbook accounts found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($legacyEntries !== null)
    @php
        $lbTruth = static function ($v): bool {
            if ($v === true) {
                return true;
            }
            if ($v === false || $v === null) {
                return false;
            }
            $s = strtolower(trim((string) $v));

            return $s === '1' || $s === 'true' || $s === 'yes';
        };
    @endphp
    <style>
        .logbook-ledger-card {
            max-width: 100%;
        }
        .logbook-ledger-card .table-wrapper {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-logbook-ledger {
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }
        .table-logbook-ledger thead th {
            vertical-align: bottom;
        }
        .table-logbook-ledger .logbook-th-check {
            width: 1%;
            padding-left: 8px;
            padding-right: 8px;
            white-space: nowrap;
            text-align: center;
        }
        .table-logbook-ledger .logbook-th-check .logbook-th-short {
            display: block;
            font-size: 10px;
            letter-spacing: 0;
            text-transform: none;
            font-weight: 700;
            line-height: 1.2;
        }
        .table-logbook-ledger .logbook-td-check {
            padding-left: 8px;
            padding-right: 8px;
            text-align: center;
            vertical-align: middle;
        }
        .table-logbook-ledger .logbook-td-purpose {
            min-width: 12rem;
            max-width: 22rem;
            max-height: 7.5rem;
            overflow-y: auto;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
            vertical-align: top;
            font-size: 13px;
        }
        .table-logbook-ledger .logbook-td-entry-type {
            min-width: 11rem;
            max-width: 20rem;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
            vertical-align: top;
            font-size: 13px;
        }
        .table-logbook-ledger .logbook-td-nowrap {
            white-space: nowrap;
        }
    </style>
    <section id="employee-legacy-logbook" class="employee-logbook-panel" role="tabpanel" @if($activeLogbookTab !== 'employee-legacy-logbook') hidden @endif data-logbook-panel>
        <div class="card card-compact logbook-ledger-card">
            <div class="card-header">Logbook Transactions</div>
            <div class="table-wrapper">
                <table class="table table-logbook-ledger">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Purpose of Transaction</th>
                            <th>Entry type</th>
                            <th class="logbook-th-check" title="Authorized representative"><span class="logbook-th-short">Rep</span></th>
                            <th class="logbook-th-check" title="Authorization letter verified"><span class="logbook-th-short">Letter</span></th>
                            <th class="logbook-th-check" title="Valid ID verified"><span class="logbook-th-short">ID</span></th>
                            <th>Representing</th>
                            <th>Fingerprint</th>
                            <th>Time in</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($legacyEntries as $entry)
                        <tr>
                            <td class="text-mono" style="font-size:13px;color:var(--c-text-muted);">{{ $entry->logbook_id }}</td>
                            <td>
                                <span class="btn btn-sm" style="cursor:default;pointer-events:none;">{{ $entry->full_name ?: $entry->user_name }}</span>
                            </td>
                            <td class="text-secondary" style="font-size:13px;">{{ $entry->address }}</td>
                            <td class="text-secondary" style="font-size:13px;">{{ $entry->contact_number }}</td>
                            <td class="logbook-td-purpose">{{ $entry->purpose_of_transaction }}</td>
                            <td class="logbook-td-entry-type" title="{{ e((string) data_get($entry, 'transaction_type', '')) }}">{{ data_get($entry, 'transaction_type') ?: '—' }}</td>
                            <td class="logbook-td-check">
                                <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'is_authorized_representative'))) aria-label="Authorized representative" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                            </td>
                            <td class="logbook-td-check">
                                <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'authorization_letter_verified'))) aria-label="Authorization letter verified" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                            </td>
                            <td class="logbook-td-check">
                                <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'valid_id_verified'))) aria-label="Valid ID verified" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                            </td>
                            <td style="font-size:13px;min-width:6rem;max-width:12rem;overflow-wrap:anywhere;">{{ $lbTruth(data_get($entry, 'is_authorized_representative')) ? trim((string) data_get($entry, 'represented_person_name', '')) : '' }}</td>
                            <td class="text-mono logbook-td-nowrap" style="font-size:13px;">{{ $entry->fingerprint_id }}</td>
                            <td class="text-mono logbook-td-nowrap" style="font-size:13px;">{{ \App\Support\PortalUi::dt($entry->time_in ?? null) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="table-empty">No legacy logbook records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($legacyEntries === null && $entries === null && $accounts === null)
    <div class="card text-secondary" style="padding:24px;">
        No logbook storage tables are available (<code class="text-mono" style="font-size:12px;">logbook</code> / <code class="text-mono" style="font-size:12px;">logbook_entries</code>). Run migrations or check your database connection.
    </div>
@endif
