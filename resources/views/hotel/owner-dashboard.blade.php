<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financial Overview | {{ $tenant->hotel_name ?? $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .wrap { width: 100%; max-width: 1280px; margin: 0 auto; padding: 24px 28px 48px; }
        main { padding: 0; }
        .hero { padding: 22px 24px; margin-bottom: 16px; }
        .hero h1 { margin: 6px 0 4px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin: 16px 0; }
        .card { padding: 18px 20px; }
        .label { font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--st-muted); }
        .value { margin-top: 6px; font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; color: var(--st-text); font-variant-numeric: tabular-nums; }
        .columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; margin-top: 16px; }
        .title { font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--st-text); }
        .list { padding: 0; margin: 0; list-style: none; }
        .grid-rooms { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
        .room { border: 1px solid var(--st-line); border-radius: 12px; padding: 12px; background: var(--st-card); }
        .room strong { display: block; font-size: 14px; color: var(--st-text); }
        .room small { display: block; margin-top: 4px; color: var(--st-muted); font-size: 12px; }
        .status { display: inline-block; margin-top: 6px; border-radius: 999px; padding: 3px 9px; background: var(--st-accent-soft); color: var(--st-accent-text); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .status--maintenance { background: #fef2f2; color: #b91c1c; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .button { display: inline-flex; align-items: center; padding: 8px 14px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font-size: 13px; font-weight: 600; text-decoration: none; transition: background-color .15s ease; }
        .button:hover { background: var(--st-hover); }
        .form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); align-items: end; gap: 10px; }
        .form label { display: grid; gap: 6px; font-size: 12px; font-weight: 600; color: var(--st-text); }
        .muted { color: var(--st-muted); }
        .chart { display: grid; gap: 10px; margin-top: 14px; }
        .bar-row { display: grid; grid-template-columns: 84px minmax(0, 1fr) 120px; align-items: center; gap: 12px; font-size: 13px; }
        .bar-row > span { color: var(--st-muted); }
        .bar-row > strong { text-align: right; font-weight: 600; color: var(--st-text); font-variant-numeric: tabular-nums; }
        .bar-track { height: 10px; border-radius: 999px; background: var(--st-hover); overflow: hidden; }
        .bar { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--st-accent), var(--st-accent-hover)); transition: width .4s ease; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: var(--st-accent-soft); color: var(--st-accent-text); }
        .pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .pill.warn { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .list { padding: 0; margin: 0; list-style: none; }
        .list li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--st-line); font-size: 13.5px; color: var(--st-text); }
        .list li:last-child { border-bottom: 0; }
        .note { margin-top: 8px; font-size: 12.5px; color: var(--st-muted); line-height: 1.5; }
        .hero .pill { margin-top: 4px; }
        @media (max-width: 900px) { .wrap { padding: 16px; } }
    </style>
