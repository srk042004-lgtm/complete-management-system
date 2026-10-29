@extends('super-admin.layouts.app')

@section('title', 'Artflow Management System — Hotel Registry')
@section('heading', 'Artflow Management System — Hotel Registry')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-[#4f3da0]">Registered hotels directory with country-aware pricing, subscription health, and tenant actions.</p>
        </div>
        <button type="button" id="showAddHotelModal" class="rounded-xl bg-teal-700 hover:bg-teal-800 px-4 py-2 text-sm font-semibold text-white shadow-sm">Add New Hotel</button>
    </div>

    <div id="addHotelModal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 px-4 opacity-0 transition-all duration-200 ease-out">
        <div id="addHotelModalPanel" class="max-h-[90vh] w-full max-w-4xl translate-y-4 scale-[0.98] overflow-y-auto rounded-3xl bg-white shadow-2xl transition-all duration-200 ease-out">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.3em] text-slate-500">Artflow Management System</div>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Add New Hotel Manually</h2>
                </div>
                <button type="button" id="closeAddHotelModal" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Close</button>
            </div>

            <form method="POST" action="{{ route('super-admin.hotel-registry.store') }}" class="grid gap-4 p-6 md:grid-cols-2">
                @csrf

                <div>
                    <label for="hotel_name" class="mb-1 block text-sm font-medium text-slate-700">Hotel / Property Name</label>
                    <input id="hotel_name" name="hotel_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="property_type" class="mb-1 block text-sm font-medium text-slate-700">Property Type</label>
                    <select id="property_type" name="property_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="Hotel">Hotel</option>
                        <option value="Motel">Motel</option>
                        <option value="Resort">Resort</option>
                        <option value="Apartment">Apartment</option>
                        <option value="Guest House">Guest House</option>
                    </select>
                </div>

                <div>
                    <label for="owner_name" class="mb-1 block text-sm font-medium text-slate-700">Owner Name</label>
                    <input id="owner_name" name="owner_name" type="text" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Owner Email</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" name="phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                    <select id="country" name="country" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @foreach($countryOptions as $countryOption)
                            <option value="{{ $countryOption }}">{{ $countryOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="city" class="mb-1 block text-sm font-medium text-slate-700">City</label>
                    <input id="city" name="city" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Street Address</label>
                    <textarea id="address" name="address" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                </div>

                <div>
                    <label for="website" class="mb-1 block text-sm font-medium text-slate-700">Website</label>
                    <input id="website" name="website" type="url" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Registry Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-center justify-between gap-3 text-sm font-medium text-slate-700">
                        <span>Include Restaurant Module</span>
                        <input id="include_restaurant_module" name="include_restaurant_module" value="1" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                    </label>
                </div>

                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">Save Hotel</button>
                    <button type="button" id="cancelAddHotelModal" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-6 rounded-3xl border border-[#d5d9ff] bg-white/90 p-4 shadow-sm">
        <form method="GET" action="{{ route('super-admin.hotel-registry') }}" class="grid gap-3 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Region Selector</label>
                <select name="region" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                    <option value="all" {{ $region === 'all' ? 'selected' : '' }}>All Regions</option>
                    <option value="domestic" {{ $region === 'domestic' ? 'selected' : '' }}>Domestic (Pakistan)</option>
                    <option value="international" {{ $region === 'international' ? 'selected' : '' }}>International (Outside Pakistan)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Country Selector</label>
                <select name="country" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                    <option value="">All Countries</option>
                    @foreach($countryOptions as $countryOption)
                        <option value="{{ $countryOption }}" {{ $country === $countryOption ? 'selected' : '' }}>{{ $countryOption }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Subscription Status</label>
                <select name="subscription_status" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                    <option value="all" {{ $subscriptionStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ $subscriptionStatus === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ $subscriptionStatus === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="pending" {{ $subscriptionStatus === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Search Input</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search hotel name, owner, or email" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
            </div>

            <div class="lg:col-span-5 flex gap-2">
                <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Apply Filters</button>
                <a href="{{ route('super-admin.hotel-registry') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Tenant ID & Registered Date</th>
                    <th class="px-4 py-3 text-left font-medium">Hotel Details</th>
                    <th class="px-4 py-3 text-left font-medium">Country & Region Flag</th>
                    <th class="px-4 py-3 text-left font-medium">Property Type</th>
                    <th class="px-4 py-3 text-left font-medium">Subscription Status</th>
                    <th class="px-4 py-3 text-left font-medium">Tier & Time Left</th>
                    <th class="px-4 py-3 text-left font-medium">Revenue Contributed</th>
                    <th class="px-4 py-3 text-left font-medium">Quick Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($tenants as $tenant)
                    @php
                        $currency = $currencyMap[$tenant->country] ?? ['symbol' => '$', 'code' => 'USD', 'format' => '$1,234.56'];
                        $subscription = $tenant->subscriptions->first();
                        $revenue = (float) $tenant->payments->where('payment_status', 'paid')->sum('amount');
                        $daysLeft = $subscription?->ends_at ? max(0, (int) floor(now()->diffInDays($subscription->ends_at, false))) : 0;
                        $progress = $subscription?->plan_type === 'lifetime' ? 100 : min(100, max(8, (int) ($daysLeft / 30 * 100)));
                        $statusClass = match ($tenant->status) {
                            'active' => 'bg-emerald-50 text-emerald-700',
                            'suspended' => 'bg-rose-50 text-rose-700',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-slate-900">#{{ $tenant->id }}</div>
                            <div class="text-xs text-slate-500">{{ $tenant->created_at?->format('d M Y') ?? 'n/a' }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-slate-900">{{ $tenant->hotel_name }}</div>
                            <div class="text-slate-600">{{ $tenant->owner_name }}</div>
                            <div class="text-slate-500">{{ $tenant->email }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-slate-900">{{ $tenant->country ?? 'Pakistan' }}</div>
                            <div class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                {{ $tenant->country === 'Pakistan' ? 'Domestic' : 'International' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $tenant->property_type ?? 'Hotel' }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ ucfirst($tenant->status) }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-slate-900">{{ ucfirst($subscription?->plan_type ?? 'n/a') }}</div>
                            <div class="mt-2 h-2 w-24 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%;"></div>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">{{ $subscription?->plan_type === 'lifetime' ? 'Lifetime' : $daysLeft . ' days left' }}</div>
                        </td>
                        <td class="px-4 py-3 align-top font-semibold text-slate-900">
                            {{ $currency['symbol'] }}{{ number_format($revenue, 2) }}
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('super-admin.hotels.show', $tenant->id) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">View</a>
                                <form class="inline-flex" action="{{ route('super-admin.hotel-registry.impersonate', $tenant->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800 transition-colors duration-150 hover:bg-teal-100">Impersonate</button>
                                </form>
                                <form class="inline-flex" action="{{ route('super-admin.hotel-registry.toggle-status', $tenant->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="{{ $tenant->status === 'active' ? 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors duration-150">
                                        {{ $tenant->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                    </button>
                                </form>
                                @if($tenant->status === 'pending')
                                    <form class="inline-flex" action="{{ route('super-admin.hotel-registry.approve', $tenant->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Approve</button>
                                    </form>
                                    <form class="inline-flex" action="{{ route('super-admin.hotel-registry.reject', $tenant->id) }}" method="POST" onsubmit="return confirm('Reject this hotel registration?')">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="Rejected by super admin">
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">Reject</button>
                                    </form>
                                @endif
                                <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50" onclick="openSubscriptionModal('{{ $tenant->id }}', '{{ addslashes($tenant->hotel_name ?? $tenant->tenant_name) }}', '{{ ucfirst($subscription?->plan_type ?? 'monthly') }}', '{{ $subscription?->ends_at?->format('Y-m-d') ?? '' }}', '{{ $currency['symbol'] }}')">Manage Subscription</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No registered hotels match the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="subscriptionModal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4 opacity-0 transition-all duration-200 ease-out">
        <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.28em] text-slate-500">Subscription Update</div>
                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Manage Subscription</h3>
                </div>
                <button type="button" onclick="closeSubscriptionModal()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>

            <form id="subscriptionForm" method="POST" class="space-y-4 p-6">
                @csrf
                <input type="hidden" id="subscriptionTenantId" name="tenant_id">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tenant ID</label>
                        <div id="subscriptionTenantIdLabel" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">—</div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Hotel Name</label>
                        <div id="subscriptionHotelNameLabel" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">—</div>
                    </div>

                    <div>
                        <label for="plan_tier" class="mb-1 block text-sm font-medium text-slate-700">Plan Tier</label>
                        <select id="plan_tier" name="plan_tier" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            <option value="Monthly">Monthly</option>
                            <option value="Yearly">Yearly</option>
                            <option value="Lifetime">Lifetime</option>
                        </select>
                    </div>

                    <div>
                        <label for="ends_at" class="mb-1 block text-sm font-medium text-slate-700">Expiration Date</label>
                        <input id="ends_at" name="ends_at" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-center justify-between gap-3 text-sm font-medium text-slate-700">
                        <span>Include Restaurant Module</span>
                        <input id="include_restaurant_module" name="include_restaurant_module" value="1" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input id="record_payment" type="checkbox" name="record_payment" value="1" class="h-4 w-4 rounded border-slate-300">
                        Record payment transaction for this update
                    </label>

                    <div id="paymentFields" class="mt-3 hidden grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="amount_received" class="mb-1 block text-sm font-medium text-slate-700">Amount Received</label>
                            <div class="relative">
                                <span id="currencySymbol" class="pointer-events-none absolute left-3 top-2 text-sm font-semibold text-slate-600">Rs.</span>
                                <input id="amount_received" name="amount_received" type="number" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-3 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label for="reference_number" class="mb-1 block text-sm font-medium text-slate-700">Reference / Receipt Number</label>
                            <input id="reference_number" name="reference_number" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeSubscriptionModal()" class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                        <span id="saveSubscriptionSpinner" class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4">{{ $tenants->links() }}</div>

    <script>
        const addHotelModal = document.getElementById('addHotelModal');
        const addHotelModalPanel = document.getElementById('addHotelModalPanel');
        const showAddHotelModalButton = document.getElementById('showAddHotelModal');
        const closeAddHotelModalButton = document.getElementById('closeAddHotelModal');
        const cancelAddHotelModalButton = document.getElementById('cancelAddHotelModal');
        const subscriptionModal = document.getElementById('subscriptionModal');
        const subscriptionForm = document.getElementById('subscriptionForm');
        const recordPaymentToggle = document.getElementById('record_payment');
        const paymentFields = document.getElementById('paymentFields');
        const currencySymbol = document.getElementById('currencySymbol');
        const planTier = document.getElementById('plan_tier');
        const endsAtField = document.getElementById('ends_at');
        const restaurantModuleToggle = document.getElementById('include_restaurant_module');
        const saveSubscriptionSpinner = document.getElementById('saveSubscriptionSpinner');

        const toggleModal = (shouldShow) => {
            if (!addHotelModal || !addHotelModalPanel) {
                return;
            }

            addHotelModal.classList.toggle('hidden', !shouldShow);
            addHotelModal.classList.toggle('flex', shouldShow);
            addHotelModal.classList.toggle('opacity-0', !shouldShow);
            addHotelModal.classList.toggle('opacity-100', shouldShow);
            addHotelModal.classList.toggle('pointer-events-none', !shouldShow);
            addHotelModal.classList.toggle('pointer-events-auto', shouldShow);
            addHotelModalPanel.classList.toggle('translate-y-4', !shouldShow);
            addHotelModalPanel.classList.toggle('scale-[0.98]', !shouldShow);
            addHotelModalPanel.classList.toggle('translate-y-0', shouldShow);
            addHotelModalPanel.classList.toggle('scale-100', shouldShow);
            addHotelModal.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
        };

        const openSubscriptionModal = (tenantId, hotelName, currentPlan, currentExpiryDate, countryCurrency) => {
            if (!subscriptionModal || !subscriptionForm) {
                return;
            }

            document.getElementById('subscriptionTenantId').value = tenantId;
            document.getElementById('subscriptionTenantIdLabel').textContent = tenantId;
            document.getElementById('subscriptionHotelNameLabel').textContent = hotelName;

            const planTierValue = currentPlan && currentPlan.toLowerCase();
            if (planTierValue === 'lifetime') {
                planTier.value = 'Lifetime';
                endsAtField.disabled = true;
                endsAtField.value = '';
            } else {
                planTier.value = currentPlan || 'Monthly';
                endsAtField.disabled = false;
                endsAtField.value = currentExpiryDate || '';
            }

            currencySymbol.textContent = countryCurrency || 'Rs.';
            restaurantModuleToggle.checked = false;
            subscriptionForm.action = `/superadmin/tenants/${tenantId}/subscription/update`;
            subscriptionModal.classList.remove('hidden');
            subscriptionModal.classList.add('flex', 'opacity-100', 'pointer-events-auto');
            subscriptionModal.classList.remove('opacity-0', 'pointer-events-none');
        };

        const closeSubscriptionModal = () => {
            if (!subscriptionModal) {
                return;
            }

            subscriptionModal.classList.add('hidden');
            subscriptionModal.classList.remove('flex', 'opacity-100', 'pointer-events-auto');
            subscriptionModal.classList.add('opacity-0', 'pointer-events-none');
            paymentFields.classList.add('hidden');
            recordPaymentToggle.checked = false;
            restaurantModuleToggle.checked = false;
            saveSubscriptionSpinner.classList.add('hidden');
            subscriptionForm?.reset();
        };

        recordPaymentToggle?.addEventListener('change', () => {
            paymentFields.classList.toggle('hidden', !recordPaymentToggle.checked);
        });

        planTier?.addEventListener('change', () => {
            const selectedPlan = planTier.value.toLowerCase();
            if (selectedPlan === 'lifetime') {
                endsAtField.disabled = true;
                endsAtField.value = '';
                return;
            }

            endsAtField.disabled = false;
            const today = new Date();
            const offsetDays = selectedPlan === 'yearly' ? 365 : 30;
            today.setDate(today.getDate() + offsetDays);
            endsAtField.value = today.toISOString().split('T')[0];
        });

        subscriptionForm?.addEventListener('submit', () => {
            saveSubscriptionSpinner.classList.remove('hidden');
        });

        showAddHotelModalButton?.addEventListener('click', () => toggleModal(true));
        closeAddHotelModalButton?.addEventListener('click', () => toggleModal(false));
        cancelAddHotelModalButton?.addEventListener('click', () => toggleModal(false));
        addHotelModal?.addEventListener('click', (event) => {
            if (event.target === addHotelModal) {
                toggleModal(false);
            }
        });
    </script>

    <footer class="fixed bottom-0 left-0 right-0 border-t border-slate-200 bg-white/90 px-4 py-3 text-center text-sm font-medium text-slate-600 backdrop-blur">
        Created at Artflow Studio, Designed by Shahrukh Khan
    </footer>
@endsection
