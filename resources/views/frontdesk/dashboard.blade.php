<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->hotel_name ?? $tenant->name }} | Front Desk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --ink: #102b43; --muted: #64748b; --line: rgba(18, 52, 84, .1); }
        body { min-height: 100vh; background: linear-gradient(135deg, #edf5ff, #f8fafd); color: var(--ink); font-size: 14px; }
        .shell { max-width: 1440px; margin: auto; padding: 14px 14px 28px; }
        .hero { border-radius: 18px; padding: 14px 18px; color: white; background: linear-gradient(135deg, #091d36, #1e4d73); box-shadow: 0 20px 40px rgba(10, 28, 46, .16); }
        .hero h1 { margin: 2px 0 0; font-size: clamp(1.15rem, 2vw, 1.5rem); font-weight: 800; }
        .eyebrow { font-size: .68rem; letter-spacing: .16em; text-transform: uppercase; font-weight: 800; opacity: .78; }
        .surface { border: 1px solid var(--line); border-radius: 18px; background: rgba(255,255,255,.96); box-shadow: 0 14px 30px rgba(11,31,56,.07); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .surface:hover, .kpi:hover { transform: translateY(-3px); box-shadow: 0 18px 34px rgba(11,31,56,.11); border-color: rgba(46,102,184,.2); }
        .kpi { padding: 11px 13px; border: 1px solid var(--line); border-radius: 15px; background: linear-gradient(180deg,#fff,#f5f9ff); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .kpi-label { color: var(--muted); font-size: .68rem; text-transform: uppercase; letter-spacing: .1em; font-weight: 800; }
        .kpi-value { margin-top: 5px; font-size: 1.2rem; line-height: 1; font-weight: 800; }
        .kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        @media (min-width: 768px) { .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (min-width: 1200px) { .kpi-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .quick-action { display: flex; align-items: center; justify-content: center; min-height: 40px; padding: 6px 8px; font-size: .84rem; border: 1px solid #d8e4f1; border-radius: 12px; background: #f5f9ff; color: #183a5b; font-weight: 700; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease, background .2s ease; }
        .quick-action:hover { color: #143d63; background: #e9f3ff; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(16,75,122,.12); }
        .section-title { font-size: 1rem; font-weight: 800; }
        .table-clean { border-collapse: collapse; }
        .table-clean th { color: #64748b; font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; }
        .table-clean td, .table-clean th { padding: 11px 14px; vertical-align: middle; border-bottom: 1px solid #edf2f7; }
        .badge-soft { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #edf5ff; color: #24547d; font-size: .7rem; font-weight: 700; }
        .status-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #edf2f7; }
        .activity-item { padding: 11px 0; border-bottom: 1px solid #edf2f7; }
        @media (max-width: 767px) { .shell { padding: 14px 12px 28px; } .hero { padding: 18px; } }
    </style>
<link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
<script src="{{ asset('js/theme-switcher.js') }}"></script>
@include('partials.slate-teal-theme')
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar" data-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="shell">
    <div class="toolbar mb-3" hidden aria-hidden="true">
        <div class="toolbar-group">
            <button type="button" class="badge-soft" data-sidebar-toggle aria-controls="front-desk-menu">Menu</button>
            <button type="button" class="badge-soft" data-layout-toggle>Switch to menu view</button>
        </div>
        <div class="toolbar-group">
            <a href="#today-arrivals" class="badge-soft">Arrivals</a>
            <a href="#recent-reservations" class="badge-soft">Reservations</a>
        </div>
        <div class="toolbar-group">
            <span class="header-role-badge header-role-badge--receptionist">Receptionist &amp; POS</span>
            <div data-theme-switcher-slot="sidebar">
                <button type="button" class="theme-switcher" data-theme-switcher aria-label="Switch to dark theme" aria-pressed="false"><span class="theme-switcher__icon" aria-hidden="true"></span><span class="theme-switcher__label">Dark mode</span></button>
            </div>
            <form method="POST" action="{{ route('hotel-admin.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="frontdesk-logout-button badge-soft">Log out</button>
            </form>
        </div>
    </div>

    <header class="hero mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div><div class="eyebrow">{{ \App\Support\HotelRoleCatalog::label('receptionist') }} · Artflow Front Desk</div><h1>Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}, {{ auth()->user()->name }}</h1><p class="mb-0 mt-2 text-white-50">{{ $tenant->hotel_name ?? $tenant->name }} · {{ now()->format('l, d M Y') }}</p></div>
            <div class="align-self-start rounded-3 px-3 py-2" style="background:rgba(255,255,255,.12)"><div class="eyebrow">Hotel code</div><strong>{{ $tenant->hotel_code ?? '—' }}</strong></div>
        </div>
    </header>

    <section class="surface p-2 mb-3"><div class="quick-actions-heading">Quick actions</div><div class="row g-2"><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('reservations.booking') }}">New Reservation</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('frontdesk.shift') }}">Cash Shift</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('pos.index') }}">POS</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('reservations.index') }}">Add Guest</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('reservations.index') }}">Check-in</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('reservations.index') }}">Check-out</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('frontdesk.dashboard') }}">Room Status</a></div><div class="col-6 col-md-2"><a class="quick-action" href="{{ route('cashier.index') }}">Add Payment</a></div></div></section>

    <section class="kpi-grid mb-3">
        @foreach([['Total Rooms',$totalRooms],['Available',$availableRooms],['Occupied',$occupiedRooms],['Reserved',$reservedRooms],['Housekeeping',$dirtyRooms],['Maintenance',$maintenanceRooms],['Current Guests',$currentGuests],["Occupancy {$occupancy}%", $occupancy.'%'],['Check-ins Today', $arrivals->count()],['Check-outs Today', $departures->count()]] as $card)
            <div><div class="kpi h-100"><div class="kpi-label">{{ $card[0] }}</div><div class="kpi-value">{{ $card[1] }}</div></div></div>
        @endforeach
    </section>

    

    <div class="row g-4 mb-4">
        <section id="today-arrivals" class="col-xl-6"><div class="surface h-100 overflow-hidden"><div class="d-flex justify-content-between align-items-center p-3"><h2 class="section-title mb-0">Today's Arrivals</h2><span class="badge-soft">{{ $arrivals->count() }} scheduled</span></div><div class="table-responsive"><table class="table-clean w-100"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Guests</th><th>Status</th></tr></thead><tbody>@forelse($arrivals as $booking)<tr><td>{{ $booking->booking_code }}</td><td>{{ $booking->guest?->full_name ?? 'Guest' }}</td><td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</td><td>{{ $booking->total_guests }}</td><td><span class="badge-soft">{{ ucfirst($booking->booking_status) }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No reservations today.</td></tr>@endforelse</tbody></table></div></div></section>
        <section class="col-xl-6"><div class="surface h-100 overflow-hidden"><div class="d-flex justify-content-between align-items-center p-3"><h2 class="section-title mb-0">Today's Departures</h2><span class="badge-soft">{{ $departures->count() }} scheduled</span></div><div class="table-responsive"><table class="table-clean w-100"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Outstanding</th><th>Status</th></tr></thead><tbody>@forelse($departures as $booking)<tr><td>{{ $booking->booking_code }}</td><td>{{ $booking->guest?->full_name ?? 'Guest' }}</td><td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</td><td>{{ number_format(max(0, (float) $booking->grand_total - (float) $booking->paid_amount), 2) }}</td><td><span class="badge-soft">{{ ucfirst($booking->booking_status) }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No departures today.</td></tr>@endforelse</tbody></table></div></div></section>
    </div>

    <div class="row g-4 mb-4">
        <section class="col-lg-5"><div class="surface p-4 h-100"><h2 class="section-title">Room occupancy overview</h2><div class="mt-3"><div class="status-row"><span>Available</span><strong class="text-success">{{ $availableRooms }}</strong></div><div class="status-row"><span>Occupied</span><strong class="text-danger">{{ $occupiedRooms }}</strong></div><div class="status-row"><span>Reserved</span><strong class="text-warning">{{ $reservedRooms }}</strong></div><div class="status-row"><span>Cleaning / dirty</span><strong>{{ $dirtyRooms }}</strong></div><div class="status-row"><span>Maintenance</span><strong>{{ $maintenanceRooms }}</strong></div></div><div class="mt-4"><div class="d-flex justify-content-between small text-secondary"><span>Current occupancy</span><strong>{{ $occupancy }}%</strong></div><div class="progress mt-2" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $occupancy }}%"></div></div></div></div></section>
        <section class="col-lg-7"><div class="surface p-4 h-100"><div class="d-flex justify-content-between"><h2 class="section-title">Housekeeping by room type</h2><a href="{{ route('housekeeping.index') }}" class="small text-decoration-none">Open housekeeping</a></div><div class="table-responsive mt-2"><table class="table-clean w-100"><thead><tr><th>Room type</th><th>Total</th><th>Occupied</th><th>Action needed</th><th>Maintenance</th></tr></thead><tbody>@forelse($roomTypeStatuses as $type)<tr><td>{{ $type->room_type }}</td><td>{{ $type->total }}</td><td>{{ $type->occupied }}</td><td>{{ $type->housekeeping }}</td><td>{{ $type->maintenance }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No room inventory found.</td></tr>@endforelse</tbody></table></div></div></section>
    </div>

    <div class="row g-4 mb-4">
        <section id="recent-reservations" class="col-lg-8"><div class="surface overflow-hidden"><div class="d-flex justify-content-between align-items-center p-3"><h2 class="section-title mb-0">Recent reservations</h2><a href="{{ route('reservations.index') }}" class="small text-decoration-none">View all</a></div><div class="table-responsive"><table class="table-clean w-100"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Stay</th><th>Amount</th><th>Status</th></tr></thead><tbody>@forelse($recentBookings as $booking)<tr><td>{{ $booking->booking_code }}</td><td>{{ $booking->guest?->full_name ?? 'Guest' }}</td><td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</td><td>{{ $booking->check_in_date?->format('d M') }} - {{ $booking->check_out_date?->format('d M') }}</td><td>{{ number_format((float) $booking->grand_total, 2) }}</td><td><span class="badge-soft">{{ ucfirst($booking->booking_status) }}</span></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">No recent reservations.</td></tr>@endforelse</tbody></table></div></div></section>
        <aside class="col-lg-4"><div class="surface p-4 h-100"><h2 class="section-title">Alerts and activity</h2>@if((float) $pendingPayments > 0)<div class="alert alert-warning py-2 small">Outstanding guest payments: {{ number_format((float) $pendingPayments, 2) }}</div>@endif @if($dirtyRooms > 0)<div class="alert alert-info py-2 small">{{ $dirtyRooms }} rooms require housekeeping action.</div>@endif @forelse($activity as $item)<div class="activity-item"><div class="fw-semibold small">{{ $item['title'] }}</div><div class="text-secondary small">{{ $item['detail'] }} · {{ $item['time'] }}</div></div>@empty<div class="text-secondary small">No recent activity.</div>@endforelse</div></aside>
    </div>
    @include('partials.designer-credit')
</main>
<script>
(function () {
    var body = document.body;
    var toggle = document.querySelector('[data-layout-toggle]');
    if (!toggle) return;
    toggle.addEventListener('click', function () {
        var sidebar = body.getAttribute('data-layout-mode') === 'sidebar';
        body.setAttribute('data-layout-mode', sidebar ? 'menu' : 'sidebar');
        toggle.textContent = sidebar ? 'Switch to sidebar view' : 'Switch to menu view';
    });
})();
</script>
@include('partials.hotel-shell-end')
</body>
</html>
