@php $themeToggleVariant = $themeToggleVariant ?? 'blue'; @endphp
<button type="button" class="theme-switch theme-switch--{{ $themeToggleVariant }}" id="theme-toggle-btn" aria-pressed="true" aria-label="Switch to light mode">
    <span class="theme-switch__track" aria-hidden="true">
        <span class="theme-switch__glow"></span>
        <span class="theme-switch__glyphs">
            <svg class="theme-switch__svg theme-switch__svg--sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.886-1.591 1.591M21 12h-2.25m-.886 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg class="theme-switch__svg theme-switch__svg--moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </span>
        <span class="theme-switch__thumb" aria-hidden="true"></span>
    </span>
</button>

<style>
    .theme-switch {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: none;
        background: transparent;
        cursor: pointer;
        font: inherit;
        vertical-align: middle;
        -webkit-tap-highlight-color: transparent;
    }

    .theme-switch:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 3px;
        border-radius: 999px;
    }

    .theme-switch__track {
        position: relative;
        width: 52px;
        height: 30px;
        border-radius: 999px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.08));
        box-shadow:
            inset 0 1px 1px rgba(255, 255, 255, 0.65),
            inset 0 -1px 2px rgba(15, 23, 42, 0.08),
            0 1px 2px rgba(15, 23, 42, 0.06);
        border: 1px solid var(--c-border-strong);
        transition: border-color 0.35s ease, box-shadow 0.35s ease, background 0.35s ease;
    }

    .theme-switch__glow {
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        opacity: 0;
        background: linear-gradient(120deg, transparent 30%, rgba(59, 130, 246, 0.35) 50%, transparent 70%);
        background-size: 200% 100%;
        animation: theme-switch-scan 3.2s linear infinite;
        pointer-events: none;
        transition: opacity 0.4s ease;
    }

    @keyframes theme-switch-scan {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .theme-switch__glyphs {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 9px;
        pointer-events: none;
    }

    .theme-switch__svg {
        width: 14px;
        height: 14px;
        color: var(--c-text-muted);
        opacity: 0.45;
        transition: color 0.3s ease, opacity 0.3s ease, transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .theme-switch__svg--sun {
        color: #ca8a04;
        opacity: 0.85;
    }

    .theme-switch__thumb {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: linear-gradient(160deg, #ffffff 0%, #f1f5f9 45%, #e2e8f0 100%);
        box-shadow:
            0 2px 6px rgba(15, 23, 42, 0.12),
            0 0 0 1px rgba(255, 255, 255, 0.9) inset;
        transition: transform 0.45s cubic-bezier(0.34, 1.45, 0.64, 1), background 0.4s ease, box-shadow 0.4s ease;
    }

    .theme-switch.is-dark .theme-switch__track {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.75));
        border-color: rgba(56, 189, 248, 0.35);
        box-shadow:
            inset 0 1px 0 rgba(56, 189, 248, 0.12),
            inset 0 -2px 8px rgba(0, 0, 0, 0.35),
            0 0 0 1px rgba(56, 189, 248, 0.08),
            0 4px 18px rgba(56, 189, 248, 0.12);
    }

    .theme-switch.is-dark .theme-switch__glow {
        opacity: 0.55;
    }

    .theme-switch.is-dark .theme-switch__svg--sun {
        opacity: 0.35;
        color: var(--c-text-muted);
        transform: scale(0.92);
    }

    .theme-switch.is-dark .theme-switch__svg--moon {
        opacity: 1;
        color: #7dd3fc;
        transform: scale(1.05);
    }

    .theme-switch:not(.is-dark) .theme-switch__svg--moon {
        opacity: 0.35;
        transform: scale(0.92);
    }

    .theme-switch.is-dark .theme-switch__thumb {
        transform: translateX(22px);
        background: radial-gradient(circle at 30% 25%, #38bdf8 0%, #1d4ed8 55%, #0f172a 100%);
        box-shadow:
            0 0 14px rgba(56, 189, 248, 0.45),
            0 2px 8px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
    }

    html[data-theme="dark"] .theme-switch__track {
        border-color: rgba(56, 189, 248, 0.28);
    }

    @media (prefers-reduced-motion: reduce) {
        .theme-switch__thumb,
        .theme-switch__track,
        .theme-switch__svg {
            transition-duration: 0.01ms !important;
        }
        .theme-switch__glow {
            animation: none;
            opacity: 0;
        }
    }

    /* Teal variant (employee portal) */
    .theme-switch--teal .theme-switch__glow {
        background: linear-gradient(120deg, transparent 30%, rgba(20, 184, 166, 0.38) 50%, transparent 70%);
    }

    .theme-switch--teal.is-dark .theme-switch__track {
        border-color: rgba(45, 212, 191, 0.38);
        box-shadow:
            inset 0 1px 0 rgba(45, 212, 191, 0.12),
            inset 0 -2px 8px rgba(0, 0, 0, 0.35),
            0 0 0 1px rgba(20, 184, 166, 0.1),
            0 4px 18px rgba(20, 184, 166, 0.14);
    }

    .theme-switch--teal.is-dark .theme-switch__svg--moon {
        color: #5eead4;
    }

    .theme-switch--teal.is-dark .theme-switch__thumb {
        background: radial-gradient(circle at 30% 25%, #2dd4bf 0%, #0f766e 55%, #042f2e 100%);
        box-shadow:
            0 0 14px rgba(45, 212, 191, 0.4),
            0 2px 8px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.22);
    }

    html[data-theme="dark"] .theme-switch--teal .theme-switch__track {
        border-color: rgba(45, 212, 191, 0.26);
    }
</style>

<script>
(function () {
    var KEY = 'hris-theme';

    function setBtnState() {
        var btn = document.getElementById('theme-toggle-btn');
        if (!btn) {
            return;
        }
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        btn.classList.toggle('is-dark', dark);
        btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
        btn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    }

    document.addEventListener('DOMContentLoaded', function () {
        setBtnState();
        var btn = document.getElementById('theme-toggle-btn');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function () {
            var cur = document.documentElement.getAttribute('data-theme') === 'dark';
            var next = cur ? 'light' : 'dark';
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            function apply() {
                document.documentElement.setAttribute('data-theme', next);
                try {
                    localStorage.setItem(KEY, next);
                } catch (e) {}
                setBtnState();
                window.dispatchEvent(new CustomEvent('hris-theme-changed', { detail: { theme: next } }));
            }
            if (reduce) {
                apply();
                return;
            }
            document.documentElement.classList.add('theme-transitioning');
            window.requestAnimationFrame(function () {
                apply();
            });
            window.setTimeout(function () {
                document.documentElement.classList.remove('theme-transitioning');
            }, 430);
        });
    });
})();
</script>
