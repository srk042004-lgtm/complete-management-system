@php
    $shellUser = auth()->user();
    $shellRole = \App\Support\HotelRoleCatalog::normalize((string) $shellUser->role);
    $shellRoleLabel = [
        'receptionist' => 'Front Desk Agent',
        'admin' => 'Administrator',
        'manager' => 'Hotel Manager',
        'owner' => 'Owner Account',
        'housekeeping' => 'Housekeeping Staff',
    ][$shellRole] ?? \App\Support\HotelRoleCatalog::label($shellRole);
    $shellCurrent = url()->current();

    // Every link lists the roles allowed to see it; grouping keeps long menus scannable.
    $shellGroups = array_values(array_filter([
        ['title' => 'Overview', 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'href' => $shellRole === 'receptionist' ? route('frontdesk.dashboard') : route('hotel.role.dashboard', ['role' => $shellRole]), 'roles' => ['receptionist', 'admin', 'manager', 'owner', 'housekeeping']],
        ]],
        ['title' => 'Front desk', 'items' => [
            ['label' => 'Check-in', 'icon' => 'login', 'href' => route('frontdesk.check-in'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'Check-out', 'icon' => 'logout', 'href' => route('frontdesk.check-out'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'In-house guests', 'icon' => 'users', 'href' => route('frontdesk.in-house'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'Reservations', 'icon' => 'calendar', 'href' => route('reservations.index'), 'roles' => ['receptionist', 'admin', 'manager']],
            ['label' => 'Room status', 'icon' => 'bed', 'href' => route('frontdesk.rooms.status'), 'roles' => ['receptionist', 'admin']],
        ]],
        ['title' => 'Operations', 'items' => [
            ['label' => 'Housekeeping', 'icon' => 'broom', 'href' => route('housekeeping.index'), 'roles' => ['housekeeping', 'admin', 'manager']],
            ['label' => 'POS', 'icon' => 'utensils', 'href' => route('pos.index'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'Cash shift', 'icon' => 'receipt', 'href' => route('frontdesk.shift'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'Inventory', 'icon' => 'box', 'href' => route('frontdesk.inventory'), 'roles' => ['receptionist', 'admin']],
            ['label' => 'Food catalog', 'icon' => 'utensils', 'href' => route('frontdesk.food-catalog'), 'roles' => ['admin']],
            ['label' => 'Night audit', 'icon' => 'moon', 'href' => route('audit.night'), 'roles' => ['admin']],
        ]],
        ['title' => 'Management', 'items' => [
            ['label' => 'Cash verification', 'icon' => 'shield', 'href' => route('hotel.cash-verification'), 'roles' => ['owner', 'admin'], 'badge' => in_array($shellRole, ['owner', 'admin'], true) ? \App\Models\CashTransfer::query()->where('tenant_id', $shellUser->tenant_id)->where('transfer_type', 'vault_drop')->where('status', 'pending')->count() : 0],
            ['label' => 'Reports', 'icon' => 'chart', 'href' => route('home.reports'), 'roles' => ['owner', 'admin', 'manager']],
            ['label' => 'Audit logs', 'icon' => 'shield', 'href' => route('hotel.audit-logs'), 'roles' => ['owner'], 'locked_for' => ['admin']],
            ['label' => 'Billing', 'icon' => 'receipt', 'href' => route('account.subscription'), 'roles' => ['owner'], 'locked_for' => ['admin']],
            ['label' => 'Settings', 'icon' => 'gear', 'href' => route('home.settings'), 'roles' => ['owner', 'admin']],
        ]],
    ], fn (array $group): bool => true));
    $shellGroups = array_values(array_filter(array_map(function (array $group) use ($shellRole, $shellCurrent): array {
        // Show an item when the role can open it, or when it is a 1-2 page 'locked' preview for this role. Everything else stays hidden.
        $group['items'] = array_values(array_filter($group['items'], fn (array $item): bool => in_array($shellRole, $item['roles'], true) || in_array($shellRole, $item['locked_for'] ?? [], true)));
        foreach ($group['items'] as &$item) {
            $item['allowed'] = in_array($shellRole, $item['roles'], true);
            $item['active'] = $item['href'] === $shellCurrent || (! str_ends_with($item['href'], '/dashboard') && $item['href'] !== url('/') && str_starts_with($shellCurrent, $item['href']));
        }
        return $group;
    }, $shellGroups), fn (array $group): bool => $group['items'] !== []));
    $shellLinks = collect($shellGroups)->pluck('items')->flatten(1)->filter(fn (array $item): bool => $item['allowed'])->values()->all();
    $shellRoleBadge = 'hotel-role-badge--'.$shellRole;
    $shellIcons = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/><path d="M22 21a7 7 0 0 0-4-6.3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'bed' => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20"/><circle cx="6.5" cy="11.5" r="2.5"/>',
        'broom' => '<path d="M19 3l-6 6"/><path d="M3 21l6-6"/><path d="M9 15l-2-2 6-6 2 2z"/>',
        'utensils' => '<path d="M4 3v7a3 3 0 0 0 6 0V3"/><path d="M7 3v18"/><path d="M18 21V3c-2 1-3 4-3 7s1 3 3 3"/>',
        'receipt' => '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2 3 2V2l-3 2-3-2-3 2-3-2-3 2z"/><path d="M8 8h8M8 12h8"/>',
        'box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    ];
@endphp
<div class="hotel-app">
    {{-- Phone-only menu bar: the sidebar is a drawer on small screens, so this button is its only opener. --}}
    <div class="hotel-mobile-bar" data-hotel-mobile-bar>
        <button type="button" class="hotel-mobile-bar__menu" data-hotel-sidebar-open hidden aria-controls="hotel-sidebar-links" aria-expanded="false">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Menu</span>
        </button>
        <span class="hotel-mobile-bar__title">{{ $shellUser->name }}</span>
    </div>
    <aside class="hotel-sidebar" data-hotel-sidebar aria-label="Hotel navigation">
        <div class="hotel-sidebar__brand">
            <span class="hotel-sidebar__mark" aria-hidden="true">A</span>
            <span>
                <strong>{{ auth()->user()->tenant->hotel_name ?? 'ArtFlow' }}</strong>
                <small>ArtFlow PMS</small>
            </span>
        </div>
        <button type="button" class="hotel-sidebar__control" data-hotel-sidebar-toggle aria-expanded="false" aria-controls="hotel-sidebar-links">
            <span class="hotel-sidebar__control-label">Open menu</span>
        </button>
        <nav id="hotel-sidebar-links" class="hotel-sidebar__nav">
            @foreach($shellGroups as $group)
                <div class="hotel-sidebar__group">
                    <div class="hotel-sidebar__group-title">{{ $group['title'] }}</div>
                    @foreach($group['items'] as $link)
                        @if(! $link['allowed'])
                        <span class="hotel-sidebar__link is-locked" aria-disabled="true" title="Not available for your role">
                            <svg class="hotel-sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $shellIcons[$link['icon']] ?? '' !!}</svg>
                            <span class="hotel-sidebar__label">{{ $link['label'] }}</span>
                            <svg class="hotel-sidebar__lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        </span>
                        @else
                        <a href="{{ $link['href'] }}" class="hotel-sidebar__link{{ $link['active'] ? ' is-active' : '' }}" @if($link['active']) aria-current="page" @endif>
                            <svg class="hotel-sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $shellIcons[$link['icon']] ?? '' !!}</svg>
                            <span class="hotel-sidebar__label">{{ $link['label'] }}</span>
                            @if(($link['badge'] ?? 0) > 0)<span class="hotel-sidebar__badge" aria-label="{{ $link['badge'] }} pending" style="margin-left:auto;min-width:20px;padding:0 7px;border-radius:999px;background:#e11d48;color:#fff;font-size:11px;font-weight:700;line-height:20px;text-align:center">{{ $link['badge'] }}</span>@endif
                        </a>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="hotel-sidebar__footer">
            <div data-hotel-sidebar-theme></div>
            <div class="hotel-sidebar__user">
                <span class="hotel-sidebar__avatar" aria-hidden="true">{{ strtoupper(substr($shellUser->name, 0, 1)) }}</span>
                <span class="hotel-sidebar__who">
                    <strong>{{ $shellUser->name }}</strong>
                    <small>{{ $shellRoleLabel }}</small>
                </span>
            </div>
            <form method="POST" action="{{ route('hotel-admin.logout') }}" class="hotel-logout-form">
                @csrf
                <button type="submit" class="hotel-sidebar__link hotel-sidebar__logout">
                    <svg class="hotel-sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $shellIcons['logout'] !!}</svg>
                    <span class="hotel-sidebar__label">Log out</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="hotel-sidebar__scrim" data-hotel-sidebar-scrim hidden></div>
    <div class="hotel-main">
        <header class="hotel-topbar" data-hotel-topbar>
            <div class="hotel-topbar__identity">
                <span class="hotel-topbar__heading">{{ $shellUser->name }}</span>
                <span class="header-role-badge {{ $shellRoleBadge }}">{{ $shellRoleLabel }}</span>
            </div>
            <nav class="hotel-topbar__nav" aria-label="Quick links">
                @foreach(array_slice($shellLinks, 0, 5) as $link)
                    <a href="{{ $link['href'] }}" class="hotel-topbar__link{{ $link['active'] ? ' is-active' : '' }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="hotel-topbar__tools">
                <form method="POST" action="{{ route('hotel-admin.logout') }}" class="topbar-logout-form">
                    @csrf
                    <button type="submit" class="topbar-logout">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        Log out
                    </button>
                </form>
                <div data-hotel-topbar-theme></div>
                <button type="button" class="badge-soft" data-hotel-layout-toggle>Switch to menu view</button>
                <span hidden data-hotel-layout-alternate>Switch to sidebar</span>
            </div>
        </header>
        <div class="hotel-content">
            