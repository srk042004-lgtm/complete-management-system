@extends('super-admin.layouts.app')

@section('title', 'Artflow Management System &mdash; Super Admin Financial Ledger')
@section('heading', 'Artflow Management System &mdash; Super Admin Financial Ledger')

@section('content')
    @php
        $currencySymbol = static function (string $currency): string {
            return match ($currency) {
                'PKR' => 'Rs. ',
                'USD' => '$',
                'GBP' => '&pound;',
                'AED' => 'AED ',
                default => $currency . ' ',
            };
        };

        $formatMoney = static fn (string|float $amount, string $currency): string => $currencySymbol($currency) . number_format((float) $amount, 2);
        $typeLabels = [
            'subscription_base' => 'Subscription Base',
            'addon_restaurant' => 'Restaurant Add-on',
            'manual_adjustment' => 'Manual Adjustment',
        ];
    @endphp

    <div class="space-y-5 pb-20">
        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Platform Revenue</p>
                <p class="mt-3 break-words text-2xl font-bold tracking-tight text-slate-950">{{ $summary['total_platform_revenue'] }}</p>
                <p class="mt-2 text-xs text-slate-500">Successful net ledger revenue.</p>
            </article>
            <article class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Active MRR</p>
                <p class="mt-3 break-words text-2xl font-bold tracking-tight text-indigo-950">{{ $summary['active_mrr'] }}</p>
                <p class="mt-2 text-xs text-indigo-700/75">Successful base subscriptions posted this month.</p>
            </article>
            <article class="rounded-2xl border border-cyan-100 bg-cyan-50/60 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-800">Add-On Revenue</p>
                <p class="mt-3 break-words text-2xl font-bold tracking-tight text-cyan-950">{{ $summary['addon_revenue'] }}</p>
                <p class="mt-2 text-xs text-cyan-800/75">Successful restaurant add-on revenue.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('super-admin.billing') }}" class="grid gap-3 border-b border-slate-200 bg-slate-50/80 p-4 lg:grid-cols-[220px_220px_minmax(0,1fr)_auto] lg:items-end">
                <div>
                    <label for="region" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Region</label>
                    <select id="region" name="region" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" @selected($region === 'all')>All Regions</option>
                        <option value="domestic" @selected($region === 'domestic')>Domestic (Pakistan)</option>
                        <option value="international" @selected($region === 'international')>International (Outside Pakistan)</option>
                    </select>
                </div>
                <div>
                    <label for="type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Transaction Type</label>
                    <select id="type" name="type" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" @selected($transactionType === 'all')>All Types</option>
                        <option value="subscription_base" @selected($transactionType === 'subscription_base')>Subscription Base</option>
                        <option value="addon_restaurant" @selected($transactionType === 'addon_restaurant')>Restaurant Add-on</option>
                    </select>
                </div>
                <div>
                    <label for="search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Invoice or Property</label>
                    <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Search AMS-2026-XXXX or hotel name" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Apply filters</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-900 text-xs uppercase tracking-wider text-slate-200">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice Ref</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Property Name</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Region</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Type</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Gateway</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Gross</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Tax</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Net System Revenue</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @forelse ($ledger as $entry)
                            <tr class="transition hover:bg-indigo-50/50">
                                <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-semibold text-slate-900">{{ $entry->invoice_number }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ $entry->tenant->hotel_name ?: $entry->tenant->tenant_name }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                    <span class="font-medium text-slate-800">{{ $entry->currency_code === 'PKR' ? 'Domestic' : 'International' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $entry->tenant->country ?: 'Not set' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ $typeLabels[$entry->transaction_type] }}</td>
                                <td class="whitespace-nowrap px-4 py-4 capitalize text-slate-700">{{ str_replace('_', ' ', $entry->payment_method) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-medium text-slate-800">{{ $formatMoney($entry->gross_amount, $entry->currency_code) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-slate-600">{{ $formatMoney($entry->tax_amount, $entry->currency_code) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-slate-950">{{ $formatMoney($entry->net_amount, $entry->currency_code) }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-600 text-white' => $entry->payment_status === 'successful',
                                        'bg-amber-100 text-amber-800 ring-1 ring-amber-200' => $entry->payment_status === 'pending_verification',
                                        'bg-red-100 text-red-700 ring-1 ring-red-200' => $entry->payment_status === 'refunded',
                                    ])>
                                        {{ $entry->payment_status === 'pending_verification' ? 'Pending verification' : ucfirst($entry->payment_status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">No ledger entries match the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ledger->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $ledger->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
