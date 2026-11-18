@extends('layouts.admin', ['title' => $pageTitle ?? 'Logbook'])

@section('content')

    <style>
        #logbook-export-modal.account-edit-modal[hidden] { display: none; }
        #logbook-export-modal.account-edit-modal {
            position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center;
            padding: 20px; background: rgba(15, 23, 42, .55);
        }
        #logbook-export-modal .account-edit-dialog {
            position: relative; width: min(520px, 100%); max-height: 90vh; overflow-y: auto;
            padding: 24px; border-radius: var(--radius-xl); background: var(--c-surface);
            border: 1px solid var(--c-border); box-shadow: var(--shadow-lg);
        }
    </style>

    <div class="action-bar">
        <form id="logbook-search-form" method="GET" action="{{ $logbooksListUrl ?? route('admin.logbooks.index') }}" style="display:flex;gap:10px;align-items:center;flex:1;">
            <div style="position:relative;flex:1;max-width:360px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                     style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--c-text-muted);pointer-events:none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input class="form-control" type="text" name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Search name, classification, address, contact, or purpose…"
                       aria-label="Search logbook records"
                       style="padding-left:36px;">
            </div>
            <button id="logbook-clear-search" class="btn btn-danger" type="button">Clear</button>
        </form>

        <form id="delete-selected-logbooks-form" method="POST" data-no-ajax
              action="{{ route('admin.logbooks.bulk-destroy') }}"
              onsubmit="return confirm('Delete all selected logbook entries? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit" disabled>Delete Selected</button>
        </form>

        <a class="btn btn-primary" href="{{ route('admin.logbooks.create') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Entry
        </a>

        <button class="btn" type="button" id="btn-download-format">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-4.5-6L12 15m0 0-4.5-4.5M12 15V3" />
            </svg>
            Download Format
        </button>

        <button class="btn" type="button" id="btn-import-logbook">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3m0 0L7.5 7.5M12 3l4.5 4.5M3 15.75V18a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-2.25" />
            </svg>
            Import
        </button>

        <button class="btn" type="button" id="btn-export-logbook">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5V21m0 0 4.5-4.5M12 21l-4.5-4.5M3 8.25V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v2.25" />
            </svg>
            Export
        </button>

        <button class="btn" type="button" id="btn-print-logbook" disabled>Print All Filtered</button>

        <input id="logbook-import-file" type="file" accept=".xlsx,.csv" style="display:none;">
    </div>

    <div id="logbook-transfer-feedback" style="display:none;padding:12px 14px;border-radius:var(--radius-md);margin-bottom:14px;font-size:13px;font-weight:600;"></div>

    <div class="card" style="margin-bottom:14px;padding:12px 14px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <label class="form-label" style="margin:0;">
                From Date
                <input id="logbook-from-date" class="form-control" type="date" value="{{ $fromDate ?? '' }}" style="margin-top:5px;min-width:170px;">
            </label>
            <label class="form-label" style="margin:0;">
                To Date
                <input id="logbook-to-date" class="form-control" type="date" value="{{ $toDate ?? '' }}" style="margin-top:5px;min-width:170px;">
            </label>
            <button id="logbook-clear-dates" class="btn btn-danger" type="button">Clear Dates</button>
        </div>
    </div>

    <div id="logbooks-live-region" data-live-signature="{{ $liveSignature ?? '' }}">
        @include('admin.logbooks.partials.live-region')
    </div>

    <div id="logbook-export-modal" class="account-edit-modal" hidden>
        <section class="account-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="logbook-export-title">
            <button type="button" id="close-logbook-export-modal" style="position:absolute;top:14px;right:14px;" class="btn btn-sm btn-danger">Close</button>
            <h2 id="logbook-export-title" style="margin-bottom:16px;">Export Logbook Records</h2>
            <div style="display:grid;gap:12px;">
                <label class="form-label">Export Format
                    <select id="logbook-export-format" class="form-control">
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                </label>
                <button id="logbook-export-download" class="btn btn-primary" type="button">Download Export</button>
            </div>
        </section>
    </div>

    {{-- User Overlay Modal (redesigned) --}}
    <div id="user-overlay" style="display:none;position:fixed;inset:0;background:var(--c-modal-backdrop);backdrop-filter:blur(4px);z-index:999;align-items:center;justify-content:center;padding:20px;">
        <div class="logbook-user-modal-panel" style="background:var(--c-surface);border-radius:var(--radius-xl);padding:28px;max-width:480px;width:100%;box-shadow:var(--shadow-lg);position:relative;animation:modalIn .2s ease both;">
            <button type="button" onclick="closeUserOverlay()"
                    style="position:absolute;top:16px;right:16px;width:30px;height:30px;border-radius:50%;border:1px solid var(--c-border);background:var(--c-surface-2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--c-text-secondary);transition:background .15s;">✕</button>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;" id="overlay-avatar">?</div>
                <div>
                    <h3 style="font-size:16px;font-weight:700;margin:0;" id="overlay-name">—</h3>
                    <p style="font-size:13px;color:var(--c-text-muted);margin:2px 0 0;">Logbook User Details</p>
                </div>
            </div>

            <div style="display:grid;gap:10px;margin-bottom:20px;">
                <div style="background:var(--c-surface-2);border:1px solid var(--c-border);border-radius:var(--radius-md);padding:12px 14px;">
                    <div style="font-size:11px;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;">Address</div>
                    <div style="font-size:14px;font-weight:500;" id="overlay-address">—</div>
                </div>
                <div style="background:var(--c-surface-2);border:1px solid var(--c-border);border-radius:var(--radius-md);padding:12px 14px;">
                    <div style="font-size:11px;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;">Contact Number</div>
                    <div style="font-size:14px;font-weight:500;" id="overlay-contact">—</div>
                </div>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a id="overlay-edit-user" class="btn btn-primary" href="#" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                    Edit User
                </a>
                <form id="overlay-delete-user-form" method="POST" action="#">
                    @csrf @method('DELETE')
                    <button id="overlay-delete-user-button" class="btn btn-danger"
                            onclick="return confirm('Delete this user and all related logbook history?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Delete This User
                    </button>
                </form>
            </div>

            <p id="overlay-no-linked-user" style="display:none;margin-top:12px;font-size:13px;color:var(--c-text-muted);">
                No linked user account found for this logbook row.
            </p>
        </div>
    </div>

    <style>
        @keyframes modalIn {
            from { opacity:0; transform:scale(.95) translateY(8px); }
            to   { opacity:1; transform:scale(1)  translateY(0); }
        }
        #user-overlay.show { display:flex; }
    </style>

    @vite('resources/js/logbook-xlsx.js')
    <script>
        (function () {
            const liveRegion = document.getElementById('logbooks-live-region');
            if (!liveRegion) {
                return;
            }

            const liveSyncUrl = @json(route('admin.logbooks.live-sync'));
            const searchInput = document.querySelector('#logbook-search-form input[name="search"]');
            const searchForm = document.getElementById('logbook-search-form');
            let liveSignature = liveRegion.getAttribute('data-live-signature') || '';
            let refreshSequence = 0;
            let searchDebounceTimer = null;
            const feedbackBox = document.getElementById('logbook-transfer-feedback');
            const downloadBtn = document.getElementById('btn-download-format');
            const importBtn = document.getElementById('btn-import-logbook');
            const exportBtn = document.getElementById('btn-export-logbook');
            const printBtn = document.getElementById('btn-print-logbook');
            const importFileInput = document.getElementById('logbook-import-file');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function updatePrintButton() {
                const selectedCount = liveRegion.querySelectorAll('.logbook-entry-select:checked').length;
                if (!printBtn) return;
                printBtn.textContent = selectedCount > 0 ? `Print Selected (${selectedCount})` : 'Print All Filtered';
                printBtn.disabled = selectedCount === 0 && (searchInput?.value.trim().length || 0) === 0;
            }

            function updateBulkDeleteButton() {
                const selectedCount = liveRegion.querySelectorAll('.logbook-entry-select:checked').length;
                const button = document.querySelector('#delete-selected-logbooks-form button[type="submit"]');
                if (button) {
                    button.disabled = selectedCount === 0;
                }
                updatePrintButton();
            }

            document.addEventListener('change', function (event) {
                if (event.target.matches('#logbook-per-page')) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('per_page', event.target.value);
                    currentUrl.searchParams.set('page', '1');
                    window.history.pushState({}, '', currentUrl);
                    refreshLogbooksWithAjax({ resetPage: true, updateUrl: false });
                    return;
                }

                if (event.target.matches('#logbook-select-all')) {
                    liveRegion.querySelectorAll('.logbook-entry-select').forEach((checkbox) => {
                        checkbox.checked = event.target.checked;
                    });
                    updateBulkDeleteButton();
                    return;
                }

                if (event.target.matches('.logbook-entry-select')) {
                    const checkboxes = Array.from(liveRegion.querySelectorAll('.logbook-entry-select'));
                    const selectAll = liveRegion.querySelector('#logbook-select-all');
                    if (selectAll) {
                        selectAll.checked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
                    }
                    updateBulkDeleteButton();
                }
            });

            updateBulkDeleteButton();

            if (window.__logbookLiveRefreshTimer) {
                window.clearInterval(window.__logbookLiveRefreshTimer);
            }
            if (window.__logbookLiveRefreshAbortController) {
                window.__logbookLiveRefreshAbortController.abort();
                window.__logbookLiveRefreshAbortController = null;
            }

            async function refreshLogbooksWithAjax(options = {}) {
                if (window.__logbookLiveRefreshAbortController) {
                    window.__logbookLiveRefreshAbortController.abort();
                }

                const requestController = new AbortController();
                const requestSequence = ++refreshSequence;
                window.__logbookLiveRefreshAbortController = requestController;

                try {
                    const syncUrl = new URL(liveSyncUrl, window.location.origin);
                    const searchValue = searchInput ? searchInput.value.trim() : '';
                    const effectiveSearch = Array.from(searchValue).length >= 1 ? searchValue : '';
                    const currentUrl = new URL(window.location.href);
                    const knownFilters = new URLSearchParams(currentUrl.search);

                    if (options.updateUrl) {
                        if (effectiveSearch) {
                            currentUrl.searchParams.set('search', effectiveSearch);
                        } else {
                            currentUrl.searchParams.delete('search');
                        }
                        if (options.resetPage) {
                            currentUrl.searchParams.set('page', '1');
                        }
                        window.history.pushState({}, '', currentUrl);
                    }

                    if (liveSignature && !options.resetPage) {
                        syncUrl.searchParams.set('signature', liveSignature);
                    }
                    if (effectiveSearch !== '') {
                        syncUrl.searchParams.set('search', effectiveSearch);
                    } else {
                        syncUrl.searchParams.delete('search');
                    }
                    knownFilters.forEach((value, key) => {
                        if (!['page', 'per_page', 'search', 'signature'].includes(key)) {
                            syncUrl.searchParams.set(key, value);
                        }
                    });
                    const perPageSelector = liveRegion.querySelector('[name="per_page"]');
                    syncUrl.searchParams.set('page', options.resetPage ? '1' : (currentUrl.searchParams.get('page') || '1'));
                    syncUrl.searchParams.set('per_page', perPageSelector ? perPageSelector.value : '10');

                    if (options.resetPage) {
                        liveSignature = '';
                        liveRegion.setAttribute('data-live-signature', '');
                    }

                    const response = await fetch(syncUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        signal: requestController.signal
                    });

                    if (!response.ok) {
                        console.error('Logbook AJAX refresh failed with status:', response.status);
                        return;
                    }

                    const payload = await response.json();
                    if (requestSequence !== refreshSequence) {
                        return;
                    }
                    if (!payload || payload.changed !== true) {
                        if (payload && payload.signature) {
                            liveSignature = payload.signature;
                            liveRegion.setAttribute('data-live-signature', liveSignature);
                        }
                        return;
                    }

                    if (typeof payload.html === 'string') {
                        liveRegion.innerHTML = payload.html;
                        updateBulkDeleteButton();
                    }

                    if (payload.signature) {
                        liveSignature = payload.signature;
                        liveRegion.setAttribute('data-live-signature', liveSignature);
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    console.error('Logbook AJAX refresh failed:', error);
                } finally {
                    if (requestSequence === refreshSequence) {
                        window.__logbookLiveRefreshAbortController = null;
                    }
                }
            }

            searchForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                if (searchDebounceTimer !== null) {
                    window.clearTimeout(searchDebounceTimer);
                }
                refreshLogbooksWithAjax({ resetPage: true, updateUrl: true });
            });

            searchInput?.addEventListener('input', function () {
                updatePrintButton();
                if (searchDebounceTimer !== null) {
                    window.clearTimeout(searchDebounceTimer);
                }
                searchDebounceTimer = window.setTimeout(() => {
                    refreshLogbooksWithAjax({ resetPage: true, updateUrl: true });
                }, 400);
            });

            const clearSearchButton = document.getElementById('logbook-clear-search');
            clearSearchButton?.addEventListener('click', function () {
                if (searchDebounceTimer !== null) {
                    window.clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = null;
                }
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                liveSignature = '';
                liveRegion.setAttribute('data-live-signature', '');
                refreshLogbooksWithAjax({ resetPage: true, updateUrl: true });
            });

            const fromDateInput = document.getElementById('logbook-from-date');
            const toDateInput = document.getElementById('logbook-to-date');
            const clearDatesButton = document.getElementById('logbook-clear-dates');

            const applyDateFilters = () => {
                const currentUrl = new URL(window.location.href);
                if (fromDateInput?.value) {
                    currentUrl.searchParams.set('from_date', fromDateInput.value);
                } else {
                    currentUrl.searchParams.delete('from_date');
                }
                if (toDateInput?.value) {
                    currentUrl.searchParams.set('to_date', toDateInput.value);
                } else {
                    currentUrl.searchParams.delete('to_date');
                }
                currentUrl.searchParams.set('page', '1');
                window.history.pushState({}, '', currentUrl);
                liveSignature = '';
                liveRegion.setAttribute('data-live-signature', '');
                refreshLogbooksWithAjax({ resetPage: true, updateUrl: false });
            };

            fromDateInput?.addEventListener('change', applyDateFilters);
            toDateInput?.addEventListener('change', applyDateFilters);
            clearDatesButton?.addEventListener('click', () => {
                if (fromDateInput) fromDateInput.value = '';
                if (toDateInput) toDateInput.value = '';
                applyDateFilters();
            });

            liveRegion.addEventListener('click', function (event) {
                const link = event.target.closest('.logbook-page-nav a');
                if (!link) {
                    return;
                }

                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const currentParams = new URLSearchParams(window.location.search);
                const page = targetUrl.searchParams.get('page');
                const currentUrl = new URL(window.location.href);

                if (page) {
                    currentUrl.searchParams.set('page', page);
                }
                if (currentParams.get('per_page')) {
                    currentUrl.searchParams.set('per_page', currentParams.get('per_page'));
                } else {
                    currentUrl.searchParams.set('per_page', '10');
                }
                window.history.pushState({}, '', currentUrl);
                refreshLogbooksWithAjax({ resetPage: false });
            });

            window.addEventListener('popstate', function () {
                const currentUrl = new URL(window.location.href);
                if (searchInput) {
                    searchInput.value = currentUrl.searchParams.get('search') || '';
                }
                refreshLogbooksWithAjax({ resetPage: true });
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

            printBtn?.addEventListener('click', function () {
                const printUrl = new URL(@json(route('admin.barangay-logbook.print-records')), window.location.origin);
                const selected = Array.from(liveRegion.querySelectorAll('.logbook-entry-select:checked'));
                if (selected.length > 0) {
                    selected.forEach((checkbox) => printUrl.searchParams.append('selected_ids[]', checkbox.value));
                } else {
                    printUrl.searchParams.set('search', searchInput?.value.trim() || '');
                    printUrl.searchParams.set('from_date', fromDateInput?.value || '');
                    printUrl.searchParams.set('to_date', toDateInput?.value || '');
                }
                window.open(printUrl.toString(), '_blank', 'noopener');
            });
            updatePrintButton();

            window.__logbookLiveRefreshTimer = window.setInterval(refreshLogbooksWithAjax, 5000);

            function setFeedback(message, type) {
                if (!feedbackBox) return;
                feedbackBox.textContent = message;
                feedbackBox.style.display = 'block';
                if (type === 'error') {
                    feedbackBox.style.background = 'var(--c-danger-soft, #fff1f2)';
                    feedbackBox.style.border = '1px solid #fda4af';
                    feedbackBox.style.color = '#9f1239';
                    return;
                }
                feedbackBox.style.background = '#eff6ff';
                feedbackBox.style.border = '1px solid #93c5fd';
                feedbackBox.style.color = '#1e40af';
            }

            function setLoading(button, active, idleLabel, loadingLabel) {
                if (!button) return;
                button.disabled = active;
                button.dataset.idleLabel = idleLabel;
                button.textContent = active ? loadingLabel : idleLabel;
            }

            async function downloadBlob(url, fallbackFilename) {
                const res = await fetch(url, { credentials: 'same-origin' });
                if (!res.ok) {
                    throw new Error('Request failed');
                }
                const blob = await res.blob();
                const disposition = res.headers.get('Content-Disposition') || '';
                let filename = fallbackFilename;
                const match = disposition.match(/filename="?([^"]+)"?/i);
                if (match && match[1]) {
                    filename = match[1];
                }
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(blobUrl);
            }

            async function parseExcelRows(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        try {
                            const workbook = XLSX.read(event.target.result, { type: 'array', cellDates: false });
                            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                            const rows = XLSX.utils.sheet_to_json(firstSheet, { defval: '', raw: true });
                            rows.forEach((row) => {
                                const timeKey = Object.prototype.hasOwnProperty.call(row, 'Time In')
                                    ? 'Time In'
                                    : (Object.prototype.hasOwnProperty.call(row, 'time_in') ? 'time_in' : null);
                                if (timeKey === null || typeof row[timeKey] !== 'number') {
                                    return;
                                }

                                const date = XLSX.SSF.parse_date_code(row[timeKey]);
                                if (!date) {
                                    throw new Error('A Time In value is not a valid Excel date.');
                                }

                                const parsedTime = new Date(Date.UTC(
                                    date.y,
                                    date.m - 1,
                                    date.d,
                                    date.H,
                                    date.M,
                                    Math.round(date.S),
                                ));
                                const pad = (part) => String(part).padStart(2, '0');
                                row[timeKey] = `${parsedTime.getUTCFullYear()}-${pad(parsedTime.getUTCMonth() + 1)}-${pad(parsedTime.getUTCDate())} ${pad(parsedTime.getUTCHours())}:${pad(parsedTime.getUTCMinutes())}:${pad(parsedTime.getUTCSeconds())}`;
                            });
                            resolve(rows);
                        } catch (error) {
                            reject(error);
                        }
                    };
                    reader.onerror = reject;
                    reader.readAsArrayBuffer(file);
                });
            }

            async function importLogbookFile(file) {
                const formData = new FormData();
                const lowerName = (file.name || '').toLowerCase();

                if (lowerName.endsWith('.xlsx')) {
                    const rows = await parseExcelRows(file);
                    formData.append('rows', JSON.stringify(rows));
                } else {
                    formData.append('file', file);
                }

                const res = await fetch(@json(route('admin.logbooks.import')), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                let payload = null;
                try {
                    payload = await res.json();
                } catch (error) {
                    payload = null;
                }

                if (!res.ok || !payload || payload.ok !== true) {
                    throw new Error(payload?.message || 'Import failed.');
                }

                return payload;
            }

            downloadBtn?.addEventListener('click', async function () {
                setLoading(downloadBtn, true, 'Download Format', 'Downloading...');
                try {
                    await downloadBlob(@json(route('admin.logbooks.download-format')), 'logbook_import_template.xlsx');
                    setFeedback('Template downloaded successfully.', 'ok');
                } catch (error) {
                    setFeedback('Unable to download template.', 'error');
                } finally {
                    setLoading(downloadBtn, false, 'Download Format', 'Downloading...');
                }
            });

            exportBtn?.addEventListener('click', async function () {
                document.getElementById('logbook-export-modal').hidden = false;
            });

            const exportModal = document.getElementById('logbook-export-modal');

            document.getElementById('close-logbook-export-modal')?.addEventListener('click', () => {
                exportModal.hidden = true;
            });
            exportModal?.addEventListener('click', (event) => {
                if (event.target === exportModal) exportModal.hidden = true;
            });

            document.getElementById('logbook-export-download')?.addEventListener('click', async function () {
                const format = document.getElementById('logbook-export-format').value;
                const exportUrl = new URL(@json(route('admin.logbooks.export')), window.location.origin);
                exportUrl.searchParams.set('format', format);
                exportUrl.searchParams.set('mode', 'all');
                exportUrl.searchParams.set('search', searchInput?.value.trim() || '');
                exportUrl.searchParams.set('from_date', fromDateInput?.value || '');
                exportUrl.searchParams.set('to_date', toDateInput?.value || '');

                try {
                    this.disabled = true;
                    await downloadBlob(exportUrl.toString(), `logbook_export.${format}`);
                    setFeedback('Logbook export downloaded.', 'ok');
                    exportModal.hidden = true;
                } catch (error) {
                    setFeedback('Unable to export logbook records.', 'error');
                } finally {
                    this.disabled = false;
                }
            });

            importBtn?.addEventListener('click', function () {
                importFileInput?.click();
            });

            importFileInput?.addEventListener('change', async function () {
                const file = this.files && this.files[0] ? this.files[0] : null;
                if (!file) {
                    return;
                }
                setLoading(importBtn, true, 'Import', 'Importing...');
                try {
                    const payload = await importLogbookFile(file);
                    setFeedback(payload.message || 'Import completed.', 'ok');
                    await refreshLogbooksWithAjax();
                } catch (error) {
                    setFeedback(error.message || 'Import failed.', 'error');
                } finally {
                    setLoading(importBtn, false, 'Import', 'Importing...');
                    importFileInput.value = '';
                }
            });
        })();

        function openUserOverlay(logbookId, name, address, contact, userId) {
            document.getElementById('overlay-name').textContent = name || '—';
            document.getElementById('overlay-address').textContent = address || '—';
            document.getElementById('overlay-contact').textContent = contact || '—';
            document.getElementById('overlay-avatar').textContent = (name || '?').charAt(0).toUpperCase();

            const editLink     = document.getElementById('overlay-edit-user');
            const deleteForm   = document.getElementById('overlay-delete-user-form');
            const deleteButton = document.getElementById('overlay-delete-user-button');
            const noLinked     = document.getElementById('overlay-no-linked-user');

            deleteForm.action = "{{ url('/admin/logbooks') }}/" + logbookId + "/user";

            if (userId && userId > 0) {
                editLink.href = "{{ url('/admin/employees') }}/" + userId + "/edit";
                editLink.style.display = 'inline-flex';
                deleteButton.disabled = false;
                noLinked.style.display = 'none';
            } else {
                editLink.style.display = 'none';
                deleteButton.disabled = false;
                noLinked.style.display = 'block';
            }

            const overlay = document.getElementById('user-overlay');
            overlay.classList.add('show');
        }

        function closeUserOverlay() {
            document.getElementById('user-overlay').classList.remove('show');
        }

        document.getElementById('user-overlay').addEventListener('click', function(e) {
            if (e.target === this) closeUserOverlay();
        });
    </script>

@endsection
