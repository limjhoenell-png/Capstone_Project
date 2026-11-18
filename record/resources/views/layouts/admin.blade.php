<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Dashboard' }} — ReCORD</title>
    <style>
        :root {
            --c-bg: #f0f4ff;
            --c-surface: #ffffff;
            --c-surface-2: #f8faff;
            --c-border: #e4eaf5;
            --c-border-strong: #94a3b8;
            --c-primary: #2563eb;
            --c-primary-hover: #1d4ed8;
            --c-primary-light: #dbeafe;
            --c-primary-text: #1e40af;
            --c-sidebar-bg: #0d1426;
            --c-sidebar-surface: #1a2540;
            --c-sidebar-hover: #1e2d50;
            --c-sidebar-active: #2563eb;
            --c-sidebar-text: #94a3b8;
            --c-sidebar-text-bright: #e2e8f0;
            --c-text: #111827;
            --c-text-secondary: #475569;
            --c-text-muted: #64748b;
            --c-success-bg: #ecfdf5;
            --c-success-border: #6ee7b7;
            --c-success-text: #065f46;
            --c-error-bg: #fff1f2;
            --c-error-border: #fda4af;
            --c-error-text: #9f1239;
            --c-warning-bg: #fffbeb;
            --c-warning-border: #fcd34d;
            --c-warning-text: #92400e;
            --c-danger: #ef4444;
            --c-danger-hover: #dc2626;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 20px;
            --shadow-xs: 0 1px 2px rgba(0,0,0,.05);
            --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04);
            --shadow-lg: 0 10px 24px rgba(0,0,0,.1), 0 4px 8px rgba(0,0,0,.06);
            --sidebar-w: 256px;
            --header-h: 64px;
            --transition: 180ms cubic-bezier(.4,0,.2,1);
            --c-topbar-bg: rgba(240, 244, 255, 0.85);
            --c-sidebar-rule: #1e2d50;
            --c-form-focus-ring: rgba(37, 99, 235, 0.12);
            --c-scrollbar-thumb: #2a3a5e;
            --c-btn-hover-border: #93afd4;
            --c-modal-backdrop: rgba(15, 23, 42, 0.5);
            --c-loading-accent-end: #818cf8;
            --c-topbar-badge-border: #bfdbfe;
            --c-analytics-accent-hris: #22d3ee;
            --c-analytics-accent-logbook: #2563eb;
            --c-logout-hover-bg: #3b1212;
            --c-logout-hover-border: #7f1d1d;
            --c-logout-hover-text: #fca5a5;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { font-size: 15px; -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--c-bg);
            color: var(--c-text);
            min-height: 100vh;
        }

        /* ── Layout ── */
        .layout { min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            background: var(--c-sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            transition: transform var(--transition);
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--c-scrollbar-thumb); border-radius: 4px; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--c-sidebar-rule);
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 36px; height: 36px;
            background: var(--c-primary);
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-brand-icon svg { width: 20px; height: 20px; color: #fff; }

        .sidebar-brand-text {
            display: flex; flex-direction: column; gap: 1px;
        }

        .sidebar-brand-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--c-sidebar-text-bright);
            letter-spacing: .01em;
        }

        .sidebar-brand-sub {
            font-size: 10.5px;
            font-weight: 500;
            color: var(--c-sidebar-text);
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--c-sidebar-text);
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 12px 8px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: var(--radius-md);
            color: var(--c-sidebar-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background var(--transition), color var(--transition);
            position: relative;
        }

        .sidebar-link:hover {
            background: var(--c-sidebar-hover);
            color: var(--c-sidebar-text-bright);
        }

        .sidebar-link.active {
            background: var(--c-primary);
            color: #fff;
        }

        .sidebar-link.active .nav-icon { opacity: 1; }

        .nav-icon {
            width: 18px; height: 18px;
            flex-shrink: 0;
            opacity: .7;
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--c-sidebar-rule);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 10px;
            border-radius: var(--radius-md);
            margin-bottom: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: inherit;
            font: inherit;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
            transition: background var(--transition), border-color var(--transition);
        }

        .user-pill:hover,
        .user-pill:focus-visible {
            background: var(--c-sidebar-surface);
            border-color: var(--c-sidebar-rule);
            outline: none;
        }

        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .user-info { min-width: 0; }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--c-sidebar-text-bright);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: var(--c-sidebar-text);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 10px;
            border-radius: var(--radius-md);
            border: 1px solid var(--c-sidebar-rule);
            background: transparent;
            color: var(--c-sidebar-text);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: background var(--transition), color var(--transition), border-color var(--transition);
            text-decoration: none;
        }

        .logout-btn:hover {
            background: var(--c-logout-hover-bg);
            border-color: var(--c-logout-hover-border);
            color: var(--c-logout-hover-text);
        }

        /* ── Main Content ── */
        .main {
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
            width: calc(100% - var(--sidebar-w));
        }

        /* ── Top Header ── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--c-topbar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--c-border);
            height: var(--header-h);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--c-text);
            flex: 1;
        }

        .topbar-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--c-primary-text);
            background: var(--c-primary-light);
            border: 1px solid var(--c-topbar-badge-border);
            padding: 4px 10px;
            border-radius: 99px;
        }
        .admin-request-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-text-secondary);
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 650;
            white-space: nowrap;
        }
        .admin-request-button:hover { border-color: var(--c-primary); color: var(--c-primary-text); }
        .admin-request-count {
            position: absolute;
            top: -6px;
            right: -7px;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            border: 2px solid var(--c-surface);
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            font-weight: 750;
            line-height: 15px;
            text-align: center;
        }
        .admin-request-count[hidden] { display: none; }
        .admin-request-modal[hidden] { display: none; }
        .admin-request-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 20px;
            background: var(--c-modal-backdrop);
        }
        .admin-request-dialog {
            display: flex;
            flex-direction: column;
            width: min(100%, 760px);
            max-height: min(84vh, 760px);
            overflow: hidden;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            box-shadow: var(--shadow-lg);
        }
        .admin-request-dialog > header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--c-border);
        }
        .admin-request-dialog h2 { margin: 0; color: var(--c-text); font-size: 17px; }
        .admin-request-dialog > header p { margin: 4px 0 0; color: var(--c-text-muted); font-size: 12px; }
        .admin-request-close {
            width: 34px;
            height: 34px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-sm);
            background: var(--c-surface);
            color: var(--c-text);
            cursor: pointer;
            font-size: 20px;
        }
        .admin-request-list { overflow-y: auto; padding: 8px 20px 20px; }
        .admin-request-item { padding: 16px 0; border-bottom: 1px solid var(--c-border); }
        .admin-request-item:last-child { border-bottom: 0; }
        .admin-request-item header { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
        .admin-request-item strong { color: var(--c-text); font-size: 14px; }
        .admin-request-item time { color: var(--c-text-muted); font-size: 11px; }
        .admin-request-period { margin-top: 5px; color: var(--c-text-secondary); font-size: 12px; }
        .admin-request-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .admin-request-actions form { margin: 0; }
        .admin-request-actions .btn { min-height: 32px; }
        .admin-request-empty,
        .admin-request-error { padding: 24px 0; color: var(--c-text-muted); font-size: 13px; text-align: center; }
        .admin-request-error { color: var(--c-error-text); }

        /* ── Page Body ── */
        .page-body {
            flex: 1;
            width: 100%;
            min-width: 0;
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 32px 36px;
        }

        /* ── Flash messages ── */
        .flash {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            animation: slideDown .25s ease both;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .flash-success {
            background: var(--c-success-bg);
            border: 1px solid var(--c-success-border);
            color: var(--c-success-text);
        }

        .flash-success.flash-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: min(420px, calc(100vw - 40px));
            margin: 0;
            padding: 14px 16px;
            border-left: 4px solid var(--c-success-text);
            box-shadow: var(--shadow-lg);
            animation: successToast 3.8s ease forwards;
            pointer-events: none;
        }

        @keyframes successToast {
            0% { opacity: 0; transform: translate3d(12px, -4px, 0); }
            8%, 88% { opacity: 1; transform: translate3d(0, 0, 0); }
            100% { opacity: 0; transform: translate3d(8px, -2px, 0); }
        }

        @media (max-width: 640px) {
            .flash-success.flash-toast {
                top: 12px;
                right: 12px;
                width: calc(100vw - 24px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .flash-success.flash-toast {
                animation-name: successToastReduced;
            }

            @keyframes successToastReduced {
                0%, 88% { opacity: 1; }
                100% { opacity: 0; }
            }
        }

        .flash-error {
            background: var(--c-error-bg);
            border: 1px solid var(--c-error-border);
            color: var(--c-error-text);
        }

        .flash-icon { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .flash-text { font-size: 14px; font-weight: 500; line-height: 1.4; }

        /* ── Cards ── */
        .card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--c-text);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: box-shadow var(--transition), transform var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 22px; height: 22px; }

        .stat-icon-blue { background: #dbeafe; color: #1d4ed8; }
        .stat-icon-green { background: #d1fae5; color: #065f46; }
        .stat-icon-violet { background: #ede9fe; color: #5b21b6; }
        .stat-icon-amber { background: #fef3c7; color: #92400e; }

        .stat-body { min-width: 0; flex: 1; }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--c-text);
            font-family: 'DM Mono', monospace;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--c-text-secondary);
            margin-top: 4px;
            font-weight: 500;
        }

        /* ── Tables ── */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-md);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: var(--c-surface-2);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--c-text-secondary);
            padding: 10px 16px;
            border-bottom: 1px solid var(--c-border);
            white-space: nowrap;
            text-align: left;
        }

        .table tbody tr {
            transition: background var(--transition);
            border-bottom: 1px solid var(--c-border);
        }

        .table tbody tr:last-child { border-bottom: none; }

        .table tbody tr:hover { background: var(--c-surface-2); }

        .table tbody td {
            padding: 12px 16px;
            font-size: 14px;
            color: var(--c-text);
            vertical-align: middle;
        }

        .table-empty {
            text-align: center;
            padding: 48px 16px !important;
            color: var(--c-text-muted) !important;
        }

        /* ── Buttons ── */
        .btn, button.btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--c-border-strong);
            background: var(--c-surface);
            color: var(--c-text);
            transition: background var(--transition), border-color var(--transition), color var(--transition), box-shadow var(--transition), transform var(--transition);
            white-space: nowrap;
            line-height: 1;
        }

        .btn:hover {
            background: var(--c-surface-2);
            border-color: var(--c-btn-hover-border);
            box-shadow: var(--shadow-xs);
        }

        .btn:active { transform: scale(.97); }

        .btn-primary {
            background: var(--c-primary);
            color: #fff;
            border-color: var(--c-primary);
        }

        .btn-primary:hover {
            background: var(--c-primary-hover);
            border-color: var(--c-primary-hover);
            box-shadow: 0 4px 12px rgba(37,99,235,.3);
        }

        .btn-danger {
            background: #fff;
            color: var(--c-danger);
            border-color: #fca5a5;
        }

        .btn-danger:hover {
            background: #fff1f2;
            border-color: var(--c-danger);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12.5px;
        }

        .btn-icon {
            padding: 7px;
            border-radius: var(--radius-sm);
        }

        .btn-icon svg { width: 15px; height: 15px; }

        /* ── Badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #9f1239; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        /* ── Forms ── */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--c-text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid var(--c-border-strong);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
            color: var(--c-text);
            background: var(--c-surface);
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }

        .form-control::placeholder { color: var(--c-text-muted); }

        select.form-control { cursor: pointer; }

        /* ── Module tiles ── */
        .hris-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
        }

        .hris-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 100px;
            padding: 20px 16px;
            border-radius: var(--radius-lg);
            border: 1.5px solid var(--c-border);
            background: var(--c-surface);
            color: var(--c-text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition), color var(--transition), transform var(--transition);
            box-shadow: var(--shadow-xs);
        }

        .hris-tile:hover {
            border-color: var(--c-primary);
            box-shadow: 0 4px 16px rgba(37,99,235,.12);
            color: var(--c-primary);
            transform: translateY(-3px);
        }

        .hris-tile svg { width: 28px; height: 28px; }

        .hris-tile-active {
            background: var(--c-primary);
            border-color: var(--c-primary);
            color: #fff;
            box-shadow: 0 4px 16px rgba(37,99,235,.3);
        }

        .hris-tile-active:hover {
            background: var(--c-primary-hover);
            border-color: var(--c-primary-hover);
            color: #fff;
        }

        /* ── Link overrides ── */
        .link-primary {
            color: var(--c-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition);
        }
        .link-primary:hover { color: var(--c-primary-hover); text-decoration: underline; }

        /* ── Action bar ── */
        .action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 22px;
        }

        .action-bar-right { margin-left: auto; }

        /* ── Inline form ── */
        form.inline { display: inline; }

        /* ── Loading bar ── */
        .loading-bar {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--c-primary), var(--c-loading-accent-end));
            border-radius: 0 3px 3px 0;
            z-index: 9999;
            width: 0;
            transition: width .3s ease, opacity .3s ease;
            opacity: 0;
        }

        .loading-bar.active {
            opacity: 1;
            animation: loadingPulse 1.2s ease infinite;
        }

        @keyframes loadingPulse {
            0%   { width: 10%; }
            50%  { width: 60%; }
            100% { width: 85%; }
        }

        .loading-bar.done {
            width: 100% !important;
            opacity: 0;
        }

        /* ── Pagination (override Laravel default) ── */
        nav[role="navigation"] { margin-top: 16px; }

        nav[role="navigation"] svg { display: none; }

        nav[role="navigation"] span,
        nav[role="navigation"] a {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            font-size: 13px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--c-border);
            margin: 0 2px;
            color: var(--c-text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: background var(--transition);
        }

        nav[role="navigation"] a:hover { background: var(--c-surface-2); }

        nav[role="navigation"] span[aria-current="page"] > span {
            background: var(--c-primary);
            color: #fff;
            border-color: var(--c-primary);
        }

        /* ── Page animation ── */
        .page-body { animation: pageIn .25s ease both; }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Utility ── */
        .text-muted { color: var(--c-text-muted); font-size: 13px; }
        .text-mono { font-family: 'DM Mono', monospace; }
        .font-bold { font-weight: 700; }
        .mb-0 { margin-bottom: 0; }
        .mb-4 { margin-bottom: 16px; }
        .gap-2 { gap: 8px; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .flex-wrap { flex-wrap: wrap; }

        /* ── Responsive ── */
        @include('partials.topbar-breadcrumbs-css')
        @include('partials.sidebar-drawer-css')

        @media (max-width: 900px) {
            .page-body {
                padding: 18px 14px 22px;
            }
        }

        @include('partials.theme-transition-css')
        @include('partials.theme-dark-variables', ['palette' => 'admin'])
        @include('partials.theme-global-components')
    </style>
</head>
<body data-ajax-root=".layout">
<div id="loading-bar" class="loading-bar"></div>
<div class="layout">
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <!-- Sidebar -->
    <aside class="sidebar" id="app-sidebar">
        <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
            <div class="sidebar-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">ADMIN PANEL</span>
                <span class="sidebar-brand-sub">BioPayLog Web Based System</span>
            </div>
        </a>

        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Overview</span>

            <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Dashboard
            </a>

            <span class="sidebar-section-label">Modules</span>

            <a class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}"
               href="{{ route('admin.employees.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Employees
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}"
               href="{{ route('admin.attendances.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Attendance
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}"
               href="{{ route('admin.payroll.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
                Payroll
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.payslips.*') ? 'active' : '' }}"
               href="{{ route('admin.payslips.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Payslips
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.password-reset-requests.*') ? 'active' : '' }}"
               href="{{ route('admin.password-reset-requests.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6.75a2.25 2.25 0 0 1-2.25 2.25h-10.5A2.25 2.25 0 0 1 4.5 19.5v-6.75a2.25 2.25 0 0 1 2.25-2.25Z" />
                </svg>
                Password Reset Requests
            </a>

            @if(auth()->user()?->canAccessLogbook())
            <span class="sidebar-section-label">Logbook</span>

            <a class="sidebar-link {{ request()->routeIs('admin.barangay-logbook.index', 'admin.barangay-logbook.report') ? 'active' : '' }}"
               href="{{ route('admin.barangay-logbook.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Logbook Reports
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.barangay-logbook.logs', 'admin.logbooks.*') ? 'active' : '' }}"
               href="{{ route('admin.barangay-logbook.logs') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Logbook Records
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.visitors.*') ? 'active' : '' }}"
               href="{{ route('admin.visitors.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 0 3-3v-1.5a3 3 0 0 0-3-3m0 7.5h-9m9 0v.75a2.25 2.25 0 0 1-2.25 2.25h-4.5A2.25 2.25 0 0 1 7.5 19.5v-.75m0 0a3 3 0 0 1-3-3v-1.5a3 3 0 0 1 3-3m0 7.5h9m-9-7.5a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0Z" />
                </svg>
                Logbook Accounts
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a class="user-pill" href="{{ route('admin.profile.edit') }}" aria-label="Edit administrator profile">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                    <div class="user-role">Administrator</div>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" data-no-ajax>
                @csrf
                <button class="logout-btn" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main">
        <header class="topbar">
            @include('partials.sidebar-toggle')
            @include('partials.topbar-breadcrumbs', ['pageTitle' => $title ?? 'Admin Dashboard'])
            @include('partials.theme-toggle')
            <span class="topbar-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                </svg>
                Admin
            </span>
        </header>

        <main class="page-body">
            @if (session('status'))
                <div class="flash flash-success flash-toast" role="alert">
                    <svg class="flash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="flash-text">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash flash-error" role="alert">
                    <svg class="flash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="flash-text">{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@if (request()->routeIs('admin.payroll.index'))
