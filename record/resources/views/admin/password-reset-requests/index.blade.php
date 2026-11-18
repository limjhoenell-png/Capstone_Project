@extends('layouts.admin', ['title' => 'Password Reset Requests'])

@section('content')
    <style>
        .reset-requests { display: grid; gap: 16px; }
        .reset-request {
            display: grid;
            grid-template-columns: minmax(220px, .8fr) minmax(360px, 1.2fr);
            gap: 20px;
            padding: 20px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            box-shadow: var(--shadow-sm);
        }
        .reset-request__details { min-width: 0; }
        .reset-request__details p { margin: 7px 0 0; color: var(--c-text-secondary); font-size: 13px; overflow-wrap: anywhere; }
        .reset-request__details time { display: block; margin-top: 12px; color: var(--c-text-muted); font-size: 12px; }
        .reset-request__matched {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface-2);
            font-size: 13px;
        }
        .reset-request__matched strong { display: block; margin-bottom: 3px; }
        .reset-request__form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .reset-request__form .form-group { margin: 0; }
        .reset-password-field { position: relative; }
        .reset-password-field .form-control { padding-right: 44px; }
        .reset-password-visibility {
            position: absolute;
            top: 50%;
            right: 8px;
            display: grid;
            width: 30px;
            height: 30px;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--c-text-muted);
            cursor: pointer;
        }
        .reset-password-visibility:hover,
        .reset-password-visibility:focus-visible {
            background: var(--c-surface-2);
            color: var(--c-text);
        }
        .reset-password-visibility:focus-visible { outline: 2px solid var(--c-primary); }
        .reset-password-visibility svg { width: 18px; height: 18px; }
        .reset-password-visibility [hidden] { display: none; }
        .reset-password-field .form-control.is-password-mismatch {
            border-color: var(--c-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .14);
        }
        .password-match-error {
            grid-column: 1 / -1;
            margin: -4px 0 0;
            color: var(--c-error-text);
            font-size: 12px;
            font-weight: 600;
        }
        .password-match-error[hidden] { display: none; }
        .reset-request__form .form-group--wide,
        .reset-request__verify { grid-column: 1 / -1; }
        .reset-request__verify { display: flex; align-items: flex-start; gap: 9px; color: var(--c-text-secondary); font-size: 13px; line-height: 1.45; }
        .reset-request__verify input { margin-top: 3px; accent-color: var(--c-primary); }
        .reset-request__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .reset-requests__empty { padding: 26px; border: 1px dashed var(--c-border-strong); border-radius: var(--radius-lg); color: var(--c-text-secondary); text-align: center; }
        @media (max-width: 800px) {
            .reset-request { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .reset-request__form { grid-template-columns: 1fr; }
            .reset-request__form .form-group--wide,
            .reset-request__verify { grid-column: auto; }
        }
    </style>

    <section id="password-reset-requests-region" class="reset-requests">
        <header>
            <h1>Pending password reset requests</h1>
        </header>

        @forelse($requests as $resetRequest)
            <article class="reset-request">
                <div class="reset-request__details">
                    <p><strong>Submitted identifier:</strong> {{ $resetRequest->identifier }}</p>
                    <time datetime="{{ $resetRequest->created_at->toIso8601String() }}">Submitted {{ $resetRequest->created_at->format('M j, Y g:i A') }}</time>
                    @if($resetRequest->user)
                        <div class="reset-request__matched">
                            <strong>Possible account match</strong>
                            {{ $resetRequest->user->full_name ?: $resetRequest->user->name }}
                            <br>{{ $resetRequest->user->email }}
                            @if($resetRequest->user->employee_id)
                                <br>Employee ID: {{ $resetRequest->user->employee_id }}
                            @endif
                            <br>{{ ucfirst($resetRequest->user->role) }}
                        </div>
                    @else
                        <div class="reset-request__matched">No unique account match was found. Select the account only after confirming the requester's identity.</div>
                    @endif
                </div>

                <div>
                    <form id="resolve-password-reset-{{ $resetRequest->id }}" class="reset-request__form" method="POST" action="{{ route('admin.password-reset-requests.resolve', $resetRequest) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="reset_request_id" value="{{ $resetRequest->id }}">
                        <div class="form-group form-group--wide">
                            <label class="form-label" for="target-user-{{ $resetRequest->id }}">Account to reset</label>
                            <select class="form-control" id="target-user-{{ $resetRequest->id }}" name="target_user_id" required>
                                <option value="">Select a verified account</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('target_user_id') === $user->id && old('reset_request_id') == $resetRequest->id || old('target_user_id') === null && (int) $resetRequest->user_id === $user->id)>
                                        {{ $user->full_name ?: $user->name }} — {{ $user->email }} ({{ ucfirst($user->role) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reset-password-{{ $resetRequest->id }}">New password</label>
                            <div class="reset-password-field">
                                <input class="form-control" id="reset-password-{{ $resetRequest->id }}" name="password" type="password" minlength="6" autocomplete="new-password" required>
                                <button class="reset-password-visibility" type="button" aria-label="Show new password" aria-pressed="false" aria-controls="reset-password-{{ $resetRequest->id }}" data-toggle-password>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                        <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reset-password-confirmation-{{ $resetRequest->id }}">Confirm new password</label>
                            <div class="reset-password-field">
                                <input class="form-control" id="reset-password-confirmation-{{ $resetRequest->id }}" name="password_confirmation" type="password" minlength="6" autocomplete="new-password" aria-describedby="password-match-error-{{ $resetRequest->id }}" required>
                                <button class="reset-password-visibility" type="button" aria-label="Show password confirmation" aria-pressed="false" aria-controls="reset-password-confirmation-{{ $resetRequest->id }}" data-toggle-password>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                        <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="password-match-error" id="password-match-error-{{ $resetRequest->id }}" data-password-match-error role="alert" hidden>Passwords do not match.</p>
                        <label class="reset-request__verify">
                            <input type="checkbox" name="identity_verified" value="1" required>
                            <span>I have verified this person’s identity locally and confirmed the selected account.</span>
                        </label>
                    </form>
                    <div class="reset-request__actions">
                        <button class="btn btn-primary" type="submit" form="resolve-password-reset-{{ $resetRequest->id }}">Set new password</button>
                        <form method="POST" action="{{ route('admin.password-reset-requests.dismiss', $resetRequest) }}" onsubmit="return confirm('Dismiss this password reset request?');">
                            @csrf
                            <button class="btn" type="submit">Dismiss request</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="reset-requests__empty">There are no pending password reset requests.</div>
        @endforelse

        {{ $requests->links() }}
    </section>

    <script>
        (function () {
            if (window.__passwordResetRequestNavigationInitialized) {
                return;
            }
            window.__passwordResetRequestNavigationInitialized = true;

            function validatePasswordMatch(form) {
                const password = form.elements.namedItem('password');
                const confirmation = form.elements.namedItem('password_confirmation');
                const error = form.querySelector('[data-password-match-error]');
                if (!password || !confirmation || !error) {
                    return true;
                }

                const mismatch = confirmation.value !== '' && password.value !== confirmation.value;
                confirmation.setCustomValidity(mismatch ? 'Passwords do not match.' : '');
                confirmation.classList.toggle('is-password-mismatch', mismatch);
                confirmation.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
                error.hidden = !mismatch;

                return !mismatch;
            }

            document.addEventListener('input', function (event) {
                const form = event.target.closest('.reset-request__form');
                if (form && (event.target.name === 'password' || event.target.name === 'password_confirmation')) {
                    validatePasswordMatch(form);
                }
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (form.matches('.reset-request__form') && !validatePasswordMatch(form)) {
                    event.preventDefault();
                    form.elements.namedItem('password_confirmation').reportValidity();
                }
            });

            document.addEventListener('click', function (event) {
                const button = event.target.closest('[data-toggle-password]');
                if (!button) {
                    return;
                }

                const input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) {
                    return;
                }

                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                button.setAttribute('aria-pressed', String(!isVisible));
                button.querySelector('[data-eye-visible]').hidden = !isVisible;
                button.querySelector('[data-eye-hidden]').hidden = isVisible;
            });

            async function loadRequestPage(url, addHistoryEntry) {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    throw new Error('Unable to load password reset requests (HTTP ' + response.status + ').');
                }

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextRegion = nextDocument.querySelector('#password-reset-requests-region');
                const currentRegion = document.querySelector('#password-reset-requests-region');
                if (!nextRegion || !currentRegion) {
                    throw new Error('Password reset request list was not present in the response.');
                }

                currentRegion.innerHTML = nextRegion.innerHTML;
                if (addHistoryEntry) {
                    window.history.pushState({}, '', response.url || url);
                }
                window.dispatchEvent(new CustomEvent('ajax-layout-updated'));
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('#password-reset-requests-region nav[role="navigation"] a');
                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const targetUrl = new URL(link.href, window.location.origin);
                if (targetUrl.origin !== window.location.origin) {
                    return;
                }

                event.preventDefault();
                loadRequestPage(targetUrl.toString(), true).catch(function (error) {
                    console.error('Password reset request pagination failed:', error);
                    window.alert('Unable to load the requested page. Please try again.');
                });
            });

            window.addEventListener('popstate', function () {
                if (!window.location.pathname.startsWith('/admin/password-reset-requests')) {
                    return;
                }
                loadRequestPage(window.location.href, false).catch(function (error) {
                    console.error('Password reset request history navigation failed:', error);
                    window.alert('Unable to restore the requested page. Please try again.');
                });
            });
        })();
    </script>
@endsection
