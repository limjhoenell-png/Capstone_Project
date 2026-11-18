<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ReCORD') }}</title>


    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:  #0e131f;
            --blue:  #2563eb;
            --blue-h:#1d4ed8;
            --light: #9ca3af;
        }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--navy);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
        }

        /* Decorative gradient blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
            pointer-events: none;
        }

        .blob-1 { width: 600px; height: 600px; background: #2563eb; top: -200px; left: -200px; animation: drift 12s ease-in-out infinite alternate; }
        .blob-2 { width: 400px; height: 400px; background: #0d9488; bottom: -120px; right: -100px; animation: drift 16s ease-in-out infinite alternate-reverse; }

        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.05); }
        }

        /* Main card */
        .card {
            position: relative;
            z-index: 1;
            background: #111827;
            border: 1px solid #1f293d;
            border-radius: 20px;
            padding: 48px 52px;
            max-width: 480px;
            width: 90%;
            backdrop-filter: blur(12px);
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo icon */
        .logo-wrap {
            width: 56px;
            height: 56px;
            background: var(--blue);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        }

        .logo-wrap svg { width: 30px; height: 30px; color: #fff; }

        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        p.sub {
            font-size: 15px;
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* Action buttons */
        .actions { display: flex; flex-direction: column; gap: 12px; }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--blue);
            color: #fff;
            border: none;
            box-shadow: 0 4px 14px rgba(37,99,235,0.35);
        }

        .btn-primary:hover { background: var(--blue-h); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.4); }
        .btn-primary:active { transform: translateY(0); }

        .btn-ghost {
            background: transparent;
            color: #d1d5db;
            border: 1px solid #334155;
        }

        .btn-ghost:hover { background: #172033; color: #fff; border-color: #4b5563; }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #334155;
        }

        /* Footer */
        .footer {
            position: relative;
            z-index: 1;
            margin-top: 28px;
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
            animation: fadeUp 0.5s 0.15s ease both;
        }

        .footer a { color: #cbd5e1; text-decoration: none; }
        .footer a:hover { color: #ffffff; }
    </style>
</head>
<body>

    <!-- Background blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="card">

        <!-- Logo -->
        <div class="logo-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>

        <h1>{{ config('app.name', 'ReCORD') }}</h1>
        <p class="sub">Human Resource Information System — manage employees, attendance, payroll, and more from one place.</p>

        @if (Route::has('login'))
            <div class="actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Sign In
                    </a>

                    @if (Route::has('register'))
                        <div class="divider">or</div>
                        <a href="{{ route('register') }}" class="btn btn-ghost">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Create Account
                        </a>
                    @endif
                @endauth
            </div>
        @endif

    </div>

    <footer class="footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'ReCORD') }} &mdash; Powered by <a href="https://laravel.com" target="_blank">Laravel</a>
    </footer>

</body>
</html>
