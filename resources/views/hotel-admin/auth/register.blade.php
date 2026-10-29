<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Hotel | Artflow Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --ink: #172033; --navy: #10243c; --gold: #c79a43; --mist: #f4f6f8; }
        body { min-height: 100vh; background: linear-gradient(135deg, #eef2f4, #fbfaf7); color: var(--ink); }
        .registration-shell { min-height: 100vh; padding: 2rem 1rem; }
        .registration-card { max-width: 1280px; margin: auto; overflow: hidden; border: 0; border-radius: 1.25rem; box-shadow: 0 24px 70px rgba(16, 36, 60, .15); }
        .brand-panel { min-height: 100%; padding: 3rem; color: white; background: linear-gradient(155deg, var(--navy), #244a65); }
        .brand-panel h1 { max-width: 24rem; font-weight: 800; letter-spacing: -.02em; }
        .brand-panel p { color: rgba(255,255,255,.76); }
        .brand-rule { width: 3rem; height: 3px; margin: 2rem 0; background: var(--gold); }
        .brand-list { display: grid; gap: .9rem; margin-top: 3rem; padding: 0; list-style: none; color: rgba(255,255,255,.82); }
        .brand-list strong { display: block; color: white; }
        .form-panel { padding: 2rem; background: white; }
        .eyebrow { color: var(--gold); font-size: .72rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .stepper { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin: 1.5rem 0 2rem; }
        .step { padding: .8rem .5rem; border-bottom: 3px solid #dfe5ea; color: #8995a2; font-size: .82rem; font-weight: 700; text-align: center; }
        .step.active { border-color: var(--gold); color: var(--navy); }
        .step-panel { display: none; }
        .step-panel.active { display: block; animation: reveal .25s ease-out; }
        .section-title { margin: 1.5rem 0 1rem; font-size: 1rem; font-weight: 800; color: var(--navy); }
        .form-control, .form-select { border-color: #d6dee5; border-radius: .65rem; padding: .7rem .8rem; }
        .form-control:focus, .form-select:focus { border-color: var(--gold); box-shadow: 0 0 0 .2rem rgba(199,154,67,.16); }
        .form-label { margin-bottom: .35rem; font-size: .84rem; font-weight: 700; }
        .form-text { font-size: .74rem; }
        .preview { display: none; width: 76px; height: 76px; object-fit: cover; border-radius: .65rem; border: 1px solid #d6dee5; }
        .agreement { padding: .85rem 1rem; border: 1px solid #e1e7eb; border-radius: .7rem; background: #fafbfc; }
        .btn-primary { border-color: var(--navy); background: var(--navy); }
        .btn-primary:hover { border-color: #1f4462; background: #1f4462; }
        .btn-gold { border-color: var(--gold); background: var(--gold); color: #172033; font-weight: 800; }
        .btn-gold:disabled { border-color: #d8dde1; background: #d8dde1; color: #7d8790; }
        @keyframes reveal { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 767px) { .brand-panel { padding: 2rem; } .form-panel { padding: 1.25rem; } .brand-list { margin-top: 2rem; } }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
<main class="registration-shell">
    <div class="registration-card row g-0">
        <aside class="brand-panel col-lg-4">
            <div class="eyebrow">Artflow Management System</div>
            <h1 class="mt-4">Build your hotel's operating home.</h1>
            <div class="brand-rule"></div>
            <p>Set up your property, invite your first administrator, and start the free trial with sensible defaults already in place.</p>
            <ul class="brand-list">
                <li><strong>One workspace</strong> Reservations, rooms, guests, payments, and housekeeping.</li>
                <li><strong>Tenant-safe by design</strong> Your hotel's data stays connected to its own workspace.</li>
                <li><strong>Ready for operations</strong> Currency, timezone, invoice, and reservation settings start configured.</li>
            </ul>
        </aside>
        <section class="form-panel col-lg-8">
            <div class="eyebrow">New hotel registration</div>
            <h2 class="mt-2 mb-1">Create your hotel account</h2>
            <p class="text-secondary mb-0">Fields marked with * are required.</p>

            @if (session('registration_error'))
                <div class="alert alert-danger mt-4">{{ session('registration_error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger mt-4">
                    <strong>Please correct the highlighted fields.</strong>
                    <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="stepper" aria-label="Registration progress">
                <div class="step active" data-step-label="1">1. Hotel details</div>
                <div class="step" data-step-label="2">2. Administrator</div>
                <div class="step" data-step-label="3">3. Business and trial</div>
            </div>

            <form method="POST" action="{{ route('hotel-admin.register.store') }}" enctype="multipart/form-data" id="registrationForm" novalidate>
                @csrf
                <section class="step-panel active" data-step="1">
                    <h3 class="section-title">Hotel information</h3>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label" for="hotel_name">Hotel name *</label><input class="form-control" id="hotel_name" name="hotel_name" value="{{ old('hotel_name') }}" required>@error('hotel_name')<div class="text-danger form-text">{{ $message }}</div>@enderror</div>
                        <div class="col-md-4"><label class="form-label" for="hotel_type">Hotel type *</label><select class="form-select" id="hotel_type" name="hotel_type" required><option value="">Select type</option>@foreach ($hotelTypes as $type)<option value="{{ $type }}" @selected(old('hotel_type') === $type)>{{ $type }}</option>@endforeach</select></div>
                        <div class="col-md-4" id="otherHotelTypeWrap" hidden><label class="form-label" for="other_hotel_type">Other hotel type *</label><input class="form-control" id="other_hotel_type" name="other_hotel_type" value="{{ old('other_hotel_type') }}"></div>
                        <div class="col-md-4"><label class="form-label" for="total_rooms">Number of rooms *</label><input class="form-control" id="total_rooms" type="number" min="1" name="total_rooms" value="{{ old('total_rooms') }}" required></div>
                        <div class="col-md-8"><label class="form-label" for="address">Hotel address *</label><input class="form-control" id="address" name="address" value="{{ old('address') }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="city">City *</label><input class="form-control" id="city" name="city" value="{{ old('city') }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="state">State / Province</label><input class="form-control" id="state" name="state" value="{{ old('state') }}"></div>
                        <div class="col-md-4"><label class="form-label" for="country">Country *</label><select class="form-select" id="country" name="country" required>@foreach ($countries as $country)<option value="{{ $country }}" @selected(old('country', 'Pakistan') === $country)>{{ $country }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label" for="postal_code">Postal / ZIP code</label><input class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="hotel_phone">Hotel phone number *</label><input class="form-control" id="hotel_phone" name="hotel_phone" value="{{ old('hotel_phone') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="hotel_email">Hotel email *</label><input class="form-control" id="hotel_email" type="email" name="hotel_email" value="{{ old('hotel_email') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="website">Website <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control" id="website" type="text" name="website" value="{{ old('website') }}" placeholder="www.example.com"></div>
                        <div class="col-md-6"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="1">{{ old('description') }}</textarea></div>
                    </div>
                </section>

                <section class="step-panel" data-step="2">
                    <h3 class="section-title">Hotel owner / administrator</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="admin_name">Owner / manager full name *</label><input class="form-control" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="admin_email">Email address *</label><input class="form-control" id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="admin_phone">Mobile / phone number *</label><input class="form-control" id="admin_phone" name="admin_phone" value="{{ old('admin_phone') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="username">Username *</label><input class="form-control" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required><div class="form-text">Use letters, numbers, dashes, or underscores.</div></div>
                        <div class="col-md-6"><label class="form-label" for="password">Password *</label><input class="form-control" id="password" type="password" name="password" autocomplete="new-password" required><div class="form-text">Minimum 8 characters.</div></div>
                        <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirm password *</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
                    </div>
                </section>

                <section class="step-panel" data-step="3">
                    <h3 class="section-title">Business information</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="business_name">Business / company name</label><input class="form-control" id="business_name" name="business_name" value="{{ old('business_name') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="registration_number">Business registration number</label><input class="form-control" id="registration_number" name="registration_number" value="{{ old('registration_number') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="tax_number">Tax / VAT / GST number</label><input class="form-control" id="tax_number" name="tax_number" value="{{ old('tax_number') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="currency_code">Currency *</label><select class="form-select" id="currency_code" name="currency_code" required>@foreach ($currencies as $code => $label)<option value="{{ $code }}" @selected(old('currency_code', 'PKR') === $code)>{{ $code }} - {{ $label }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label" for="timezone">Time zone *</label><select class="form-select" id="timezone" name="timezone" required>@foreach ($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', 'Asia/Karachi') === $timezone)>{{ $timezone }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label" for="date_format">Date format</label><input class="form-control" id="date_format" name="date_format" value="{{ old('date_format', 'Y-m-d') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="check_in_time">Default check-in time *</label><input class="form-control" id="check_in_time" type="time" name="check_in_time" value="{{ old('check_in_time', '14:00') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="check_out_time">Default check-out time *</label><input class="form-control" id="check_out_time" type="time" name="check_out_time" value="{{ old('check_out_time', '12:00') }}" required></div>
                    </div>
                    <h3 class="section-title">Contact and logo</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="reception_phone">Reception phone</label><input class="form-control" id="reception_phone" name="reception_phone" value="{{ old('reception_phone') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="reservation_phone">Reservation phone</label><input class="form-control" id="reservation_phone" name="reservation_phone" value="{{ old('reservation_phone') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="contact_email">Contact email</label><input class="form-control" id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="emergency_contact">Emergency contact number</label><input class="form-control" id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="whatsapp">WhatsApp number</label><input class="form-control" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"></div>
                        <div class="col-md-6"><label class="form-label" for="logo">Hotel logo</label><input class="form-control" id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">Optional. JPG, PNG, or WebP up to 2 MB.</div><img id="logoPreview" class="preview mt-2" alt="Logo preview"></div>
                        <div class="col-12"><label class="form-label" for="contact_address">Contact address</label><input class="form-control" id="contact_address" name="contact_address" value="{{ old('contact_address') }}"><div class="form-text">The hotel address above is used as the primary property address.</div></div>
                    </div>
                    <div class="section-title">Start your free trial</div>
                    <div class="p-3 rounded-3 border bg-light mb-3"><strong>Trial duration is set by the system administrator.</strong><div class="text-secondary small mt-1">Your trial starts after successful registration. The current plan is Trial and the end date is calculated securely by the server.</div></div>
                    <div class="agreement mb-2"><div class="form-check"><input class="form-check-input agreement-check" type="checkbox" id="terms_accepted" name="terms_accepted" value="1" @checked(old('terms_accepted')) required><label class="form-check-label" for="terms_accepted">I agree to the <a href="#">Terms &amp; Conditions</a> *</label></div></div>
                    <div class="agreement"><div class="form-check"><input class="form-check-input agreement-check" type="checkbox" id="privacy_accepted" name="privacy_accepted" value="1" @checked(old('privacy_accepted')) required><label class="form-check-label" for="privacy_accepted">I agree to the <a href="#">Privacy Policy</a> *</label></div></div>
                </section>

                <div class="d-flex justify-content-between gap-2 mt-4 pt-3 border-top">
                    <button class="btn btn-outline-secondary" type="button" id="backButton" disabled>Back</button>
                    <div class="d-flex gap-2"><button class="btn btn-primary" type="button" id="nextButton">Next</button><button class="btn btn-gold" type="submit" id="submitButton" disabled>Create Hotel Account</button></div>
                </div>
            </form>
        </section>
    </div>
</main>
<script>
(() => {
    const panels = [...document.querySelectorAll('.step-panel')];
    const steps = [...document.querySelectorAll('.step')];
    const back = document.getElementById('backButton');
    const next = document.getElementById('nextButton');
    const submit = document.getElementById('submitButton');
    const form = document.getElementById('registrationForm');
    const agreements = [...document.querySelectorAll('.agreement-check')];
    let current = 0;
    const update = () => {
        panels.forEach((panel, index) => panel.classList.toggle('active', index === current));
        steps.forEach((step, index) => step.classList.toggle('active', index <= current));
        back.disabled = current === 0;
        next.hidden = current === panels.length - 1;
        submit.disabled = current !== panels.length - 1;
    };
    next.addEventListener('click', () => {
        const invalidField = panels[current].querySelector(':invalid');
        if (invalidField) {
            panels[current].classList.add('was-validated');
            invalidField.focus();
            return;
        }

        current = Math.min(current + 1, panels.length - 1);
        update();
    });
    back.addEventListener('click', () => { current = Math.max(current - 1, 0); update(); });
    agreements.forEach((input) => input.addEventListener('change', update));
    document.getElementById('hotel_type').addEventListener('change', (event) => {
        const wrap = document.getElementById('otherHotelTypeWrap');
        wrap.hidden = event.target.value !== 'Other';
        document.getElementById('other_hotel_type').required = event.target.value === 'Other';
    });
    document.getElementById('logo').addEventListener('change', (event) => {
        const preview = document.getElementById('logoPreview');
        const file = event.target.files[0];
        if (!file) { preview.style.display = 'none'; return; }
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    });
    form.addEventListener('submit', (event) => {
        const invalidField = form.querySelector(':invalid');
        if (invalidField || agreements.some((input) => !input.checked)) {
            event.preventDefault();
            form.classList.add('was-validated');

            const invalidPanel = panels.findIndex((panel) => panel.contains(invalidField));
            if (invalidPanel >= 0) {
                current = invalidPanel;
                update();
                invalidField.focus();
            }
        }
    });
    update();
})();
</script>
</body>
</html>
