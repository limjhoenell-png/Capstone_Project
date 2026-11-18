<script>
    (function () {
        if (window.__recordSidebarDrawer) {
            return;
        }
        window.__recordSidebarDrawer = true;

        var query = window.matchMedia('(max-width: 900px)');

        function sidebar() {
            return document.getElementById('app-sidebar');
        }

        function setOpen(open) {
            var panel = sidebar();
            var backdrop = document.querySelector('.sidebar-backdrop');
            var toggle = document.querySelector('[data-sidebar-toggle]');
            if (panel) {
                panel.classList.toggle('open', open);
            }
            if (backdrop) {
                backdrop.classList.toggle('open', open);
            }
            document.body.classList.toggle('sidebar-open', open);
            if (toggle) {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            }
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-sidebar-toggle]')) {
                event.preventDefault();
                var panel = sidebar();
                setOpen(!(panel && panel.classList.contains('open')));
                return;
            }

            if (event.target.closest('.sidebar-backdrop')) {
                setOpen(false);
                return;
            }

            if (query.matches && event.target.closest('#app-sidebar a')) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        window.addEventListener('resize', function () {
            if (!query.matches) {
                setOpen(false);
            }
        });
    })();
</script>
