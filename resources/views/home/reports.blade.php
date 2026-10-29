<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Reports') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
    <div class="container py-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h1 class="h3 fw-bold mb-3">Reports</h1>
                <p class="text-muted">Access daily occupancy, finance, and performance reports from here.</p>
            </div>
        </div>
    </div>
@include('partials.hotel-shell-end')
</body>
</html>
