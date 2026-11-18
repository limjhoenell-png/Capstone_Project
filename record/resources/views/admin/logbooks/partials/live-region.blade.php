<style>
    /* Shared admin logbook scroll frame (legacy + app tables). */
    .logbook-table-viewport {
        max-width: 100%;
        max-height: min(82vh, 48rem);
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .logbook-table-viewport .table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: var(--c-surface-2);
        box-shadow: 0 1px 0 var(--c-border);
    }
</style>
@if(isset($legacyEntries))
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
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .logbook-ledger-card .table-wrapper {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .table-logbook-ledger {
            width: 100%;
            min-width: 1445px;
            table-layout: fixed;
        }
        .table-logbook-ledger thead th {
            vertical-align: bottom;
        }
        .table-logbook-ledger thead th:nth-child(4) {
            white-space: nowrap;
        }
        .table-logbook-ledger thead th,
        .table-logbook-ledger tbody td {
            padding: 10px 8px;
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
        .table-logbook-ledger .logbook-td-user,
        .table-logbook-ledger .logbook-td-category,
        .table-logbook-ledger .logbook-td-address,
        .table-logbook-ledger .logbook-td-contact,
        .table-logbook-ledger .logbook-td-representing {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .table-logbook-ledger .logbook-td-user .btn {
            display: flex;
            width: 100%;
            min-width: 0;
            justify-content: flex-start;
            white-space: normal;
            line-height: 1.3;
        }
        .table-logbook-ledger .logbook-td-category,
        .table-logbook-ledger .logbook-td-address,
        .table-logbook-ledger .logbook-td-contact,
        .table-logbook-ledger .logbook-td-representing {
            vertical-align: top;
            font-size: 13px;
        }
        .table-logbook-ledger .logbook-td-purpose {
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
            vertical-align: top;
            font-size: 13px;
        }
        .table-logbook-ledger .logbook-td-nowrap {
            white-space: nowrap;
        }
        .table-logbook-ledger .logbook-td-actions {
            vertical-align: top;
            white-space: nowrap;
        }
    </style>
    <div class="card logbook-ledger-card" style="padding:0;">
        <div class="table-wrapper logbook-table-viewport">
            <table class="table table-logbook-ledger">
                <colgroup>
                    <col style="width:42px;">
                    <col style="width:56px;">
                    <col style="width:145px;">
                    <col style="width:180px;">
                    <col style="width:125px;">
                    <col style="width:110px;">
                    <col style="width:190px;">
                    <col style="width:46px;">
                    <col style="width:55px;">
                    <col style="width:48px;">
                    <col style="width:110px;">
                    <col style="width:175px;">
                    <col style="width:160px;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="logbook-th-check"><input id="logbook-select-all" type="checkbox" aria-label="Select all logbook entries"></th>
                        <th>ID</th>
                        <th>User</th>
                        <th>Person classification</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Purpose of Transaction</th>
                        <th class="logbook-th-check" title="Authorized representative"><span class="logbook-th-short">Rep</span></th>
                        <th class="logbook-th-check" title="Authorization letter verified"><span class="logbook-th-short">Letter</span></th>
                        <th class="logbook-th-check" title="Valid ID verified"><span class="logbook-th-short">ID</span></th>
                        <th>Representing</th>
                        <th>Time in</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($legacyEntries as $entry)
                    <tr>
                        <td class="logbook-td-check">
                            <input class="logbook-entry-select" type="checkbox" name="selected_ids[]" form="delete-selected-logbooks-form" value="{{ $entry->logbook_id }}" aria-label="Select logbook entry {{ $entry->logbook_id }}">
                        </td>
                        <td class="text-mono" style="font-size:13px;color:var(--c-text-muted);">{{ $entry->logbook_id }}</td>
                        <td class="logbook-td-user">
                            @if($entry->user_name !== null && $entry->user_name !== '')
                                <button type="button" class="btn btn-sm"
                                    onclick="openUserOverlay(
                                        '{{ (int) $entry->logbook_id }}',
                                        '{{ addslashes((string) $entry->user_name) }}',
                                        '{{ addslashes((string) $entry->address) }}',
                                        '{{ addslashes((string) $entry->contact_number) }}',
                                        '{{ (int) ($entry->mapped_user_id ?? 0) }}'
                                    )"
                                >{{ $entry->user_name }}</button>
                            @endif
                        </td>
                        <td class="logbook-td-category">{{ \Illuminate\Support\Str::title((string) data_get($entry, 'person_category', '')) }}</td>
                        <td class="logbook-td-address" style="color:var(--c-text-secondary);">{{ $entry->address }}</td>
                        <td class="logbook-td-contact" style="color:var(--c-text-secondary);">{{ $entry->contact_number }}</td>
                        <td class="logbook-td-purpose">{{ $entry->purpose_of_transaction }}</td>
                        <td class="logbook-td-check">
                            <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'is_authorized_representative'))) aria-label="Authorized representative" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                        </td>
                        <td class="logbook-td-check">
                            <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'authorization_letter_verified'))) aria-label="Authorization letter verified" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                        </td>
                        <td class="logbook-td-check">
                            <input type="checkbox" disabled @checked($lbTruth(data_get($entry, 'valid_id_verified'))) aria-label="Valid ID verified" style="width:18px;height:18px;accent-color:var(--c-primary,#2563eb);cursor:default;">
                        </td>
                        <td class="logbook-td-representing">{{ $lbTruth(data_get($entry, 'is_authorized_representative')) ? trim((string) data_get($entry, 'represented_person_name', '')) : '' }}</td>
                        <td class="text-mono logbook-td-nowrap" style="font-size:13px;">{{ \Illuminate\Support\Carbon::parse($entry->time_in)->format('Y-m-d h:i:s A') }}</td>
                        <td class="logbook-td-actions">
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a class="btn btn-sm" href="{{ route('admin.logbooks.edit', $entry->logbook_id) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                    Edit
                                </a>
                                <form class="inline" method="POST" action="{{ route('admin.logbooks.destroy', $entry->logbook_id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this logbook entry?')">
                                        Delete
                                    </button>
                                </form>
                                @if(!empty($entry->mapped_user_id))
                                    <form class="inline" method="POST" data-no-ajax action="{{ route('admin.employees.destroy', $entry->mapped_user_id) }}">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="confirm_name" value="{{ $entry->user_name }}">
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete account {{ $entry->user_name }} and records linked by their device id?')">
                                            Del User
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="table-empty">No logbook records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card" style="padding:0;">
        <div class="table-wrapper logbook-table-viewport">
            <table class="table">
                <thead>
                    <tr>
                        <th class="logbook-th-check"><input id="logbook-select-all" type="checkbox" aria-label="Select all logbook entries"></th>
                        <th>User</th>
                        <th>Person classification</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Purpose of Transaction</th>
                        <th>Entry Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="logbook-td-check">
                            <input class="logbook-entry-select" type="checkbox" name="selected_ids[]" form="delete-selected-logbooks-form" value="{{ $entry->id }}" aria-label="Select logbook entry {{ $entry->id }}">
                        </td>
                        <td style="font-weight:600;">{{ $entry->full_name ?: ($entry->user?->name ?? $entry->user?->full_name ?? '') }}</td>
                        <td>{{ \Illuminate\Support\Str::title((string) ($entry->person_category ?? '')) }}</td>
                        <td>{{ $entry->address ?? '' }}</td>
                        <td>{{ $entry->contact_number ?? '' }}</td>
                        <td style="color:var(--c-text-secondary);">{{ $entry->purpose_of_transaction ?? ($entry->description === 'Imported logbook entry' ? '' : $entry->description) }}</td>
                        <td class="text-mono" style="font-size:13px;">{{ optional($entry->entry_at)->format('Y-m-d h:i:s A') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a class="btn btn-sm" href="{{ route('admin.logbooks.edit', $entry) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                    Edit
                                </a>
                                <form class="inline" method="POST" action="{{ route('admin.logbooks.destroy', $entry) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this logbook entry?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="table-empty">No logbook records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@php
    $listingEntries = $legacyEntries ?? $entries;
    $pageWindow = range(
        max(1, $listingEntries->currentPage() - 2),
        min($listingEntries->lastPage(), $listingEntries->currentPage() + 2)
    );
@endphp
<div class="logbook-pagination">
    <div class="logbook-pagination-summary">
        Showing {{ $listingEntries->firstItem() ?? 0 }}–{{ $listingEntries->lastItem() ?? 0 }}
        of {{ $listingEntries->total() }} records
    </div>
    <form method="GET" action="{{ request()->url() }}" class="logbook-page-size">
        @foreach(request()->except(['page', 'per_page']) as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="logbook-per-page">Rows per page</label>
        <select id="logbook-per-page" name="per_page" aria-label="Rows per page">
            @foreach($perPageOptions ?? [10, 15, 25, 50] as $option)
                <option value="{{ $option }}" @selected(($perPage ?? $listingEntries->perPage()) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </form>
    <nav class="logbook-page-nav" aria-label="Logbook pages">
        @if($listingEntries->onFirstPage())
            <span class="logbook-page-disabled" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $listingEntries->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @foreach($pageWindow as $pageNumber)
            @if($pageNumber === $listingEntries->currentPage())
                <span class="logbook-page-current" aria-current="page">{{ $pageNumber }}</span>
            @else
                <a href="{{ $listingEntries->url($pageNumber) }}">{{ $pageNumber }}</a>
            @endif
        @endforeach

        @if($listingEntries->hasMorePages())
            <a href="{{ $listingEntries->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="logbook-page-disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
</div>
<style>
    .logbook-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px 20px;
        flex-wrap: wrap;
        padding: 14px 4px 2px;
        color: var(--c-text-secondary);
        font-size: 13px;
    }
    .logbook-page-size,
    .logbook-page-nav {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .logbook-page-size select {
        min-height: 34px;
        padding: 5px 26px 5px 9px;
        border: 1px solid var(--c-border-strong);
        border-radius: var(--radius-sm);
        background: var(--c-surface);
        color: var(--c-text);
        font: inherit;
    }
    .logbook-page-nav a,
    .logbook-page-current,
    .logbook-page-disabled {
        display: inline-flex;
        min-width: 34px;
        min-height: 34px;
        align-items: center;
        justify-content: center;
        padding: 0 9px;
        border: 1px solid var(--c-border);
        border-radius: var(--radius-sm);
        background: var(--c-surface);
        color: var(--c-text);
        text-decoration: none;
    }
    .logbook-page-nav a:hover {
        border-color: var(--c-primary);
        color: var(--c-primary);
    }
    .logbook-page-current {
        border-color: var(--c-primary);
        background: var(--c-primary);
        color: #fff;
        font-weight: 700;
    }
    .logbook-page-disabled {
        color: var(--c-text-muted);
        background: var(--c-surface-2);
    }
    @media (max-width: 640px) {
        .logbook-pagination {
            align-items: flex-start;
        }
        .logbook-pagination-summary {
            width: 100%;
        }
        .logbook-page-nav {
            flex-wrap: wrap;
        }
    }
</style>
