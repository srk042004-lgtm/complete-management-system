<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operations Overview | {{ $tenant->hotel_name ?? $tenant->name }}</title>
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
        @media (max-width: 900px) { .wrap { padding: 16px; } }
    </style>
@include('partials.slate-teal-theme')
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <main class="wrap">
        <section class="hero"><div class="eyebrow">Daily operations · {{ now()->format('l, d M Y') }}</div><h1>Hotel operations control</h1><p class="muted mb-0">Front desk, room inventory, arrivals, departures, housekeeping, and shift cash.</p></section>
        <section class="grid" aria-label="Daily operations metrics">
            <article class="card"><div class="label">Today's arrivals</div><div class="value">{{ $arrivals->count() }}</div></article>
            <article class="card"><div class="label">Today's departures</div><div class="value">{{ $departures->count() }}</div></article>
            <article class="card"><div class="label">Occupied rooms</div><div class="value">{{ $occupiedRooms }}</div></article>
            <article class="card"><div class="label">Dirty / clean rooms</div><div class="value">{{ $dirtyRooms->count() }} / {{ $cleanRooms }}</div></article>
            <article class="card"><div class="label">Expected shift cash</div><div class="value">{{ $tenant->currency_code ?? 'PKR' }} {{ number_format($expectedShiftCash, 2) }}</div><div class="muted" style="font-size:10px;margin-top:6px">Cash receipts recorded today; opening float is not tracked.</div></article>
        </section>

        <section class="card mb-3" id="room-grid">
            <h2 class="title">Live room status grid</h2>
            <div class="grid-rooms">
                @forelse($roomGrid as $room)
                    @php
                        $roomState = strtolower((string) $room->housekeeping_status);
                        $roomGuest = $room->bookings->first()?->guest?->full_name;
                    @endphp
                    <article class="room">
                        <strong>Room {{ $room->room_number }}</strong>
                        <small>{{ $room->category?->title ?? $room->room_type ?? 'Room' }} · {{ ucfirst($room->occupancy_status) }}</small>
                        <span class="status {{ in_array($roomState, ['dirty', 'cleaning', 'inspection_pending'], true) ? 'status--dirty' : (in_array($roomState, ['maintenance', 'out_of_order'], true) ? 'status--maintenance' : '') }}">{{ str($roomState)->replace('_', ' ')->headline() }}</span>
                        @if($roomGuest)<small>Guest: {{ $roomGuest }}</small>@endif
                    </article>
                @empty
                    <div class="empty">No room inventory has been added to this hotel.</div>
                @endforelse
            </div>
            <div class="actions"><a class="button" href="{{ route('frontdesk.dashboard') }}">Open front desk</a><a class="button" href="{{ route('housekeeping.index') }}">Housekeeping board</a></div>
        </section>

        <div class="columns">
            <section class="card"><h2 class="title">Daily operations feed</h2><h3 class="title">Pending housekeeping tasks</h3>
                @forelse($dirtyRooms as $room)<ul class="list"><li><strong>Room {{ $room->room_number }}</strong> · {{ str($room->housekeeping_status)->replace('_', ' ')->headline() }}</li></ul>
                @empty<div class="empty">No rooms are waiting for housekeeping.</div>@endforelse
                <h3 class="title mt-3">Unassigned check-ins</h3>
                @forelse($unassignedArrivals as $booking)<ul class="list"><li><strong>{{ $booking->booking_code }}</strong> · {{ $booking->guest?->full_name ?? 'Guest' }}</li></ul>
                @empty<div class="empty">No unassigned arrivals due today.</div>@endforelse
            </section>
            <section class="card"><h2 class="title">Today's arrivals &amp; departures</h2>
                <ul class="list">@forelse($arrivals as $booking)<li><strong>Arrival · {{ $booking->guest?->full_name ?? $booking->booking_code }}</strong> · {{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</li>@empty<li class="muted">No arrivals scheduled today.</li>@endforelse</ul>
                <ul class="list">@forelse($departures as $booking)<li><strong>Departure · {{ $booking->guest?->full_name ?? $booking->booking_code }}</strong> · Room {{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</li>@empty<li class="muted">No departures scheduled today.</li>@endforelse</ul>
            </section>
        </div>

        @include('hotel.partials.staff-management', [
    'title' => 'Staff management · Receptionists & Housekeeping',
    'intro' => 'Invite operational staff. Hotel Admin and Manager accounts can only be managed by the owner.',
    'availableRoles' => $availableRoles,
    'pendingInvitations' => $pendingInvitations,
    'managedStaff' => $managedStaff,
    'memberRoleOptions' => ['receptionist' => 'Receptionist / Cashier', 'housekeeping' => 'Housekeeping', 'accountant' => 'Accountant', 'maintenance' => 'Maintenance'],
    'footerLink' => false,
])
        <div class="actions mt-3"><a class="button" href="{{ route('reservations.index') }}">Reservations</a><a class="button" href="{{ route('audit.night') }}">Night audit</a><a class="button" href="{{ route('housekeeping.index') }}">Housekeeping</a></div>
            <section class="surface shift-panel">
            <div class="section-head">
                <div><h2>Shift cash overview</h2><p class="muted">Cash drawer status for this month</p></div>
                <a href="{{ route('frontdesk.shift') }}" class="btn-primary btn-compact">Open shift desk</a>
            </div>
            <div class="stat-grid">
                <div class="stat-tile"><div class="kpi-label">Shift status</div><div class="stat-value">{{ $cashShifts['open'] ? 'Open' : 'No shift open' }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Opened by</div><div class="stat-value">{{ $cashShifts['open']?->opener?->name ?? '—' }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Variance this month</div><div class="stat-value {{ $cashShifts['monthVariance'] < 0 ? 'text-neg' : 'text-pos' }}">{{ number_format($cashShifts['monthVariance'], 2) }}</div></div>
            </div>
            <div class="table-responsive shift-table">
                <table class="table-clean w-100">
                    <thead><tr><th>Closed</th><th>Receptionist</th><th>Closed by</th><th class="num">Expected</th><th class="num">Counted</th><th class="num">Variance</th></tr></thead>
                    <tbody>
                    @forelse($cashShifts['recent'] as $shift)
                        <tr><td>{{ $shift->closed_at?->format('d M Y, H:i') }}</td><td>{{ $shift->opener?->name ?? '—' }}</td><td>{{ $shift->closer?->name ?? '—' }}</td><td class="num">{{ number_format((float) $shift->expected_cash, 2) }}</td><td class="num">{{ number_format((float) $shift->counted_cash, 2) }}</td><td class="num">{{ number_format((float) $shift->variance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="muted">No closed shifts yet.</td></tr>
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
