@php $palette = $palette ?? 'admin'; @endphp
@if ($palette === 'employee')
        html[data-theme="dark"] {
            color-scheme: dark;
            --c-bg: #0e131f;
            --c-surface: #111827;
            --c-surface-2: #172033;
            --c-border: #1f293d;
            --c-border-strong: #334155;
            --c-primary: #0f766e;
            --c-primary-hover: #115e59;
            --c-primary-light: rgba(20, 184, 166, 0.16);
            --c-primary-text: #5eead4;
            --c-sidebar-bg: #0b1019;
            --c-sidebar-surface: #111827;
            --c-sidebar-hover: #1a2433;
            --c-sidebar-active: #0f766e;
            --c-sidebar-text: #cbd5e1;
            --c-sidebar-text-bright: #ffffff;
            --c-text: #ffffff;
            --c-text-secondary: #cbd5e1;
            --c-text-muted: #a8b3c2;
            --c-success-bg: rgba(20, 83, 45, 0.32);
            --c-success-border: #15803d;
            --c-success-text: #bbf7d0;
            --c-error-bg: rgba(127, 29, 29, 0.28);
            --c-error-border: #b91c1c;
            --c-error-text: #fecaca;
            --c-warning-bg: rgba(120, 53, 15, 0.28);
            --c-warning-border: #b45309;
            --c-warning-text: #fde68a;
            --c-topbar-bg: rgba(14, 19, 31, 0.92);
            --c-sidebar-rule: #1f293d;
            --c-form-focus-ring: rgba(20, 184, 166, 0.28);
            --c-scrollbar-thumb: #374151;
            --c-btn-hover-border: #4b5563;
            --c-danger: #f87171;
            --c-danger-hover: #ef4444;
            --c-modal-backdrop: rgba(0, 0, 0, 0.72);
            --c-topbar-badge-border: rgba(20, 184, 166, 0.35);
            --c-logout-hover-bg: rgba(127, 29, 29, 0.32);
            --c-logout-hover-border: #b91c1c;
            --c-logout-hover-text: #fecaca;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.22);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.28);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.32);
        }

        html[data-theme="dark"] .badge-green { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        html[data-theme="dark"] .badge-gray { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; }
        html[data-theme="dark"] .badge-red { background: rgba(248, 113, 113, 0.15); color: #fecaca; }

        html[data-theme="dark"] .stat-icon-teal { background: rgba(20, 184, 166, 0.16); color: #5eead4; }
        html[data-theme="dark"] .stat-icon-green { background: rgba(16, 185, 129, 0.16); color: #6ee7b7; }

        html[data-theme="dark"] .btn-primary:hover {
            box-shadow: 0 4px 16px rgba(20, 184, 166, 0.24);
        }
@else
        html[data-theme="dark"] {
            color-scheme: dark;
            --c-bg: #0e131f;
            --c-surface: #111827;
            --c-surface-2: #172033;
            --c-border: #1f293d;
            --c-border-strong: #334155;
            --c-primary: #1d4ed8;
            --c-primary-hover: #1e40af;
            --c-primary-light: rgba(37, 99, 235, 0.18);
            --c-primary-text: #bfdbfe;
            --c-sidebar-bg: #0b1019;
            --c-sidebar-surface: #111827;
            --c-sidebar-hover: #1a2433;
            --c-sidebar-active: #2563eb;
            --c-sidebar-text: #cbd5e1;
            --c-sidebar-text-bright: #ffffff;
            --c-text: #ffffff;
            --c-text-secondary: #cbd5e1;
            --c-text-muted: #a8b3c2;
            --c-success-bg: rgba(20, 83, 45, 0.32);
            --c-success-border: #15803d;
            --c-success-text: #bbf7d0;
            --c-error-bg: rgba(127, 29, 29, 0.28);
            --c-error-border: #b91c1c;
            --c-error-text: #fecaca;
            --c-warning-bg: rgba(120, 53, 15, 0.28);
            --c-warning-border: #b45309;
            --c-warning-text: #fde68a;
            --c-danger: #f87171;
            --c-danger-hover: #ef4444;
            --c-topbar-bg: rgba(14, 19, 31, 0.92);
            --c-sidebar-rule: #1f293d;
            --c-form-focus-ring: rgba(59, 130, 246, 0.28);
            --c-scrollbar-thumb: #374151;
            --c-btn-hover-border: #4b5563;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.22);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.28);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.32);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.36);
            --c-modal-backdrop: rgba(0, 0, 0, 0.72);
            --c-loading-accent-end: #93c5fd;
            --c-topbar-badge-border: rgba(59, 130, 246, 0.35);
            --c-logout-hover-bg: rgba(127, 29, 29, 0.32);
            --c-logout-hover-border: #b91c1c;
            --c-logout-hover-text: #fecaca;
        }

        html[data-theme="dark"] .badge-blue { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        html[data-theme="dark"] .badge-green { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
        html[data-theme="dark"] .badge-red { background: rgba(248, 113, 113, 0.15); color: #fecaca; }
        html[data-theme="dark"] .badge-gray { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; }

        html[data-theme="dark"] .stat-icon-blue { background: rgba(59, 130, 246, 0.18); color: #93c5fd; }
        html[data-theme="dark"] .stat-icon-green { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
        html[data-theme="dark"] .stat-icon-violet { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
        html[data-theme="dark"] .stat-icon-amber { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }

        html[data-theme="dark"] .hris-tile:hover {
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.16);
        }

        html[data-theme="dark"] .hris-tile-active {
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.28);
        }

        html[data-theme="dark"] .btn-primary:hover {
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.24);
        }

        html[data-theme="dark"] .brgy-stat-highlight {
            background: linear-gradient(120deg, rgba(59, 130, 246, 0.16) 0%, rgba(30, 58, 138, 0.18) 100%);
            border-color: rgba(59, 130, 246, 0.3);
        }

        html[data-theme="dark"] .brgy-stat-highlight .big {
            color: #bfdbfe;
        }

        html[data-theme="dark"] .brgy-stat-highlight .lbl {
            color: #93c5fd;
        }
@endif
