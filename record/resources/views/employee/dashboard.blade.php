@extends('layouts.employee', ['title' => 'Dashboard'])

@section('content')
    <style>
        .me-hero { margin-bottom: 16px; }
        .me-kicker {
            margin: 0 0 4px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--c-primary-text);
        }
        .me-hero h1 { margin: 0; font-size: 26px; letter-spacing: -.03em; }
        .me-hero p { margin: 6px 0 0; color: var(--c-text-secondary); font-size: 14px; }
        .me-today {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .me-card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 16px;
            box-shadow: var(--shadow-xs);
            min-width: 0;
        }
        .me-notification-card {
            position: relative;
            display: block;
            width: 100%;
            color: var(--c-text);
            text-align: left;
            cursor: pointer;
            font: inherit;
        }
        .me-notification-card:hover { border-color: var(--c-primary); }
        .me-card .me-notification-topline { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .me-card .me-notification-icon {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
            color: var(--c-primary-text);
        }
        .me-notification-unread {
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 11px;
            font-weight: 750;
            line-height: 22px;
            text-align: center;
        }
        .me-notification-unread[hidden] { display: none; }
        .me-notification-count { margin-top: 16px; }
        .me-notification-hint { display: block; margin-top: 4px; font-size: 12px; color: var(--c-text-muted); }
        .me-notification-modal[hidden] { display: none; }
        .me-notification-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: grid;
            place-items: center;
            padding: 16px;
            background: rgba(15, 23, 42, .62);
        }
        .me-notification-dialog {
            display: flex;
            flex-direction: column;
            width: min(100%, 700px);
            max-height: min(82vh, 720px);
            overflow: hidden;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            box-shadow: var(--shadow-lg, 0 24px 60px rgba(0,0,0,.24));
        }
        .me-notification-dialog > header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--c-border);
        }
        .me-notification-dialog h2 { margin: 0; font-size: 17px; }
        .me-notification-dialog header p { margin: 4px 0 0; color: var(--c-text-muted); font-size: 12px; }
        .me-notification-close {
            width: 34px;
            height: 34px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-sm);
            background: var(--c-surface);
            color: var(--c-text);
            cursor: pointer;
            font-size: 20px;
        }
        .me-notification-tools,
        .me-notification-pagination,
        .me-notification-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .me-notification-tools {
            justify-content: space-between;
            padding: 10px 20px;
            border-bottom: 1px solid var(--c-border);
            font-size: 12px;
        }
        .me-notification-tools label { display: inline-flex; align-items: center; gap: 7px; }
        .me-notification-pagination {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            padding: 10px 20px;
            border-top: 1px solid var(--c-border);
            font-size: 12px;
        }
        .me-notification-action,
        .me-notification-pagination button,
        .me-notification-bulk-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 11px;
            border: 1px solid #1e3a8a;
            border-radius: var(--radius-sm);
            background: #1e3a8a;
            color: #fff;
            font: inherit;
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }
        .me-notification-action:hover,
        .me-notification-pagination button:hover,
        .me-notification-bulk-delete:hover { border-color: #172554; background: #172554; }
        .me-notification-action--delete {
            border-color: #991b1b;
            background: #991b1b;
        }
        .me-notification-action--delete:hover {
            border-color: #7f1d1d;
            background: #7f1d1d;
        }
        .me-notification-bulk-delete {
            border-color: var(--c-border);
            background: var(--c-surface);
            color: var(--c-text-secondary);
        }
        .me-notification-bulk-delete:hover {
            border-color: var(--c-primary);
            background: var(--c-surface-2);
            color: var(--c-text);
        }
        .me-notification-pagination button:disabled,
        .me-notification-bulk-delete:disabled { opacity: .5; cursor: not-allowed; }
        .me-notification-pagination button:first-child { justify-self: start; }
        .me-notification-pagination button:last-child { justify-self: end; }
        .me-notification-title-link { color: inherit; text-decoration: none; }
        .me-notification-title-link:hover { color: var(--c-primary-text); text-decoration: underline; }
        .me-notification-select { margin: 0; }
        .me-notification-page-status { color: var(--c-text-muted); }
        .me-notification-list { overflow-y: auto; padding: 4px 20px; }
        .me-notification-item {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--c-border);
        }
        .me-notification-item[data-view-url] { cursor: pointer; }
        .me-notification-item:last-child { border-bottom: 0; }
        .me-notification-copy { min-width: 0; }
        .me-notification-item header {
            display: flex;
            align-items: baseline;
            justify-content: flex-start;
            gap: 8px 12px;
            padding: 0;
            border: 0;
        }
        .me-notification-item strong { min-width: 0; font-size: 13px; }
        .me-notification-item time { margin-left: auto; color: var(--c-text-muted); font-size: 11px; white-space: nowrap; }
        .me-notification-item p { margin: 4px 0 0; color: var(--c-text-secondary); font-size: 12px; line-height: 1.45; }
        .me-notification-actions { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .me-notification-empty { padding: 24px 0; color: var(--c-text-muted); font-size: 13px; text-align: center; }
        .me-notification-error { margin: 0; padding: 0 20px 16px; color: var(--c-error-text); font-size: 12px; }
        @media (max-width: 560px) {
            .me-notification-modal { padding: 8px; }
            .me-notification-dialog { max-height: 90vh; }
            .me-notification-dialog > header { padding: 14px 16px; }
            .me-notification-tools,
            .me-notification-pagination { padding-right: 16px; padding-left: 16px; }
            .me-notification-list { padding-right: 16px; padding-left: 16px; }
            .me-notification-item { grid-template-columns: 18px minmax(0, 1fr); gap: 8px; }
            .me-notification-actions { grid-column: 2; justify-self: start; }
            .me-notification-action { min-height: 28px; padding-right: 9px; padding-left: 9px; }
        }
        .me-card h2 { margin: 0 0 8px; font-size: 13px; color: var(--c-text-secondary); font-weight: 650; }
        .me-card b {
            display: block;
            font-family: 'DM Mono', ui-monospace, monospace;
            font-size: 22px;
            letter-spacing: -.03em;
        }
        .me-card span { display: block; margin-top: 4px; font-size: 12px; color: var(--c-text-muted); }
        .me-status { font-family: inherit; font-size: 22px; font-weight: 750; letter-spacing: -.03em; }
        .me-status--in { color: var(--c-success-text); }
        .me-status--late { color: #9a3412; }
        .me-status--out { color: var(--c-text); }
        .me-table-card { padding: 0; overflow: hidden; }
        .me-table-card header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: baseline;
            padding: 14px 16px 0;
        }
        .me-table-card h2 { margin: 0; font-size: 15px; }
        .me-table-card a { font-size: 13px; font-weight: 650; color: var(--c-primary-text); text-decoration: none; }
        .me-week-pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid var(--c-border);
        }
        .me-week-pagination a,
        .me-week-pagination span {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-sm);
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
        }
        .me-week-pagination .is-active {
            border-color: var(--c-primary);
            background: var(--c-primary);
            color: var(--c-primary-contrast, #fff);
        }
        html[data-theme="dark"] .me-status--late { color: #fdba74; }
        @media (max-width: 800px) {
            .me-today { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .me-today { grid-template-columns: 1fr; }
        }
    </style>

    <header class="me-hero">
        <p class="me-kicker">{{ now()->format('l, F j') }}</p>
        <h1>Hello, {{ auth()->user()->name }}</h1>
    </header>

    <div
        id="employee-dashboard-live"
        data-live-sync-url="{{ route('employee.dashboard.live-sync', ['week_month' => $weekMonth, 'week_page' => $weekPage]) }}"
        data-live-signature="{{ $liveSignature }}"
        data-notifications-read-url="{{ route('employee.dashboard.notifications.read') }}"
        data-notifications-url="{{ route('employee.dashboard.notifications') }}"
        data-notification-delete-url="{{ route('employee.dashboard.notifications.delete', ['notice' => '__NOTICE__']) }}"
        data-notifications-bulk-delete-url="{{ route('employee.dashboard.notifications.bulk-delete') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-notification-count="{{ $notificationPayload['total'] }}"
        data-notification-unread-count="{{ $unreadNoticeCount }}"
    >
        @include('employee.dashboard.partials.live-region')
    </div>

    <div class="me-notification-modal" id="employee-notification-modal" hidden>
        <section class="me-notification-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-notification-title" tabindex="-1">
            <header>
                <div>
                    <h2 id="employee-notification-title">Notifications &amp; Activity</h2>
                    <p>Payroll updates and account alerts</p>
                </div>
                <button class="me-notification-close" type="button" data-notification-close aria-label="Close notifications">&times;</button>
            </header>
            <div class="me-notification-tools">
                <label><input id="employee-notification-select-all" type="checkbox"> Select all on this page</label>
                <button class="me-notification-bulk-delete" id="employee-notification-bulk-delete" type="button" disabled>Delete selected</button>
            </div>
            <div class="me-notification-list" id="employee-notification-list" aria-live="polite">
                @forelse ($notificationPayload['notifications'] as $notification)
                    <article class="me-notification-item" data-notification-id="{{ $notification['id'] }}" @if ($notification['view_url']) data-view-url="{{ $notification['view_url'] }}" @endif>
                        <label><input class="me-notification-select" type="checkbox" value="{{ $notification['id'] }}" aria-label="Select notification"></label>
                        <div class="me-notification-copy">
                            <header>
                                <strong>
                                    @if ($notification['view_url'])
                                        <a class="me-notification-title-link" href="{{ $notification['view_url'] }}">{{ $notification['title'] }}</a>
                                    @else
                                        {{ $notification['title'] }}
                                    @endif
                                </strong>
                                <time>{{ $notification['created_at'] }}</time>
                            </header>
                            <p>{{ $notification['body'] }}</p>
                        </div>
                        <div class="me-notification-actions">
                            @if ($notification['view_url'])
                                <a class="me-notification-action" href="{{ $notification['view_url'] }}">View</a>
                                <a class="me-notification-action" href="{{ $notification['print_url'] }}" target="_blank" rel="noopener">Print</a>
                            @endif
                            <button class="me-notification-action me-notification-action--delete" type="button" data-notification-delete>Delete</button>
                        </div>
                    </article>
                @empty
                    <p class="me-notification-empty">No notifications yet.</p>
                @endforelse
            </div>
            <nav class="me-notification-pagination" aria-label="Notification pages">
                <button type="button" id="employee-notification-previous" disabled>Previous</button>
                <span class="me-notification-page-status" id="employee-notification-page-status">Page {{ $notificationPayload['current_page'] }} of {{ $notificationPayload['last_page'] }}</span>
                <button type="button" id="employee-notification-next" @disabled($notificationPayload['current_page'] >= $notificationPayload['last_page'])>Next</button>
            </nav>
            <p class="me-notification-error" id="employee-notification-error" role="alert" hidden></p>
        </section>
    </div>

    <script>
        (function () {
            var root = document.getElementById('employee-dashboard-live');
            var modal = document.getElementById('employee-notification-modal');
            var dialog = modal && modal.querySelector('[role="dialog"]');
            var list = document.getElementById('employee-notification-list');
            var error = document.getElementById('employee-notification-error');
            var selectAll = document.getElementById('employee-notification-select-all');
            var bulkDelete = document.getElementById('employee-notification-bulk-delete');
            var previousPage = document.getElementById('employee-notification-previous');
            var nextPage = document.getElementById('employee-notification-next');
            var pageStatus = document.getElementById('employee-notification-page-status');
            var opener = document.querySelector('[data-notification-open]');
            var badge = document.querySelector('[data-notification-unread]');
            var summary = document.querySelector('[data-notification-summary]');
            var previousFocus = null;
            if (!root || !modal || !dialog || !list || !opener || !badge || !summary || !selectAll || !bulkDelete || !previousPage || !nextPage || !pageStatus) {
                return;
            }

            var pageMatch = pageStatus.textContent.match(/Page\s+(\d+)\s+of\s+(\d+)/);
            var currentPage = pageMatch ? Number(pageMatch[1]) : 1;
            var lastPage = pageMatch ? Number(pageMatch[2]) : 1;

            function updateSelectionState() {
                var checkboxes = Array.from(list.querySelectorAll('.me-notification-select'));
                var checkedCount = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                bulkDelete.disabled = checkedCount === 0;
            }

            function renderNotifications(payload) {
                var notifications = payload.notifications || [];
                list.replaceChildren();
                if (!notifications.length) {
                    var empty = document.createElement('p');
                    empty.className = 'me-notification-empty';
                    empty.textContent = 'No notifications yet.';
                    list.appendChild(empty);
                } else {
                    notifications.forEach(function (notification) {
                        var item = document.createElement('article');
                        var heading = document.createElement('header');
                        var selectLabel = document.createElement('label');
                        var checkbox = document.createElement('input');
                        var copy = document.createElement('div');
                        var title = document.createElement('strong');
                        var time = document.createElement('time');
                        var body = document.createElement('p');
                        var actions = document.createElement('div');
                        var remove = document.createElement('button');
                        item.className = 'me-notification-item';
                        item.dataset.notificationId = notification.id;
                        if (notification.view_url) {
                            item.dataset.viewUrl = notification.view_url;
                        }
                        title.textContent = notification.title;
                        if (notification.view_url) {
                            var titleLink = document.createElement('a');
                            titleLink.className = 'me-notification-title-link';
                            titleLink.href = notification.view_url;
                            titleLink.textContent = notification.title;
                            title.replaceChildren(titleLink);
                        }
                        checkbox.type = 'checkbox';
                        checkbox.className = 'me-notification-select';
                        checkbox.value = notification.id;
                        checkbox.setAttribute('aria-label', 'Select notification');
                        selectLabel.appendChild(checkbox);
                        copy.className = 'me-notification-copy';
                        time.textContent = notification.created_at || '';
                        body.textContent = notification.body;
                        heading.append(title, time);
                        copy.append(heading, body);
                        actions.className = 'me-notification-actions';
                        if (notification.view_url && notification.print_url) {
                            var view = document.createElement('a');
                            view.className = 'me-notification-action';
                            view.href = notification.view_url;
                            view.textContent = 'View';
                            var print = document.createElement('a');
                            print.className = 'me-notification-action';
                            print.href = notification.print_url;
                            print.target = '_blank';
                            print.rel = 'noopener';
                            print.textContent = 'Print';
                            actions.append(view, print);
                        }
                        remove.type = 'button';
                        remove.className = 'me-notification-action me-notification-action--delete';
                        remove.dataset.notificationDelete = '';
                        remove.textContent = 'Delete';
                        actions.appendChild(remove);
                        item.append(selectLabel, copy, actions);
                        list.appendChild(item);
                    });
                }

                var unread = Number(payload.unread_count) || 0;
                badge.textContent = unread > 99 ? '99+' : String(unread);
                badge.hidden = unread === 0;
                opener.setAttribute('aria-label', 'Notifications and activity, ' + unread + ' unread');
                summary.textContent = payload.total + (payload.total === 1 ? ' notification' : ' notifications');
                root.setAttribute('data-notification-count', String(payload.total));
                root.setAttribute('data-notification-unread-count', String(unread));
                currentPage = Number(payload.current_page) || 1;
                lastPage = Number(payload.last_page) || 1;
                pageStatus.textContent = 'Page ' + currentPage + ' of ' + lastPage;
                previousPage.disabled = currentPage <= 1;
                nextPage.disabled = currentPage >= lastPage;
                selectAll.checked = false;
                selectAll.indeterminate = false;
                updateSelectionState();
            }

            async function requestJson(url, options) {
                var requestOptions = Object.assign({
                    credentials: 'same-origin'
                }, options || {});
                requestOptions.headers = Object.assign({
                    'X-CSRF-TOKEN': root.dataset.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }, (options && options.headers) || {});
                var response = await fetch(url, requestOptions);
                var payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || payload.error || 'Unable to update notifications.');
                }
                return payload;
            }

            async function loadPage(page) {
                error.hidden = true;
                try {
                    var payload = await requestJson(root.dataset.notificationsUrl + '?page=' + encodeURIComponent(page));
                    renderNotifications(payload);
                } catch (requestError) {
                    error.textContent = requestError.message || 'Unable to load notifications.';
                    error.hidden = false;
                }
            }

            function closeModal() {
                modal.hidden = true;
                if (previousFocus) {
                    previousFocus.focus();
                }
            }

            opener.addEventListener('click', async function () {
                previousFocus = document.activeElement;
                modal.hidden = false;
                error.hidden = true;
                dialog.focus();
                try {
                    var payload = await requestJson(root.dataset.notificationsReadUrl, { method: 'POST' });
                    renderNotifications(payload);
                } catch (requestError) {
                    error.textContent = requestError.message || 'Unable to update notification status.';
                    error.hidden = false;
                }
            });

            selectAll.addEventListener('change', function () {
                list.querySelectorAll('.me-notification-select').forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateSelectionState();
            });

            list.addEventListener('change', function (event) {
                if (event.target.matches('.me-notification-select')) {
                    updateSelectionState();
                }
            });

            list.addEventListener('click', async function (event) {
                var deleteButton = event.target.closest('[data-notification-delete]');
                if (!deleteButton) {
                    var clickableItem = event.target.closest('[data-notification-id][data-view-url]');
                    if (clickableItem && !event.target.closest('a, button, input, label')) {
                        window.location.assign(clickableItem.dataset.viewUrl);
                    }
                    return;
                }
                var item = deleteButton.closest('[data-notification-id]');
                if (!item) {
                    return;
                }
                deleteButton.disabled = true;
                try {
                    var url = root.dataset.notificationDeleteUrl.replace('__NOTICE__', encodeURIComponent(item.dataset.notificationId)) + '?page=' + currentPage;
                    renderNotifications(await requestJson(url, { method: 'DELETE' }));
                    error.hidden = true;
                } catch (requestError) {
                    deleteButton.disabled = false;
                    error.textContent = requestError.message || 'Unable to delete notification.';
                    error.hidden = false;
                }
            });

            bulkDelete.addEventListener('click', async function () {
                var ids = Array.from(list.querySelectorAll('.me-notification-select:checked')).map(function (checkbox) {
                    return Number(checkbox.value);
                });
                if (!ids.length) {
                    return;
                }
                bulkDelete.disabled = true;
                try {
                    var payload = await requestJson(root.dataset.notificationsBulkDeleteUrl, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: ids, page: currentPage })
                    });
                    renderNotifications(payload);
                    error.hidden = true;
                } catch (requestError) {
                    bulkDelete.disabled = false;
                    error.textContent = requestError.message || 'Unable to delete notifications.';
                    error.hidden = false;
                }
            });

            previousPage.addEventListener('click', function () {
                if (currentPage > 1) {
                    loadPage(currentPage - 1);
                }
            });
            nextPage.addEventListener('click', function () {
                if (currentPage < lastPage) {
                    loadPage(currentPage + 1);
                }
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.closest('[data-notification-close]')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });

            window.addEventListener('employee-notifications-updated', function (event) {
                if (event.detail && Array.isArray(event.detail.notifications) && modal.hidden) {
                    badge.textContent = event.detail.unread_count > 99 ? '99+' : String(event.detail.unread_count || 0);
                    badge.hidden = !event.detail.unread_count;
                    summary.textContent = event.detail.total + (event.detail.total === 1 ? ' notification' : ' notifications');
                    root.setAttribute('data-notification-count', String(event.detail.total));
                    root.setAttribute('data-notification-unread-count', String(event.detail.unread_count || 0));
                }
            });
        }());
    </script>
@endsection
