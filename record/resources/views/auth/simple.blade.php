<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Account' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0e131f; color: #ffffff; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .wrap { width: min(420px, 100%); background: #111827; border: 1px solid #1f293d; border-radius: 14px; padding: 28px; box-shadow: 0 20px 50px rgba(0,0,0,.28); }
        h1 { margin: 0 0 12px; font-size: 22px; color: #ffffff; }
        label { display: block; font-size: 13px; margin: 12px 0 4px; color: #d1d5db; }
        input { width: 100%; box-sizing: border-box; padding: 10px; color: #ffffff; background: #172033; border: 1px solid #334155; border-radius: 8px; }
        input:focus { outline: 2px solid rgba(37, 99, 235, .35); border-color: #2563eb; }
        button, .btn { margin-top: 16px; background: #2563eb; color: #ffffff; font-weight: 700; border: 0; border-radius: 8px; padding: 10px 14px; display: inline-block; text-decoration: none; cursor: pointer; transition: background 180ms ease, box-shadow 180ms ease; }
        button:hover, .btn:hover { background: #1d4ed8; box-shadow: 0 4px 14px rgba(37, 99, 235, .22); }
        .error { color: #fecaca; font-size: 13px; }
        .ok { color: #6ee7b7; font-size: 13px; }
        @media (max-width: 520px) { body { padding: 14px; } .wrap { padding: 20px; } }
    </style>
</head>
<body>
<div class="wrap">
    @if (session('status'))<p class="ok">{{ session('status') }}</p>@endif
    @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
    @yield('content')
</div>
</body>
</html>
