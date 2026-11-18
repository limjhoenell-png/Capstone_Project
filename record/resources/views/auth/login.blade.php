<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — BioPayLog System</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: #0e131f;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Background decoration */
        body::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 80vmax;
            height: 80vmax;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,235,.12) 0%, transparent 65%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -15%;
            width: 60vmax;
            height: 60vmax;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.08) 0%, transparent 65%);
            pointer-events: none;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: fadeUp .4s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo / Brand */
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
            margin-bottom: 32px;
        }

        .brand-icon {
            width: 44px; height: 44px;
            background: #2563eb;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(37,99,235,.4);
        }

        .brand-icon svg { width: 24px; height: 24px; color: #fff; }

        .brand-name {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -.01em;
        }

        .brand-sub {
            font-size: 11px;
            color: #9ca3af;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Card */
        .card {
            background: #111827;
            border: 1px solid #1f293d;
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 24px 64px rgba(0,0,0,.5);
        }

        .card-heading {
            margin-bottom: 4px;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
        }

        .card-sub {
            font-size: 13.5px;
            color: #9ca3af;
            margin-bottom: 28px;
        }

        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 7px;
            letter-spacing: .01em;
        }

        .input-wrap { position: relative; }
        .input-wrap .form-control.has-visibility-toggle { padding-right: 44px; }

        .input-icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            width: 17px; height: 17px;
            color: #9ca3af;
            pointer-events: none;
        }

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
            color: #9ca3af;
            cursor: pointer;
        }
        .password-visibility-toggle:hover,
        .password-visibility-toggle:focus-visible {
            background: #1f2937;
            color: #fff;
        }
        .password-visibility-toggle:focus-visible { outline: 2px solid #60a5fa; }
        .password-visibility-toggle svg { width: 18px; height: 18px; }
        .password-visibility-toggle [hidden] { display: none; }

        .form-control {
            width: 100%;
            padding: 11px 12px 11px 38px;
            border: 1.5px solid #334155;
            border-radius: 10px;
            font-size: 14.5px;
            font-family: inherit;
            color: #ffffff;
            background: #0e131f;
            transition: border-color .18s, box-shadow .18s;
            outline: none;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.18);
        }

        .form-control::placeholder { color: #748094; }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .remember-row label {
            font-size: 13.5px;
            color: #9ca3af;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background .18s, box-shadow .18s, transform .1s;
            letter-spacing: .01em;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            box-shadow: 0 8px 24px rgba(37,99,235,.35);
        }

        .btn-submit:active { transform: scale(.98); }

        .error-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #1c0a0a;
            border: 1px solid #7f1d1d;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 16px;
            color: #fca5a5;
            font-size: 13.5px;
            font-weight: 500;
            animation: shake .4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-4px); }
            40% { transform: translateX(4px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }

        .error-icon { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }

        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #748094;
        }

        .forgot-link {
            display: inline-block;
            margin-top: 12px;
            border: 0;
            padding: 0;
            background: transparent;
            color: #93c5fd;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
            text-decoration: underline;
        }
        .forgot-link:hover { color: #bfdbfe; }
        .reset-dialog {
            width: min(440px, calc(100vw - 32px));
            margin: auto;
            padding: 26px;
            border: 1px solid #334155;
            border-radius: 16px;
            background: #111827;
            color: #f8fafc;
            box-shadow: 0 24px 64px rgba(0,0,0,.6);
        }
        .reset-dialog::backdrop { background: rgba(3, 7, 18, .76); }
        .reset-dialog h2 { margin: 0 0 8px; font-size: 19px; }
        .reset-dialog p { margin: 0 0 18px; color: #9ca3af; font-size: 13.5px; line-height: 1.5; }
        .reset-dialog .form-group { margin-bottom: 16px; }
        .reset-dialog .form-control { padding-left: 12px; }
        .reset-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .reset-dialog-actions button {
            min-height: 40px;
            padding: 9px 14px;
            border: 1px solid #334155;
            border-radius: 9px;
            background: #1f2937;
            color: #e2e8f0;
            font: inherit;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .reset-dialog-actions .btn-submit {
            width: auto;
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }
        .status-alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid #065f46;
            border-radius: 10px;
            background: #052e2b;
            color: #a7f3d0;
            font-size: 13.5px;
            line-height: 1.45;
        }
    </style>
</head>
<body data-ajax-root="body">
    <div class="login-wrap">
        <div class="brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <div class="brand-name">BioPayLog System</div>
            </div>
        </div>

        <div class="card">
            <h1 class="card-heading">Welcome back</h1>
            <p class="card-sub">Sign in to your account to continue.</p>
            @if (session('status'))
                <div class="status-alert" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" data-no-ajax>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <div class="input-wrap">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <input
                            id="email"
                            class="form-control"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@company.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <input
                            id="password"
                            class="form-control has-visibility-toggle"
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                                <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Keep me signed in</label>
                </div>

                <button class="btn-submit" type="submit">Sign In</button>
                <button class="forgot-link" type="button" data-open-reset-request>Forgot password?</button>
            </form>

            @if ($errors->any())
                <div class="error-alert" role="alert">
                    <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif
        </div>

        <p class="footer-note">Admin and Employee accounts only.</p>
    </div>
    <dialog class="reset-dialog" data-reset-dialog @if(session('open-reset-request') || $errors->has('identifier')) data-open-on-load @endif aria-labelledby="reset-dialog-title">
        <form method="POST" action="{{ route('password.email') }}" data-no-ajax>
            @csrf
            <h2 id="reset-dialog-title">Request password assistance</h2>
            <p>Enter your registered email, username, or employee ID. An administrator will verify your identity locally. No email will be sent.</p>
            <div class="form-group">
                <label class="form-label" for="reset-identifier">Email, username, or employee ID</label>
                <input class="form-control" id="reset-identifier" name="identifier" type="text" maxlength="190" value="{{ old('identifier') }}" autocomplete="username" required>
            </div>
            <div class="reset-dialog-actions">
                <button type="button" data-close-reset-request>Cancel</button>
                <button class="btn-submit" type="submit">Submit request</button>
            </div>
        </form>
    </dialog>
    <script>
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

        (function () {
            var dialog = document.querySelector('[data-reset-dialog]');
            if (!dialog) return;

            document.querySelector('[data-open-reset-request]').addEventListener('click', function () {
                dialog.showModal();
                dialog.querySelector('input[name="identifier"]').focus();
            });
            document.querySelector('[data-close-reset-request]').addEventListener('click', function () {
                dialog.close();
            });
            if (dialog.hasAttribute('data-open-on-load')) {
                dialog.showModal();
                dialog.querySelector('input[name="identifier"]').focus();
            }
        })();
    </script>
</body>
</html>
