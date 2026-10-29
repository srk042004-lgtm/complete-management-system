@extends('super-admin.layouts.app')

@section('title', 'Subscription Plans | Artflow')
@section('heading', 'Subscription plans')

@section('content')
    @if (session('success'))
        <div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        {{-- Plan editor --}}
        <section class="h-fit rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-teal-700">Plan editor</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">Create a plan</h2>
                <p class="mt-1 text-sm text-slate-500">Plans set the price, billing cycle, trial length, and the modules a hotel can use.</p>
            </div>

            <form method="POST" action="{{ route('super-admin.plans.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="plan-name" class="mb-1.5 block text-xs font-semibold text-slate-700">Plan name</label>
                    <input id="plan-name" name="name" required placeholder="e.g. Hotel PMS Monthly" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="plan-description" class="mb-1.5 block text-xs font-semibold text-slate-700">Description</label>
                    <textarea id="plan-description" name="description" rows="2" placeholder="What this plan includes" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="plan-cycle" class="mb-1.5 block text-xs font-semibold text-slate-700">Billing cycle</label>
                        <select id="plan-cycle" name="billing_cycle" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>
                    <div>
                        <label for="plan-price" class="mb-1.5 block text-xs font-semibold text-slate-700">Price</label>
                        <input id="plan-price" name="price" type="number" min="0" step="0.01" required placeholder="0.00" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label for="plan-currency" class="mb-1.5 block text-xs font-semibold text-slate-700">Currency</label>
                        <input id="plan-currency" name="currency_code" value="PKR" maxlength="3" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm uppercase text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label for="plan-trial" class="mb-1.5 block text-xs font-semibold text-slate-700">Trial days</label>
                        <input id="plan-trial" name="trial_days" type="number" min="0" value="14" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold text-slate-700">Limits <span class="font-normal text-slate-400">(leave empty for unlimited)</span></p>
                    <div class="grid grid-cols-2 gap-3">
                        <input name="max_rooms" type="number" min="1" placeholder="Max rooms" aria-label="Max rooms" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <input name="max_users" type="number" min="1" placeholder="Max users" aria-label="Max users" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <input name="max_staff" type="number" min="1" placeholder="Max staff" aria-label="Max staff" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <input name="max_reservations" type="number" min="1" placeholder="Max reservations" aria-label="Max reservations" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold text-slate-700">Included modules</p>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                            <span>Rooms and reservations</span>
                            <input type="checkbox" name="has_rooms" value="1" checked class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                        </label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                            <span>Restaurant POS</span>
                            <input type="checkbox" name="has_restaurant_pos" value="1" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                        </label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                            <span>Available to hotels</span>
                            <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                        </label>
                    </div>
                </div>

                <button class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-teal-800">Create plan</button>
            </form>
        </section>

        {{-- Plan catalogue --}}
        <section>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Plan catalogue</h2>
                    <p class="text-sm text-slate-500">Changes apply to new subscriptions and sync to hotels on their next renewal.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @forelse($plans as $plan)
                    <article class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-slate-900">{{ $plan->name }}</h3>
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $plan->description ?: 'No description provided.' }}</p>
                            </div>
                            @if($plan->is_active)
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @else
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Inactive</span>
                            @endif
                        </div>

                        <div class="mt-5">
                            <p class="text-2xl font-semibold tracking-tight text-slate-900">{{ $plan->currency_code }} {{ number_format((float) $plan->price, 2) }}</p>
                            <p class="text-xs text-slate-500">per {{ $plan->billing_cycle === 'lifetime' ? 'one-time payment' : $plan->billing_cycle }}</p>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-100 pt-4 text-sm">
                            <div><dt class="text-xs text-slate-500">Trial</dt><dd class="font-medium text-slate-900">{{ $plan->trial_days }} days</dd></div>
                            <div><dt class="text-xs text-slate-500">Rooms</dt><dd class="font-medium text-slate-900">{{ $plan->max_rooms ? 'Up to '.$plan->max_rooms : 'Unlimited' }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Users</dt><dd class="font-medium text-slate-900">{{ $plan->max_users ? 'Up to '.$plan->max_users : 'Unlimited' }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Restaurant POS</dt><dd class="font-medium text-slate-900">{{ $plan->has_restaurant_pos ? 'Included' : 'Not included' }}</dd></div>
                        </dl>

                        <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                            <form method="POST" action="{{ route('super-admin.plans.toggle-status', $plan) }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">No subscription plans yet. Create your first plan from the editor.</div>
                @endforelse
            </div>

            <div class="mt-4">{{ $plans->links() }}</div>
        </section>
    </div>
@endsection
