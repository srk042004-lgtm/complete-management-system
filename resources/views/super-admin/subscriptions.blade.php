@extends('super-admin.layouts.app')

@section('title', 'Subscription Plans | Artflow')
@section('heading', 'Subscription Plans')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">Manage billing plans and tenant access windows.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Back to Dashboard</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($subscriptions as $subscription)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ ucfirst($subscription->plan_type ?? 'Plan') }}</h2>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($subscription->status ?? 'active') }}</span>
                </div>
                <div class="mt-4 text-sm text-slate-600">
                    <div>Tenant: {{ $subscription->tenant?->tenant_name ?? 'Unknown' }}</div>
                    <div>Starts: {{ $subscription->starts_at ?? 'n/a' }}</div>
                    <div>Ends: {{ $subscription->ends_at ?? 'n/a' }}</div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-5 text-slate-500">No subscriptions available.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $subscriptions->links() }}</div>
@endsection
