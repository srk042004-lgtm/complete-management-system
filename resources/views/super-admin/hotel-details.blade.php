@extends('super-admin.layouts.app')

@section('title', ($tenant->hotel_name ?? $tenant->tenant_name).' | Hotel details')
@section('heading', $tenant->hotel_name ?? $tenant->tenant_name ?? 'Hotel details')

@section('content')
@php
    $statusTone = match ($tenant->status) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'suspended' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $subTone = match ($subscription?->status) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'trial' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'expired' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $currency = $tenant->currency_code ?? 'PKR';
    $label = fn (?string $value) => $value !== null && $value !== '' ? $value : '—';
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('super-admin.dashboard') }}" class="text-sm font-semibold text-teal-700 transition-colors duration-150 hover:text-teal-900">&larr; Back to dashboard</a>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusTone }}">
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ ucfirst($tenant->status ?? 'unknown') }}
        </span>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $tenant->hotel_code ?? 'No code' }}</span>
    </div>
</div>

{{-- Headline figures --}}
<section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach([
        ['Rooms', $stats['rooms']],
        ['Bookings', $stats['bookings']],
        ['In house now', $stats['in_house']],
        ['Paid revenue', $currency.' '.number_format($stats['revenue'], 2)],
    ] as [$title, $value])
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $title }}</p>
            <p class="mt-2 text-xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
        </div>
    @endforeach
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    {{-- Hotel profile --}}
    <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm xl:col-span-2">
        <h2 class="mb-5 text-base font-bold text-slate-900">Hotel profile</h2>
        <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
            @foreach([
                ['Hotel name', $tenant->hotel_name ?? $tenant->tenant_name],
                ['Legal / tenant name', $tenant->tenant_name],
                ['Hotel type', $tenant->hotel_type ?? $tenant->property_type],
                ['Total rooms', $tenant->total_rooms],
                ['Email', $tenant->email],
                ['Phone', $tenant->phone],
                ['Address', collect([$tenant->address, $tenant->city, $tenant->state, $tenant->country])->filter()->implode(', ')],
                ['Timezone', $tenant->timezone],
                ['Currency', $currency],
                ['Registered on', $tenant->created_at?->format('d M Y')],
            ] as [$term, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $term }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $label($value !== null ? (string) $value : null) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Owner and subscription --}}
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-bold text-slate-900">Owner</h2>
            <p class="text-sm font-semibold text-slate-900">{{ $label($tenant->owner_name) }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $label($tenant->owner_email) }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $label($tenant->owner_phone ?? null) }}</p>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Subscription</h2>
                @if($subscription)
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $subTone }}">{{ ucfirst($subscription->status) }}</span>
                @endif
            </div>
            @if($subscription)
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Plan</dt><dd class="font-semibold text-slate-900">{{ ucfirst($subscription->plan_type) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Price</dt><dd class="font-semibold text-slate-900">{{ $currency }} {{ number_format((float) $subscription->price, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Started</dt><dd class="text-slate-900">{{ $subscription->starts_at?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ends</dt><dd class="text-slate-900">{{ $subscription->ends_at?->format('d M Y') ?? ($subscription->plan_type === 'lifetime' ? 'Never' : '—') }}</dd></div>
                    @if($subscription->status === 'trial')
                        <div class="flex justify-between"><dt class="text-slate-500">Trial ends</dt><dd class="text-slate-900">{{ $subscription->trial_end?->format('d M Y') ?? '—' }}</dd></div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-slate-500">No subscription on record.</p>
            @endif
        </section>
    </div>

    {{-- Staff --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm xl:col-span-2">
        <div class="border-b border-slate-200 px-6 py-4"><h2 class="text-base font-bold text-slate-900">Staff accounts</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr><th class="px-6 py-3">Name</th><th class="px-6 py-3">Email</th><th class="px-6 py-3">Role</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Last login</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staff as $member)
                        <tr class="transition-colors duration-150 hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $member->email }}</td>
                            <td class="px-6 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', ucfirst($member->role)) }}</span></td>
                            <td class="px-6 py-3 text-slate-600">{{ ucfirst($member->status) }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">No staff accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Payments and activity --}}
    <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-bold text-slate-900">Subscription payments</h2>
        <ul class="space-y-3">
            @forelse($payments as $payment)
                <li class="flex items-center justify-between gap-3 text-sm">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $payment->payment_reference }}</p>
                        <p class="text-xs text-slate-500">{{ $payment->created_at?->format('d M Y') }} · {{ ucfirst($payment->payment_status) }}</p>
                    </div>
                    <span class="shrink-0 font-semibold tabular-nums text-slate-900">{{ $payment->currency_code }} {{ number_format((float) $payment->amount, 2) }}</span>
                </li>
            @empty
                <li class="text-sm text-slate-500">No payments yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm xl:col-span-3">
        <h2 class="mb-4 text-base font-bold text-slate-900">Recent activity</h2>
        <ol class="space-y-3">
            @forelse($activity as $entry)
                <li class="flex items-start gap-3 text-sm">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-600"></span>
                    <div>
                        <p class="text-slate-800">{{ $entry->description ?? str_replace('.', ' ', $entry->action) }}</p>
                        <p class="text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</p>
                    </div>
                </li>
            @empty
                <li class="text-sm text-slate-500">No recorded activity for this hotel.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
