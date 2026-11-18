@extends('layouts.admin', ['title' => 'Logbook Accounts'])

@section('content')
    <style>
        .logbook-accounts { display: grid; gap: 18px; }
        .logbook-accounts .accounts-intro { color: var(--c-text-muted); margin: 5px 0 0; }
        .logbook-accounts .accounts-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            border-bottom: 1px solid var(--c-border);
            padding-bottom: 12px;
        }
        .logbook-accounts .accounts-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 8px 13px;
            border: 1px solid var(--c-border-strong);
            border-radius: 9px;
            color: var(--c-text);
            background: var(--c-surface);
            font-weight: 700;
            text-decoration: none;
            transition: background-color var(--transition), color var(--transition), border-color var(--transition);
        }
        .logbook-accounts .accounts-tab:hover,
        .logbook-accounts .accounts-tab.active {
            color: #fff;
            background: var(--c-primary);
            border-color: var(--c-primary);
        }
        .logbook-accounts .accounts-count {
            min-width: 24px;
            padding: 2px 6px;
            border-radius: 999px;
            background: var(--c-surface-2);
            text-align: center;
            font-size: 12px;
            transition: background-color var(--transition), color var(--transition);
        }
        .logbook-accounts .accounts-tab.active .accounts-count { background: rgba(255, 255, 255, .18); }
        .logbook-accounts .accounts-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 14px;
            margin: 15px 0;
        }
        .logbook-accounts .accounts-toolbar label {
            display: grid;
            gap: 5px;
            color: var(--c-text-muted);
            font-size: 12px;
            font-weight: 700;
        }
        .logbook-accounts .accounts-toolbar .search-field { flex: 1 1 320px; max-width: 520px; }
        .logbook-accounts .accounts-toolbar .per-page-field { flex: 0 0 150px; }
        .logbook-accounts .accounts-toolbar .form-control { width: 100%; }
        .logbook-accounts .accounts-table-wrap { overflow-x: auto; }
        .logbook-accounts .accounts-table { min-width: 800px; width: 100%; }
        .logbook-accounts .accounts-bulk-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .logbook-accounts .accounts-bulk-actions[hidden] { display: none; }
        .logbook-accounts .accounts-table input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        .logbook-accounts .accounts-table tbody tr[data-profile-id] { cursor: pointer; }
        .logbook-accounts .accounts-table tbody tr[data-profile-id]:hover,
        .logbook-accounts .accounts-table tbody tr[data-profile-id]:focus {
            outline: 2px solid var(--c-primary);
            outline-offset: -2px;
            background: var(--c-surface-2);
        }
        .logbook-accounts .classification-badge {
            display: inline-flex;
            padding: 4px 9px;
            border-radius: 999px;
            color: var(--c-text-secondary);
            background: var(--c-surface-2);
            font-size: 12px;
            font-weight: 800;
            transition: background-color var(--transition), color var(--transition);
        }
        .logbook-accounts .accounts-pagination {
            display: flex;
            justify-content: center;
            margin-top: 16px;
        }
        .logbook-accounts .accounts-pagination nav { margin: 0; }
        .logbook-accounts .accounts-pagination ul.pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .logbook-accounts .accounts-pagination .pagination li { display: flex; }
        .logbook-accounts .accounts-pagination .pagination a,
        .logbook-accounts .accounts-pagination .pagination li > span {
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
        .logbook-accounts .accounts-pagination .pagination a:hover,
        .logbook-accounts .accounts-pagination .pagination li.active > span {
            border-color: var(--c-primary);
            background: var(--c-primary);
            color: #fff;
        }
        .logbook-accounts .accounts-pagination .pagination li.disabled > span {
            background: var(--c-surface-2);
            color: var(--c-text-muted);
            cursor: not-allowed;
        }
        .logbook-accounts .accounts-pagination .pagination a:focus-visible {
            outline: 2px solid var(--c-primary);
            outline-offset: 2px;
        }
        .logbook-accounts .search-status { min-height: 1.4em; color: var(--c-text-muted); font-size: 12px; }
        .account-edit-modal[hidden] { display: none; }
        .account-edit-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 18px;
            background: var(--c-modal-backdrop);
        }
        .account-edit-dialog {
            width: min(100%, 560px);
            max-height: min(90vh, 760px);
            overflow-y: auto;
            padding: 22px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            color: var(--c-text);
            box-shadow: var(--shadow-lg);
        }
        .account-edit-dialog h2 { margin: 0 0 16px; font-size: 19px; }
        .account-edit-fields { display: grid; gap: 13px; }
        .account-edit-fields label { display: grid; gap: 5px; font-size: 12px; font-weight: 700; }
        .account-edit-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }
        .history-print-periods { display: grid; gap: 9px; margin: 0; padding: 0; border: 0; }
        .history-print-periods legend { margin-bottom: 10px; font-size: 13px; font-weight: 700; }
        .history-print-periods label { display: flex; align-items: center; gap: 9px; font-size: 13px; }
        .history-print-periods input[type="radio"] { width: 16px; height: 16px; accent-color: var(--c-primary); }
        .history-print-date { display: grid; gap: 5px; margin-top: 12px; }
        .history-print-date[hidden] { display: none; }
        @media (max-width: 640px) {
            .account-edit-dialog { padding: 17px; }
            .account-edit-actions { flex-direction: column-reverse; }
        }
    </style>

    <div class="logbook-accounts">
        <section class="card">
            <div class="card-title">Profiles from Logbook Records</div>

            <nav class="accounts-tabs" role="tablist" aria-label="Filter profiles by classification" id="logbook-account-tabs">
                @foreach (['all' => 'All profiles', 'Personnel' => 'Personnel', 'Visitor' => 'Visitor', 'Resident' => 'Residents'] as $value => $label)
                    <a class="accounts-tab {{ $classification === $value ? 'active' : '' }}"
                       role="tab"
                       aria-selected="{{ $classification === $value ? 'true' : 'false' }}"
                       href="{{ route('admin.visitors.index', ['classification' => $value, 'search' => $search ?: null, 'per_page' => $perPage]) }}">
                        {{ $label }}
                        <span class="accounts-count">{{ $value === 'all' ? array_sum($profileCounts) : ($profileCounts[$value] ?? 0) }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="GET" action="{{ route('admin.visitors.index') }}" class="accounts-toolbar" data-live-search-form>
                <input type="hidden" name="classification" value="{{ $classification }}">
                <label class="search-field">
                    Search profiles
                    <input class="form-control" type="search" name="search" value="{{ $search }}" autocomplete="off" data-live-search>
                </label>
                <label class="per-page-field">
                    Rows per page
                    <select class="form-control" name="per_page" aria-label="Rows per page" data-per-page>
                        @foreach ([5, 10, 15, 20, 25, 50, 75, 100] as $option)
                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            <div class="search-status" role="status" aria-live="polite" data-search-status></div>

            <form id="bulk-delete-profiles-form" class="accounts-bulk-actions" method="POST" data-no-ajax
                  action="{{ route('admin.visitors.bulk-destroy') }}"
                  onsubmit="return confirm('Delete the selected logbook profiles and all their matching records? This cannot be undone.')"
                  hidden>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" data-delete-selected-profiles>
                    Delete Selected
                </button>
            </form>

            <div class="accounts-table-wrap">
                <table class="table accounts-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" aria-label="Select all profiles on this page" data-select-all-profiles>
                            </th>
                            <th>Classification</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Visits</th>
                            <th>Last seen</th>
                            <th>Print</th>
                        </tr>
                    </thead>
                    <tbody id="logbook-account-rows">
                        @forelse ($profiles as $profile)
                            <tr tabindex="0"
                                role="button"
                                aria-label="Edit {{ $profile->full_name }}"
                                data-profile-id="{{ $profile->profile_id }}"
                                data-name="{{ $profile->full_name }}"
                                data-classification="{{ $profile->person_category }}"
                                data-address="{{ $profile->address }}"
                                data-contact="{{ $profile->contact_number }}">
                                <td>
                                    <input type="checkbox" form="bulk-delete-profiles-form" name="profile_ids[]"
                                           value="{{ $profile->profile_id }}" aria-label="Select {{ $profile->full_name }}"
                                           data-profile-checkbox>
                                </td>
                                <td><span class="classification-badge">{{ $profile->person_category }}</span></td>
                                <td>{{ $profile->full_name }}</td>
                                <td>{{ $profile->address ?: '—' }}</td>
                                <td>{{ $profile->contact_number ?: '—' }}</td>
                                <td>{{ $profile->visit_count }}</td>
                                <td>{{ $profile->last_seen ?: '—' }}</td>
                                <td>
                                    <button class="btn btn-sm" type="button" data-profile-print>Print History</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-empty">No logbook profiles match this classification and search.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="accounts-pagination" id="logbook-account-pagination">{{ $profiles->links() }}</div>
        </section>
    </div>

    <div class="account-edit-modal" data-account-modal hidden>
        <section class="account-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="account-edit-title">
            <h2 id="account-edit-title">Edit Logbook Profile</h2>
            <form method="POST" data-account-edit-form
                  data-update-url-template="{{ route('admin.visitors.profiles.update', ['profile' => 'PROFILE_ID']) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="profile_id" value="{{ old('profile_id') }}" data-edit-profile-id>
                <div class="account-edit-fields">
                    <label>
                        Full name
                        <input class="form-control" type="text" name="full_name" value="{{ old('full_name') }}" maxlength="150" required data-edit-name>
                    </label>
                    <label>
                        Person classification
                        <select class="form-control" name="person_category" required data-edit-classification>
                            <option value="Personnel" @selected(old('person_category') === 'Personnel')>Personnel</option>
                            <option value="Visitor" @selected(old('person_category') === 'Visitor')>Visitor</option>
                            <option value="Resident" @selected(old('person_category') === 'Resident')>Resident</option>
                        </select>
                    </label>
                    <label>
                        Address
                        <input class="form-control" type="text" name="address" value="{{ old('address') }}" maxlength="255" data-edit-address>
                    </label>
                    <label>
                        Contact number
                        <input class="form-control" type="text" name="contact_number" value="{{ old('contact_number') }}" maxlength="50" data-edit-contact>
                    </label>
                </div>
                <div class="account-edit-actions">
                    <button class="btn btn-danger" type="button" data-close-account-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Profile</button>
                </div>
            </form>
        </section>
    </div>

    <div class="account-edit-modal" data-history-print-modal hidden>
        <section class="account-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="history-print-title">
            <h2 id="history-print-title">Print Logbook History</h2>
            <p data-history-print-person style="margin:-6px 0 16px;color:var(--c-text-muted);"></p>
            <form data-history-print-form>
                <input type="hidden" name="name">
                <input type="hidden" name="classification">
                <input type="hidden" name="contact_number">
                <fieldset class="history-print-periods">
                    <legend>Choose a timeframe</legend>
                    <label><input type="radio" name="period" value="all" checked> All Records</label>
                    <label><input type="radio" name="period" value="day"> Today / Specific Day</label>
                    <label><input type="radio" name="period" value="week"> This Week</label>
                    <label><input type="radio" name="period" value="month"> This Month / Specific Month</label>
                    <label><input type="radio" name="period" value="custom"> Custom Date Range</label>
                </fieldset>
                <label class="history-print-date" data-history-date-field="day" hidden>
                    Specific day
                    <input class="form-control" type="date" name="date" value="{{ now()->format('Y-m-d') }}" disabled required>
                </label>
                <label class="history-print-date" data-history-date-field="month" hidden>
                    Month
                    <input class="form-control" type="month" name="month" value="{{ now()->format('Y-m') }}" disabled required>
                </label>
                <div class="history-print-date" data-history-date-field="custom" hidden>
                    <label>
                        From Date
                        <input class="form-control" type="date" name="from_date" disabled required>
                    </label>
                    <label>
                        To Date
                        <input class="form-control" type="date" name="to_date" disabled required>
                    </label>
                </div>
                <div class="account-edit-actions">
                    <button class="btn btn-danger" type="button" data-close-history-print>Cancel</button>
                    <button class="btn btn-primary" type="submit">Generate &amp; Print</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-live-search-form]');
            const input = document.querySelector('[data-live-search]');
            const perPage = document.querySelector('[data-per-page]');
            const status = document.querySelector('[data-search-status]');
            const modal = document.querySelector('[data-account-modal]');
            const editForm = document.querySelector('[data-account-edit-form]');
            const printModal = document.querySelector('[data-history-print-modal]');
            const printForm = document.querySelector('[data-history-print-form]');
            const printPerson = document.querySelector('[data-history-print-person]');
            const tableBody = document.getElementById('logbook-account-rows');
            const pagination = document.getElementById('logbook-account-pagination');
            const tabs = document.getElementById('logbook-account-tabs');
            const accountsTable = document.querySelector('.accounts-table');
            const bulkDeleteForm = document.getElementById('bulk-delete-profiles-form');
            const bulkDeleteButton = document.querySelector('[data-delete-selected-profiles]');
            const selectedProfileIds = new Set();
            let searchTimer;
            let activeRequest;

            if (!form || !input || !perPage || !status || !modal || !editForm || !printModal || !printForm || !printPerson || !tableBody || !pagination || !accountsTable || !bulkDeleteForm || !bulkDeleteButton) {
                return;
            }

            const updateProfileSelection = () => {
                const checkboxes = Array.from(tableBody.querySelectorAll('[data-profile-checkbox]'));
                const visibleIds = new Set(checkboxes.map((checkbox) => checkbox.value));
                selectedProfileIds.forEach((id) => {
                    if (!visibleIds.has(id)) {
                        selectedProfileIds.delete(id);
                    }
                });
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectedProfileIds.has(checkbox.value);
                });

                const selectAll = accountsTable.querySelector('[data-select-all-profiles]');
                const selectedVisibleCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selectAll) {
                    selectAll.checked = checkboxes.length > 0 && selectedVisibleCount === checkboxes.length;
                    selectAll.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < checkboxes.length;
                }
                bulkDeleteForm.hidden = selectedVisibleCount === 0;
                bulkDeleteButton.textContent = selectedVisibleCount > 0
                    ? `Delete Selected (${selectedVisibleCount})`
                    : 'Delete Selected';
            };

            const showProfile = (row) => {
                editForm.action = editForm.dataset.updateUrlTemplate.replace('PROFILE_ID', row.dataset.profileId);
                editForm.querySelector('[data-edit-profile-id]').value = row.dataset.profileId;
                editForm.querySelector('[data-edit-name]').value = row.dataset.name || '';
                editForm.querySelector('[data-edit-classification]').value = row.dataset.classification || 'Visitor';
                editForm.querySelector('[data-edit-address]').value = row.dataset.address || '';
                editForm.querySelector('[data-edit-contact]').value = row.dataset.contact || '';
                modal.hidden = false;
                editForm.querySelector('[data-edit-name]').focus();
            };

            const showPrintOptions = (row) => {
                printForm.elements.name.value = row.dataset.name || '';
                printForm.elements.classification.value = row.dataset.classification || '';
                printForm.elements.contact_number.value = row.dataset.contact || '';
                printPerson.textContent = `${row.dataset.name || 'Profile'} · ${row.dataset.classification || ''}`;
                printForm.querySelector('[name="period"][value="all"]').checked = true;
                updatePrintDateFields();
                printModal.hidden = false;
                printForm.querySelector('[name="period"][value="all"]').focus();
            };

            const updatePrintDateFields = () => {
                const period = printForm.querySelector('[name="period"]:checked')?.value || 'all';
                printForm.querySelectorAll('[data-history-date-field]').forEach((field) => {
                    const active = field.dataset.historyDateField === period;
                    field.hidden = !active;
                    field.querySelectorAll('input').forEach((dateInput) => {
                        dateInput.disabled = !active;
                    });
                });
            };

            const closePrintOptions = () => {
                printModal.hidden = true;
            };

            const closeModal = () => {
                modal.hidden = true;
            };

            const refreshProfiles = async (profilePage = null) => {
                selectedProfileIds.clear();
                updateProfileSelection();
                if (activeRequest) {
                    activeRequest.abort();
                }
                activeRequest = new AbortController();
                status.textContent = 'Filtering profiles...';

                const url = new URL(form.action, window.location.origin);
                url.searchParams.set('classification', form.elements.classification.value);
                url.searchParams.set('per_page', perPage.value);
                url.searchParams.delete('profile_page');
                if (profilePage && profilePage !== '1') {
                    url.searchParams.set('profile_page', profilePage);
                }
                if (input.value.trim().length >= 2) {
                    url.searchParams.set('search', input.value.trim());
                } else {
                    url.searchParams.delete('search');
                }

                try {
                    const response = await fetch(url, {
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: activeRequest.signal,
                    });
                    if (!response.ok) {
                        throw new Error(`Profile search failed with status ${response.status}.`);
                    }

                    const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextRows = documentResult.getElementById('logbook-account-rows');
                    const nextPagination = documentResult.getElementById('logbook-account-pagination');
                    const nextTabs = documentResult.getElementById('logbook-account-tabs');
                    if (!nextRows || !nextPagination) {
                        throw new Error('The profile search response did not contain the account table.');
                    }

                    tableBody.replaceChildren(...nextRows.childNodes);
                    pagination.replaceChildren(...nextPagination.childNodes);
                    if (nextTabs && tabs) {
                        tabs.replaceChildren(...nextTabs.childNodes);
                    }
                    window.history.pushState({}, '', url.pathname + url.search + url.hash);
                    status.textContent = input.value.trim().length >= 2 ? 'Profiles filtered.' : 'Showing all profiles.';
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    status.textContent = 'Live search failed. Please refresh the page and try again.';
                    console.error(error);
                }
            };

            form.addEventListener('submit', (event) => event.preventDefault());
            input.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                if (input.value.trim().length === 1) {
                    status.textContent = 'Type at least 2 characters to search.';
                }
                searchTimer = window.setTimeout(refreshProfiles, 300);
            });
            perPage.addEventListener('change', () => {
                window.clearTimeout(searchTimer);
                refreshProfiles();
            });

            tabs?.addEventListener('click', (event) => {
                const tab = event.target.closest('a.accounts-tab');
                if (!tab) {
                    return;
                }
                event.preventDefault();
                const targetUrl = new URL(tab.href, window.location.origin);
                form.elements.classification.value = targetUrl.searchParams.get('classification') || 'all';
                window.clearTimeout(searchTimer);
                refreshProfiles();
            });

            pagination.addEventListener('click', (event) => {
                const link = event.target.closest('a');
                if (!link) {
                    return;
                }
                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const profilePage = targetUrl.searchParams.get('profile_page');
                if (profilePage) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('profile_page', profilePage);
                    window.history.pushState({}, '', currentUrl);
                    refreshProfiles(profilePage);
                } else {
                    refreshProfiles();
                }
            });

            const initialParams = new URLSearchParams(window.location.search);
            if (!initialParams.has('per_page')) {
                initialParams.set('per_page', perPage.value || '10');
                window.history.replaceState(
                    {},
                    '',
                    window.location.pathname + '?' + initialParams.toString() + window.location.hash
                );
            }

            tableBody.addEventListener('click', (event) => {
                if (event.target.closest('[data-profile-checkbox]')) {
                    return;
                }
                const row = event.target.closest('tr[data-profile-id]');
                if (row) {
                    if (event.target.closest('[data-profile-print]')) {
                        showPrintOptions(row);
                        return;
                    }
                    showProfile(row);
                }
            });
            tableBody.addEventListener('keydown', (event) => {
                if (event.target.closest('[data-profile-checkbox], [data-profile-print]')) {
                    return;
                }
                const row = event.target.closest('tr[data-profile-id]');
                if (row && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    showProfile(row);
                }
            });
            accountsTable.addEventListener('change', (event) => {
                if (event.target.matches('[data-profile-checkbox]')) {
                    if (event.target.checked) {
                        selectedProfileIds.add(event.target.value);
                    } else {
                        selectedProfileIds.delete(event.target.value);
                    }
                    updateProfileSelection();
                    return;
                }

                if (event.target.matches('[data-select-all-profiles]')) {
                    tableBody.querySelectorAll('[data-profile-checkbox]').forEach((checkbox) => {
                        if (event.target.checked) {
                            selectedProfileIds.add(checkbox.value);
                        } else {
                            selectedProfileIds.delete(checkbox.value);
                        }
                    });
                    updateProfileSelection();
                }
            });
            modal.addEventListener('click', (event) => {
                if (event.target === modal || event.target.closest('[data-close-account-modal]')) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
                if (event.key === 'Escape' && !printModal.hidden) {
                    closePrintOptions();
                }
            });
            printForm.addEventListener('change', (event) => {
                if (event.target.matches('[name="period"]')) {
                    updatePrintDateFields();
                }
            });
            printForm.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!printForm.reportValidity()) {
                    return;
                }

                const printUrl = new URL(@json(route('admin.barangay-logbook.print-records')), window.location.origin);
                const fields = new FormData(printForm);
                fields.forEach((value, key) => {
                    if (value !== '') {
                        printUrl.searchParams.set(key, value);
                    }
                });
                window.open(printUrl.toString(), '_blank', 'noopener');
                closePrintOptions();
            });
            printModal.addEventListener('click', (event) => {
                if (event.target === printModal || event.target.closest('[data-close-history-print]')) {
                    closePrintOptions();
                }
            });

            @if ($errors->any() && old('profile_id'))
                editForm.action = editForm.dataset.updateUrlTemplate.replace('PROFILE_ID', @json(old('profile_id')));
                modal.hidden = false;
            @endif
        })();
    </script>
@endsection
