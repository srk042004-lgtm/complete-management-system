@extends('super-admin.layouts.app')

@section('title', 'Super Admin Dashboard | Artflow')
@section('heading', 'Platform overview')

@section('content')
    @php
        $sym = $currency['symbol'];
        $expiring = $metrics['expiring_subscriptions'];
        $expiringTotal = method_exists($expiring, 'total') ? $expiring->total() : $expiring->count();
        $statusPill = function (array $row): array {
            $sub = $row['subscription'];
            if (($row['tenant']->status ?? 'active') === 'suspended') {
                return ['Suspended', 'bg-rose-50 text-rose-700 ring-rose-200'];
            }
            if ($sub?->status === 'trial') {
                return ['Trial', 'bg-amber-50 text-amber-700 ring-amber-200'];
            }
            if ($sub?->status === 'expired' || (is_int($row['days_left'] ?? null) && $row['days_left'] < 0)) {
                return ['Expired', 'bg-rose-50 text-rose-700 ring-rose-200'];
            }
            if (is_int($row['days_left'] ?? null) && $row['days_left'] <= 7) {
                return ['Expiring', 'bg-amber-50 text-amber-700 ring-amber-200'];
            }
            return ['Active', 'bg-emerald-50 text-emerald-700 ring-emerald-200'];
        };
    @endphp

    {{-- Headline metrics: one card style, one grid --}}
    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Monthly recurring revenue</p>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-teal-50 text-teal-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M14 7h7v7"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $sym }}{{ number_format($metrics['mrr'], 2) }}</p>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-inset ring-teal-200">ARR {{ $sym }}{{ number_format($metrics['arr'], 2) }}</span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Active hotels</p>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M14 9h1M9 13h1M14 13h1"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $metrics['tenant_counts']['active'] }}<span class="ml-1 text-sm font-medium text-slate-500">active</span></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">{{ $metrics['subscription_counts']['trial'] }} trial</span>
                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">{{ $metrics['tenant_counts']['suspended'] }} suspended</span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Subscription lifecycle</p>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $expiringTotal }}<span class="ml-1 text-sm font-medium text-slate-500">expiring in 7 days</span></p>
            <div class="mt-3 flex items-center gap-2">
                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">{{ $metrics['subscription_counts']['expired'] }} expired</span>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Verification queue</p>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-50 text-sky-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M3 10h18"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $metrics['pending_payments'] }}<span class="ml-1 text-sm font-medium text-slate-500">awaiting review</span></p>
            <div class="mt-3">
                <a href="#verification" class="text-xs font-semibold text-teal-700 transition-colors duration-150 hover:text-teal-900">Review payments &rarr;</a>
            </div>
        </article>
    </section>

    {{-- Secondary metrics, one row, same card rhythm --}}
    <section class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach([
            ['Lifetime revenue', $sym.number_format($metrics['lifetime_revenue'], 2), 'text-slate-900'],
            ['Total users', $metrics['user_counts']['total'], 'text-slate-900'],
            ['Trial hotels', $metrics['subscription_counts']['trial'], 'text-amber-700'],
            ['Expired subscriptions', $metrics['subscription_counts']['expired'], 'text-rose-700'],
        ] as [$label, $value, $tone])
            <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm transition-colors duration-150 hover:bg-slate-50">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-lg font-bold {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    {{-- Expiry alert --}}
    @if($expiringTotal > 0)
        <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/70 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-amber-900">{{ $expiringTotal }} subscription{{ $expiringTotal === 1 ? '' : 's' }} end within 7 days</h2>
                    <p class="text-xs text-amber-800/80">Send a renewal notice or extend the grace period before access lapses.</p>
                </div>
            </div>
            <ul class="mt-4 divide-y divide-amber-200/70">
                @foreach($expiring->take(3) as $sub)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $sub->tenant?->hotel_name ?? $sub->tenant?->tenant_name }}</p>
                            <p class="text-xs text-slate-600">Ends {{ $sub->ends_at?->format('d M Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('super-admin.extend', $sub->tenant_id) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-300 transition-colors duration-150 hover:bg-amber-100">Extend grace period</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Filters --}}
    <section class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto] md:items-end">
            <label class="block">
                <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Location</span>
                <select id="location-category" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    <option value="all" @selected($location_category === 'all')>All locations</option>
                    <option value="domestic" @selected($location_category === 'domestic')>Domestic (Pakistan)</option>
                    <option value="international" @selected($location_category === 'international')>International</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Country</span>
                <select id="country-filter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    <option value="">All countries</option>
                    @foreach($country_options as $country)
                        <option value="{{ $country }}" @selected($country_filter === $country)>{{ $country }}</option>
                    @endforeach
                </select>
            </label>
            <button type="button" id="apply-filters" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-teal-800">Apply</button>
            <a href="{{ route('super-admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">Reset</a>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        {{-- Tenant directory --}}
        <section class="min-w-0 rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Hotel directory</h2>
                    <p class="text-xs text-slate-500">Every registered property, its plan and renewal status.</p>
                </div>
                <a href="{{ route('super-admin.hotel-registry') }}" class="text-xs font-semibold text-teal-700 transition-colors duration-150 hover:text-teal-900">Open registry &rarr;</a>
            </div>
            <div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Hotel / owner</th>
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Renewal</th>
                            <th class="px-5 py-3 text-right">Revenue</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tenants as $row)
                            @php([$pillText, $pillClass] = $statusPill($row))
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="px-5 py-3.5">
                                    <div class="relative inline-block" x-data="hotelStaffPreview({{ (int) $row['tenant']->id }})" @mouseleave="close()">
                                        <a href="{{ route('super-admin.users', ['tenant_id' => $row['tenant']->id]) }}" @mouseenter="open()" @focus="open()" class="font-semibold text-slate-900 transition-colors duration-150 hover:text-teal-800">{{ $row['tenant']->hotel_name ?? $row['tenant']->tenant_name }}</a>
                                        <div x-show="visible" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl">
                                            <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Staff at this hotel</p>
                                            <p x-show="loading" class="text-xs text-slate-500">Loading...</p>
                                            <template x-for="person in staff" :key="person.id">
                                                <div class="flex items-center justify-between gap-2 border-t border-slate-100 py-1.5 text-xs">
                                                    <span class="font-semibold text-slate-900" x-text="person.name"></span>
                                                    <span class="text-slate-500" x-text="person.role_label"></span>
                                                </div>
                                            </template>
                                            <p x-show="!loading && staff.length === 0" class="text-xs text-slate-500">No staff accounts yet.</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $row['tenant']->owner_name ?? $row['tenant']->owner_email ?? '—' }} · {{ $row['tenant']->country ?? 'Pakistan' }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ ucfirst($row['subscription']?->plan_type ?? 'n/a') }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $pillClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $pillText }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    <p>{{ $row['subscription']?->ends_at?->format('d M Y') ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ is_string($row['days_left']) ? $row['days_left'] : $row['days_left'].' days left' }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-right font-semibold tabular-nums text-slate-900">{{ $sym }}{{ number_format($row['revenue_contributed'], 2) }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                                        <button type="button" @click="open = !open" :aria-expanded="open" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-100">Manage</button>
                                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-20 mt-1 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                                            <a href="{{ route('super-admin.hotels.show', $row['tenant']->id) }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">View details</a>
                                            <form method="POST" action="{{ route('super-admin.impersonate', $row['tenant']->id) }}">
                                                @csrf
                                                <button class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">View as hotel</button>
                                            </form>
                                            <form method="POST" action="{{ route('super-admin.extend', $row['tenant']->id) }}">
                                                @csrf
                                                <button class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">Extend subscription</button>
                                            </form>
                                            <a href="{{ route('super-admin.plan-manager') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-100">Change plan tier</a>
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <form method="POST" action="{{ route('super-admin.suspend', $row['tenant']->id) }}">
                                                @csrf
                                                <button class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition-colors duration-150 hover:bg-rose-50">Suspend hotel</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No hotels match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">{{ $tenants->links() }}</div>
        </section>

        {{-- Side column: verification queue and activity --}}
        <aside class="space-y-6">
            <section id="verification" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" x-data="{ selected: null }">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Payment verification</h2>
                    <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">{{ $metrics['pending_payments'] }} pending</span>
                </div>
                @forelse($pendingPayments as $payment)
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $payment->tenant?->hotel_name ?? $payment->tenant?->tenant_name }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->payment_reference }} · {{ $sym }}{{ number_format((float) $payment->amount, 2) }}</p>
                        </div>
                        <button type="button" @click="selected = {{ $payment->id }}" class="shrink-0 rounded-lg bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-teal-800">Review</button>
                    </div>
                    <div x-show="selected === {{ $payment->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="selected = null" @keydown.escape.window="selected = null">
                        <div x-show="selected === {{ $payment->id }}" x-transition.opacity.duration.200ms class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Review payment</h3>
                                    <p class="text-xs text-slate-500">{{ $payment->payment_reference }}</p>
                                </div>
                                <button type="button" @click="selected = null" class="rounded-lg p-1 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">&times;</button>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-xs text-slate-500">Hotel</dt><dd class="font-semibold text-slate-900">{{ $payment->tenant?->hotel_name ?? $payment->tenant?->tenant_name }}</dd></div>
                                <div><dt class="text-xs text-slate-500">Amount</dt><dd class="font-semibold text-slate-900">{{ $sym }}{{ number_format((float) $payment->amount, 2) }}</dd></div>
                                <div><dt class="text-xs text-slate-500">Method</dt><dd class="text-slate-700">{{ ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) }}</dd></div>
                                <div><dt class="text-xs text-slate-500">Transaction ID</dt><dd class="break-all text-slate-700">{{ $payment->transaction_id ?? '—' }}</dd></div>
                            </dl>
                            @if($payment->proof_path)
                                <a href="{{ route('super-admin.payments.proof', $payment) }}" target="_blank" rel="noopener" class="mt-4 inline-flex text-xs font-semibold text-teal-700 hover:text-teal-900">View payment proof &rarr;</a>
                            @endif
                            <div class="mt-5 grid grid-cols-2 gap-2">
                                <form method="POST" action="{{ route('super-admin.payments.mark-paid', $payment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-emerald-700">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('super-admin.payments.reject', $payment) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <input name="review_notes" required maxlength="500" placeholder="Reason" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs text-slate-700 focus:border-rose-400 focus:outline-none">
                                    <button class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-rose-700">Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500">No payments are waiting for review.</p>
                @endforelse
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-bold text-slate-900">Recent activity</h2>
                <ol class="space-y-4">
                    @forelse($recentActivity as $entry)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-600"></span>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-800">{{ $entry->description ?? str_replace('.', ' ', $entry->action) }}</p>
                                <p class="text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No activity recorded yet.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('hotelStaffPreview', (tenantId) => ({
                visible: false, loading: false, loaded: false, staff: [],
                open() {
                    this.visible = true;
                    if (this.loaded) return;
                    this.loading = true;
                    fetch('{{ route('super-admin.users.preview') }}?tenant_id=' + tenantId, { headers: { Accept: 'application/json' } })
                        .then((r) => r.json())
                        .then((d) => { this.staff = d.users; this.loaded = true; })
                        .finally(() => { this.loading = false; });
                },
                close() { this.visible = false; },
            }));
        });
        document.getElementById('apply-filters')?.addEventListener('click', function () {
            const params = new URLSearchParams();
            const location = document.getElementById('location-category').value;
            const country = document.getElementById('country-filter').value;
            if (location && location !== 'all') params.set('location_category', location);
            if (country) params.set('country', country);
            window.location.href = '{{ route('super-admin.dashboard') }}' + (params.toString() ? '?' + params.toString() : '');
        });
    </script>
@endpush
