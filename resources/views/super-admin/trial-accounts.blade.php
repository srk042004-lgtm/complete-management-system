@extends('super-admin.layouts.app')

@section('title', 'Demo & Trial Accounts | Artflow')
@section('heading', 'Demo & Trial Accounts')

@section('content')
    <div class="mb-5">
        <p class="text-sm text-slate-600">Separate registry for demo requests and self-service trial accounts. Live accounts link to their tenant, user, and subscription records.</p>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['All records', $counts['all'], 'text-slate-900'],
            ['Awaiting registration', $counts['pending'], 'text-amber-700'],
            ['Active trials', $counts['active'], 'text-emerald-700'],
            ['Expired trials', $counts['expired'], 'text-rose-700'],
            ['Converted accounts', $counts['converted'], 'text-indigo-700'],
        ] as [$label, $count, $color])
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-bold {{ $color }}">{{ $count }}</div>
            </article>
        @endforeach
    </section>

    <form method="GET" action="{{ route('super-admin.trial-accounts') }}" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[minmax(0,1fr)_190px_210px_auto_auto] md:items-end">
        <div>
            <label for="trial-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input id="trial-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Business, contact, email, phone" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="trial-system" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">System</label>
            <select id="trial-system" name="system" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="">All systems</option>
                @foreach(['hotel', 'clinic', 'school', 'restaurant', 'retail'] as $system)
                    <option value="{{ $system }}" @selected(($filters['system'] ?? '') === $system)>{{ ucfirst($system) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="trial-status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="trial-status" name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach(['pending_registration' => 'Awaiting registration', 'active' => 'Active trial', 'expired' => 'Expired', 'converted' => 'Converted', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Apply filters</button>
        <a href="{{ route('super-admin.trial-accounts') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700">Reset</a>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Business / Contact</th>
                        <th class="px-4 py-3">System</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3">Trial dates</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($accounts as $account)
                        @php
                            $statusClasses = match ($account->status) {
                                'active' => 'bg-emerald-50 text-emerald-700',
                                'expired', 'cancelled' => 'bg-rose-50 text-rose-700',
                                'converted' => 'bg-indigo-50 text-indigo-700',
                                default => 'bg-amber-50 text-amber-700',
                            };
                        @endphp
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $account->business_name }}</div>
                                <div class="mt-1 text-xs text-slate-600">{{ $account->contact_name ?: 'Contact not set' }}</div>
                                <div class="text-xs text-slate-500">{{ $account->email ?: 'Email not set' }} · {{ $account->phone ?: 'Phone not set' }}</div>
                                @if($account->demo_request_id)<div class="mt-1 text-[11px] text-slate-400">Demo request #{{ $account->demo_request_id }}</div>@endif
                            </td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('-', ' ', $account->system) }}</td>
                            <td class="px-4 py-3">{{ $account->source === 'demo_request' ? 'Demo request' : 'Direct registration' }}</td>
                            <td class="px-4 py-3">
                                @if($account->tenant)
                                    <div class="font-medium">Tenant #{{ $account->tenant->id }}</div>
                                    <div class="text-xs text-slate-500">{{ $account->user?->email ?? $account->tenant->owner_email }}</div>
                                @else
                                    <span class="text-slate-400">Registration not completed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                <div>Start: {{ $account->trial_starts_at?->format('d M Y H:i') ?? '—' }}</div>
                                <div>End: {{ $account->trial_ends_at?->format('d M Y H:i') ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $account->status === 'pending_registration' ? 'Awaiting registration' : ucfirst($account->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No demo or trial account records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $accounts->links() }}</div>@endif
    </section>
@endsection
