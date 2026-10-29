<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artflow Management System Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(140deg, #edf5ff 0%, #dfeaf9 36%, #f7f8fc 100%);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }
        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .auth-card {
            border-radius: 26px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 24px 48px rgba(11, 31, 56, 0.12);
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(8px);
            max-width: 1100px;
            width: 100%;
        }
        .brand-panel {
            background: linear-gradient(160deg, #091d36 0%, #153856 42%, #1e4d73 100%);
            color: white;
            padding: 36px;
        }
        .brand-panel h1 {
            font-size: clamp(2rem, 3vw, 2.7rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(201, 169, 110, 0.2);
            border: 1px solid rgba(201, 169, 110, 0.4);
            color: #f7e7be;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .feature-box {
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 1rem;
            margin-top: 0.75rem;
        }
        .feature-box strong {
            display: block;
            font-size: 1rem;
        }
        .form-panel {
            padding: 36px;
        }
        .form-control {
            border-radius: 14px;
            padding: 0.8rem 0.95rem;
            background: #f8fafc;
            border: 1px solid #dbe4f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(19, 52, 84, 0.12);
            border-color: #123454;
            background: white;
        }
        .btn-primary {
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #123454 0%, #1f5f96 100%);
            border: none;
        }
        .btn-outline-dark {
            border-radius: 14px;
            padding: 0.8rem 1rem;
            font-weight: 600;
        }
        .mini-note {
            font-size: 0.92rem;
            color: #64748b;
        }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card row g-0 align-items-stretch">
            <div class="brand-panel col-lg-5">
                <div class="brand-badge">Artflow Hotel</div>
                <h1>Property Management System</h1>
                <p class="mt-3 mb-4 opacity-75">Manage reservations, housekeeping, billing, and guest operations from one secure control center.</p>

                <div class="feature-box">
                    <strong>Secure access</strong>
                    <div class="small opacity-75 mt-1">Role-based login for trusted hotel and admin staff.</div>
                </div>
                <div class="feature-box">
                    <strong>Live operations</strong>
                    <div class="small opacity-75 mt-1">Track occupancy, check-ins, departures, and service queues in real time.</div>
                </div>
            </div>

            <div class="form-panel col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 mb-1 fw-bold">Welcome back</h2>
                        <p class="mini-note mb-0">Sign in to open the management dashboard.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@yourhotel.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your secure password" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input">
                            <label for="remember" class="form-check-label mini-note">Remember me</label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Sign in to dashboard</button>
                        <a href="{{ route('super-admin.register') }}" class="btn btn-outline-dark">Register new account</a>
                    </div>
                </form>
                <p class="mini-note mt-3 mb-0 text-center">Hotel staff? <a href="{{ route('hotel-admin.login') }}">Sign in to your hotel</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