<div class="admin-request-modal" id="admin-payslip-request-modal" hidden>
    <section class="admin-request-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-payslip-request-title" tabindex="-1">
        <header>
            <div>
                <h2 id="admin-payslip-request-title">Pending Payslip Requests</h2>
                <p>Review and generate requested employee payslips.</p>
            </div>
            <button class="admin-request-close" type="button" data-admin-request-close aria-label="Close requests">&times;</button>
        </header>
        <div class="admin-request-list" id="admin-payslip-request-list" aria-live="polite">
            <p class="admin-request-empty">Loading pending requests…</p>
        </div>
    </section>
</div>
@endif

@include('partials.ajax-system')
@include('partials.sidebar-drawer')

@if (request()->routeIs('admin.payroll.index'))
<script>
    (function () {
        var button = document.getElementById('admin-payslip-request-open');
        var badge = document.getElementById('admin-payslip-request-count');
        var modal = document.getElementById('admin-payslip-request-modal');
        var dialog = modal && modal.querySelector('[role="dialog"]');
        var list = document.getElementById('admin-payslip-request-list');
        if (!button || !badge || !modal || !dialog || !list) {
            return;
        }

        var endpoint = @json(route('admin.payslip-requests.pending'));
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfMeta ? csrfMeta.content : '';
        var requests = [];
        var previousFocus = null;
        var loading = false;

        function makeForm(action, label, fields, className) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            var token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrf;
            form.appendChild(token);
            Object.keys(fields || {}).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            if (Object.prototype.hasOwnProperty.call(fields || {}, 'status')) {
                form.dataset.requestStatus = 'true';
            }
            var submit = document.createElement('button');
            submit.type = 'submit';
            submit.className = className || 'btn btn-sm';
            submit.textContent = label;
            form.appendChild(submit);
            return form;
        }

        function renderRequests() {
            list.replaceChildren();
            if (!requests.length) {
                var empty = document.createElement('p');
                empty.className = 'admin-request-empty';
                empty.textContent = 'There are no pending payslip requests.';
                list.appendChild(empty);
                return;
            }

            requests.forEach(function (request) {
                var item = document.createElement('article');
                var heading = document.createElement('header');
                var employee = document.createElement('strong');
                var submitted = document.createElement('time');
                var period = document.createElement('p');
                var actions = document.createElement('div');
                var review = document.createElement('a');
                var status = document.createElement('span');

                item.className = 'admin-request-item';
                employee.textContent = request.employee;
                submitted.textContent = request.submitted_at || '';
                period.className = 'admin-request-period';
                period.textContent = request.period_label + ' (' + request.period_start + ' – ' + request.period_end + ')';
                status.className = 'admin-request-period';
                status.textContent = request.status === 'reviewing' ? 'In review' : 'Pending review';
                actions.className = 'admin-request-actions';
                review.className = 'btn btn-sm';
                review.href = request.review_url;
                review.textContent = 'Review payroll';
                heading.append(employee, submitted);
                actions.appendChild(review);

                if (request.approve_url) {
                    actions.appendChild(makeForm(request.approve_url, 'Approve & Send to Employee', {}, 'btn btn-sm btn-primary'));
                } else if (request.view_url) {
                    var viewPayslip = document.createElement('a');
                    viewPayslip.className = 'btn btn-sm';
                    viewPayslip.href = request.view_url;
                    viewPayslip.textContent = 'View payslip';
                    actions.appendChild(viewPayslip);
                } else {
                    actions.appendChild(makeForm(request.generate_url, 'Generate & Publish', {}, 'btn btn-sm btn-primary'));
                }
                actions.appendChild(makeForm(request.request_status_url, 'Mark fulfilled', { status: 'fulfilled' }, 'btn btn-sm'));

                item.append(heading, period, status, actions);
                list.appendChild(item);
            });
        }

        async function refreshRequests() {
            if (loading) {
                return;
            }
            loading = true;
            try {
                var response = await fetch(endpoint, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                var payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.error || 'Unable to load pending payslip requests.');
                }
                requests = payload.requests || [];
                var count = Number(payload.pending_count) || 0;
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count === 0;
                button.setAttribute('aria-label', count + ' pending payslip requests');
                if (!modal.hidden) {
                    renderRequests();
                }
            } catch (error) {
                if (!modal.hidden) {
                    var message = document.createElement('p');
                    message.className = 'admin-request-error';
                    message.textContent = error.message || 'Unable to load pending payslip requests.';
                    list.replaceChildren(message);
                }
            } finally {
                loading = false;
            }
        }

        function closeModal() {
            modal.hidden = true;
            if (previousFocus) {
                previousFocus.focus();
            }
        }

        button.addEventListener('click', function () {
            previousFocus = document.activeElement;
            modal.hidden = false;
            dialog.focus();
            refreshRequests();
        });
        list.addEventListener('submit', async function (event) {
            var form = event.target.closest('form[data-request-status]');
            if (!form) {
                return;
            }
            event.preventDefault();
            var submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }
            try {
                var response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                var payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Unable to update payslip request.');
                }
                badge.textContent = payload.pending_count > 99 ? '99+' : String(payload.pending_count || 0);
                badge.hidden = Number(payload.pending_count) === 0;
                await refreshRequests();
            } catch (error) {
                var message = document.createElement('p');
                message.className = 'admin-request-error';
                message.textContent = error.message || 'Unable to update payslip request.';
                list.prepend(message);
                if (submit) {
                    submit.disabled = false;
                }
            }
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-admin-request-close]')) {
                closeModal();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
        refreshRequests();
        window.setInterval(refreshRequests, 30000);
        window.addEventListener('pageshow', refreshRequests);
    }());
</script>
@endif

<script>
    // Loading bar for AJAX navigation
    (function() {
        const bar = document.getElementById('loading-bar');
        let timer;
        function startBar() {
            clearTimeout(timer);
            bar.style.transition = 'none';
            bar.style.width = '0';
            bar.classList.add('active');
            bar.classList.remove('done');
        }
        function finishBar() {
            bar.classList.remove('active');
            bar.style.transition = 'width .25s ease, opacity .4s ease .15s';
            bar.style.width = '100%';
            timer = setTimeout(() => bar.style.opacity = '0', 150);
            setTimeout(() => { bar.style.width = '0'; bar.style.opacity = '1'; }, 500);
        }
        window.addEventListener('ajax-start', startBar);
        window.addEventListener('ajax-end', finishBar);
    })();
</script>
</body>
</html>
