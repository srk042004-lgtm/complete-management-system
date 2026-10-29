<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Artflow Management System | Super Admin')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.14.1/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] }, colors: { brand: { 50: '#f0fdfa', 100: '#ccfbf1', 600: '0d9488', 700: '0f766e', 800: '#115e59' } } } } };</script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-[272px] flex-col gap-6 sticky top-0 h-screen overflow-y-auto border-r border-slate-200 bg-white p-5 text-slate-700 lg:flex">
            <div class="px-2">
                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-teal-700">Artflow Platform</div>
                <div class="mt-2 text-lg font-bold text-slate-900">Artflow Management System</div>
                <div class="mt-2 rounded-2xl border border-teal-100 bg-teal-50 px-3 py-2 text-xs text-teal-900">
                    Multi-tenant hotel operations control center.
                </div>
            </div>

            @php
                $saIcons = [
                    'home' => '<path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5Z"/>',
                    'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>',
                    'building' => '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M14 9h1M9 13h1M14 13h1"/>',
                    'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 3.1a4 4 0 0 1 0 7.8M22 21a7 7 0 0 0-4-6.3"/>',
                    'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
                    'bell' => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10 21a2 2 0 0 0 4 0"/>',
                    'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
                    'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
                    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
                    'receipt' => '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2 3 2V2l-3 2-3-2-3 2-3-2-3 2z"/><path d="M8 8h8M8 12h8"/>',
                    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
                ];
                $saNav = [
                    ['route' => 'super-admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                    ['route' => 'super-admin.profile', 'label' => 'Profile', 'icon' => 'user'],
                    ['route' => 'super-admin.hotel-registry', 'label' => 'Registered hotels', 'icon' => 'building'],
                    ['route' => 'super-admin.users', 'label' => 'Users', 'icon' => 'users'],
                    ['route' => 'super-admin.payments', 'label' => 'Payments', 'icon' => 'card'],
                    ['route' => 'super-admin.notifications', 'label' => 'Notifications', 'icon' => 'bell'],
                    ['route' => 'super-admin.activity-logs', 'label' => 'Activity logs', 'icon' => 'list'],
                    ['route' => 'super-admin.reports', 'label' => 'Reports', 'icon' => 'chart'],
                    ['route' => 'super-admin.plan-manager', 'label' => 'Subscription plans', 'icon' => 'tag'],
                    ['route' => 'super-admin.billing', 'label' => 'Billing', 'icon' => 'receipt'],
                    ['route' => 'super-admin.team-access', 'label' => 'Team access', 'icon' => 'shield'],
                    ['route' => 'super-admin.settings', 'label' => 'Settings', 'icon' => 'gear'],
                ];
            @endphp
            @php($unreadNotifications = \App\Models\SuperAdminNotification::query()->whereNull('read_at')->count())
            <nav class="flex flex-col gap-0.5" aria-label="Super admin navigation">
                @foreach($saNav as $item)
                    @php($active = request()->routeIs($item['route']))
                    <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                       class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $active ? 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-teal-700' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">{!! $saIcons[$item['icon']] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                        @if($item['route'] === 'super-admin.notifications')
                            <span id="sa-notification-badge" class="ml-auto inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1.5 text-[11px] font-bold leading-none text-white" style="display: {{ $unreadNotifications > 0 ? 'inline-flex' : 'none' }}">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>

        <main class="relative flex-1 bg-slate-50 p-4 md:p-6 lg:p-8">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-teal-700">Software house console</p>
                    <h1 class="mt-1 truncate text-2xl font-bold text-slate-900">@yield('heading', 'Artflow Management System')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm sm:inline-flex">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Platform live
                    </span>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm">{{ now()->format('D, d M Y') }}</span>
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-teal-700 text-xs font-bold text-white">{{ strtoupper(substr(auth('super_admin')->user()->name ?? 'S', 0, 1)) }}</span>
                            <span class="hidden sm:inline">{{ auth('super_admin')->user()->name ?? 'Super Admin' }}</span>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            <a href="{{ route('super-admin.profile') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">Software house profile</a>
                            <a href="{{ route('super-admin.settings') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">Admin settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition-colors duration-150 hover:bg-rose-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div data-super-admin-mobile-navigation class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm lg:hidden">
                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('super-admin.dashboard') }}" class="rounded-2xl px-3 py-3 text-center text-sm font-semibold transition duration-300 ease-in-out {{ request()->routeIs('super-admin.dashboard') ? 'bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] text-white shadow-lg' : 'bg-slate-950 text-white hover:bg-slate-800' }}">Dashboard</a>
                    <a href="{{ route('super-admin.hotel-registry') }}" class="rounded-2xl px-3 py-3 text-center text-sm font-semibold transition duration-300 ease-in-out {{ request()->routeIs('super-admin.hotel-registry') ? 'bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] text-white shadow-lg' : 'bg-slate-950 text-white hover:bg-slate-800' }}">Hotels</a>
                    <a href="{{ route('super-admin.plan-manager') }}" class="rounded-2xl px-3 py-3 text-center text-sm font-semibold transition duration-300 ease-in-out {{ request()->routeIs('super-admin.plan-manager') || request()->routeIs('super-admin.plan.update') ? 'bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] text-white shadow-lg' : 'bg-slate-950 text-white hover:bg-slate-800' }}">Plans</a>
                    <a href="{{ route('super-admin.billing') }}" class="rounded-2xl px-3 py-3 text-center text-sm font-semibold transition duration-300 ease-in-out {{ request()->routeIs('super-admin.billing') ? 'bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] text-white shadow-lg' : 'bg-slate-950 text-white hover:bg-slate-800' }}">Billing</a>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="mt-4" data-super-admin-mobile-logout>
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5M15 12H3M21 3v18"/></svg>
                        Logout
                    </button>
                </form>
            </div>

            @yield('content')
            @include('partials.designer-credit')
        </main>
    </div>

    @stack('scripts')
    <script>
        // Live notification count: refreshes every 5 seconds, when the tab regains focus, and after each notification is seen.
        window.saNotifications = (() => {
            const badge = document.getElementById('sa-notification-badge');
            const countUrl = @js(route('super-admin.notifications.unread-count'));

            const setCount = (count) => {
                if (!badge) return;
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            };

            const poll = async () => {
                try {
                    const response = await fetch(countUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                    if (response.ok) {
                        setCount((await response.json()).count);
                    }
                } catch (error) {
                    // Network hiccup: keep the last known count and try again on the next tick.
                }
            };

            setInterval(poll, 5000);
            window.addEventListener('focus', poll);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

            return { setCount, poll };
        })();
    </script>
    @include('partials.dev-role-switcher')
    <script>
        (function () {
            var root = document.documentElement;
            var scope = document.querySelector('meta[name="theme-scope"]');
            var key = 'artflow-theme' + (scope && scope.content ? ':' + scope.content : '');
            var toggle = document.querySelector('.super-admin-theme-toggle');
            var apply = function (theme) {
                root.setAttribute('data-theme', theme);
                if (toggle) toggle.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
            };
            var saved = null;
            try { saved = window.localStorage.getItem(key); } catch (error) { saved = null; }
            apply(saved === 'dark' ? 'dark' : 'light');
            if (!toggle) return;
            toggle.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                apply(next);
                try { window.localStorage.setItem(key, next); } catch (error) { /* storage unavailable */ }
            });
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        html[data-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        html[data-theme="dark"] .bg-white { background-color: #1e293b !important; }
        html[data-theme="dark"] .bg-slate-50, html[data-theme="dark"] .bg-slate-100 { background-color: #0f172a !important; }
        html[data-theme="dark"] .text-slate-900 { color: #f1f5f9 !important; }
        html[data-theme="dark"] .text-slate-700, html[data-theme="dark"] .text-slate-600 { color: #cbd5e1 !important; }
        html[data-theme="dark"] .border-slate-200 { border-color: #334155 !important; }
        html[data-theme="dark"] .bg-slate-50 { background-color: #0f172a !important; }
    </style>
    <script src="{{ asset('js/action-tooltips.js') }}" defer></script>
</body>
</html>
