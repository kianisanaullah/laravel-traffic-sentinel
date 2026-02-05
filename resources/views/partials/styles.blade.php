<style>
    :root{
        --ts-bg: #0b1220;
        --ts-card-border: rgba(255,255,255,.12);
        --ts-muted: rgba(255,255,255,.55);
        --ts-line: rgba(255,255,255,.12);
        --ts-radius: 16px;
    }

    body{
        background:
            radial-gradient(1200px 500px at 10% -10%, rgba(99,102,241,.35), transparent 60%),
            radial-gradient(900px 450px at 90% 0%, rgba(16,185,129,.25), transparent 55%),
            radial-gradient(900px 450px at 20% 100%, rgba(236,72,153,.20), transparent 55%),
            var(--ts-bg);
        color: #e8eefc;
    }

    .ts-shell{ max-width: 1180px; }

    .ts-header{
        border: 1px solid var(--ts-line);
        background: rgba(255,255,255,.04);
        border-radius: var(--ts-radius);
        backdrop-filter: blur(10px);
    }

    .ts-title{ letter-spacing: .2px; font-weight: 800; line-height: 1.1; }
    .ts-subtitle{ color: var(--ts-muted); font-size: .95rem; }

    .ts-card{
        border-radius: var(--ts-radius);
        border: 1px solid var(--ts-card-border);
        background: rgba(255,255,255,.06);
        box-shadow: 0 18px 40px rgba(0,0,0,.25);
        backdrop-filter: blur(10px);
    }

    .ts-pill{
        border: 1px solid var(--ts-line);
        background: rgba(255,255,255,.04);
        border-radius: 999px;
        padding: .35rem .6rem;
        font-size: .85rem;
        color: rgba(255,255,255,.85);
    }

    .ts-table thead th{
        color: rgba(255,255,255,.75);
        font-weight: 600;
        border-bottom: 1px solid var(--ts-line);
        background: rgba(255,255,255,.03);
    }

    .ts-table td, .ts-table th{
        border-color: rgba(255,255,255,.08) !important;
        vertical-align: middle;
    }

    .ts-table tbody tr:hover{ background: rgba(255,255,255,.04); }

    .ts-badge{
        font-size: .78rem;
        border-radius: 999px;
        padding: .25rem .55rem;
        border: 1px solid rgba(255,255,255,.18);
        background: rgba(255,255,255,.06);
        color: rgba(255,255,255,.85);
    }

    .ts-empty{ padding: 2rem 1rem; color: var(--ts-muted); }
    .ts-foot{ color: rgba(255,255,255,.55); font-size: .85rem; }

    .ts-logo{ filter: drop-shadow(0 6px 14px rgba(99,102,241,.35)); }

    .nav-pills .nav-link{
        border-radius: 999px;
        padding: .35rem .75rem;
        border: 1px solid rgba(255,255,255,.10);
        color: rgba(255,255,255,.75);
    }
    .nav-pills .nav-link.active{
        color: #fff;
        background: rgba(99,102,241,.30);
        border-color: rgba(99,102,241,.45);
    }
</style>
