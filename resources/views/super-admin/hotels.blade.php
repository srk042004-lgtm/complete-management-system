@extends('super-admin.layouts.app')

@section('title', 'Registered Hotels | Artflow')
@section('heading', 'Registered Hotels')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">Tenant portfolio managed from the super admin workspace.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Back to Dashboard</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Tenant</th>
                <th class="px-4 py-3 text-left">Owner Email</th>
                <th class="px-4 py-3 text-left">Country</th>
                <th class="px-4 py-3 text-left">Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tenants as $tenant)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 font-semibold">{{ $tenant->tenant_name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $tenant->owner_email }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $tenant->country ?? 'Pakistan' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ ucfirst($tenant->status) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">No hotels found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tenants->links() }}</div>
@endsection
