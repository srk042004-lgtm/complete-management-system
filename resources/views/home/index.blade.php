<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Artflow Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            min-height: 100vh;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 18px 40px;
        }

        .hotel-header {
            background: linear-gradient(135deg, #091d36 0%, #153856 42%, #1e4d73 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.6rem;
            box-shadow: 0 28px 50px rgba(11, 31, 56, 0.2);
            padding: 1.5rem 1.6rem 1.2rem;
            color: #fff;
        }

        .hotel-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .eyebrow {
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.72rem;
            opacity: 0.8;
            font-weight: 700;
        }

        .header-user {
            font-size: 0.82rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
        }

        .hotel-title-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .title-tag {
            display: inline-flex;
            background: rgba(201, 169, 110, 0.2);
            border: 1px solid rgba(201, 169, 110, 0.45);
            color: #f6e7bf;
            border-radius: 999px;
            padding: 0.35rem 0.8rem;
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 0.7rem;
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-gold {
            background: linear-gradient(135deg, #d7ba7a, #b88d48);
            border: none;
            color: #0c1c2c;
            border-radius: 0.9rem;
            font-weight: 700;
            padding: 0.72rem 1.1rem;
        }

        .btn-soft {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            color: #fff;
            border-radius: 0.9rem;
            font-weight: 600;
            padding: 0.72rem 1.1rem;
        }

        .hotel-nav {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(27, 78, 118, 0.1);
            border-radius: 1.2rem;
            margin: 1.25rem 0 1.5rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 14px 30px rgba(11, 31, 56, 0.08);
        }

        .navbar-brand {
            color: #0f2c45 !important;
            font-weight: 800;
            letter-spacing: -0.04em;
            font-size: 1.35rem;
        }

        .nav-link {
            color: #304866 !important;
            font-weight: 600;
            border-radius: 0.75rem;
            padding: 0.65rem 0.9rem !important;
            transition: all 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(19, 59, 89, 0.08);
            color: #123454 !important;
        }

        .hero-grid,
        .stats-grid,
        .dashboard-grid {
            display: grid;
            gap: 1.4rem;
        }

        .hero-grid {
            grid-template-columns: 1.5fr 1fr;
            margin-bottom: 1.5rem;
        }

        .feature-card,
        .summary-card,
        .panel-card {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(18, 52, 84, 0.08);
            border-radius: 1.4rem;
            box-shadow: 0 18px 36px rgba(11, 31, 56, 0.08);
        }

        .welcome-card {
            overflow: hidden;
        }

        .welcome-content {
            padding: 1.6rem;
        }

        .welcome-content h2 {
            margin-bottom: 0.8rem;
            font-weight: 800;
            color: #102b43;
            letter-spacing: -0.03em;
        }

        .welcome-content p {
            color: #5d7288;
            margin-bottom: 1.2rem;
        }

        .welcome-side {
            background: linear-gradient(180deg, rgba(13, 46, 76, 0.98), rgba(30, 77, 115, 0.96));
            color: white;
            display: flex;
            min-height: 100%;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.2rem;
        }

        .room-total {
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            font-weight: 800;
            letter-spacing: -0.06em;
        }

        .mini-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .mini-stat {
            padding: 1rem;
            border-radius: 1.1rem;
            background: #f9fbff;
            border: 1px solid rgba(18, 52, 84, 0.08);
            height: 100%;
        }

        .mini-stat .icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.9rem;
        }

        .mini-stat h6 {
            margin: 0 0 0.25rem;
            font-weight: 700;
            color: #112d47;
        }

        .mini-stat p {
            margin: 0;
            font-size: 0.82rem;
            color: #60758b;
        }

        .stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 1.5rem;
        }

        .metric-card {
            padding: 1.25rem 1.1rem;
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245,249,255,0.96));
        }

        .metric-label {
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #637791;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: clamp(1.8rem, 2.6vw, 2.5rem);
            line-height: 1;
            font-weight: 800;
            color: #132f4d;
        }

        .metric-value.primary { color: #153856; }
        .metric-value.success { color: #1a7a55; }
        .metric-value.warning { color: #b47d17; }
        .metric-value.danger { color: #c14d4d; }

        .dashboard-grid {
            grid-template-columns: 0.95fr 1.9fr;
        }

        .action-card,
        .summary-card {
            padding: 1.25rem;
        }

        .action-card h5,
        .summary-card h5 {
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
            font-weight: 800;
            color: #112d47;
        }

        .list-group {
            border-radius: 1rem;
            overflow: hidden;
        }

        .list-group-item {
            border: 0;
            border-radius: 0.95rem !important;
            margin-bottom: 0.7rem;
            background: #f6f9ff;
            color: #183a5b;
            font-weight: 700;
            padding: 0.9rem 1rem;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .list-group-item:hover {
            transform: translateX(3px);
            box-shadow: 0 10px 18px rgba(19, 52, 84, 0.08);
            background: #edf5ff;
            color: #123454;
        }

        .live-tag {
            background: rgba(58, 120, 204, 0.1);
            color: #204d7a;
            border: 1px solid rgba(58, 120, 204, 0.2);
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.5rem 0.8rem;
        }

        .table-clean {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 0.7rem;
        }

        .table-clean thead th {
            background: transparent;
            border: 0;
            color: #516987;
            font-size: 0.74rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 800;
            padding: 0.5rem 0.75rem;
        }

        .table-clean tbody td,
        .table-clean tbody th {
            background: #f8fbff;
            border: 1px solid rgba(18, 52, 84, 0.06);
            border-right: none;
            border-left: none;
            padding: 0.9rem 0.75rem;
            vertical-align: middle;
            color: #223c5a;
        }

        .table-clean tbody tr td:first-child,
        .table-clean tbody tr th:first-child {
            border-left: 1px solid rgba(18, 52, 84, 0.06);
            border-radius: 0.85rem 0 0 0.85rem;
        }

        .table-clean tbody tr td:last-child,
        .table-clean tbody tr th:last-child {
            border-right: 1px solid rgba(18, 52, 84, 0.06);
            border-radius: 0 0.85rem 0.85rem 0;
        }

        .hotel-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: #4d6781;
            background: rgba(255,255,255,0.75);
            border: 1px solid rgba(18, 52, 84, 0.06);
            border-radius: 1rem;
            padding: 1rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 991.98px) {
            .hero-grid,
            .dashboard-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .page-shell {
                padding-left: 12px;
                padding-right: 12px;
            }

            .hotel-topbar,
            .hotel-title-wrap {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .mini-stat-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
    <div class="page-shell">
        <header class="hotel-header">
            <div class="hotel-topbar">
                <div class="eyebrow">Artflow Management System</div>
                <div class="header-user">{{ now()->format('l, d M Y') }} · Raheel Ahmad Khan</div>
            </div>

            <div class="hotel-title-wrap">
                <div>
                    <div class="title-tag">Suite operations</div>
                    <h1>Hotel Management Dashboard</h1>
                </div>
                <div class="header-actions">
                    <a href="{{ route('home.settings') }}" class="btn btn-soft">Settings</a>
                    <a href="#" class="btn btn-gold">Logout</a>
                </div>
            </div>
        </header>

        <nav class="navbar navbar-expand-lg hotel-nav">
            <div class="container-fluid px-2">
                <a class="navbar-brand" href="{{ route('home.index') }}">Artflow</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link active" href="{{ route('home.index') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('reservations.index') }}">Reservations</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('frontdesk.dashboard') }}">Front Desk</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('pos.index') }}">POS</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('cashier.index') }}">Cashier</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('audit.night') }}">Night Audit</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('housekeeping.index') }}">Housekeeping</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <section class="hero-grid">
            <div class="feature-card welcome-card">
                <div class="row g-0 align-items-stretch">
                    <div class="col-md-7 welcome-content">
                        <h2>Welcome to your hotel operations control center</h2>
                        <p>Manage room inventory, monitor check-ins and departures, and keep guest services flowing from one efficient dashboard.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('reservations.booking') }}" class="btn btn-primary">New Booking</a>
                            <a href="{{ route('home.reports') }}" class="btn btn-outline-primary">View Reports</a>
                        </div>
                    </div>
                    <div class="col-md-5 welcome-side">
                        <div>
                            <div class="room-total">{{ $roomsTotal }}</div>
                            <div class="fw-semibold">Total rooms managed</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mini-stat-grid">
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(59,130,196,0.12); color: #1f5f96;">🏨</div>
                    <h6>Rooms</h6>
                    <p>{{ $roomsTotal }} total</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(34,160,107,0.12); color: #1c8b63;">🛎️</div>
                    <h6>Front Desk</h6>
                    <p>{{ $inhouse }} in-house</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(201,169,110,0.18); color: #9a7333;">💳</div>
                    <h6>Payments</h6>
                    <p>{{ $departures }} departures</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(124,102,214,0.12); color: #5b47b1;">📊</div>
                    <h6>Reports</h6>
                    <p>{{ $roomsAvailable }} available</p>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="metric-card">
                <div class="metric-label">Arrivals</div>
                <div class="metric-value primary">{{ $arrivals }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Inhouse</div>
                <div class="metric-value success">{{ $inhouse }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Departures</div>
                <div class="metric-value warning">{{ $departures }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Occupied</div>
                <div class="metric-value danger">{{ $roomsOccupied }}</div>
            </div>
        </section>

        <section class="dashboard-grid">
            <aside class="action-card">
                <h5>Quick Actions</h5>
                <div class="list-group list-group-flush">
                    <a href="{{ route('reservations.booking') }}" class="list-group-item">New Reservation</a>
                    <a href="{{ route('frontdesk.dashboard') }}" class="list-group-item">Front Desk</a>
                    <a href="{{ route('pos.index') }}" class="list-group-item">Open POS</a>
                    <a href="{{ route('cashier.index') }}" class="list-group-item">Cashier</a>
                    <a href="{{ route('housekeeping.index') }}" class="list-group-item">Housekeeping</a>
                </div>
            </aside>

            <div class="summary-card">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Operational Status</h5>
                        <p class="text-muted mb-0">Live occupancy, housekeeping, and room availability overview.</p>
                    </div>
                    <span class="live-tag">Updated now</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Today</th>
                                <th>Current</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Arrivals</th>
                                <td>{{ $arrivals }}</td>
                                <td>{{ $arrivals }}</td>
                                <td><span class="badge bg-primary-subtle text-primary-emphasis">On track</span></td>
                            </tr>
                            <tr>
                                <th>Departures</th>
                                <td>{{ $departures }}</td>
                                <td>{{ $departures }}</td>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis">Review</span></td>
                            </tr>
                            <tr>
                                <th>Rooms Available</th>
                                <td>{{ $roomsAvailable }}</td>
                                <td>{{ $roomsAvailable }}</td>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Ready</span></td>
                            </tr>
                            <tr>
                                <th>Dirty Rooms</th>
                                <td>{{ $roomsDirty }}</td>
                                <td>{{ $roomsDirty }}</td>
                                <td><span class="badge bg-danger-subtle text-danger-emphasis">Action</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <footer class="hotel-footer">
            Artflow Management System · Suite operations dashboard · 2026 © Hotel Management System
        </footer>
    </div>
</body>
</html>
