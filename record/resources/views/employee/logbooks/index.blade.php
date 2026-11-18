@extends('layouts.employee', ['title' => 'Logbook'])

@section('content')

    <style>
        #employee-logbooks-live {
            gap: 0;
        }
        .employee-logbook-search-form .search-field {
            min-width: 280px;
        }
        .employee-logbook-search-form .search-field-icon {
            left: 12px;
        }
        .employee-logbook-search-form .form-control-with-icon {
            padding-left: 2.5rem;
        }
    </style>

    <div class="action-bar">
        <form method="GET" action="{{ route('employee.logbooks.index') }}" class="toolbar-form employee-logbook-search-form" data-no-ajax>
            <div class="search-field">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                     class="search-field-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input class="form-control form-control-with-icon" type="text" name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Search name, address, contact, purpose of transaction…">
            </div>
            <button class="btn" type="submit">Apply</button>
            @if(!empty($search))
                <a class="btn" href="{{ route('employee.logbooks.index') }}">Clear</a>
            @endif
        </form>
    </div>

    @php
        $logbookSyncQuery = array_filter([
            'search' => request('search'),
            'tab' => request('tab'),
            'legacy_page' => request('legacy_page'),
            'entries_page' => request('entries_page'),
            'accounts_page' => request('accounts_page'),
            'per_page' => request('per_page'),
            'legacy_per_page' => request('legacy_per_page'),
            'accounts_per_page' => request('accounts_per_page'),
        ]);
        $logbookSyncUrl = route('employee.logbooks.live-sync').(count($logbookSyncQuery) ? '?'.http_build_query($logbookSyncQuery) : '');
    @endphp

    <div
        id="employee-logbooks-live"
        class="content-stack"
        data-live-sync-url="{{ $logbookSyncUrl }}"
        data-live-signature="{{ $liveSignature ?? '' }}"
    >
        @include('employee.logbooks.partials.live-region', [
            'legacyEntries' => $legacyEntries ?? null,
            'entries' => $entries ?? null,
            'accounts' => $accounts ?? null,
            'legacyPerPage' => $legacyPerPage,
            'accountsPerPage' => $accountsPerPage,
            'pageSizes' => $pageSizes,
            'globalPerPage' => $globalPerPage,
        ])
    </div>
    <p id="employee-logbooks-status" role="status" hidden></p>

    <script>
        (function () {
            const root = document.getElementById('employee-logbooks-live');
            const status = document.getElementById('employee-logbooks-status');
            let activeRequest = null;
            const initialUrl = new URL(window.location.href);
            let initializedUrl = false;
            if (!initialUrl.searchParams.has('per_page')) {
                initialUrl.searchParams.set('per_page', '10');
                initialUrl.searchParams.delete('accounts_per_page');
                initialUrl.searchParams.delete('legacy_per_page');
                initializedUrl = true;
            } else {
                initialUrl.searchParams.delete('accounts_per_page');
                initialUrl.searchParams.delete('legacy_per_page');
                initializedUrl = true;
            }
            if (!initialUrl.searchParams.has('tab')) {
                initialUrl.searchParams.set('tab', 'accounts');
                initializedUrl = true;
            }
            if (initializedUrl) {
                window.history.replaceState({}, '', initialUrl.toString());
            }

            async function refreshLogbook(params) {
                if (!root) {
                    return;
                }

                if (activeRequest) {
                    activeRequest.abort();
                }
                activeRequest = new AbortController();
                const request = activeRequest;
                const activePanel = root.querySelector('[data-logbook-panel]:not([hidden])');
                const activePanelId = activePanel ? activePanel.id : null;
                const syncUrl = new URL(root.dataset.liveSyncUrl, window.location.href);
                params.delete('signature');
                syncUrl.search = params.toString();
                root.setAttribute('aria-busy', 'true');
                if (status) {
                    status.hidden = true;
                    status.textContent = '';
                }

                try {
                    const response = await fetch(syncUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        signal: request.signal
                    });
                    if (!response.ok) {
                        throw new Error('Unable to update logbook records.');
                    }

                    const data = await response.json();
                    if (data.changed !== true || typeof data.html !== 'string') {
                        throw new Error(data.error || 'Unable to update logbook records.');
                    }

                    root.innerHTML = data.html;
                    root.dataset.liveSignature = data.signature || '';
                    root.dataset.liveSyncUrl = syncUrl.toString();

                    const nextPanel = activePanelId ? root.querySelector('#' + activePanelId) : null;
                    if (nextPanel) {
                        nextPanel.hidden = false;
                        root.querySelectorAll('[data-logbook-pagination]').forEach(function (pagination) {
                            pagination.hidden = pagination.getAttribute('data-logbook-pagination') !== activePanelId;
                        });
                        const tab = root.querySelector('[data-logbook-tab="' + activePanelId + '"]');
                        if (tab) {
                            tab.setAttribute('aria-expanded', 'true');
                            tab.classList.add('is-active');
                        }
                    }

                    const pageUrl = new URL(window.location.href);
                    pageUrl.search = params.toString();
                    window.history.pushState({}, '', pageUrl.toString());
                } catch (error) {
                    if (error.name !== 'AbortError' && status) {
                        status.textContent = error.message || 'Unable to update logbook records.';
                        status.hidden = false;
                    }
                } finally {
                    if (activeRequest === request) {
                        activeRequest = null;
                        root.removeAttribute('aria-busy');
                    }
                }
            }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-logbook-tab]');
            if (button && root && root.contains(button)) {
                const targetId = button.getAttribute('data-logbook-tab');
                const target = root.querySelector('#' + targetId);
                if (!target) {
                    return;
                }

                root.querySelectorAll('[data-logbook-panel]').forEach(function (panel) {
                    panel.hidden = true;
                });
                root.querySelectorAll('[data-logbook-pagination]').forEach(function (pagination) {
                    pagination.hidden = true;
                });
                root.querySelectorAll('[data-logbook-tab]').forEach(function (tab) {
                    tab.setAttribute('aria-expanded', 'false');
                    tab.classList.remove('is-active');
                });

                target.hidden = false;
                const pagination = root.querySelector('[data-logbook-pagination="' + targetId + '"]');
                if (pagination) {
                    pagination.hidden = false;
                }
                button.setAttribute('aria-expanded', 'true');
                button.classList.add('is-active');
                const params = new URLSearchParams(window.location.search);
                if (targetId === 'employee-logbook-accounts') {
                    params.delete('accounts_page');
                    params.set('tab', 'accounts');
                } else {
                    params.delete('legacy_page');
                    params.delete('entries_page');
                    params.set('tab', 'transactions');
                }
                params.set('search', params.get('search') || '');
                if (params.get('search') === '') {
                    params.delete('search');
                }
                params.set('per_page', params.get('per_page') || '10');
                refreshLogbook(params);
                return;
            }

            const pageLink = event.target.closest('#employee-logbooks-live .pagination-shell a');
            if (!pageLink || !root) {
                return;
            }

            event.preventDefault();
            refreshLogbook(new URL(pageLink.href, window.location.href).searchParams);
        });

        document.addEventListener('change', function (event) {
            const select = event.target.closest('#employee-logbooks-live [data-logbook-page-size]');
            if (!select || !root) {
                return;
            }

            const params = new URLSearchParams(window.location.search);
            params.set('per_page', select.value);
            params.delete('accounts_per_page');
            params.delete('legacy_per_page');
            params.delete('accounts_page');
            params.delete('legacy_page');
            params.delete('entries_page');
            refreshLogbook(params);
        });
        })();
    </script>

@endsection
