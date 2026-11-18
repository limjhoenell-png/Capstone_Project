@media (prefers-reduced-motion: no-preference) {
    html.theme-transitioning,
    html.theme-transitioning *,
    html.theme-transitioning *::before,
    html.theme-transitioning *::after {
        transition-property: background-color, color, border-color, box-shadow, fill, stroke, opacity;
        transition-duration: 0.38s;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }
}

@media (prefers-reduced-motion: reduce) {
    html.theme-transitioning,
    html.theme-transitioning *,
    html.theme-transitioning *::before,
    html.theme-transitioning *::after {
        transition-duration: 0.01ms !important;
    }
}
