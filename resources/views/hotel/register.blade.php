<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Hotel Registration') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.slate-teal-theme')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="text-[11px] uppercase tracking-[0.3em] text-slate-500">Artflow Management System</div>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Hotel Registration</h1>
                <p class="mt-2 text-sm text-slate-500">Register a new hotel and associate it with its country so the super-admin dashboard can automatically render native currency metrics.</p>
            </div>

            <form method="POST" action="{{ route('hotel-admin.register.store') }}" class="grid gap-6 p-6 md:grid-cols-2">
                @csrf

                <div class="md:col-span-2">
                    @if (session('success'))
                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif
                </div>

                <div>
                    <label for="tenant_name" class="mb-1 block text-sm font-medium text-slate-700">Hotel Name</label>
                    <input id="tenant_name" name="tenant_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="owner_email" class="mb-1 block text-sm font-medium text-slate-700">Owner Email</label>
                    <input id="owner_email" name="owner_email" type="email" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                    <select id="country" name="country" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @foreach($countries as $country)
                            <option value="{{ $country }}">{{ $country }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="trial">Trial</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Register Hotel</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
