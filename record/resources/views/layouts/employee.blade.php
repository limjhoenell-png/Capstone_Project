<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Employee Dashboard' }} — ReCORD</title>
    <style>
        :root {
            --c-bg: #f0f7f4;
            --c-surface: #ffffff;
            --c-surface-2: #f6faf8;
            --c-border: #d4e8de;
            --c-border-strong: #94a3b8;
            --c-primary: #0f766e;
            --c-primary-hover: #115e59;
            --c-primary-light: #ccfbf1;
            --c-primary-text: #0f766e;
            --c-sidebar-bg: #0d1f1c;
            --c-sidebar-surface: #142420;
            --c-sidebar-hover: #1a3330;
            --c-sidebar-active: #0f766e;
            --c-sidebar-text: #a7c7bf;
            --c-sidebar-text-bright: #d1fae5;
            --c-text: #111827;
            --c-text-secondary: #475569;
            --c-text-muted: #64748b;
            --c-success-bg: #ecfdf5;
            --c-success-border: #6ee7b7;
            --c-success-text: #065f46;
            --c-error-bg: #fff1f2;
            --c-error-border: #fda4af;
            --c-error-text: #9f1239;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-xs: 0 1px 2px rgba(0,0,0,.05);
            --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08);
            --sidebar-w: 240px;
            --header-h: 64px;
            --transition: 180ms cubic-bezier(.4,0,.2,1);
            --c-topbar-bg: rgba(240, 247, 244, 0.85);
            --c-sidebar-rule: #1a3330;
            --c-form-focus-ring: rgba(13, 148, 136, 0.12);
            --c-scrollbar-thumb: #2d5c54;
            --c-btn-hover-border: #7ab8aa;
            --c-modal-backdrop: rgba(15, 23, 42, 0.5);
            --c-topbar-badge-border: #99f6e4;
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

        /* Match admin: fixed sidebar + offset main only. A grid here plus margin-left on .main
           double-counts the sidebar width and can push or crush page content off-screen. */
        .layout {
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: var(--c-sidebar-bg);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 100;
            transition: transform var(--transition);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 18px 16px;
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

        .sidebar-brand-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--c-sidebar-text-bright);
        }

        .sidebar-brand-sub {
            font-size: 10.5px;
            font-weight: 500;
            color: var(--c-sidebar-text);
            letter-spacing: .06em;
            text-transform: uppercase;
            display: block;
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
        }

        .sidebar-link:hover {
            background: var(--c-sidebar-hover);
            color: var(--c-sidebar-text-bright);
        }

        .sidebar-link.active {
            background: var(--c-primary);
            color: #fff;
        }

        .nav-icon { width: 18px; height: 18px; flex-shrink: 0; opacity: .7; }
        .sidebar-link.active .nav-icon { opacity: 1; }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--c-sidebar-rule);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: var(--radius-md);
            margin-bottom: 8px;
            color: inherit;
            text-decoration: none;
            transition: background var(--transition);
        }
        .user-pill:hover, .user-pill:focus-visible { background: var(--c-sidebar-hover); }

        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d9488, #059669);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }

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
        }

        .logout-btn:hover {
            background: var(--c-logout-hover-bg);
            border-color: var(--c-logout-hover-border);
            color: var(--c-logout-hover-text);
        }

        /* Main */
        .main {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }

        .topbar {
            position: sticky;
            top: 0; z-index: 50;
            background: var(--c-topbar-bg);
            backdrop-filter: blur(12px);
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

        .page-body {
            flex: 1;
            padding: 28px;
            animation: pageIn .25s ease both;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .page-body-inner {
            width: 100%;
            max-width: 1160px;
            margin: 0 auto;
        }

        nav .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            list-style: none;
            padding: 14px 16px;
            margin: 0;
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid var(--c-border);
            background: var(--c-surface-2);
        }

        nav .pagination li { display: inline-flex; }

        nav .pagination li a,
        nav .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--c-border);
            font-size: 13px;
            font-weight: 600;
            color: var(--c-text-secondary);
            text-decoration: none;
            background: var(--c-surface);
        }

        nav .pagination li.active span,
        nav .pagination li.active span[aria-current="page"] {
            background: var(--c-primary);
            border-color: var(--c-primary);
            color: #fff;
        }

        nav .pagination li.disabled span {
            opacity: .45;
            cursor: default;
        }

        nav .pagination li a:hover {
            background: var(--c-surface-2);
            color: var(--c-text);
        }

        @keyframes pageIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

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

        .flash-success { background: var(--c-success-bg); border: 1px solid var(--c-success-border); color: var(--c-success-text); }
        .flash-error   { background: var(--c-error-bg);   border: 1px solid var(--c-error-border);   color: var(--c-error-text); }
        .flash-icon { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .flash-text { font-size: 14px; font-weight: 500; }

        /* Cards / stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
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

        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon-teal  { background: #ccfbf1; color: #0f766e; }
        .stat-icon-green { background: #d1fae5; color: #065f46; }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--c-text-secondary);
            margin-top: 4px;
            font-weight: 500;
        }

        .card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }

        .content-stack {
            display: grid;
            gap: 20px;
        }

        .content-stack > .card {
            margin-bottom: 0;
        }

        .card-compact {
            padding: 0;
            overflow: hidden;
            margin-bottom: 0;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--c-border);
            background: var(--c-surface-2);
            color: var(--c-text);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .card-header-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--c-text);
        }

        .card-header-subtitle {
            font-size: 12.5px;
            color: var(--c-text-muted);
        }

        .toolbar-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
        }

        .search-field {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 360px;
        }

        .search-field-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-text-muted);
            pointer-events: none;
        }

        .form-control-with-icon {
            padding-left: 36px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .table { width: 100%; border-collapse: collapse; }

        .table thead th {
            background: var(--c-surface-2);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--c-text-secondary);
            padding: 10px 16px;
            border-bottom: 1px solid var(--c-border);
            text-align: left;
            white-space: nowrap;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--c-border);
            transition: background var(--transition);
        }

        .table tbody tr:last-child { border-bottom: none; }
        .table tbody tr:hover { background: var(--c-surface-2); }

        .table tbody td {
            padding: 12px 16px;
            font-size: 14px;
            vertical-align: middle;
        }

        .table-empty {
            text-align: center;
            padding: 48px 16px !important;
            color: var(--c-text-muted) !important;
        }

        .btn {
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
            transition: background var(--transition), border-color var(--transition), box-shadow var(--transition), transform var(--transition);
            white-space: nowrap;
            line-height: 1;
        }

        .btn:hover {
            background: var(--c-surface-2);
            border-color: var(--c-btn-hover-border);
        }
        .btn:active { transform: scale(.97); }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12.5px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }

        .btn-primary {
            background: var(--c-primary);
            color: #fff;
            border-color: var(--c-primary);
        }

        .btn-primary:hover {
            background: var(--c-primary-hover);
            border-color: var(--c-primary-hover);
            box-shadow: 0 4px 12px rgba(13,148,136,.3);
        }

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

        .action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-gray  { background: #f1f5f9; color: #475569; }
        .badge-red   { background: #fee2e2; color: #9f1239; }

        form.inline { display: inline; }

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

        .text-muted { color: var(--c-text-muted); font-size: 13px; }
        .text-mono { font-family: 'DM Mono', monospace; }
        .mb-4 { margin-bottom: 16px; }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        .table td.text-secondary {
            color: var(--c-text-secondary);
        }

        .pagination-shell nav[role="navigation"] {
            margin-top: 0;
        }

        .live-sync-indicator {
            font-size: 11px;
            font-weight: 600;
            color: var(--c-text-muted);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        @include('partials.topbar-breadcrumbs-css')
        @include('partials.sidebar-drawer-css')

        @media (max-width: 900px) {
            .page-body {
                padding: 20px 14px;
            }

            .card-header {
                padding: 14px 16px;
            }

            .search-field {
                max-width: none;
                min-width: 100%;
            }
        }

        .employee-password-modal[hidden],
        .employee-password-toast[hidden] { display: none; }
        .employee-password-modal {
            position: fixed;
            inset: 0;
            z-index: 1300;
            display: grid;
            place-items: center;
            padding: 18px;
            background: var(--c-modal-backdrop);
        }
        .employee-password-dialog {
            width: min(100%, 440px);
            padding: 22px;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            color: var(--c-text);
            box-shadow: var(--shadow-md);
        }
        .employee-password-dialog-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .employee-password-dialog-header h2 { font-size: 19px; }
        .employee-password-dialog-header p {
            margin-top: 5px;
            color: var(--c-text-muted);
            font-size: 13px;
        }
        .employee-password-close {
            display: inline-grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-sm);
            background: var(--c-surface-2);
            color: var(--c-text-secondary);
            font: inherit;
            font-size: 22px;
            cursor: pointer;
        }
        .employee-password-form { display: grid; gap: 14px; }
        .employee-password-form label {
            display: grid;
            gap: 6px;
            color: var(--c-text-secondary);
            font-size: 12px;
            font-weight: 700;
        }
        .employee-password-input { position: relative; display: block; }
        .employee-password-form input {
            width: 100%;
            min-height: 40px;
            padding: 8px 11px;
            border: 1px solid var(--c-border-strong);
            border-radius: var(--radius-sm);
            background: var(--c-surface-2);
            color: var(--c-text);
            font: inherit;
        }
        .employee-password-input input { padding-right: 44px; }
        .employee-password-form input:focus {
            border-color: var(--c-primary);
            outline: 0;
            box-shadow: 0 0 0 3px var(--c-form-focus-ring);
        }
        .employee-password-form input.is-password-mismatch {
            border-color: var(--c-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .14);
        }
        .employee-password-form .password-visibility-toggle {
            position: absolute;
            top: 50%;
            right: 7px;
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
        .employee-password-form .password-visibility-toggle:hover,
        .employee-password-form .password-visibility-toggle:focus-visible {
            background: var(--c-surface);
            color: var(--c-text);
        }
        .employee-password-form .password-visibility-toggle:focus-visible { outline: 2px solid var(--c-primary); }
        .employee-password-form .password-visibility-toggle svg { width: 18px; height: 18px; }
        .employee-password-form .password-visibility-toggle [hidden] { display: none; }
        .employee-password-error {
            color: var(--c-error-text);
            font-size: 12px;
        }
        .employee-password-match-error {
            color: var(--c-error-text);
            font-size: 12px;
            font-weight: 600;
        }
        .employee-password-match-error[hidden] { display: none; }
        .employee-password-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 4px;
        }
        .employee-password-toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1400;
            max-width: min(90vw, 420px);
            padding: 12px 16px;
            border: 1px solid var(--c-success-border);
            border-radius: var(--radius-md);
            background: var(--c-success-bg);
            color: var(--c-success-text);
            box-shadow: var(--shadow-md);
            font-weight: 650;
        }

        @include('partials.theme-transition-css')
        @include('partials.theme-dark-variables', ['palette' => 'employee'])
        @include('partials.theme-global-components')
    </style>
</head>
<body data-ajax-root=".layout" data-ajax-nav-links="0">
<div class="layout">
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <aside class="sidebar" id="app-sidebar">
        <a class="sidebar-brand" href="{{ route('employee.dashboard') }}">
            <div class="sidebar-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <div>
                <span class="sidebar-brand-name">My Panel</span>
                <span class="sidebar-brand-sub">Employee Portal</span>
            </div>
        </a>

        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Navigation</span>

            <a class="sidebar-link {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}"
               href="{{ route('employee.dashboard') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Dashboard
            </a>

            <a class="sidebar-link {{ request()->routeIs('employee.attendances.*') ? 'active' : '' }}"
               href="{{ route('employee.attendances.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                My Attendance
            </a>

            <a class="sidebar-link {{ request()->routeIs('employee.payslips.*') ? 'active' : '' }}"
               href="{{ route('employee.payslips.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Payslips
            </a>

            @if(auth()->user()?->canAccessLogbook())
            <a class="sidebar-link {{ request()->routeIs('employee.logbooks.*') ? 'active' : '' }}"
               href="{{ route('employee.logbooks.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Logbook
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a class="user-pill" href="{{ route('employee.profile.edit') }}" aria-label="Edit your profile">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'E', 0, 1)) }}</div>
                <div>
                    <div class="user-name">{{ auth()->user()->name ?? 'Employee' }}</div>
                    <div class="user-role">Employee</div>
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

    <div class="main">
        <header class="topbar">
            @include('partials.sidebar-toggle')
            @include('partials.topbar-breadcrumbs', ['pageTitle' => $title ?? 'Employee Dashboard'])
            @include('partials.theme-toggle', ['themeToggleVariant' => 'teal'])
            @if(request()->routeIs('employee.dashboard', 'employee.attendances.index'))
                <span class="live-sync-indicator" title="Dashboard and tables refresh automatically">Live update</span>
            @endif
            <span class="topbar-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                Employee
            </span>
        </header>

        <main class="page-body">
            <div class="page-body-inner">
            @if (session('status'))
                <div class="flash flash-success">
                    <svg class="flash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="flash-text">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash flash-error">
                    <svg class="flash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="flash-text">{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
            </div>
        </main>
    </div>
</div>

<div class="employee-password-modal" id="employee-password-modal" data-password-modal hidden>
    <section class="employee-password-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-password-title">
        <div class="employee-password-dialog-header">
            <div>
                <h2 id="employee-password-title">Change password</h2>
            </div>
            <button class="employee-password-close" type="button" data-close-password-modal aria-label="Close dialog">&times;</button>
        </div>
        <form class="employee-password-form" method="POST" action="{{ route('password.change') }}" data-password-form>
            @csrf
            @method('PUT')
            <p class="employee-password-error" data-password-error role="alert" hidden></p>
            <label>
                Current password
                <span class="employee-password-input">
                    <input type="password" name="current_password" required autocomplete="current-password">
                    <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                            <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                        </svg>
                    </button>
                </span>
            </label>
            <label>
                New password
                <span class="employee-password-input">
                    <input type="password" name="password" required autocomplete="new-password">
                    <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                            <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                        </svg>
                    </button>
                </span>
            </label>
            <label>
                Confirm
                <span class="employee-password-input">
                    <input type="password" name="password_confirmation" aria-describedby="employee-password-match-error" required autocomplete="new-password">
                    <button class="password-visibility-toggle" type="button" data-password-visibility aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <g data-eye-visible><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></g>
                            <g data-eye-hidden hidden><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75s-3.5 6.75-9.75 6.75a10.7 10.7 0 0 1-4.12-.82"/></g>
                        </svg>
                    </button>
                </span>
            </label>
            <p class="employee-password-match-error" id="employee-password-match-error" data-password-match-error role="alert" hidden>New password and confirmation must match.</p>
            <div class="employee-password-form-actions">
                <button class="btn" type="button" data-close-password-modal>Cancel</button>
                <button class="btn btn-primary" type="submit" data-password-submit>Update password</button>
            </div>
        </form>
    </section>
</div>
<div class="employee-password-toast" data-password-toast role="status" aria-live="polite" hidden></div>

@include('partials.ajax-system')
@include('partials.sidebar-drawer')
<script>
(function () {
    var modal = document.querySelector('[data-password-modal]');
    var form = document.querySelector('[data-password-form]');
    var openLinks = document.querySelectorAll('[data-open-password-modal]');
    var errorMessage = document.querySelector('[data-password-error]');
    var toast = document.querySelector('[data-password-toast]');
    var newPasswordInput = form.querySelector('input[name="password"]');
    var confirmationInput = form.querySelector('input[name="password_confirmation"]');
    var matchError = form.querySelector('[data-password-match-error]');
    var previousFocus = null;
    var toastTimer = null;

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

    function openPasswordModal(event) {
        if (event) event.preventDefault();
        previousFocus = document.activeElement;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        form.querySelector('input[name="current_password"]').focus();
    }

    function closePasswordModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        errorMessage.hidden = true;
        matchError.hidden = true;
        confirmationInput.setCustomValidity('');
        confirmationInput.classList.remove('is-password-mismatch');
        confirmationInput.setAttribute('aria-invalid', 'false');
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    }

    function validatePasswordMatch() {
        var mismatch = confirmationInput.value !== '' && newPasswordInput.value !== confirmationInput.value;
        confirmationInput.setCustomValidity(mismatch ? 'New password and confirmation must match.' : '');
        confirmationInput.classList.toggle('is-password-mismatch', mismatch);
        confirmationInput.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
        matchError.hidden = !mismatch;
        return !mismatch;
    }

    openLinks.forEach(function (link) {
        link.addEventListener('click', openPasswordModal);
    });
    modal.querySelectorAll('[data-close-password-modal]').forEach(function (button) {
        button.addEventListener('click', closePasswordModal);
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closePasswordModal();
    });
    document.addEventListener('keydown', function (event) {
        if (modal.hidden) return;
        if (event.key === 'Escape') {
            closePasswordModal();
        } else if (event.key === 'Tab') {
            var focusable = modal.querySelectorAll('button:not([disabled]), input:not([disabled])');
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    form.addEventListener('input', function (event) {
        errorMessage.hidden = true;
        if (event.target === newPasswordInput || event.target === confirmationInput) {
            validatePasswordMatch();
        }
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!validatePasswordMatch()) {
            confirmationInput.reportValidity();
            return;
        }
        var submitButton = form.querySelector('[data-password-submit]');
        submitButton.disabled = true;
        errorMessage.hidden = true;

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var payload;
            try {
                payload = await response.json();
            } catch (parseError) {
                throw new Error('The server returned an invalid response. Please try again.');
            }
            if (!response.ok) {
                var errors = payload.errors ? Object.values(payload.errors).reduce(function (all, messages) {
                    return all.concat(messages);
                }, []) : [];
                throw new Error(errors[0] || payload.message || 'Unable to update password.');
            }
            if (!payload || typeof payload.message !== 'string') {
                throw new Error('The server did not confirm the password update.');
            }

            form.reset();
            confirmationInput.setCustomValidity('');
            confirmationInput.classList.remove('is-password-mismatch');
            confirmationInput.setAttribute('aria-invalid', 'false');
            matchError.hidden = true;
            closePasswordModal();
            toast.textContent = payload.message || 'Password updated.';
            toast.hidden = false;
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () { toast.hidden = true; }, 5000);
        } catch (error) {
            errorMessage.textContent = error.message || 'Unable to update password.';
            errorMessage.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    });

    async function pollEmployeeLiveRoot(root) {
        var baseUrl = root.getAttribute('data-live-sync-url');
        if (!baseUrl) {
            return;
        }

        var sig = root.getAttribute('data-live-signature') || '';

        try {
            var sep = baseUrl.indexOf('?') >= 0 ? '&' : '?';
            var res = await fetch(baseUrl + sep + 'signature=' + encodeURIComponent(sig), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            if (!res.ok) {
                return;
            }
            var data = await res.json();
            // Never replace SSR markup: live-sync only tracks signatures (replacing innerHTML caused blank pages for some sessions/browsers).
            if (data.signature) {
                root.setAttribute('data-live-signature', data.signature);
            }
            if (root.id === 'employee-dashboard-live' && Array.isArray(data.notifications)) {
                window.dispatchEvent(new CustomEvent('employee-notifications-updated', { detail: data }));
            }
        } catch (e) {
            console.debug('employee live-sync', e);
        }
    }

    function pollEmployeeLiveRegions() {
        document.querySelectorAll('[data-live-sync-url]').forEach(function (root) {
            pollEmployeeLiveRoot(root);
        });
    }

    setInterval(pollEmployeeLiveRegions, 12000);
    document.body.addEventListener('ajax-layout-updated', pollEmployeeLiveRegions);
    pollEmployeeLiveRegions();
})();
</script>
</body>
</html>
