        .topbar-crumbs {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .topbar-back-link {
            display: flex;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 7px 11px;
            border: 1px solid var(--c-border-strong);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-text);
            font-size: 13px;
            font-weight: 650;
            text-decoration: none;
            flex-shrink: 0;
            white-space: nowrap;
            transition: background var(--transition), border-color var(--transition), color var(--transition);
        }

        .topbar-back-link:hover {
            border-color: var(--c-primary);
            background: var(--c-primary-light);
            color: var(--c-primary-text);
        }

        .topbar-back-link:focus-visible {
            outline: 2px solid var(--c-primary);
            outline-offset: 2px;
        }

        .topbar-back-link svg { width: 16px; height: 16px; flex-shrink: 0; }

        .topbar-page-context {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            margin: 0;
            color: var(--c-text);
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .topbar-crumbs { gap: 8px; }
            .topbar-back-link { min-height: 34px; padding: 6px 9px; }
            .topbar-page-context { font-size: 14px; }
        }
