<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Admin Login | Artflow Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }
        .auth-card {
            width: min(1240px, 100%);
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 24px 48px var(--bp-shadow);
        }
        .auth-brand {
            min-height: 640px;
            padding: 2.5rem;
            color: #fff;
            background: linear-gradient(155deg, var(--bp-navy-900), var(--bp-navy-700));
        }
        .brand-kicker {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #f7e7be;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .auth-brand h1 {
            max-width: 32rem;
            font-weight: 800;
        }
        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 2rem;
        }
        .dashboard-stat {
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0.85rem;
            background: rgba(255, 255, 255, 0.08);
        }
        .dashboard-stat strong {
            display: block;
            font-size: 1.5rem;
        }
        .dashboard-stat span {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
        }
        .operations-list {
            display: grid;
            gap: 0.65rem;
            margin-top: 1.5rem;
        }
        .operation-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.9rem;
        }
        .operation-row strong {
            color: #fff;
        }
        .auth-feature {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0.85rem;
            background: rgba(255, 255, 255, 0.08);
        }
        .auth-form {
            padding: 2.5rem;
        }
        .auth-form .form-control {
            padding: 0.8rem 0.95rem;
            border-radius: 0.8rem;
        }
        .auth-form .btn {
            padding: 0.8rem 1rem;
            border-radius: 0.8rem;
            font-weight: 700;
        }
        @media (max-width: 767.98px) {
            .auth-shell,
            .auth-brand,
            .auth-form {
                padding: 1.25rem;
            }
            .auth-brand {
                min-height: auto;
            }
        }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
    <main class="auth-shell">
        <div class="auth-card row g-0">
            <section class="auth-brand col-lg-5">
                <span class="brand-kicker">Artflow Management System</span>
                <h1>Hotel Management Dashboard</h1>
                <p class="mt-3 mb-0 opacity-75">Your hotel operations control center for reservations, rooms, guests, payments, and housekeeping.</p>

                <div class="auth-feature mt-4">
                    <strong>One connected workspace</strong>
                    <p class="mb-0 mt-2">Manage reservations, rooms, guests, payments, and housekeeping from one hotel operations dashboard.</p>
                </div>
                <div class="auth-feature">
                    <strong>Your data, your hotel</strong>
                    <p class="mb-0 mt-2">Hotel records are kept within the workspace assigned to your administrator account.</p>
                </div>
            </section>

            <section class="auth-form col-lg-7 d-flex flex-column justify-content-center">
                <div class="mb-4">
                    <p class="text-uppercase text-gold small fw-bold mb-2">Hotel admin portal</p>
                    <h2 class="h3 fw-bold mb-1">Welcome back</h2>
                    <p class="text-secondary mb-0">Sign in to open your management dashboard.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('hotel-admin.login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="identifier" class="form-label fw-semibold">Email address or username</label>
                        <input type="text" id="identifier" name="identifier" class="form-control" value="{{ old('identifier', old('email')) }}" autocomplete="username" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label text-secondary">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Sign in to dashboard</button>
                    <a href="{{ route('hotel-admin.register') }}" class="btn btn-outline-primary w-100 mt-2">Register a hotel account</a>
                    <a href="{{ route('login') }}" class="d-block mt-3 text-center text-secondary">Super Admin sign in</a>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
