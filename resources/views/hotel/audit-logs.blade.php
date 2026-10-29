<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotel Audit Logs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <main class="container py-4">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div><p class="text-uppercase text-secondary small fw-bold mb-1">Owner access only</p><h1 class="h3 mb-0">Audit &amp; security logs</h1></div>
            <a class="btn btn-outline-primary" href="{{ route('hotel.role.dashboard', ['role' => 'owner']) }}">Back to financial overview</a>
        </header>
        <p class="text-secondary">High-risk hotel actions: price overrides, refunds, staff suspensions, and deleted bookings.</p>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Action</th><th>Performed by</th><th>Details</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr><td class="fw-semibold">{{ str($log->action)->replace('.', ' ')->headline() }}</td><td>{{ $log->actor?->name ?? 'System' }}</td><td>{{ collect($log->details ?? [])->except(['password', 'token'])->map(fn ($value, $key) => str($key)->replace('_', ' ')->headline().': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') ?: '—' }}</td><td>{{ $log->created_at?->format('Y-m-d H:i') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">No high-risk events have been recorded.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </main>
@include('partials.hotel-shell-end')
</body>
</html>
