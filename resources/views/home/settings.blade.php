<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotel Features & Settings | {{ config('app.name', 'ArtFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <div class="container hotel-feature-settings py-5" style="max-width:1050px">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div><div class="small fw-bold text-uppercase text-success" style="letter-spacing:.14em">Property configuration</div><h1 class="h2 fw-bold mt-2 mb-1">Hotel Features</h1><p class="text-secondary mb-0">Choose the modules available to {{ $tenant->hotel_name ?? $tenant->tenant_name }}. Changes apply to navigation and protected module routes.</p></div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ ucfirst(\App\Support\HotelFeatureCatalog::normalizeType($tenant->hotel_type, $tenant->total_rooms)) }} hotel defaults</span>
        </div>

        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('home.settings.update') }}">
            @csrf @method('PUT')
            <input type="hidden" name="features_present" value="1">
            <div class="d-grid gap-3">
                @foreach($featureGroups as $category => $items)
                    <section class="card hotel-feature-settings__group border-0 rounded-4 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold text-capitalize mb-3">{{ str_replace('_', ' & ', $category) }}</h2>
                            <div class="row g-3">
                                @foreach($items as $key => $label)
                                    @php $locked = in_array($key, ['rooms', 'reservations', 'front_desk', 'guest_management'], true); @endphp
                                    <div class="col-md-6">
                                        <label class="hotel-feature-settings__option d-flex align-items-center justify-content-between gap-3 rounded-3 border bg-light-subtle px-3 py-3" for="feature-{{ $key }}">
                                            <span class="hotel-feature-settings__copy"><span class="d-block fw-semibold">{{ $label }}</span><small class="text-secondary">{{ $locked ? 'Core hotel capability' : 'Enable or disable this module for your property' }}</small></span>
                                            @if($locked)
                                                <span class="hotel-feature-settings__always-on badge text-bg-success">Always on</span>
                                            @else
                                                <input class="form-check-input flex-shrink-0 m-0" id="feature-{{ $key }}" type="checkbox" name="features[]" value="{{ $key }}" @checked($features[$key] ?? false)>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <details class="card hotel-feature-settings__group hotel-feature-settings__optional border-0 rounded-4 shadow-sm mt-3">
                <summary class="card-body fw-semibold" style="cursor:pointer">Optional modules planned for future releases</summary>
                <div class="px-4 pb-4"><p class="small text-secondary">These modules are shown for planning only and cannot be enabled until their workflows are implemented.</p>
                    <div class="row g-3">
                        @foreach($comingSoonGroups as $category => $items)
                            <div class="col-md-6 col-xl-4"><div class="hotel-feature-settings__future h-100 rounded-3 border bg-light px-3 py-3"><h3 class="h6 fw-bold text-capitalize">{{ str_replace('_', ' & ', $category) }}</h3>@foreach($items as $label)<div class="small text-secondary py-1">{{ $label }} <span class="badge text-bg-light border float-end">Coming soon</span></div>@endforeach</div></div>
                        @endforeach
                    </div>
                </div>
            </details>

            <div class="d-flex justify-content-end mt-4"><button type="submit" class="btn btn-success px-4">Save feature configuration</button></div>
        </form>
    </div>
@include('partials.hotel-shell-end')
</body>
</html>
