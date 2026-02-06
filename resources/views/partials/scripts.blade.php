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
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    window.TS = window.TS || {};

    TS.initDataTable = function (selector, opts) {
        if (!window.jQuery || !$.fn.DataTable) return;

        const $el = $(selector);
        if (!$el.length) return;

        if ($.fn.DataTable.isDataTable($el[0])) return;

        $el.DataTable(Object.assign({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [],
            stateSave: true,
            responsive: true,
            autoWidth: false,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            }
        }, opts || {}));
        $el.on('draw.dt', function(){ document.dispatchEvent(new Event('ts:datatable:draw')); });
    };
</script>

