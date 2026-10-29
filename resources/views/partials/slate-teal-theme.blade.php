{{-- Shared design system for every hotel and super-admin page. Light by default; dark is softer, never pitch black. --}}
@php($themeScope = auth()->check() ? 'user-'.auth()->id() : (auth('super_admin')->check() ? 'sa-'.auth('super_admin')->id() : 'guest'))
<meta name="theme-scope" content="{{ $themeScope }}">
<meta name="theme-preference" content="{{ auth()->user()?->theme_preference ?? auth('super_admin')->user()?->theme_preference ?? '' }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Apply the saved theme before first paint, so a dark-mode user never sees a flash of light. --}}
<script>(function () { try { var m = document.querySelector('meta[name="theme-scope"]'); var t = localStorage.getItem('artflow-theme' + (m && m.content ? ':' + m.content : '')); if (!t) { var p = document.querySelector('meta[name="theme-preference"]'); t = p ? p.content : ''; } if (t === 'dark' || t === 'light') { document.documentElement.setAttribute('data-theme', t); } } catch (e) {} })();</script>
{{-- Alpine.js powers every menu, modal and dropdown on hotel pages. The super-admin layout loads it itself. --}}
@unless (str_starts_with(request()->route()?->getName() ?? '', 'super-admin'))
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.14.1/cdn.min.js"></script>
@endunless
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
    :root {
        --st-bg: #f5f7fb;
        --st-card: #ffffff;
        --st-side: #ffffff;
        --st-text: #1e293b;
        --st-muted: #64748b;
        --st-line: #e6ebf2;
        --st-hover: #f6f8fb;
        --st-accent: #0f766e;
        --st-accent-hover: #0d9488;
        --st-accent-soft: #e7f6f4;
        --st-accent-text: #0f766e;
        --st-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 4px 14px rgba(15, 23, 42, .04);
        --st-shadow-hover: 0 2px 4px rgba(15, 23, 42, .05), 0 12px 28px rgba(15, 23, 42, .09);
        --st-radius: 16px;
        --st-font: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }
    html[data-theme="dark"] {
        --st-bg: #151c2c;
        --st-card: #1c2638;
        --st-side: #1a2336;
        --st-text: #e6ebf3;
        --st-muted: #9aa8bd;
        --st-line: #2c3950;
        --st-hover: #222e44;
        --st-accent: #2dd4bf;
        --st-accent-hover: #5eead4;
        --st-accent-soft: rgba(45, 212, 191, .12);
        --st-accent-text: #5eead4;
        --st-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        --st-shadow-hover: 0 12px 28px rgba(0, 0, 0, .28);
    }

    /* Base typography */
    html, body { font-family: var(--st-font) !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
    body { background: var(--st-bg) !important; color: var(--st-text) !important; font-size: 14px; line-height: 1.6; }
    h1 { font-size: 1.375rem; font-weight: 700; letter-spacing: -.02em; line-height: 1.3; }
    h2, .section-title { font-size: 1rem; font-weight: 600; letter-spacing: -.01em; }
    h3 { font-size: .9375rem; font-weight: 600; }
    p, small, .muted, .text-secondary { color: var(--st-muted); }
    .eyebrow, .kpi-label, .label { font-size: 11px !important; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--st-muted) !important; }
    .kpi-value { font-size: 1.5rem !important; font-weight: 700; letter-spacing: -.02em; line-height: 1.2; font-variant-numeric: tabular-nums; color: var(--st-text) !important; }

    /* Cards and panels */
    .surface, .kpi, .card, .feature, .form-card, .hero, .panel {
        background: var(--st-card) !important;
        border: 1px solid var(--st-line) !important;
        border-radius: var(--st-radius) !important;
        box-shadow: var(--st-shadow);
        color: var(--st-text) !important;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .surface:hover, .kpi:hover, .card:hover { box-shadow: var(--st-shadow-hover); }
    .kpi:hover, .card:hover { transform: translateY(-2px); }
    .hero { border-left: 4px solid var(--st-accent) !important; }
    .hero h1, .hero p { color: inherit !important; }

    /* Dashboard building blocks: plain CSS, so layout never depends on utility classes. */
    .section-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .section-head h2 { margin: 0; }
    .section-head p { margin: 2px 0 0; font-size: 12.5px; }
    .shift-panel { margin-top: 24px; padding: 22px 24px; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 18px; }
    .stat-tile { padding: 16px 18px; border: 1px solid var(--st-line); border-radius: 14px; background: var(--st-hover); }
    .stat-tile .kpi-label { margin-bottom: 6px; }
    .stat-value { font-size: 1.25rem; font-weight: 700; color: var(--st-text); letter-spacing: -.01em; font-variant-numeric: tabular-nums; }
    .text-pos { color: #059669 !important; }
    .text-neg { color: #e11d48 !important; }
    .btn-compact { padding: 8px 14px; font-size: 13px; white-space: nowrap; }
    .form-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) auto; gap: 16px; align-items: end; }
    @media (max-width: 760px) { .form-row { grid-template-columns: 1fr; } }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .shift-table { margin-top: 0; }

    /* Operational pages: housekeeping, POS and night audit share these components. */
    .page { max-width: 1280px; margin: 0 auto; padding: 24px 28px 56px; }
    .page-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
    .page-head h1 { margin: 0; }
    .page-head p { margin: 4px 0 0; font-size: 13px; color: var(--st-muted); }
    .chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background-color .15s ease, border-color .15s ease; }
    .chip:hover { background: var(--st-hover); }
    .chip.is-active { background: var(--st-accent); border-color: var(--st-accent); color: #fff; }
    .chip .count { font-weight: 500; opacity: .8; }
    .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
    .pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .pill--clean { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .pill--dirty { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .pill--inspect { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
    .pill--maint { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .pill--neutral { background: var(--st-hover); color: var(--st-muted); border-color: var(--st-line); }
    .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
    .room-card { background: var(--st-card); border: 1px solid var(--st-line); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; box-shadow: var(--st-shadow); transition: box-shadow .2s ease, transform .2s ease; }
    .room-card:hover { box-shadow: var(--st-shadow-hover); transform: translateY(-2px); }
    .room-card h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--st-text); }
    .room-card .meta { font-size: 12.5px; color: var(--st-muted); }
    .room-card .head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin: 0; }
    .action-btn { padding: 8px 10px; border-radius: 9px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; transition: background-color .15s ease, border-color .15s ease; }
    .action-btn:hover:not([disabled]) { background: var(--st-hover); }
    .action-btn[disabled] { opacity: .45; cursor: default; }
    .action-btn--clean:hover:not([disabled]) { border-color: #a7f3d0; color: #047857; }
    .action-btn--dirty:hover:not([disabled]) { border-color: #fecaca; color: #b91c1c; }
    .action-btn--inspect:hover:not([disabled]) { border-color: #c7d2fe; color: #4338ca; }
    .action-btn--maint:hover:not([disabled]) { border-color: #fde68a; color: #b45309; }
    .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(176px, 1fr)); gap: 12px; }
    .menu-card { display: flex; flex-direction: column; justify-content: space-between; gap: 10px; text-align: left; background: var(--st-card); border: 1px solid var(--st-line); border-radius: 14px; padding: 14px; cursor: pointer; font: inherit; color: var(--st-text); box-shadow: var(--st-shadow); transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease; }
    .menu-card:hover { box-shadow: var(--st-shadow-hover); transform: translateY(-2px); border-color: var(--st-accent); }
    .menu-card .name { font-size: 14px; font-weight: 600; color: var(--st-text); }
    .menu-card .desc { font-size: 12px; color: var(--st-muted); }
    .menu-card .price { font-size: 14px; font-weight: 700; color: var(--st-accent-text); font-variant-numeric: tabular-nums; }
    .pos-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
    .cart { position: sticky; top: 84px; padding: 20px; }
    .cart-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--st-line); font-size: 13.5px; }
    .cart-line:last-child { border-bottom: 0; }
    .qty { display: inline-flex; align-items: center; gap: 6px; }
    .qty button { width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); font: inherit; font-weight: 700; cursor: pointer; }
    .qty button:hover { background: var(--st-hover); }
    .qty span { min-width: 18px; text-align: center; font-weight: 600; }
    .sum-row { display: flex; justify-content: space-between; font-size: 13.5px; padding: 6px 0; color: var(--st-muted); }
    .sum-total { font-size: 1.25rem; font-weight: 700; color: var(--st-text); padding-top: 10px; border-top: 1px solid var(--st-line); margin-top: 6px; font-variant-numeric: tabular-nums; }
    .field-label { display: block; font-size: 12px; font-weight: 600; color: var(--st-text); margin-bottom: 6px; }
    .input, .select { width: 100%; padding: 9px 12px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 13.5px; }
    .input:focus, .select:focus { outline: none; border-color: var(--st-accent); box-shadow: 0 0 0 3px var(--st-accent-soft); }
    .btn-block { width: 100%; padding: 11px 14px; border: 0; border-radius: 10px; background: var(--st-accent); color: #fff; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color .15s ease; }
    .btn-block:hover:not([disabled]) { background: var(--st-accent-hover); }
    .btn-block[disabled] { opacity: .45; cursor: not-allowed; }
    .notice-ok, .notice-warn, .notice-err { padding: 12px 16px; border-radius: 12px; font-size: 13.5px; border: 1px solid; margin-bottom: 16px; }
    .notice-ok { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
    .notice-warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .notice-err { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .check-list { list-style: none; margin: 0; padding: 0; }
    .check-list li { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--st-line); }
    .check-list li:last-child { border-bottom: 0; }
    .check-list .detail { font-size: 12.5px; color: var(--st-muted); }
    .form-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) auto; gap: 16px; align-items: end; }
    @media (max-width: 760px) { .form-row { grid-template-columns: 1fr; } }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .text-pos { color: #059669 !important; }
    .text-neg { color: #e11d48 !important; }
    @media (max-width: 1024px) { .pos-layout { grid-template-columns: 1fr; } .cart { position: static; } }
    @media (max-width: 900px) { .page { padding: 16px; } }

    /* Form controls without their own class (staff invites, access forms) get the same look as the rest of the system. */
    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="submit"]), select, textarea {
        min-height: 40px; padding: 8px 12px; border: 1px solid var(--st-line); border-radius: 10px;
        background: var(--st-card); color: var(--st-text); font: inherit; font-size: 13.5px;
    }
    input:focus, select:focus, textarea:focus { outline: none; border-color: var(--st-accent); box-shadow: 0 0 0 3px var(--st-accent-soft); }
    label { font-size: 12.5px; font-weight: 600; color: var(--st-text); }
    button:not([class]), input[type="submit"]:not([class]) {
        min-height: 40px; padding: 8px 16px; border: 1px solid var(--st-accent); border-radius: 10px;
        background: var(--st-accent); color: #ffffff; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
        transition: background-color .15s ease, transform .15s ease;
    }
    button:not([class]):hover { background: var(--st-accent-hover); transform: translateY(-1px); }
    /* Tables inside cards keep a clear margin on both sides; header text is bold. */
    .card > .table-responsive, .surface > .table-responsive, .card > .table-wrap, .surface > .table-wrap { margin: 0 18px 18px; border-radius: 12px; }
    .table-clean th, th { font-weight: 700 !important; }
    /* Tables */
    .table-responsive, .table-wrap { border-radius: var(--st-radius); overflow: hidden; border: 1px solid var(--st-line); background: var(--st-card); }
    .table-clean th, th { color: var(--st-muted) !important; font-size: 11px !important; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; background: var(--st-hover) !important; border-bottom: 1px solid var(--st-line) !important; }
    .table-clean td, td { font-size: 13px; color: var(--st-text) !important; border-bottom: 1px solid var(--st-line) !important; }
    .table-clean tbody tr, tbody tr { transition: background-color .15s ease; }
    .table-clean tbody tr:hover td, tbody tr:hover td { background: var(--st-hover) !important; color: var(--st-text) !important; }

    /* Badges, buttons, links */
    .badge-soft, .badge { background: var(--st-accent-soft) !important; color: var(--st-accent-text) !important; border-radius: 999px; font-size: 12px; font-weight: 600; padding: 4px 10px; display: inline-flex; align-items: center; }
    .quick-action { background: var(--st-card) !important; border: 1px solid var(--st-line) !important; color: var(--st-text) !important; border-radius: 12px; font-weight: 600; font-size: 13px; transition: background-color .15s ease, border-color .15s ease, transform .15s ease; }
    .quick-action:hover { background: var(--st-accent-soft) !important; border-color: var(--st-accent) !important; color: var(--st-accent-text) !important; transform: translateY(-1px); }
    .btn-primary, .inventory-button.primary, .primary-btn { background: var(--st-accent) !important; border-color: var(--st-accent) !important; color: #fff !important; border-radius: 10px; font-weight: 600; font-size: 13px; transition: background-color .15s ease, transform .15s ease; }
    .btn-primary:hover, .inventory-button.primary:hover, .primary-btn:hover { background: var(--st-accent-hover) !important; transform: translateY(-1px); }
    input, select, textarea { font-family: var(--st-font); background: var(--st-card); color: var(--st-text); border-color: var(--st-line); }
    button, a { transition: background-color .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease; }
    :focus-visible { outline: 2px solid var(--st-accent); outline-offset: 2px; border-radius: 8px; }
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: 0ms !important; animation-duration: 0ms !important; } }

    /* Sidebar: light, quiet, professional */
    .hotel-sidebar {
        width: 256px !important;
        padding: 18px 14px !important;
        background: var(--st-side) !important;
        color: var(--st-text) !important;
        border-right: 1px solid var(--st-line) !important;
        box-shadow: none !important;
        gap: 6px;
    }
    body.hotel-sidebar-page { padding-left: 256px !important; }
    .hotel-sidebar__brand { display: flex; align-items: center; gap: 10px; padding: 4px 8px 16px; border-bottom: 1px solid var(--st-line); margin-bottom: 8px; }
    .hotel-sidebar__brand strong { display: block; font-size: 14px; font-weight: 700; color: var(--st-text); line-height: 1.25; }
    .hotel-sidebar__brand small { display: block; font-size: 11px; color: var(--st-muted); letter-spacing: .02em; }
    .hotel-sidebar__mark { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--st-accent); color: #fff; font-weight: 700; font-size: 15px; flex: 0 0 auto; }
    .hotel-sidebar__control { display: none; }
    .hotel-sidebar__nav { display: flex; flex-direction: column; gap: 2px; flex: 1 1 auto; overflow-y: auto; }
    .hotel-sidebar__group { margin-top: 10px; }
    .hotel-sidebar__group-title { padding: 6px 10px; font-size: 10.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--st-muted); }
    .hotel-sidebar__link { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; color: var(--st-text) !important; font-size: 13.5px; font-weight: 500; text-decoration: none; border: 0; background: transparent; width: 100%; text-align: left; cursor: pointer; }
    .hotel-sidebar__link:hover { background: var(--st-hover) !important; color: var(--st-text) !important; }
    .hotel-sidebar__link.is-active { background: var(--st-accent-soft) !important; color: var(--st-accent-text) !important; font-weight: 600; box-shadow: inset 3px 0 0 var(--st-accent); }
    .hotel-sidebar__icon { width: 18px; height: 18px; flex: 0 0 18px; color: var(--st-muted); }
    .hotel-sidebar__link.is-active .hotel-sidebar__icon { color: var(--st-accent-text); }
    .hotel-sidebar__label { white-space: nowrap; }
    .hotel-sidebar__footer { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--st-line); display: flex; flex-direction: column; gap: 8px; }
    .hotel-sidebar__user { display: flex; align-items: center; gap: 10px; padding: 8px 6px; }
    .hotel-sidebar__avatar { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: var(--st-accent-soft); color: var(--st-accent-text); font-weight: 700; font-size: 13px; flex: 0 0 auto; }
    .hotel-sidebar__who strong { display: block; font-size: 13px; font-weight: 600; color: var(--st-text); line-height: 1.3; }
    .hotel-sidebar__who small { display: block; font-size: 11.5px; color: var(--st-muted); }
    .hotel-sidebar__logout { color: #dc2626 !important; }
    .hotel-sidebar__logout .hotel-sidebar__icon { color: #dc2626; }
    .hotel-logout-form { margin: 0; }

    /* Sidebar is always a fixed left rail. A saved "top bar" layout preference must never push it into the page flow. */
    body.hotel-sidebar-page[data-hotel-layout-mode="topbar"] .hotel-sidebar,
    .hotel-sidebar {
        position: fixed !important;
        top: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        display: flex !important;
        transform: none !important;
        z-index: 60;
    }
    body.hotel-sidebar-page[data-hotel-layout-mode="topbar"],
    body.hotel-sidebar-page { padding-left: 256px !important; }
    /* Sidebar colours are set explicitly, so no page stylesheet can make its text disappear. */
    .hotel-sidebar, .hotel-sidebar * { font-family: var(--st-font) !important; }
    .hotel-sidebar { background: #ffffff !important; color: #1e293b !important; }
    .hotel-sidebar strong, .hotel-sidebar .hotel-sidebar__who strong, .hotel-sidebar__brand strong { color: #0f172a !important; }
    .hotel-sidebar small, .hotel-sidebar .hotel-sidebar__who small, .hotel-sidebar__brand small { color: #64748b !important; }
    .hotel-sidebar .hotel-sidebar__group-title { color: #64748b !important; }
    .hotel-sidebar .hotel-sidebar__link, .hotel-sidebar .hotel-sidebar__link span, .hotel-sidebar .hotel-sidebar__label { color: #334155 !important; opacity: 1 !important; }
    .hotel-sidebar .hotel-sidebar__link .hotel-sidebar__icon { color: #64748b !important; stroke: currentColor; }
    .hotel-sidebar .hotel-sidebar__link.is-active, .hotel-sidebar .hotel-sidebar__link.is-active span { color: #0f766e !important; }
    .hotel-sidebar .hotel-sidebar__link.is-active .hotel-sidebar__icon { color: #0f766e !important; }
    .hotel-sidebar .hotel-sidebar__logout, .hotel-sidebar .hotel-sidebar__logout span, .hotel-sidebar .hotel-sidebar__logout .hotel-sidebar__icon { color: #dc2626 !important; }
    .hotel-sidebar .hotel-sidebar__avatar { color: #0f766e !important; }
    .hotel-sidebar .hotel-sidebar__mark { color: #ffffff !important; }
    html[data-theme="dark"] .hotel-sidebar { background: #1a2336 !important; color: #e6ebf3 !important; }
    html[data-theme="dark"] .hotel-sidebar strong, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__link span, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__label, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__who strong, html[data-theme="dark"] .hotel-sidebar__brand strong { color: #e6ebf3 !important; }
    html[data-theme="dark"] .hotel-sidebar small, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__group-title, html[data-theme="dark"] .hotel-sidebar__brand small, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__who small { color: #9aa8bd !important; }
    html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__link.is-active, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__link.is-active span, html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__link.is-active .hotel-sidebar__icon { color: #5eead4 !important; }
    html[data-theme="dark"] .hotel-sidebar .hotel-sidebar__link span { color: #cbd5e1 !important; }
    /* Locked menu items: visible for context, but not clickable for this role. */
    .hotel-sidebar__link.is-locked { cursor: not-allowed; opacity: .42; pointer-events: none; user-select: none; }
    .hotel-sidebar__link.is-locked:hover { background: transparent !important; }
    .hotel-sidebar__lock { width: 14px; height: 14px; margin-left: auto; flex: 0 0 14px; color: var(--st-muted); }

    /* Explicit sidebar column: brand at top, navigation scrolls, user and logout pinned to the bottom. */
    .hotel-sidebar {
        top: 0 !important; left: 0 !important; bottom: auto !important; right: auto !important;
        height: 100vh !important;
        width: 256px !important;
        flex-direction: column !important;
        justify-content: flex-start !important;
        align-items: stretch !important;
        overflow: hidden !important;
        padding: 18px 14px !important;
    }
    .hotel-sidebar > * { flex-shrink: 0; }
    .hotel-sidebar__brand { order: 0; }
    .hotel-sidebar__control { display: none !important; }
    .hotel-sidebar__nav { order: 1; flex: 1 1 auto !important; min-height: 0; overflow-y: auto !important; padding-right: 4px; }
    .hotel-sidebar__footer { order: 2; margin-top: auto !important; }
    .hotel-sidebar__footer, .hotel-sidebar__footer * { text-align: left; }
    .hotel-sidebar__group-title { white-space: nowrap; }
    .hotel-sidebar__brand strong, .hotel-sidebar__brand small { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hotel-sidebar__who strong, .hotel-sidebar__who small { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 160px; }
    .hotel-app { display: block; min-height: 100vh; }
    [data-hotel-layout-toggle], [data-hotel-layout-alternate] { display: none !important; }
    .hotel-sidebar__scrim { display: none !important; }

    /* Topbar: sticky, light, aligned with content */
    .hotel-main { min-height: 100vh; }
    .hotel-topbar { display: flex !important; position: sticky; top: 0; z-index: 40; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 28px; background: var(--st-card); border-bottom: 1px solid var(--st-line); }
    .hotel-topbar__identity { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .hotel-topbar__heading { font-size: 14px; font-weight: 600; color: var(--st-text); white-space: nowrap; }
    .hotel-topbar__nav { display: flex; flex-wrap: wrap; gap: 4px; }
    .hotel-topbar__link { padding: 6px 10px; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--st-muted) !important; text-decoration: none; }
    .hotel-topbar__link:hover { background: var(--st-hover); color: var(--st-text) !important; }
    .hotel-topbar__link.is-active { background: var(--st-accent-soft); color: var(--st-accent-text) !important; }
    .hotel-topbar__tools { display: flex; align-items: center; gap: 8px; }
    .hotel-content { padding: 0; }
    .header-role-badge { border-radius: 999px; font-size: 11.5px; font-weight: 600; padding: 3px 9px; border: 1px solid transparent; }
    .header-role-badge--owner { background: #f3e8ff; color: #6b21a8; border-color: #e9d5ff; }
    .header-role-badge--admin { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
    .header-role-badge--manager { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
    .header-role-badge--receptionist { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .header-role-badge--housekeeping { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    html[data-theme="dark"] .header-role-badge--owner { background: rgba(168, 85, 247, .16); color: #e9d5ff; border-color: transparent; }
    html[data-theme="dark"] .header-role-badge--admin { background: rgba(99, 102, 241, .18); color: #c7d2fe; border-color: transparent; }
    html[data-theme="dark"] .header-role-badge--manager { background: rgba(59, 130, 246, .18); color: #bfdbfe; border-color: transparent; }
    html[data-theme="dark"] .header-role-badge--receptionist { background: rgba(16, 185, 129, .16); color: #a7f3d0; border-color: transparent; }
    html[data-theme="dark"] .header-role-badge--housekeeping { background: rgba(245, 158, 11, .16); color: #fde68a; border-color: transparent; }

    /* Top bar log out: a clear, quiet button that turns red on hover. */
    .topbar-logout-form { margin: 0; }
    .topbar-logout { display: inline-flex; align-items: center; gap: 6px; min-height: 34px; padding: 6px 12px; border: 1px solid var(--st-line); border-radius: 999px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background-color .15s ease, color .15s ease, border-color .15s ease; }
    .topbar-logout:hover { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .topbar-logout:focus-visible { outline: 2px solid #dc2626; outline-offset: 2px; }
    /* Theme switcher and layout toggles match the system */
    .theme-switcher, [data-hotel-layout-toggle] { font-family: var(--st-font); font-size: 12.5px; font-weight: 600; border-radius: 999px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); padding: 6px 12px; cursor: pointer; }
    .theme-switcher:hover, [data-hotel-layout-toggle]:hover { background: var(--st-hover); }
    .quick-actions-heading { color: var(--st-muted); font-size: 11px; letter-spacing: .07em; text-transform: uppercase; font-weight: 600; margin-bottom: .5rem; }

    @media (max-width: 900px) {
        body.hotel-sidebar-page { padding-left: 0 !important; }
        .hotel-sidebar { transform: translateX(-100%); }
        .hotel-sidebar[data-hotel-sidebar-mobile-open="true"], body[data-hotel-sidebar-mobile-open="true"] .hotel-sidebar { transform: translateX(0); }
        .hotel-topbar { padding: 10px 16px; }
        .hotel-topbar__nav { display: none; }
    }
    /* Light/dark switch: bottom-right, above the sidebar and page content, always visible. */
    .theme-switcher { position: fixed !important; left: auto !important; top: auto !important; right: 18px !important; bottom: 18px !important; z-index: 9000 !important; }

    /* Footer sits at the bottom of the screen on short pages; tables fill their card width. */
    .hotel-main { display: flex; flex-direction: column; min-height: 100vh; }
    .hotel-content { flex: 1 0 auto; display: flex; flex-direction: column; }
    .hotel-content > .designer-credit { margin-top: auto !important; }
    .table-wrap table, .table-responsive table { width: 100%; }

    /* Housekeeping status pills */
    /* Housekeeping status pills: cleaning (yellow) and out of service (purple), light and dark. */
    .pill--cleaning { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
    .pill--service { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
    html[data-theme="dark"] .pill--cleaning { background: rgba(234, 179, 8, .16); color: #fde68a; border-color: rgba(234, 179, 8, .35); }
    html[data-theme="dark"] .pill--service { background: rgba(139, 92, 246, .18); color: #ddd6fe; border-color: rgba(139, 92, 246, .35); }

    [x-cloak] { display: none !important; }
    /* ===== Dark mode: one consistent palette across every hotel page ===== */
    html[data-theme="dark"] { color-scheme: dark; }
    html[data-theme="dark"] body,
    html[data-theme="dark"] .hotel-main,
    html[data-theme="dark"] .hotel-content,
    html[data-theme="dark"] .page,
    html[data-theme="dark"] .wrap,
    html[data-theme="dark"] main,
    html[data-theme="dark"] .shell { background-color: var(--st-bg) !important; color: var(--st-text) !important; }

    html[data-theme="dark"] .surface,
    html[data-theme="dark"] .card,
    html[data-theme="dark"] .kpi,
    html[data-theme="dark"] .panel,
    html[data-theme="dark"] .hero,
    html[data-theme="dark"] .stat-tile,
    html[data-theme="dark"] .room-card,
    html[data-theme="dark"] .menu-card,
    html[data-theme="dark"] .cart,
    html[data-theme="dark"] .summary-card,
    html[data-theme="dark"] .account,
    html[data-theme="dark"] .head,
    html[data-theme="dark"] .form-card,
    html[data-theme="dark"] .table-responsive,
    html[data-theme="dark"] .table-wrap,
    html[data-theme="dark"] .notice-ok,
    html[data-theme="dark"] .notice-warn,
    html[data-theme="dark"] .notice-err,
    html[data-theme="dark"] .settle-dialog,
    html[data-theme="dark"] dialog,
    html[data-theme="dark"] .folio-dialog,
    html[data-theme="dark"] .chip,
    html[data-theme="dark"] .quick-action,
    html[data-theme="dark"] .action-btn,
    html[data-theme="dark"] .topbar-logout,
    html[data-theme="dark"] .theme-switcher,
    html[data-theme="dark"] [data-hotel-layout-toggle],
    html[data-theme="dark"] .btn-ghost,
    html[data-theme="dark"] .button.secondary,
    html[data-theme="dark"] .button:not(.primary) {
        background-color: var(--st-card) !important;
        color: var(--st-text) !important;
        border-color: var(--st-line) !important;
    }

    /* Tables: rows and cells take the card colour; headers a step lighter; text always readable. */
    html[data-theme="dark"] table,
    html[data-theme="dark"] tbody,
    html[data-theme="dark"] tr,
    html[data-theme="dark"] td { background-color: var(--st-card) !important; color: var(--st-text) !important; border-color: var(--st-line) !important; }
    html[data-theme="dark"] th { background-color: var(--st-hover) !important; color: var(--st-muted) !important; border-color: var(--st-line) !important; }
    html[data-theme="dark"] tbody tr:hover td { background-color: var(--st-hover) !important; }
    html[data-theme="dark"] .empty, html[data-theme="dark"] .muted, html[data-theme="dark"] .text-secondary { color: var(--st-muted) !important; }

    /* Form controls */
    html[data-theme="dark"] input:not([type="checkbox"]):not([type="radio"]),
    html[data-theme="dark"] select,
    html[data-theme="dark"] textarea {
        background-color: var(--st-hover) !important;
        color: var(--st-text) !important;
        border-color: var(--st-line) !important;
        color-scheme: dark;
    }
    html[data-theme="dark"] input::placeholder, html[data-theme="dark"] textarea::placeholder { color: var(--st-muted) !important; opacity: .8; }
    html[data-theme="dark"] option { background: var(--st-card); color: var(--st-text); }

    /* Text: headings, labels and strong text in the primary colour; captions in the muted colour. */
    html[data-theme="dark"] h1, html[data-theme="dark"] h2, html[data-theme="dark"] h3, html[data-theme="dark"] h4,
    html[data-theme="dark"] strong, html[data-theme="dark"] b, html[data-theme="dark"] label,
    html[data-theme="dark"] dt, html[data-theme="dark"] dd, html[data-theme="dark"] li,
    html[data-theme="dark"] .value, html[data-theme="dark"] .title, html[data-theme="dark"] .section-title,
    html[data-theme="dark"] .stat-value, html[data-theme="dark"] .kpi-value { color: var(--st-text) !important; }
    html[data-theme="dark"] .eyebrow, html[data-theme="dark"] .label, html[data-theme="dark"] .kpi-label,
    html[data-theme="dark"] .note, html[data-theme="dark"] small { color: var(--st-muted) !important; }
    html[data-theme="dark"] p, html[data-theme="dark"] span { color: inherit; }

    /* Theme switch button: always visible, in both modes */
    .theme-switcher { background: var(--st-card) !important; color: var(--st-text) !important; border: 1px solid var(--st-line) !important; box-shadow: var(--st-shadow-hover); opacity: 1 !important; visibility: visible !important; }
    .theme-switcher__icon { border-color: var(--st-accent) !important; }

    /* Status pills and notices in dark mode: tinted backgrounds with readable text. */
    html[data-theme="dark"] .pill--clean, html[data-theme="dark"] .balance-clear { background: rgba(16, 185, 129, .16) !important; color: #6ee7b7 !important; border-color: rgba(16, 185, 129, .35) !important; }
    html[data-theme="dark"] .pill--dirty, html[data-theme="dark"] .balance-due { background: rgba(244, 63, 94, .16) !important; color: #fda4af !important; border-color: rgba(244, 63, 94, .35) !important; }
    html[data-theme="dark"] .pill--inspect { background: rgba(99, 102, 241, .18) !important; color: #c7d2fe !important; border-color: rgba(99, 102, 241, .35) !important; }
    html[data-theme="dark"] .pill--maint { background: rgba(245, 158, 11, .16) !important; color: #fcd34d !important; border-color: rgba(245, 158, 11, .35) !important; }
    html[data-theme="dark"] .pill--neutral { background: var(--st-hover) !important; color: var(--st-muted) !important; border-color: var(--st-line) !important; }
    html[data-theme="dark"] .pill--neutral.pill--accent { background: var(--st-accent-soft) !important; color: var(--st-accent-text) !important; }
    html[data-theme="dark"] .notice-ok { background: rgba(16, 185, 129, .14) !important; color: #a7f3d0 !important; border-color: rgba(16, 185, 129, .35) !important; }
    html[data-theme="dark"] .notice-warn { background: rgba(245, 158, 11, .14) !important; color: #fde68a !important; border-color: rgba(245, 158, 11, .35) !important; }
    html[data-theme="dark"] .notice-err { background: rgba(244, 63, 94, .14) !important; color: #fecdd3 !important; border-color: rgba(244, 63, 94, .35) !important; }
    html[data-theme="dark"] .pill::before { background: currentColor; }
    html[data-theme="dark"] .check-list li { border-color: var(--st-line) !important; }
</style>
