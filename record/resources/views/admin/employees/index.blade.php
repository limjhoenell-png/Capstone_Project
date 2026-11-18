@extends('layouts.admin', ['title' => 'Employees'])

@section('content')

    <div class="action-bar">
        <div class="action-bar-right">
            <form id="bulk-delete-employees-form" method="POST" data-no-ajax
                  action="{{ route('admin.employees.bulk-destroy') }}"
                  onsubmit="return confirm('Delete the selected employee accounts? This cannot be undone.')"
                  hidden>
                @csrf
                @method('DELETE')
                <button id="delete-selected-employees-button" class="btn btn-danger" type="submit">
                    Delete Selected
                </button>
            </form>
            <a class="btn btn-primary" href="{{ route('admin.employees.create') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Employee
            </a>
        </div>
    </div>

    <div id="employees-live-region" data-live-signature="{{ $liveSignature ?? '' }}">
        @include('admin.employees.partials.live-region')
    </div>

    <script>
        (function () {
            const liveRegion = document.getElementById('employees-live-region');
            if (!liveRegion) {
                return;
            }

            const liveSyncUrl = @json(route('admin.employees.live-sync'));
            let liveSignature = liveRegion.getAttribute('data-live-signature') || '';
            let isRefreshingEmployees = false;
            const selectedEmployeeIds = new Set();
            const bulkDeleteForm = document.getElementById('bulk-delete-employees-form');
            const bulkDeleteButton = document.getElementById('delete-selected-employees-button');

            function updateEmployeeSelection() {
                const checkboxes = Array.from(liveRegion.querySelectorAll('.employee-row-checkbox'))
                    .filter((checkbox) => !checkbox.disabled);
                const visibleIds = new Set(checkboxes.map((checkbox) => checkbox.value));
                selectedEmployeeIds.forEach((id) => {
                    if (!visibleIds.has(id)) {
                        selectedEmployeeIds.delete(id);
                    }
                });

                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectedEmployeeIds.has(checkbox.value);
                });

                const selectAll = liveRegion.querySelector('#select-all-employees');
                const selectedVisibleCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selectAll) {
                    selectAll.checked = checkboxes.length > 0 && selectedVisibleCount === checkboxes.length;
                    selectAll.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < checkboxes.length;
                }

                bulkDeleteForm.hidden = selectedVisibleCount === 0;
                bulkDeleteButton.textContent = selectedVisibleCount > 0
                    ? `Delete Selected (${selectedVisibleCount})`
                    : 'Delete Selected';
            }

            if (window.__employeesLiveRefreshTimer) {
                window.clearInterval(window.__employeesLiveRefreshTimer);
            }
            if (window.__employeesLiveRefreshAbortController) {
                window.__employeesLiveRefreshAbortController.abort();
                window.__employeesLiveRefreshAbortController = null;
            }

            function updateUrlState(params) {
                const url = new URL(window.location.href);
                url.search = '';
                params.forEach((value, key) => {
                    if (key !== 'employees_page' || value !== '1') {
                        url.searchParams.set(key, value);
                    }
                });
                window.history.pushState({}, '', url.pathname + url.search + url.hash);
            }

            async function loadEmployees(params, options = {}) {
                if (isRefreshingEmployees && !options.force) {
                    return;
                }

                isRefreshingEmployees = true;
                const requestController = new AbortController();
                window.__employeesLiveRefreshAbortController?.abort();
                window.__employeesLiveRefreshAbortController = requestController;

                try {
                    const syncUrl = new URL(liveSyncUrl, window.location.origin);
                    params.forEach((value, key) => syncUrl.searchParams.set(key, value));
                    if (options.includeSignature && liveSignature) {
                        syncUrl.searchParams.set('signature', liveSignature);
                    }

                    const response = await fetch(syncUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: requestController.signal
                    });

                    if (!response.ok) {
                        console.error('Employees AJAX refresh failed with status:', response.status);
                        return;
                    }

                    const payload = await response.json();
                    if (payload && payload.signature) {
                        liveSignature = payload.signature;
                        liveRegion.setAttribute('data-live-signature', liveSignature);
                    }
                    if (payload && payload.changed === true && typeof payload.html === 'string') {
                        liveRegion.innerHTML = payload.html;
                        updateEmployeeSelection();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Employees AJAX refresh failed:', error);
                    }
                } finally {
                    if (window.__employeesLiveRefreshAbortController === requestController) {
                        window.__employeesLiveRefreshAbortController = null;
                    }
                    isRefreshingEmployees = false;
                }
            }

            liveRegion.addEventListener('change', (event) => {
                if (event.target.matches('.employee-row-checkbox')) {
                    if (event.target.checked) {
                        selectedEmployeeIds.add(event.target.value);
                    } else {
                        selectedEmployeeIds.delete(event.target.value);
                    }
                    updateEmployeeSelection();
                    return;
                }

                if (event.target.matches('#select-all-employees')) {
                    const checkboxes = liveRegion.querySelectorAll('.employee-row-checkbox:not(:disabled)');
                    checkboxes.forEach((checkbox) => {
                        if (event.target.checked) {
                            selectedEmployeeIds.add(checkbox.value);
                        } else {
                            selectedEmployeeIds.delete(checkbox.value);
                        }
                    });
                    updateEmployeeSelection();
                    return;
                }

                if (!event.target.matches('#employees-per-page')) {
                    return;
                }

                selectedEmployeeIds.clear();
                updateEmployeeSelection();
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', event.target.value);
                params.delete('employees_page');
                params.set('employees_page', '1');
                updateUrlState(params);
                loadEmployees(params, { force: true });
            });

            liveRegion.addEventListener('click', (event) => {
                const link = event.target.closest('.employees-pagination a');
                if (!link) {
                    return;
                }

                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const params = new URLSearchParams(targetUrl.search);
                params.set('per_page', new URLSearchParams(window.location.search).get('per_page') || '10');
                selectedEmployeeIds.clear();
                updateEmployeeSelection();
                updateUrlState(params);
                loadEmployees(params, { force: true });
            });

            const initialParams = new URLSearchParams(window.location.search);
            if (!initialParams.has('per_page')) {
                const select = liveRegion.querySelector('#employees-per-page');
                if (select) {
                    initialParams.set('per_page', select.value || '10');
                    window.history.replaceState(
                        {},
                        '',
                        window.location.pathname + '?' + initialParams.toString() + window.location.hash
                    );
                }
            }

            async function refreshEmployeesWithAjax() {
                if (isRefreshingEmployees) {
                    return;
                }
                await loadEmployees(new URLSearchParams(window.location.search), { includeSignature: true });
            }

            updateEmployeeSelection();
            window.__employeesLiveRefreshTimer = window.setInterval(refreshEmployeesWithAjax, 5000);
        })();
    </script>

@endsection
