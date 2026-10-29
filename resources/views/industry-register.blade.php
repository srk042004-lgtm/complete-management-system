<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $systemLabel }} Registration | ArtFlow</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f6faf9; color: #17313a; font-family: ui-sans-serif, system-ui, sans-serif; }
        .wrap { width: min(880px, calc(100% - 32px)); margin: auto; }
        .top { padding: 18px 0; background: #fff; border-bottom: 1px solid #dbe5e7; }
        .brand { color: #0f766e; font-weight: 900; text-decoration: none; }
        .login { float: right; color: #475569; font-size: 12px; font-weight: 800; text-decoration: none; }
        .hero { padding: 42px 0 25px; text-align: center; }
        .hero h1 { font-size: 34px; }
        .hero p { color: #64748b; line-height: 1.6; }
        .card { margin-bottom: 50px; padding: 26px; border: 1px solid #dbe5e7; border-radius: 16px; background: #fff; box-shadow: 0 10px 25px #12343b12; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        .field { display: grid; gap: 5px; min-width: 0; }
        .full, .title { grid-column: 1 / -1; }
        .field label { color: #475569; font-size: 11px; font-weight: 800; }
        .field input, .field select, .field textarea { width: 100%; padding: 11px; border: 1px solid #dbe5e7; border-radius: 8px; font: inherit; font-size: 13px; }
        .field textarea { min-height: 80px; resize: vertical; }
        .title { margin: 5px 0 0; padding-bottom: 8px; border-bottom: 1px solid #dbe5e7; font-size: 16px; }
        .button { margin-top: 18px; border: 0; border-radius: 8px; padding: 12px 18px; background: #0f766e; color: #fff; font-weight: 850; cursor: pointer; }
        .error { margin-bottom: 18px; padding: 12px; border-radius: 8px; background: #fff1f2; color: #be123c; font-size: 12px; }
        .error-message { color: #be123c; font-size: 11px; font-weight: 700; }
        @media (max-width: 600px) {
            .grid { grid-template-columns: 1fr; }
            .full, .title { grid-column: auto; }
            .hero h1 { font-size: 28px; }
            .card { padding: 18px; }
            .login { font-size: 11px; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/card-hover.css') }}">
</head>
<body>
    <header class="top">
        <div class="wrap">
            <a class="brand" href="{{ route('portal') }}">AF &nbsp; ArtFlow Management System</a>
            <a class="login" href="{{ route('hotel-admin.login') }}">Already have an account? Login now</a>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="wrap">
                <h1>{{ $systemLabel }} Registration</h1>
                <p>
                    @if ($paidPlan)
                        Permanent {{ ucfirst($billingCycle) }} account registration. Submit payment proof after signup; access begins only after approval.
                    @else
                        Start your {{ $trialDurationDays }}-day trial. Only an Owner or Admin account is created here.
                    @endif
                </p>
            </div>
        </section>
        <div class="wrap">
            <form class="card" method="POST" action="{{ route('industry.register.store', $system) }}">
                @csrf
                @if($trialAccountId)
                    <input type="hidden" name="trial_account_id" value="{{ $trialAccountId }}">
                @endif
                @if(session('demo_success'))
                    <div class="error" style="background:#ecfeff;color:#0f766e">{{ session('demo_success') }}</div>
                @endif
                @if ($paidPlan)
                    <input type="hidden" name="plan" value="{{ $paidPlan }}">
                    <input type="hidden" name="cycle" value="{{ $billingCycle }}">
                    <input type="hidden" name="currency" value="{{ $currency }}">
                    <div class="error" style="background:#ecfeff;color:#0f766e">
                        {{ ucfirst($paidPlan) }} / {{ ucfirst($billingCycle) }} plan selected in {{ $currency }}. No trial will be applied; payment proof and Super Admin approval are required to activate access.
                    </div>
                @endif
                @if ($errors->any())
                    <div class="error" role="alert">
                        <strong>Please fix the following before continuing:</strong>
                        <ul style="margin:8px 0 0;padding-left:20px">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="grid">
                    <h2 class="title">Business and contact details</h2>
                    <div class="field full">
                        <label for="account_role">Initial account role *</label>
                        <select id="account_role" name="account_role" required>
                            <option value="owner" @selected(old('account_role', 'owner') === 'owner')>Owner / Executive</option>
                            <option value="admin" @selected(old('account_role') === 'admin')>Admin / General Manager</option>
                        </select>
                        @error('account_role')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="business_name">Business name *</label>
                        <input id="business_name" name="business_name" required value="{{ old('business_name') }}">
                        @error('business_name')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="contact_name">Contact person *</label>
                        <input id="contact_name" name="contact_name" required value="{{ old('contact_name') }}">
                        @error('contact_name')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="email">Email / login email *</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}">
                        @error('email')<small class="error-message">{{ $message }} If you already registered, use the login link above.</small>@enderror
                    </div>
                    <div class="field">
                        <label for="phone">Phone *</label>
                        <input id="phone" name="phone" type="tel" required value="{{ old('phone') }}">
                        @error('phone')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="city">City *</label>
                        <input id="city" name="city" required value="{{ old('city') }}">
                        @error('city')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="country">Country *</label>
                        <input id="country" name="country" required value="{{ old('country', 'Pakistan') }}">
                        @error('country')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field full">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" required>{{ old('address') }}</textarea>
                        @error('address')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <h2 class="title">{{ $systemLabel }} details</h2>
                    @if($system === 'hotel')
                        <div class="field full">
                            <label for="hotel_type">Hotel type *</label>
                            <select id="hotel_type" name="hotel_type" required>
                                <option value="luxury" @selected(old('hotel_type', 'small') === 'luxury')>Luxury / Five-Star</option>
                                <option value="large" @selected(old('hotel_type', 'small') === 'large')>Large / Full-Service</option>
                                <option value="small" @selected(old('hotel_type', 'small') === 'small')>Small / Boutique / Budget</option>
                            </select>
                            <small style="color:#64748b">Type sets suggested defaults. Optional modules remain under your control.</small>
                        </div>
                        <div class="field full">
                            <strong>Recommended hotel features</strong>
                            <small style="color:#64748b">Rooms, reservations, front desk, and guest management are included. Choose the optional modules this hotel actually operates.</small>
                            <div class="grid" id="industryHotelFeatures" data-feature-defaults='@json($featureDefaults)'>
                                @foreach($hotelFeatures as $category => $items)
                                    <section style="padding:13px;border:1px solid #dbe5e7;border-radius:10px;background:#f8fafc">
                                        <strong style="display:block;margin-bottom:8px;text-transform:capitalize">{{ str_replace('_', ' & ', $category) }}</strong>
                                        <div style="display:grid;gap:8px">
                                            @foreach($items as $key => $label)
                                                @if(in_array($key, ['rooms', 'reservations', 'front_desk', 'guest_management'], true))
                                                    <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px"><span>{{ $label }}</span><span style="color:#047857;font-weight:800">Included</span></div>
                                                @else
                                                    @php $featureInitiallyEnabled = old('features') !== null ? in_array($key, old('features', []), true) : (bool) ($featureDefaults['small'][$key] ?? false); @endphp
                                                    <label for="industry-feature-{{ $key }}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px"><span>{{ $label }}</span><input id="industry-feature-{{ $key }}" class="industry-feature" type="checkbox" name="features[]" value="{{ $key }}" data-feature-key="{{ $key }}" @checked($featureInitiallyEnabled)></label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                            <details><summary style="cursor:pointer;color:#64748b;font-size:12px;font-weight:700">Modules planned for future releases</summary><small style="color:#64748b">Events &amp; banquets · Spa &amp; wellness · Recreation · Concierge · Maintenance · Inventory · Advanced analytics</small></details>
                        </div>
                    @endif
                    <div class="field">
                        <label for="scale">Operating size</label>
                        <input id="scale" name="details[scale]" placeholder="Rooms, doctors, students, tables, or branches" value="{{ old('details.scale') }}">
                    </div>
                    <div class="field">
                        <label for="current_software">Current software</label>
                        <input id="current_software" name="details[current_software]" placeholder="Excel, another system, or none" value="{{ old('details.current_software') }}">
                    </div>
                    <div class="field full">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="details[requirements]">{{ old('details.requirements') }}</textarea>
                    </div>
                    <h2 class="title">Secure login</h2>
                    <div class="field">
                        <label for="password">Password *</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
                        @error('password')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password *</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                        @error('password_confirmation')<small class="error-message">{{ $message }}</small>@enderror
                    </div>
                </div>
                <button class="button" type="submit">
                    @if($trialAccountId)
                        Create demo account &amp; start {{ $trialDurationDays }}-day trial
                    @else
                        Create permanent account &amp; continue to payment
                    @endif
                </button>
            </form>
        </div>
    </main>
    @if($system === 'hotel')
        <script>
            (() => {
                const typeSelect = document.getElementById('hotel_type');
                const featurePanel = document.getElementById('industryHotelFeatures');
                const defaults = JSON.parse(featurePanel.dataset.featureDefaults);
                const options = [...featurePanel.querySelectorAll('.industry-feature')];
                typeSelect.addEventListener('change', () => {
                    const recommended = defaults[typeSelect.value] || defaults.small;
                    options.forEach((option) => { option.checked = Boolean(recommended[option.dataset.featureKey]); });
                });
            })();
        </script>
    @endif
    @include('partials.designer-credit')
</body>
</html>
