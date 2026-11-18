{{-- Cross-surface dark-mode refinements (buttons, chrome, analytics shells) --}}
        html[data-theme="dark"] .btn-danger {
            background: rgba(127, 29, 29, 0.35);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.45);
        }

        html[data-theme="dark"] .btn-danger:hover {
            background: rgba(153, 27, 27, 0.45);
            border-color: #f87171;
            color: #fff;
        }

        html[data-theme="dark"] .live-sync-indicator {
            color: var(--c-text-muted);
        }

        html[data-theme="dark"] .btn:not(.btn-primary):not(.btn-danger),
        html[data-theme="dark"] button.btn:not(.btn-primary):not(.btn-danger) {
            background: var(--c-surface);
            border-color: var(--c-border-strong);
            color: var(--c-text);
        }

        html[data-theme="dark"] .btn:not(.btn-primary):not(.btn-danger):hover,
        html[data-theme="dark"] button.btn:not(.btn-primary):not(.btn-danger):hover {
            background: var(--c-surface-2);
            border-color: #64748b;
            color: #ffffff;
        }

        html[data-theme="dark"] .btn-primary,
        html[data-theme="dark"] .btn-primary:hover {
            color: #ffffff;
        }

        html[data-theme="dark"] .theme-switch:focus-visible {
            outline-color: var(--c-primary-text);
        }

        html[data-theme="dark"] .link-primary,
        html[data-theme="dark"] .hris-tile:hover,
        html[data-theme="dark"] nav[role="navigation"] a:hover {
            color: var(--c-primary-text);
        }

        html[data-theme="dark"] .card,
        html[data-theme="dark"] .stat-card {
            border-color: var(--c-border);
        }

        html[data-theme="dark"] .text-secondary,
        html[data-theme="dark"] .table td.text-secondary {
            color: var(--c-text-secondary) !important;
        }

        html[data-theme="dark"] .form-control,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea {
            background: var(--c-surface-2);
            border-color: var(--c-border-strong);
            color: var(--c-text);
        }

        html[data-theme="dark"] ::placeholder {
            color: var(--c-text-muted);
            opacity: 1;
        }

        html:not([data-theme="dark"]) .form-control::placeholder,
        html:not([data-theme="dark"]) input::placeholder,
        html:not([data-theme="dark"]) textarea::placeholder {
            color: var(--c-text-muted);
            opacity: 1;
        }

        html:not([data-theme="dark"]) .btn:not(.btn-primary):not(.btn-danger),
        html:not([data-theme="dark"]) button.btn:not(.btn-primary):not(.btn-danger) {
            border-color: var(--c-border-strong);
            color: var(--c-text);
        }

        html:not([data-theme="dark"]) .ops-pill--bio {
            background: #ecfeff;
            color: #155e75;
        }

        html[data-theme="dark"] .ops-pill--bio {
            background: rgba(8, 145, 178, 0.22);
            color: #a5f3fc;
        }

        html[data-theme="dark"] .ops-pill--manual {
            background: rgba(51, 65, 85, 0.7);
            color: #e2e8f0;
        }

        .btn:focus-visible,
        button:not(.theme-switch):focus-visible,
        a:focus-visible {
            outline: 2px solid var(--c-primary-text);
            outline-offset: 2px;
        }

        input[type="checkbox"],
        input[type="radio"] {
            accent-color: var(--c-primary);
        }

        html[data-theme="dark"] ::selection {
            background: var(--c-primary-light);
            color: var(--c-text);
        }

        .payroll-settings-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: var(--c-warning-bg);
            border: 1px solid var(--c-warning-border);
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            font-size: 13.5px;
            color: var(--c-warning-text);
        }

        html[data-theme="dark"] .logbook-user-modal-panel {
            border: 1px solid var(--c-border);
        }
