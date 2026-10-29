<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Housekeeping Categories | ArtFlow</title>
    {{-- Reload the picker so categories and rooms added by the hotel admin appear without a manual refresh --}}
    <meta http-equiv="refresh" content="60">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        .hk-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 22px; }
        .hk-summary__item { background: var(--st-card); border: 1px solid var(--st-line); border-radius: 14px; padding: 14px 16px; box-shadow: var(--st-shadow); }
        .hk-summary__item span { display: block; font-size: 12px; font-weight: 600; color: var(--st-muted); text-transform: uppercase; letter-spacing: .04em; }
        .hk-summary__item strong { display: block; margin-top: 4px; font-size: 24px; font-weight: 700; color: var(--st-text); }

        .hk-cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .hk-cat { display: flex; flex-direction: column; gap: 18px; padding: 20px; background: var(--st-card); color: var(--st-text); border: 1px solid var(--st-line); border-radius: 16px; box-shadow: var(--st-shadow); text-decoration: none; transition: box-shadow .2s ease, border-color .2s ease, transform .2s ease; }
        .hk-cat:hover { box-shadow: var(--st-shadow-hover); border-color: var(--st-accent); transform: translateY(-2px); }
        .hk-cat__head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .hk-cat__title { margin: 0; font-size: 18px; font-weight: 700; }
        .hk-cat__meta { margin-top: 2px; font-size: 13px; color: var(--st-muted); }
        .hk-cat__go { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 999px; background: var(--st-accent-soft); color: var(--st-accent-text); font-size: 13px; font-weight: 700; white-space: nowrap; }
        .hk-cat__bar { height: 8px; border-radius: 999px; background: var(--st-line); overflow: hidden; }
        .hk-cat__bar span { display: block; height: 100%; border-radius: 999px; background: #10b981; transition: width .3s ease; }
        .hk-cat__legend { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .hk-cat__legend div { display: flex; flex-direction: column; gap: 2px; padding: 10px 12px; border-radius: 10px; background: var(--st-hover); }
        .hk-cat__legend strong { font-size: 18px; font-weight: 700; }
        .hk-cat__legend span { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--st-muted); }
        .hk-cat__legend span::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--dot); }
        .hk-dot--ready { --dot: #10b981; }
        .hk-dot--work { --dot: #f59e0b; }
        .hk-dot--out { --dot: #e11d48; }
        .hk-empty { padding: 48px 24px; text-align: center; color: var(--st-muted); }
        .hk-empty h2 { margin: 0 0 6px; font-size: 18px; color: var(--st-text); }
        @media (max-width: 860px) { .hk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .hk-cat-grid { grid-template-columns: 1fr; } .hk-summary { gap: 10px; } }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page">
    <div class="page-head">
        <div>
            <p class="eyebrow">Housekeeping</p>
            <h1>Room categories</h1>
            <p>{{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }} · {{ $cards->count() }} categor{{ $cards->count() === 1 ? 'y' : 'ies' }}</p>
        </div>
        <a href="{{ route('housekeeping.lost-found') }}" class="badge-soft text-decoration-none">Lost &amp; found</a>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif

    @if($cards->isEmpty())
        <div class="surface hk-empty">
            <h2>No room categories yet</h2>
            <p>Create room categories in Inventory, then assign rooms to them.</p>
        </div>
    @else
        <div class="hk-summary" aria-label="Summary across all categories">
            <div class="hk-summary__item"><span>Total rooms</span><strong>{{ $cards->sum('total') }}</strong></div>
            <div class="hk-summary__item"><span>Clean</span><strong>{{ $cards->sum('ready') }}</strong></div>
            <div class="hk-summary__item"><span>Needs work</span><strong>{{ $cards->sum('attention') }}</strong></div>
            <div class="hk-summary__item"><span>Out of service</span><strong>{{ $cards->sum('out') }}</strong></div>
        </div>

        <div class="hk-cat-grid">
            @foreach($cards as $card)
                @php($percent = $card['total'] > 0 ? round($card['ready'] / $card['total'] * 100) : 0)
                <a href="{{ route('housekeeping.index', ['category' => $card['key']]) }}" class="hk-cat">
                    <div class="hk-cat__head">
                        <div>
                            <h2 class="hk-cat__title">{{ $card['name'] }}</h2>
                            <div class="hk-cat__meta">{{ $card['total'] }} room{{ $card['total'] === 1 ? '' : 's' }} · {{ $percent }}% clean</div>
                        </div>
                        <span class="hk-cat__go">Open board →</span>
                    </div>

                    <div class="hk-cat__bar" role="img" aria-label="{{ $percent }}% of rooms clean">
                        <span style="width: {{ $percent }}%"></span>
                    </div>

                    <div class="hk-cat__legend">
                        <div><strong>{{ $card['ready'] }}</strong><span class="hk-dot--ready">Clean</span></div>
                        <div><strong>{{ $card['attention'] }}</strong><span class="hk-dot--work">Needs work</span></div>
                        <div><strong>{{ $card['out'] }}</strong><span class="hk-dot--out">Out of service</span></div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</main>
@include('partials.hotel-shell-end')
@include('partials.dev-role-switcher')
</body>
</html>
