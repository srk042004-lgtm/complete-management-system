<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Created | ArtFlow</title>
    <style>
        body{margin:0;background:#f6faf9;color:#17313a;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{width:min(720px,calc(100% - 32px));margin:auto}.card{margin:12vh auto;padding:34px;border:1px solid #dbe5e7;border-radius:18px;background:#fff;box-shadow:0 15px 35px #12343b12;text-align:center}.mark{display:grid;place-items:center;width:48px;height:48px;margin:0 auto;border-radius:14px;background:#0f766e;color:#fff;font-weight:900}.card h1{margin:17px 0 8px;font-size:30px}.card p{color:#64748b;font-size:14px;line-height:1.65}.countdown{margin:24px 0;padding:20px;border-radius:14px;background:#ecfeff;color:#0f766e;font-size:28px;font-weight:900}.label{display:block;margin-bottom:7px;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.actions{display:flex;justify-content:center;flex-wrap:wrap;gap:10px;margin-top:20px}a{border:1px solid #dbe5e7;border-radius:8px;padding:11px 16px;color:#475569;font-size:12px;font-weight:850;text-decoration:none}.primary{border-color:#12343b;background:#12343b;color:#fff}.status{margin-top:13px;color:#047857;font-size:12px;font-weight:800}
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <div class="mark">AF</div>
        <h1>Your {{ ucfirst($system) }} account is ready</h1>
        @if($isPaidRegistration)
            <p>Your paid ArtFlow subscription is active. You can manage billing and renewals from your workspace.</p>
            <div class="countdown"><span class="label">Subscription status</span><span>Active</span></div>
        @else
            <p>Your ArtFlow workspace has been created. Your trial countdown is based on the subscription expiry recorded by the server.</p>
            <div class="countdown"><span class="label">Trial time remaining</span><span id="countdown">Loading...</span></div>
            <div id="status" class="status"></div>
        @endif
        <div class="actions">
            <a class="primary" href="{{ route('account.subscription') }}">View plans &amp; submit payment</a>
            <a href="{{ route('hotel.role.dashboard', ['role' => auth()->user()->role === 'hotel_admin' ? 'admin' : auth()->user()->role]) }}">Open dashboard</a>
            <a href="{{ route('hotel-admin.login') }}">Login page</a>
        </div>
    </section>
</main>
@if(!$isPaidRegistration)
    <script>
        const output = document.getElementById('countdown');
        const statusText = document.getElementById('status');
        function updateTrial() {
            fetch('{{ route('account.trial-status') }}', { headers: { Accept: 'application/json' } })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'active') {
                        output.textContent = 'Subscription active';
                        return;
                    }
                    if (data.status === 'expired' || !data.remaining_seconds) {
                        output.textContent = 'Trial expired';
                        statusText.textContent = 'Submit a subscription payment request to restore paid access.';
                        return;
                    }
                    const seconds = data.remaining_seconds;
                    output.textContent = Math.floor(seconds / 86400) + 'd '
                        + Math.floor(seconds % 86400 / 3600) + 'h '
                        + Math.floor(seconds % 3600 / 60) + 'm '
                        + seconds % 60 + 's';
                })
                .catch(() => { output.textContent = 'Check your account status'; });
        }
        updateTrial();
        window.setInterval(updateTrial, 1000);
    </script>
@endif
@include('partials.designer-credit')
</body>
</html>
