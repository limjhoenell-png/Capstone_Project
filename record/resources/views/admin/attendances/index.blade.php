@extends('layouts.admin', ['title' => 'Attendance'])

@section('content')

    <div class="action-bar">
        <div class="action-bar-right">
            <a class="btn btn-primary" href="{{ route('admin.attendances.create') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Attendance
            </a>
        </div>
    </div>

    <div id="attendance-live-region" class="attendance-live-region" data-live-signature="{{ $liveSignature ?? '' }}">
        @include('admin.attendances.partials.live-region')
    </div>

    <script>
        (function () {
            const attendanceLiveRegion = document.getElementById('attendance-live-region');
            if (!attendanceLiveRegion) {
                return;
            }
            const attendanceLiveSyncUrl = @json(route('admin.attendances.live-sync'));
            let liveSignature = attendanceLiveRegion.getAttribute('data-live-signature') || '';
            let isRefreshingAttendance = false;

            if (window.__attendanceLiveRefreshTimer) {
                window.clearInterval(window.__attendanceLiveRefreshTimer);
            }
            if (window.__attendanceLiveRefreshAbortController) {
                window.__attendanceLiveRefreshAbortController.abort();
                window.__attendanceLiveRefreshAbortController = null;
            }

            function updateUrlState(params) {
                const url = new URL(window.location.href);
                ['date', 'per_page', 'merged_page'].forEach((name) => url.searchParams.delete(name));

                if (params.get('date')) {
                    url.searchParams.set('date', params.get('date'));
                }
                if (params.get('per_page')) {
                    url.searchParams.set('per_page', params.get('per_page'));
                }
                if (params.get('merged_page') && params.get('merged_page') !== '1') {
                    url.searchParams.set('merged_page', params.get('merged_page'));
                }

                window.history.pushState({}, '', url.pathname + url.search + url.hash);
            }

            async function loadAttendance(params, options = {}) {
                if (isRefreshingAttendance && !options.force) {
                    return;
                }

                isRefreshingAttendance = true;
                const requestController = new AbortController();
                window.__attendanceLiveRefreshAbortController?.abort();
                window.__attendanceLiveRefreshAbortController = requestController;

                try {
                    const syncUrl = new URL(attendanceLiveSyncUrl, window.location.origin);
                    params.forEach((value, name) => syncUrl.searchParams.set(name, value));

                    if (options.includeSignature) {
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
                        console.error('Attendance AJAX refresh failed with status:', response.status);
                        return;
                    }

                    const payload = await response.json();
                    if (payload && payload.signature) {
                        liveSignature = payload.signature;
                        attendanceLiveRegion.setAttribute('data-live-signature', liveSignature);
                    }

                    if (payload && payload.changed === true && typeof payload.html === 'string') {
                        attendanceLiveRegion.innerHTML = payload.html;
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Attendance AJAX refresh failed:', error);
                    }
                } finally {
                    if (window.__attendanceLiveRefreshAbortController === requestController) {
                        window.__attendanceLiveRefreshAbortController = null;
                    }
                    isRefreshingAttendance = false;
                }
            }

            async function refreshAttendanceWithAjax() {
                if (isRefreshingAttendance) {
                    return;
                }
                const params = new URLSearchParams(window.location.search);
                await loadAttendance(params, { includeSignature: true });
            }

            attendanceLiveRegion.addEventListener('change', (event) => {
                const params = new URLSearchParams(window.location.search);
                params.delete('merged_page');
                params.set('merged_page', '1');

                if (event.target.matches('#attendance-per-page')) {
                    params.set('per_page', event.target.value);
                } else if (event.target.matches('#attendance-filter-date')) {
                    if (event.target.value) {
                        params.set('date', event.target.value);
                    } else {
                        params.delete('date');
                    }
                } else {
                    return;
                }

                updateUrlState(params);
                loadAttendance(params, { force: true });
            });

            attendanceLiveRegion.addEventListener('click', (event) => {
                const link = event.target.closest('.attendance-pagination a');
                if (!link) {
                    if (event.target.closest('.attendance-filter-clear')) {
                        event.preventDefault();
                        const params = new URLSearchParams(window.location.search);
                        params.delete('date');
                        params.delete('merged_page');
                        params.set('merged_page', '1');
                        updateUrlState(params);
                        loadAttendance(params, { force: true });
                    }
                    return;
                }

                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const params = new URLSearchParams(targetUrl.search);
                params.set('per_page', new URLSearchParams(window.location.search).get('per_page') || '10');
                updateUrlState(params);
                loadAttendance(params, { force: true });
            });

            const initialUrlParams = new URLSearchParams(window.location.search);
            if (!initialUrlParams.has('per_page')) {
                const perPageSelect = attendanceLiveRegion.querySelector('#attendance-per-page');
                if (perPageSelect) {
                    initialUrlParams.set('per_page', perPageSelect.value || '10');
                    window.history.replaceState({}, '', window.location.pathname + '?' + initialUrlParams.toString() + window.location.hash);
                }
            }

            window.__attendanceLiveRefreshTimer = window.setInterval(refreshAttendanceWithAjax, 3000);
        })();
    </script>

@endsection
