<script>
(function () {
    const key = 'traffic_sentinel_theme';
    const btn = document.getElementById('tsThemeBtn');
    const html = document.documentElement;

    const saved = localStorage.getItem(key);
    if (saved === 'light' || saved === 'dark') {
        html.setAttribute('data-bs-theme', saved);
    } else {
        html.setAttribute('data-bs-theme', 'dark');
    }

    btn?.addEventListener('click', function () {
        const next = (html.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem(key, next);

        const icon = btn.querySelector('i');
        if (icon) icon.className = (next === 'dark') ? 'bi bi-moon-stars' : 'bi bi-sun';
    });
})();
</script>
