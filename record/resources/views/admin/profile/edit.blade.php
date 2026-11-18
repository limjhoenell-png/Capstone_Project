@extends('layouts.admin', ['title' => 'Administrator Profile'])

@section('content')
    <style>
        .admin-profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 16px;
        }
        .admin-profile-card {
            padding: 20px;
            border: 1px solid var(--c-border);
            border-radius: 14px;
            background: var(--c-surface);
            color: var(--c-text);
            transition: background-color var(--transition), color var(--transition), border-color var(--transition);
        }
        .admin-profile-card h2 {
            margin: 0 0 6px;
            font-size: 17px;
        }
        .admin-profile-card p {
            margin: 0 0 18px;
            color: var(--c-text-secondary);
            font-size: 13px;
        }
        .admin-profile-fields {
            display: grid;
            gap: 14px;
        }
        .admin-profile-fields label {
            display: grid;
            gap: 6px;
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 700;
        }
        .admin-profile-fields input {
            width: 100%;
            min-height: 40px;
            padding: 8px 11px;
            border: 1px solid var(--c-border-strong);
            border-radius: 8px;
            background: var(--c-surface-2);
            color: var(--c-text);
            font: inherit;
            transition: background-color var(--transition), color var(--transition), border-color var(--transition), box-shadow var(--transition);
        }
        .admin-profile-fields input:focus {
            border-color: var(--c-primary);
            outline: 0;
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .admin-profile-fields input.is-password-mismatch {
            border-color: var(--c-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .14);
        }
        .password-match-error {
            margin: 8px 0 0 !important;
            color: var(--c-error-text) !important;
            font-size: 12px !important;
            font-weight: 600;
        }
        .password-match-error[hidden] { display: none; }
        .admin-password-input { position: relative; display: block; }
        .admin-password-input input { padding-right: 44px; }
        .password-visibility-toggle {
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
        .password-visibility-toggle:hover,
        .password-visibility-toggle:focus-visible {
            background: var(--c-surface);
            color: var(--c-text);
        }
        .password-visibility-toggle:focus-visible { outline: 2px solid var(--c-primary); }
        .password-visibility-toggle svg { width: 18px; height: 18px; }
        .password-visibility-toggle [hidden] { display: none; }
        .admin-profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 18px;
        }
        @media (max-width: 760px) {
            .admin-profile-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="action-bar">
        <a class="btn" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </div>

    <div class="admin-profile-grid">
        <section class="admin-profile-card" aria-labelledby="admin-profile-details-title">
            <h2 id="admin-profile-details-title">Profile Details</h2>
            <p>Update your administrator account information.</p>
            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="admin-profile-fields">
                    <label>
                        Name
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email">
                    </label>
                    <label>
                        Contact Number
                        <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" maxlength="50" autocomplete="tel">
                    </label>
                    <label>
                        Position
                        <input type="text" name="position" value="{{ old('position', $user->position) }}" maxlength="255">
                    </label>
                    <label>
                        Department
                        <input type="text" name="department" value="{{ old('department', $user->department) }}" maxlength="255">
                    </label>
                </div>
                <div class="admin-profile-actions">
                    <button class="btn btn-primary" type="submit">Save Profile</button>
                </div>
            </form>
        </section>

        <section class="admin-profile-card" aria-labelledby="admin-profile-password-title">
            <h2 id="admin-profile-password-title">Change Password</h2>
            <p>Confirm your current password before choosing a new one.</p>
            <form method="POST" action="{{ route('admin.profile.password.update') }}" data-admin-profile-password-form>
                @csrf
                @method('PUT')
                <div class="admin-profile-fields">
                    <label>
                        Current Password
                        <span class="admin-password-input">
                            <input type="password" name="current_password" required autocomplete="current-password">
                            <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                    <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75a16.7 16.7 0 0 1-3.02 3.92M6.18 6.18C3.65 7.83 2.25 12 2.25 12s3.5 6.75 9.75 6.75a10.7 10.7 0 0 0 4.12-.82"/></g>
                                </svg>
                            </button>
                        </span>
                    </label>
                    <label>
                        New Password
                        <span class="admin-password-input">
                            <input type="password" name="password" required autocomplete="new-password">
                            <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                    <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-1.08 2.08-3.02 3.92M6.18 6.18C3.65 7.83 2.25 12 2.25 12s3.5 6.75 9.75 6.75a10.7 10.7 0 0 0 4.12-.82"/></g>
                                </svg>
                            </button>
                        </span>
                    </label>
                    <label>
                        Confirm New Password
                        <span class="admin-password-input">
                            <input type="password" name="password_confirmation" aria-describedby="admin-password-match-error" required autocomplete="new-password">
                            <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                    <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                                </svg>
                            </button>
                        </span>
                    </label>
                </div>
                <p class="password-match-error" id="admin-password-match-error" role="alert" data-password-match-error hidden>New Password and Confirm New Password must match.</p>
                <div class="admin-profile-actions">
                    <button class="btn btn-primary" type="submit">Update Password</button>
                </div>
            </form>
        </section>
    </div>
    <script>
        (function () {
            var form = document.querySelector('[data-admin-profile-password-form]');
            if (!form) {
                return;
            }

            var password = form.elements.namedItem('password');
            var confirmation = form.elements.namedItem('password_confirmation');
            var error = form.querySelector('[data-password-match-error]');

            function validatePasswordMatch() {
                var mismatch = confirmation.value !== '' && password.value !== confirmation.value;
                confirmation.setCustomValidity(mismatch ? 'New Password and Confirm New Password must match.' : '');
                confirmation.classList.toggle('is-password-mismatch', mismatch);
                confirmation.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
                error.hidden = !mismatch;
                return !mismatch;
            }

            form.addEventListener('input', function (event) {
                if (event.target === password || event.target === confirmation) {
                    validatePasswordMatch();
                }
            });

            form.addEventListener('submit', function (event) {
                if (!validatePasswordMatch()) {
                    event.preventDefault();
                    confirmation.reportValidity();
                }
            });
        })();

        document.querySelectorAll('[data-password-visibility]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = button.parentElement.querySelector('input');
                var isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                button.setAttribute('aria-pressed', String(!isVisible));
                button.querySelector('[data-eye-visible]').hidden = !isVisible;
                button.querySelector('[data-eye-hidden]').hidden = isVisible;
            });
        });
    </script>
@endsection
