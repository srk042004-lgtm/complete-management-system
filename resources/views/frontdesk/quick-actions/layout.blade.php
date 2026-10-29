<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }} | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        .shell { width: min(1120px, calc(100% - 40px)); margin: 28px auto 56px; }
        .hero, .panel { border: 1px solid var(--st-line); border-radius: 16px; background: var(--st-card); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .hero { padding: 20px 24px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .hero h1 { margin: 0; font-size: 1.375rem; font-weight: 700; color: var(--st-text); }
        .hero p { margin: 4px 0 0; color: var(--st-muted); font-size: 13px; }
        .panel { padding: 22px; margin-bottom: 16px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field { min-width: 0; }
        .field.full { grid-column: 1 / -1; }
        .field label { display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 600; color: var(--st-text); }
        .field input, .field select, .field textarea { width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 13.5px; transition: border-color .15s ease, box-shadow .15s ease; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--st-accent); box-shadow: 0 0 0 3px var(--st-accent-soft); }
        .field textarea { min-height: 82px; resize: vertical; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 9px 16px; border: 1px solid transparent; border-radius: 10px; background: var(--st-accent); color: #fff; font: inherit; font-size: 13.5px; font-weight: 600; text-decoration: none; cursor: pointer; transition: background-color .15s ease, transform .15s ease; }
        .button:hover { background: var(--st-accent-hover); transform: translateY(-1px); }
        .button.secondary { border-color: var(--st-line); background: var(--st-card); color: var(--st-text); }
        .button.secondary:hover { background: var(--st-hover); }
        .button.danger { background: #dc2626; }
        .button:disabled { opacity: .55; cursor: not-allowed; transform: none; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .notice { padding: 12px 16px; border: 1px solid #a7f3d0; border-radius: 12px; background: #ecfdf5; color: #065f46; margin-bottom: 16px; font-size: 13.5px; }
        .errors { padding: 12px 16px; border: 1px solid #fecaca; border-radius: 12px; background: #fef2f2; color: #991b1b; margin-bottom: 16px; font-size: 13.5px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--st-line); text-align: left; vertical-align: middle; color: var(--st-text); }
        th { color: var(--st-muted); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: var(--st-hover); }
        tbody tr { transition: background-color .15s ease; }
        tbody tr:hover td { background: var(--st-hover); }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; background: var(--st-accent-soft); color: var(--st-accent-text); font-size: 11.5px; font-weight: 600; }
        .muted { color: var(--st-muted); }
        .empty { text-align: center; padding: 32px; color: var(--st-muted); }
        .inline-form { display: flex; align-items: center; gap: 8px; min-width: 250px; }
        .inline-form select { min-height: 38px; padding: 6px 10px; border: 1px solid var(--st-line); border-radius: 9px; background: var(--st-card); color: var(--st-text); }
        @media (max-width: 640px) {
            .shell { width: calc(100% - 24px); margin: 14px auto 32px; }
            .hero, .panel { padding: 16px; }
            .hero { align-items: flex-start; flex-direction: column; }
            .form-grid { grid-template-columns: 1fr; }
            .field.full { grid-column: auto; }
            .actions { flex-direction: column-reverse; }
            .actions .button { width: 100%; }
        }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
            <main class="shell">
                @include('partials.subscription-trial-banner')
                <header class="hero">
                    <div><h1>@yield('title')</h1><p>@yield('description')</p></div>
                    <a class="button secondary" href="{{ route('frontdesk.dashboard') }}">Back to dashboard</a>
                </header>

                @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())
                    <div class="errors" role="alert"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                @yield('content')
            </main>
@include('partials.hotel-shell-end')
</body>
</html>
