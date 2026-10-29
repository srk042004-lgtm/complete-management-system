<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Night Audit | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page">
    <div class="page-head">
        <div>
            <p class="eyebrow">Day close</p>
            <h1>Night audit</h1>
            <p>Business date {{ now()->format('l, d M Y') }}. Review the checks, then close the day.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="notice-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-tile"><div class="kpi-label">Arrivals pending</div><div class="stat-value">{{ $summary['arrivals_pending'] }}</div></div>
        <div class="stat-tile"><div class="kpi-label">Departures pending</div><div class="stat-value {{ $summary['departures_pending'] > 0 ? 'text-neg' : '' }}">{{ $summary['departures_pending'] }}</div></div>
        <div class="stat-tile"><div class="kpi-label">In house</div><div class="stat-value">{{ $summary['in_house'] }}</div></div>
        <div class="stat-tile"><div class="kpi-label">In-house balance</div><div class="stat-value {{ $summary['in_house_balance'] > 0 ? 'text-neg' : 'text-pos' }}">PKR {{ number_format($summary['in_house_balance'], 2) }}</div></div>
    </div>

    <div class="pos-layout" style="margin-top:20px">
        <section class="surface" style="padding:22px">
            <div class="section-head"><div><h2>Closing checks</h2><p class="muted">All checks must pass before the day can close.</p></div></div>
            <ul class="check-list">
                @foreach($checks as $check)
                    <li>
                        <div>
                            <div style="font-weight:600">{{ $check['label'] }}</div>
                            <div class="detail">{{ $check['detail'] }}</div>
                        </div>
                        <span class="pill {{ $check['ok'] ? 'pill--clean' : 'pill--dirty' }}">{{ $check['ok'] ? 'Passed' : 'Action needed' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <aside class="surface cart">
            <h2 style="margin:0 0 6px">Close the day</h2>
            <p class="muted" style="font-size:13px;margin:0 0 16px">Recording the audit saves a signed entry with the business date and your name.</p>
            <form method="POST" action="{{ route('audit.night.run') }}" style="display:grid;gap:14px">
                @csrf
                <label style="display:flex;gap:10px;align-items:flex-start;font-size:13.5px">
                    <input type="checkbox" name="confirm" value="1" required style="margin-top:3px">
                    <span>I have reviewed the closing checks and the in-house balances.</span>
                </label>
                <button type="submit" class="btn-block">Run night audit</button>
            </form>
        </aside>
    </div>

    <section class="surface" style="margin-top:20px;padding:22px">
        <div class="section-head"><div><h2>Recent audits</h2><p class="muted">Each completed close, newest first.</p></div></div>
        @if($lastRuns->isEmpty())
            <p class="muted">No night audit has been recorded yet.</p>
        @else
            <ul class="check-list">
                @foreach($lastRuns as $run)
                    <li>
                        <div>
                            <div style="font-weight:600">Audit completed</div>
                            <div class="detail">{{ $run->actor?->name ?? 'Staff' }} · {{ $run->created_at?->format('d M Y, H:i') }}</div>
                        </div>
                        <span class="pill pill--neutral">{{ $run->details['business_date'] ?? $run->created_at?->toDateString() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</main>
@include('partials.hotel-shell-end')
</body>
</html>
