<script>
(function () {
    try {
        var k = 'hris-theme';
        var s = localStorage.getItem(k);
        if (s === 'dark' || s === 'light') {
            document.documentElement.setAttribute('data-theme', s);
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();
</script>
