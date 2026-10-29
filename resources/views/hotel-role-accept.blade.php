<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join {{ $invitation->tenant?->hotel_name ?? $invitation->tenant?->tenant_name ?? 'Hotel team' }} | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body{min-height:100vh;background:linear-gradient(135deg,#f2f7f4,#e7f1ec);color:#17313a;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.invite-wrap{width:min(620px,calc(100% - 28px));margin:7vh auto}.invite-card{padding:clamp(20px,5vw,38px);border:1px solid #dbe5e7;border-radius:20px;background:white;box-shadow:0 18px 48px rgba(16,47,41,.12)}.brand{display:flex;align-items:center;gap:10px;margin-bottom:28px;color:#17463c;font-weight:900}.brand-mark{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:linear-gradient(145deg,#d7bd82,#9f7c3d);color:#17382f}.eyebrow{color:#9a793f;font-size:10px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}.invite-card h1{margin:8px 0;font-size:clamp(25px,5vw,34px);letter-spacing:-.04em}.intro{color:#64748b;font-size:13px;line-height:1.65}.meta{display:flex;flex-wrap:wrap;gap:8px;margin:17px 0 22px}.badge{padding:7px 10px;border-radius:999px;background:#e8f4ee;color:#16604a;font-size:10px;font-weight:800}.badge.expiry{background:#fbf3e3;color:#805f26}.field{display:grid;gap:5px;margin:13px 0}.field label{font-size:11px;font-weight:800;color:#475569}.field input{width:100%;padding:11px 12px;border:1px solid #dbe5e7;border-radius:9px;font:inherit;font-size:13px}.field input:focus{outline:3px solid #d8eee4;border-color:#0f766e}.submit{width:100%;margin-top:8px;padding:12px;border:0;border-radius:10px;background:#0f766e;color:#fff;font-weight:850;cursor:pointer}.error-summary{margin-bottom:14px;padding:12px;border-radius:10px;background:#fff1f2;color:#be123c;font-size:12px}.error-summary ul{margin:6px 0 0;padding-left:18px}.field-error{color:#be123c;font-size:10px}
        html[data-theme="dark"] body{background:radial-gradient(ellipse at 15% 0%,#1c3540 0%,#121d29 42%,#0d1520 100%)!important;color:#edf3f8}
        html[data-theme="dark"] body .invite-card{border-color:#3b4d60;background:linear-gradient(150deg,#1d2b39 0%,#172331 60%,#14202d 100%);box-shadow:0 22px 60px rgba(0,0,0,.38),0 0 0 1px rgba(148,163,184,.04)}
        html[data-theme="dark"] body .brand{color:#a6eee2}
        html[data-theme="dark"] body .brand small{color:#aab8c7}
        html[data-theme="dark"] body .brand-mark{background:linear-gradient(145deg,#65d6c5,#198b82);color:#102b30;box-shadow:0 5px 14px rgba(20,184,166,.18)}
        html[data-theme="dark"] body .eyebrow{color:#e7c982}
        html[data-theme="dark"] body .invite-card h1{color:#f8fafc}
        html[data-theme="dark"] body .intro{color:#bac7d3}
        html[data-theme="dark"] body .badge{border:1px solid #2d665f;background:#173b3a;color:#a6eee2}
        html[data-theme="dark"] body .badge.expiry{border-color:#695733;background:#382f20;color:#f2d58e}
        html[data-theme="dark"] body .field label{color:#cbd5e1}
        html[data-theme="dark"] body .field input{border-color:#465a6e;background:#111c29;color:#f1f5f9;box-shadow:inset 0 1px 2px rgba(0,0,0,.18);transition:border-color 150ms ease,box-shadow 150ms ease,background-color 150ms ease}
        html[data-theme="dark"] body .field input::placeholder{color:#91a1b2}
        html[data-theme="dark"] body .field input:focus{border-color:#55cbbb;background:#142231;box-shadow:0 0 0 3px rgba(85,203,187,.16);outline:none}
        html[data-theme="dark"] body .submit{border:1px solid #36a99b;background:linear-gradient(135deg,#16877c,#14776f);box-shadow:0 8px 18px rgba(10,116,106,.22);transition:transform 150ms ease,box-shadow 150ms ease,background 150ms ease}
        html[data-theme="dark"] body .submit:hover{transform:translateY(-1px);background:linear-gradient(135deg,#1b9b8d,#16877c);box-shadow:0 11px 24px rgba(10,116,106,.3)}
        html[data-theme="dark"] body .submit:focus-visible{outline:3px solid rgba(131,227,211,.52);outline-offset:3px}
        html[data-theme="dark"] body .error-summary{border:1px solid #7f3d4b;background:#3c202b;color:#fecdd3}
        html[data-theme="dark"] body .field-error{color:#fda4af}
        @media(prefers-reduced-motion:reduce){html[data-theme="dark"] body .field input,html[data-theme="dark"] body .submit{transition:none}}
    </style>
</head>
<body>
    <main class="invite-wrap">
        <section class="invite-card">
            <a class="brand" href="{{ route('portal') }}"><span class="brand-mark">AF</span><span>ARTFLOW<br><small>STAFF REGISTRATION</small></span></a>
            <div class="eyebrow">Secure staff invitation</div>
            <h1>Join {{ $invitation->tenant?->hotel_name ?? $invitation->tenant?->tenant_name ?? 'your hotel team' }}</h1>
            <p class="intro">{{ $invitation->email ? 'This invitation is bound to your email and the role selected by the hotel admin.' : 'This manual invitation is bound to the role selected by the hotel admin. Enter your email to create your account.' }} It can be used once and expires at the time shown.</p>
            <div class="meta"><span class="badge">{{ match ($invitation->role) {
                'receptionist' => 'Receptionist',
                'manager' => 'Manager / Operations Supervisor',
                'housekeeping' => 'Housekeeping Manager',
                'cashier' => 'Receptionist / Front Desk',
                'accountant' => 'Accountant / Night Auditor',
                'maintenance' => 'Maintenance Engineer',
                default => ucfirst(str_replace('_', ' ', $invitation->role)),
            } }}</span>@if($invitation->email)<span class="badge">{{ $invitation->email }}</span>@endif<span class="badge expiry">Expires {{ $invitation->expires_at->format('d M Y, H:i') }}</span></div>

            @if($errors->any())
                <div class="error-summary" role="alert">Please correct the following:
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ $acceptRoute === 'hotel.team.accept.link.store' ? route($acceptRoute) : route($acceptRoute, ['token' => $token]) }}">
                @csrf
                @if($acceptRoute === 'hotel.team.accept.link.store')
                    <input type="hidden" name="token" value="{{ $token }}">
                @endif
                <div class="field"><label for="name">Your full name *</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</div>
                @if(!$invitation->email)
                    <div class="field"><label for="email">Email address *</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<small class="field-error">{{ $message }}</small>@enderror</div>
                @endif
                <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"></div>
                <div class="field"><label for="password">Create password *</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>@error('password')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="password_confirmation">Confirm password *</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
                <button class="submit" type="submit">Create account & join hotel</button>
            </form>
        </section>
    </main>
    @include('partials.designer-credit')
</body>
</html>
