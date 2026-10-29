<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful | Artflow Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #eef2f4, #fbfaf7); color: #172033; }
        .success-card { width: min(680px, calc(100% - 2rem)); border: 0; border-radius: 1.25rem; box-shadow: 0 24px 70px rgba(16, 36, 60, .15); }
        .success-mark { display: grid; place-items: center; width: 4rem; height: 4rem; border-radius: 50%; background: #e5f5ed; color: #18794e; font-size: 2rem; }
        .detail { padding: .9rem 1rem; border-bottom: 1px solid #e7ecef; }
        .detail:last-child { border-bottom: 0; }
        .detail span { display: block; color: #71808e; font-size: .78rem; }
        .detail strong { display: block; margin-top: .2rem; }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
    <main class="success-card bg-white p-4 p-md-5">
        <div class="success-mark mb-4">&#10003;</div>
        <div class="text-uppercase text-warning small fw-bold">Artflow Management System</div>
        <h1 class="h2 mt-2">Hotel Registration Successful</h1>
        <p class="text-secondary">Your hotel workspace is ready. Keep your hotel ID for support and administration.</p>
        <div class="border rounded-3 overflow-hidden my-4">
            <div class="detail"><span>Hotel name</span><strong>{{ $tenant->name }}</strong></div>
            <div class="detail"><span>Hotel ID</span><strong>{{ $tenant->hotel_code }}</strong></div>
            <div class="detail"><span>Administrator</span><strong>{{ $user->name }}</strong></div>
            <div class="detail"><span>Email</span><strong>{{ $user->email }}</strong></div>
            <div class="detail"><span>Trial status</span><strong>{{ ucfirst($subscription->status) }}</strong></div>
            <div class="detail"><span>Trial expiry</span><strong>{{ $subscription->trial_end?->format('d M Y, H:i') }}</strong></div>
        </div>
        <a class="btn btn-dark w-100" href="{{ route('frontdesk.dashboard') }}">Continue to Hotel Dashboard</a>
    </main>
</body>
</html>