@include('partials.slate-teal-theme')
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <main class="wrap">
        <section class="hero">
            <div class="eyebrow">Executive financial overview</div>
            <h1>Property performance</h1>
            <p class="muted mb-0">Financial performance, revenue trends, tax totals, subscription standing, and high-risk activity.</p>
            @if(($subscription?->ends_at ?? $subscription?->trial_end) && ($subscription->ends_at ?? $subscription->trial_end)->isFuture() && ($subscription->ends_at ?? $subscription->trial_end)->diffInDays(now()) <= 30)
                <p class="mt-3 mb-0"><span class="pill warn">Subscription expires {{ ($subscription->ends_at ?? $subscription->trial_end)->diffForHumans() }} · {{ ($subscription->ends_at ?? $subscription->trial_end)->toFormattedDateString() }}</span></p>
            @endif
        </section>

        <section class="grid" aria-label="Financial key performance indicators">
            <article class="card"><div class="label">Net revenue</div><div class="value">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($netRevenue, 2) }}</div><div class="note">Completed and in-house booking subtotal; taxes excluded.</div></article>
            <article class="card"><div class="label">Profit / loss margin</div><div class="value">Not tracked</div><div class="note">The hotel workspace has no expense ledger, so a margin cannot be calculated reliably.</div></article>
            <article class="card"><div class="label">Average daily rate</div><div class="value">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($averageDailyRate, 2) }}</div><div class="note">Average nightly room rate for current-year stays.</div></article>
            <article class="card"><div class="label">RevPAR</div><div class="value">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($averageDailyRate * $occupancyRate / 100, 2) }}</div><div class="note">Revenue per available room (ADR × occupancy)</div></article>
            <article class="card"><div class="label">Occupancy rate</div><div class="value">{{ number_format($occupancyRate, 1) }}%</div><div class="note">{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied now.</div></article>
            <article class="card"><div class="label">SaaS subscription</div><div class="value"><span class="pill {{ $subscription?->status === 'expired' ? 'warn' : '' }}">{{ ucfirst($subscription?->status ?? 'Unavailable') }}</span></div><div class="note">{{ $subscription?->plan?->name ?? ucfirst($subscription?->plan_type ?? 'No plan') }}            @if($subscription?->ends_at ?? $subscription?->trial_end) · through {{ ($subscription->ends_at ?? $subscription->trial_end)->toFormattedDateString() }}@endif</div></article>
        </section>

        <div class="columns">
            <section class="card" aria-labelledby="revenue-heading">
                <h2 class="section-title" id="revenue-heading">Revenue analytics</h2>
                <div class="eyebrow mb-2">Monthly collections · trailing 12 months</div>
                @php($monthlyMax = max(1, (float) $monthlyRevenue->max('amount')))
                <div class="chart">
                    @foreach($monthlyRevenue as $month)
                        <div class="bar-row"><span>{{ $month['label'] }}</span><div class="bar-track"><div class="bar" style="width: {{ min(100, $month['amount'] / $monthlyMax * 100) }}%"></div></div><strong>{{ number_format($month['amount'], 2) }}</strong></div>
                    @endforeach
                </div>
                <h3 class="section-title mt-4">Yearly collections</h3>
                <ul class="list">@foreach($yearlyRevenue as $year)<li class="d-flex justify-content-between"><span>{{ $year['label'] }}</span><strong>{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($year['amount'], 2) }}</strong></li>@endforeach</ul>
                <h3 class="section-title mt-4">Tax summary · current year</h3>
                <p class="mb-0">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format((float) $taxSummary, 2) }} in tax recorded on completed and in-house bookings.</p>
            </section>

            <section class="card" id="audit-feed">
                <h2 class="section-title">Audit &amp; security feed</h2>
                <p class="muted">High-risk price changes, refunds, staff suspensions, and deleted bookings.</p>
                @forelse($auditFeed as $entry)
                    <ul class="list">
                        <li><strong>{{ str($entry->action)->replace('.', ' ')->headline() }}</strong><br><span class="muted">{{ $entry->actor?->name ?? 'System' }} · {{ $entry->created_at?->diffForHumans() }}</span>@if($entry->details)<br><span>{{ collect($entry->details)->except(['password', 'token'])->map(fn ($value, $key) => str($key)->replace('_', ' ')->headline().': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}</span>@endif</li>
                    </ul>
                @empty
                    <div class="empty">No high-risk activity has been recorded for this hotel yet.</div>
                @endforelse
                <div class="actions"><a class="button" href="{{ route('hotel.audit-logs') }}">View audit logs</a></div>
            </section>
        </div>

        @include('hotel.partials.staff-management', [
    'title' => 'Owner staff management · Admins & Managers',
    'intro' => 'Invite and update the hotel leadership. Staff permissions remain tenant-scoped.',
    'availableRoles' => $availableRoles,
    'pendingInvitations' => $pendingInvitations,
    'managedStaff' => $managedStaff,
    'memberRoleOptions' => ['admin' => 'Hotel Admin', 'manager' => 'Manager'],
    'footerLink' => true,
])

            <section class="surface shift-panel">
            <div class="section-head">
                <div><h2>Cash shift reconciliation</h2><p class="muted">Feeds the revenue ledger</p></div>
                <span class="badge-soft">Live</span>
            </div>
            <div class="stat-grid">
                <div class="stat-tile"><div class="kpi-label">Cash collected this month</div><div class="stat-value">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($cashShifts['monthCollected'], 2) }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Variance this month</div><div class="stat-value {{ $cashShifts['monthVariance'] < 0 ? 'text-neg' : 'text-pos' }}">{{ number_format($cashShifts['monthVariance'], 2) }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Shift open now</div><div class="stat-value">{{ $cashShifts['open']?->opener?->name ?? 'None' }}</div></div>
            </div>
            <div class="table-responsive shift-table">
                <table class="table-clean w-100">
                    <thead><tr><th>Closed</th><th>Receptionist</th><th class="num">Expected</th><th class="num">Counted</th><th class="num">Variance</th></tr></thead>
                    <tbody>
                    @forelse($cashShifts['recent'] as $shift)
                        <tr><td>{{ $shift->closed_at?->format('d M Y, H:i') }}</td><td>{{ $shift->opener?->name ?? '—' }}</td><td class="num">{{ number_format((float) $shift->expected_cash, 2) }}</td><td class="num">{{ number_format((float) $shift->counted_cash, 2) }}</td><td class="num">{{ number_format((float) $shift->variance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="muted">No closed shifts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
<script>
    document.querySelectorAll('[data-copy-invite]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const container = button.closest('.empty');
            const field = container.querySelector('[data-invite-link]');
            const status = container.querySelector('[data-copy-status]');

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(field.value);
                } else {
                    field.focus();
                    field.select();
                    if (!document.execCommand('copy')) {
                        throw new Error('Clipboard copy was not available.');
                    }
                }
                button.textContent = 'Copied!';
                status.textContent = 'Invite link copied.';
                window.setTimeout(function () { button.textContent = 'Copy Link'; }, 2000);
            } catch (error) {
                field.focus();
                field.select();
                status.textContent = 'Copy was blocked by this browser. Select the link and copy it manually.';
            }
        });
    });
</script>
@include('partials.hotel-shell-end')
</body>
</html>
