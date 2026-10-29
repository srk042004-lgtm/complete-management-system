<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $role === 'admin' ? 'Hotel Operations Control' : $roleLabel }} | ArtFlow</title>
    <style>
        :root{--ink:#17313a;--muted:#64748b;--line:#dbe5e7;--teal:#0f766e;--surface:#fff;--bg:#f4f8f7}
        *{box-sizing:border-box}
        body{margin:0;background:linear-gradient(135deg,#f2f7f6,#f8fafc);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        a{color:inherit;text-decoration:none}
        .wrap{width:min(1100px,calc(100% - 32px));margin:auto}
        .topbar{border-bottom:1px solid var(--line);background:rgba(255,255,255,.9);backdrop-filter:blur(12px)}
        .topbar-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:68px}
        .brand{font-weight:900;letter-spacing:.04em;color:var(--teal)}
        .user{display:inline-flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;color:var(--muted);font-size:12px;font-weight:700}
        .header-role-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border:1px solid transparent;border-radius:999px;font-size:10px;font-weight:850;line-height:1;white-space:nowrap}
        .header-role-badge:before{width:7px;height:7px;border-radius:50%;background:currentColor;content:"";animation:role-badge-pulse 1.8s ease-in-out infinite}
        .header-role-badge--admin{border-color:#c7d2fe;background:#e0e7ff;color:#3730a3}
        .header-role-badge--owner{border-color:#e9d5ff;background:#f3e8ff;color:#6b21a8}
        .header-role-badge--manager{border-color:#bfdbfe;background:#dbeafe;color:#1e40af}
        .header-role-badge--receptionist{border-color:#a7f3d0;background:#d1fae5;color:#065f46}
        .header-role-badge--housekeeping{border-color:#fde68a;background:#fef3c7;color:#92400e}
        .header-role-badge--default{border-color:#cbd5e1;background:#f1f5f9;color:#475569}
        @keyframes role-badge-pulse{50%{opacity:.45;transform:scale(.82)}}
        main{padding:38px 0 70px}
        .hero{padding:30px;border:1px solid #d7e9e6;border-radius:20px;background:linear-gradient(130deg,#fff,#edf8f6);box-shadow:0 12px 32px rgba(15,35,30,.06)}
        .eyebrow{color:var(--teal);font-size:10px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
        h1{margin:9px 0;font-size:clamp(27px,4vw,38px);letter-spacing:-.045em}
        .hero p{margin:0;color:var(--muted);font-size:14px;line-height:1.65}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:20px}
        .card{padding:22px;border:1px solid var(--line);border-radius:16px;background:var(--surface);box-shadow:0 8px 22px rgba(15,35,30,.05)}
        .card h2{margin:0;font-size:16px}
        .card p{margin:7px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
        .links{display:flex;flex-wrap:wrap;gap:9px;margin-top:15px}
        .link{display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;border:1px solid var(--line);border-radius:9px;background:#f8fafc;color:#334e68;font-size:11px;font-weight:800}
        .invite{grid-column:1/-1}
        .form{display:grid;grid-template-columns:1fr 1fr auto;align-items:end;gap:10px;margin-top:16px}
        label{display:grid;gap:6px;color:#475569;font-size:11px;font-weight:800}
        input,select{width:100%;min-height:40px;padding:9px 10px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);font:inherit;font-size:12px}
        button{min-height:40px;padding:0 15px;border:0;border-radius:8px;background:var(--teal);color:white;font:inherit;font-size:11px;font-weight:850;cursor:pointer}
        .notice{margin-top:13px;padding:11px 13px;border:1px solid #99f6e4;border-radius:9px;background:#ecfdf5;color:#065f46;font-size:12px;line-height:1.5}
        .notice a{font-weight:800;text-decoration:underline;overflow-wrap:anywhere}
        .invite-link-row{display:flex;align-items:center;gap:8px;margin-top:9px}
        .invite-link-row input{min-width:0;flex:1;font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
        .invite-link-row button{flex:0 0 auto}
        .copy-status{display:block;margin-top:7px;font-size:11px;font-weight:750}
        .error{margin-top:12px;color:#b91c1c;font-size:12px}
        .invitation-list{display:grid;gap:9px;margin:15px 0 0;padding:0;list-style:none}
        .invitation-list li{display:grid;gap:9px;padding:12px;border:1px solid var(--line);border-radius:10px;background:#f8fafc;font-size:12px}
        .invitation-details{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px}
        .invitation-list small{color:var(--muted)}
        .invitation-link-row{display:flex;align-items:center;gap:8px}
        .invitation-link-row input{min-width:0;flex:1;font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
        .invitation-link-row button{flex:0 0 auto}
        .invitation-copy-status{min-height:1em;color:var(--teal);font-size:11px;font-weight:750}
        html[data-theme="dark"] body{background:var(--theme-bg);color:var(--theme-text)}
        html[data-theme="dark"] body .topbar{border-color:var(--theme-border);background:rgba(26,37,50,.94)}
        html[data-theme="dark"] body .user{color:var(--theme-muted)}
        html[data-theme="dark"] body .hero{border-color:var(--theme-border);background:linear-gradient(130deg,#1a2532,#202f3b)!important;color:var(--theme-text)}
        html[data-theme="dark"] body .hero h1{color:var(--theme-text)}
        html[data-theme="dark"] body .hero p{color:var(--theme-muted)}
        html[data-theme="dark"] body .card{border-color:var(--theme-border);background:var(--theme-surface);color:var(--theme-text)}
        html[data-theme="dark"] body .card h2{color:var(--theme-text)}
        html[data-theme="dark"] body .card p{color:var(--theme-muted)}
        html[data-theme="dark"] body .invitation-list li{border-color:#3b4d60;background:linear-gradient(145deg,#202e3d,#1a2633);color:#e2e8f0;box-shadow:0 8px 18px rgba(0,0,0,.16)}
        html[data-theme="dark"] body .invitation-list li strong{color:#f1f5f9}
        html[data-theme="dark"] body .invitation-list li small{color:#aebdca}
        html[data-theme="dark"] body .invitation-link-row input{border-color:#45596d;background:#111c29;color:#e2e8f0}
        html[data-theme="dark"] body .invitation-link-row input:focus{border-color:#4ab9aa;box-shadow:0 0 0 3px rgba(74,185,170,.16);outline:none}
        html[data-theme="dark"] body .invitation-link-row button{border:1px solid #36a99b;background:linear-gradient(135deg,#16877c,#14776f);color:#fff;transition:background-color 150ms ease,transform 150ms ease,box-shadow 150ms ease}
        html[data-theme="dark"] body .invitation-link-row button:hover{background:linear-gradient(135deg,#1b9b8d,#16877c);box-shadow:0 6px 14px rgba(10,116,106,.24);transform:translateY(-1px)}
        html[data-theme="dark"] body .invitation-link-row button:focus-visible{outline:3px solid rgba(131,227,211,.48);outline-offset:2px}
        html[data-theme="dark"] body .invitation-copy-status{color:#72dfbd}
        html[data-theme="dark"] body .link{border-color:var(--theme-border)!important;background:var(--theme-surface-raised)!important;color:var(--theme-accent)!important}
        html[data-theme="dark"] body .link:hover{border-color:var(--theme-accent)!important;background:#2b414b!important;color:#a8f0e4!important}
        html[data-theme="dark"] body input,
        html[data-theme="dark"] body select{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-text)}
        html[data-theme="dark"] body input::placeholder{color:var(--theme-muted)}
        @media(max-width:650px){.grid{grid-template-columns:1fr}.invite{grid-column:auto}.form{grid-template-columns:1fr}.hero{padding:23px}.topbar-inner{min-height:60px}}
    </style>
@include('partials.slate-teal-theme')
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <header class="topbar">
        <div class="wrap topbar-inner">
            <a class="brand" href="{{ route('hotel.role.dashboard', ['role' => $role]) }}">ARTFLOW HOTEL</a>
            @php($activeRole = \App\Support\HotelRoleCatalog::normalize((string) auth()->user()->role))
            @php($activeRoleLabel = \App\Support\HotelRoleCatalog::headerLabel($activeRole))
            <span class="user">{{ auth()->user()->name }} · {{ $tenant->hotel_name ?? $tenant->tenant_name }} <span class="header-role-badge header-role-badge--{{ in_array($activeRole, ['admin', 'owner', 'manager', 'receptionist', 'housekeeping'], true) ? $activeRole : 'default' }}">{{ $activeRoleLabel }}</span></span>
        </div>
    </header>
    <main class="wrap">
        @include('partials.subscription-trial-banner')
        <section class="hero">
            <div class="eyebrow">Hotel management workspace</div>
            @if($role === 'admin')
                <h1>Hotel Operations Control</h1>
                <p>Manage the key areas of {{ $tenant->hotel_name ?? $tenant->tenant_name }} from one secure workspace.</p>
            @elseif($role === 'owner')
                <h1>Executive Property Overview</h1>
                <p>Business performance, financial visibility, and subscription management.</p>
            @elseif($role === 'manager')
                <h1>Operations Manager Dashboard</h1>
                <p>Operational oversight for {{ $tenant->hotel_name ?? $tenant->tenant_name }}.</p>
            @elseif($role === 'housekeeping')
                <h1>Housekeeping Room Board</h1>
                <p>Review room-cleaning priorities and keep housekeeping status current.</p>
            @else
                <h1>{{ $roleLabel }}</h1>
                <p>Your hotel workspace for {{ strtolower($roleLabel) }} operations.</p>
            @endif
        </section>

        <div class="grid">
            @if($role === 'owner')
                <section class="card">
                    <h2>Financial performance</h2>
                    <p>Review hotel revenue, expense activity, and operating benchmarks without front-desk controls.</p>
                    <div class="links"><a class="link" href="{{ route('home.reports') }}">Financial reports</a></div>
                </section>
                <section class="card">
                    <h2>SaaS subscription &amp; billing</h2>
                    <p>View your trial or subscription status, choose a plan, submit payment proof, and monitor approval.</p>
                    <div class="links"><a class="link" href="{{ route('account.subscription') }}">Manage subscription</a></div>
                </section>
            @elseif($role === 'receptionist')
                <section class="card">
                    <h2>Front desk operations</h2>
                    <p>Manage arrivals, departures, room assignment, and the live in-house guest directory.</p>
                    <div class="links">
                        <a class="link" href="{{ route('frontdesk.dashboard') }}">Front desk overview</a>
                        <a class="link" href="{{ route('frontdesk.check-in') }}">Check-in</a>
                        <a class="link" href="{{ route('frontdesk.check-out') }}">Check-out</a>
                    </div>
                </section>
                <section class="card">
                    <h2>Guest folios &amp; POS</h2>
                    <p>Post room-service and incidental charges to guest folios or collect payment immediately.</p>
                    <div class="links">
                        <a class="link" href="{{ route('frontdesk.in-house') }}">In-house guests &amp; folios</a>
                        @if($tenant->hasFeature('restaurant'))<a class="link" href="{{ route('pos.index') }}">Point of Sale</a>@endif
                        <a class="link" href="{{ route('frontdesk.payments.create') }}">Record payment</a>
                    </div>
                </section>
            @elseif($role === 'manager')
                <section class="card">
                    <h2>Operational oversight</h2>
                    <p>Monitor room occupancy, arrivals, departures, and in-house guest activity.</p>
                    <div class="links">
                        <a class="link" href="{{ route('frontdesk.dashboard') }}">Operations overview</a>
                        <a class="link" href="{{ route('frontdesk.rooms.status') }}">Room status grid</a>
                        <a class="link" href="{{ route('reservations.index') }}">Reservations</a>
                    </div>
                </section>
                <section class="card">
                    <h2>Housekeeping &amp; audits</h2>
                    <p>Coordinate room status and review operational reports.</p>
                    <div class="links">
                        <a class="link" href="{{ route('housekeeping.index') }}">Housekeeping</a>
                        <a class="link" href="{{ route('home.reports') }}">Operations reports</a>
                    </div>
                </section>
            @elseif($role === 'housekeeping')
                <section class="card">
                    <h2>Room cleaning tasks</h2>
                    <p>Open the task board for dirty, cleaning, inspection-pending, and departing rooms.</p>
                    <div class="links">
                        <a class="link" href="{{ route('housekeeping.index') }}">Open housekeeping task board</a>
                        <a class="link" href="{{ route('frontdesk.rooms.status') }}">Update room status</a>
                    </div>
                </section>
            @else
                <section class="card">
                    <h2>Hotel configuration &amp; access</h2>
                    <p>Manage property settings, staff invitations, inventory, service catalogs, and operational reporting.</p>
                    <div class="links">
                        <a class="link" href="{{ route('frontdesk.dashboard') }}">Operations overview</a>
                        <a class="link" href="{{ route('frontdesk.inventory') }}">Rooms &amp; categories</a>
                        <a class="link" href="{{ route('frontdesk.food-catalog') }}">Food &amp; service catalog</a>
                        <a class="link" href="{{ route('home.reports') }}">Reports &amp; audit</a>
                        <a class="link" href="{{ route('home.settings') }}">Hotel settings</a>
                    </div>
                </section>
            @endif
            @if($role === 'admin')
            <section class="card">
                <h2>Subscription management</h2>
                <p>Review the current plan, submit payment proof, and monitor Super Admin approval.</p>
                <div class="links"><a class="link" href="{{ route('account.subscription') }}">Subscription &amp; billing</a></div>
            </section>
            @endif

            @if($role === 'admin')
                <section class="card invite">
                    <h2>Generate staff invitation</h2>
                    <p>Create a secure, tenant-bound invitation for an available hotel role. Each link expires after 48 hours and can be used once. Email delivery is not required.</p>
                    @if(session('success'))
                        <div class="notice">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="error">{{ $errors->first() }}</div>
                    @endif
                    @if(count($availableRoles))
                        <form class="form" method="POST" action="{{ route('hotel.team.invite.store') }}">
                            @csrf
                            <label>Email address <small>(optional for testing)</small>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="Leave blank to create a manual invite">
                            </label>
                            <label>Hotel role
                                <select name="role" required>
                                    @foreach($availableRoles as $roleName => $definition)
                                        <option value="{{ $roleName }}" @selected(old('role') === $roleName)>{{ $definition['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit">Create invitation</button>
                        </form>
                    @else
                        <p>Staff invitations are unavailable because staff management is not enabled for this hotel.</p>
                    @endif
                    <h2 style="margin-top:22px">Pending invitations</h2>
                    @if($pendingInvitations->isEmpty())
                        <p>No pending staff invitations.</p>
                    @else
                        <ul class="invitation-list">
                            @foreach($pendingInvitations as $invitation)
                                @php($hasFreshLink = (string) session('invitation_id') === (string) $invitation->id && session('invitation_url'))
                                <li>
                                    <div class="invitation-details">
                                        <span><strong>{{ $invitation->email ?: 'Manual Link' }}</strong><br><small>{{ \App\Support\HotelRoleCatalog::label($invitation->role) }}</small></span>
                                        <small>Expires {{ $invitation->expires_at->diffForHumans() }}</small>
                                    </div>
                                    @if($hasFreshLink)
                                        <div class="invitation-link-row">
                                            <input type="text" value="{{ session('invitation_url') }}" aria-label="Invitation link for {{ $invitation->email ?: 'manual invite' }}" readonly data-invite-link>
                                            <button type="button" data-copy-invite>Copy Link</button>
                                        </div>
                                        <span class="invitation-copy-status" role="status" aria-live="polite" data-copy-status></span>
                                    @else
                                        <small>For security, this link is only shown when the invitation is created. Create a new invitation to generate another link.</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        </div>
    </main>
<script>
    document.querySelectorAll('[data-copy-invite]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const row = button.closest('li');
            const field = row.querySelector('[data-invite-link]');
            const status = row.querySelector('[data-copy-status]');

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(field.value);
                } else {
                    field.focus();
                    field.select();
                    if (!document.execCommand('copy')) {
                        throw new Error('Clipboard copy was not available.');
                    }
                }
                button.textContent = 'Copied!';
                status.textContent = 'Invite link copied. Share it with the invited staff member.';
                window.setTimeout(function () {
                    button.textContent = 'Copy Link';
                }, 2000);
            } catch (error) {
                field.focus();
                field.select();
                status.textContent = 'Copy was blocked by this browser. Select the link and copy it manually.';
            }
        });
    });
</script>
@include('partials.dev-role-switcher')
@include('partials.hotel-shell-end')
</body>
</html>
