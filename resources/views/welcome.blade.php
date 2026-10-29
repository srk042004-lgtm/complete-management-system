<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Artflow Management System') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-shell">
        <header class="hotel-header">
            <div class="hotel-topbar">
                <div class="text-uppercase">Artflow Management System</div>
                <div>{{ now()->format('l, d M Y') }} · Raheel Ahmad Khan</div>
            </div>

            <div class="hotel-title-wrap">
                <div>
                    <div class="title-tag">Suite operations</div>
                    <h1>Hotel Management Dashboard</h1>
                </div>
                <div class="header-actions">
                    <a href="#" class="btn btn-soft">Settings</a>
                    <a href="#" class="btn btn-gold">Logout</a>
                </div>
            </div>
        </header>

        <nav class="navbar navbar-expand-lg hotel-nav">
            <div class="container-fluid px-2">
                <a class="navbar-brand" href="#">Artflow</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Reservations</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Front Desk</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">POS</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Cashier</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Night Audit</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Housekeeping</a></li>
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
                            <a href="#" class="btn btn-primary">New Booking</a>
                            <a href="#" class="btn btn-outline-primary">View Reports</a>
                        </div>
                    </div>
                    <div class="col-md-5 welcome-side">
                        <div>
                            <div class="room-total">24</div>
                            <div class="fw-semibold">Total rooms managed</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mini-stat-grid">
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(59,130,196,0.12); color: #1f5f96;">🏨</div>
                    <h6>Rooms</h6>
                    <p>24 total</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(34,160,107,0.12); color: #1c8b63;">🛎️</div>
                    <h6>Front Desk</h6>
                    <p>18 in-house</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(201,169,110,0.18); color: #9a7333;">💳</div>
                    <h6>Payments</h6>
                    <p>13 departures</p>
                </div>
                <div class="mini-stat">
                    <div class="icon-wrap" style="background: rgba(124,102,214,0.12); color: #5b47b1;">📊</div>
                    <h6>Reports</h6>
                    <p>6 available</p>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="metric-card">
                <div class="metric-label">Arrivals</div>
                <div class="metric-value primary">1</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Inhouse</div>
                <div class="metric-value success">18</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Departures</div>
                <div class="metric-value warning">13</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Occupied</div>
                <div class="metric-value danger">17</div>
            </div>
        </section>

        <section class="dashboard-grid">
            <aside class="action-card">
                <h5>Quick Actions</h5>
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item">New Reservation</a>
                    <a href="#" class="list-group-item">Front Desk</a>
                    <a href="#" class="list-group-item">Open POS</a>
                    <a href="#" class="list-group-item">Cashier</a>
                    <a href="#" class="list-group-item">Housekeeping</a>
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
                                <td>1</td>
                                <td>1</td>
                                <td><span class="badge bg-primary-subtle text-primary-emphasis">On track</span></td>
                            </tr>
                            <tr>
                                <th>Departures</th>
                                <td>13</td>
                                <td>13</td>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis">Review</span></td>
                            </tr>
                            <tr>
                                <th>Rooms Available</th>
                                <td>6</td>
                                <td>6</td>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Ready</span></td>
                            </tr>
                            <tr>
                                <th>Dirty Rooms</th>
                                <td>4</td>
                                <td>4</td>
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
