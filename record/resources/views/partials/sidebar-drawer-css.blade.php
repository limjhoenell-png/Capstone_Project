        .sidebar-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            padding: 0;
            border-radius: var(--radius-md);
            border: 1px solid var(--c-border);
            background: var(--c-surface);
            color: var(--c-text);
            cursor: pointer;
        }

        .sidebar-toggle svg { width: 18px; height: 18px; }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(15, 23, 42, 0.48);
        }

        body.sidebar-open { overflow: hidden; }

        .topbar-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .sidebar-toggle { display: inline-flex; }

            .sidebar {
                transform: translateX(-105%);
                width: min(var(--sidebar-w), calc(100vw - 56px));
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 12px 0 40px rgba(0, 0, 0, 0.28);
            }

            .sidebar-backdrop.open { display: block; }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .topbar {
                padding: 0 14px;
                gap: 10px;
            }

            .topbar-badge { display: none; }
        }
