@extends('super-admin.layouts.app')

@section('title', 'Add New Hotel')
@section('heading', 'Add New Hotel')

@section('content')
    <div class="mx-auto max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="text-[11px] uppercase tracking-[0.3em] text-slate-500">Artflow Management System</div>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Register a New Hotel Manually</h2>
            <p class="mt-2 text-sm text-slate-500">Capture the hotel owner and registry information directly from the super-admin workspace.</p>
        </div>

        <form method="POST" action="{{ route('super-admin.hotel-registry.store') }}" class="grid gap-6 p-6 md:grid-cols-2">
            @csrf

            <div>
                <label for="hotel_name" class="mb-1 block text-sm font-medium text-slate-700">Hotel Name</label>
                <input id="hotel_name" name="hotel_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            </div>

            <div>
                <label for="owner_name" class="mb-1 block text-sm font-medium text-slate-700">Owner Name</label>
                <input id="owner_name" name="owner_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                <input id="phone" name="phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
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
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <div class="md:col-span-2 flex gap-3">
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Add Hotel</button>
                <a href="{{ route('super-admin.hotel-registry') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Back to Registry</a>
            </div>
        </form>
    </div>
@endsection
