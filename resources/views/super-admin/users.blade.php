@extends('super-admin.layouts.app')

@section('title', 'User Directory | Artflow')
@section('heading', 'User Directory')

@php
    $roleBadges = [
        'owner' => 'bg-purple-100 text-purple-800 border-purple-200',
        'hotel_admin' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'admin' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'manager' => 'bg-blue-100 text-blue-800 border-blue-200',
        'receptionist' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'cashier' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'housekeeping' => 'bg-amber-100 text-amber-800 border-amber-200',
        'housekeeper' => 'bg-amber-100 text-amber-800 border-amber-200',
    ];
    $statusBadges = [
        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'suspended' => 'bg-rose-50 text-rose-700 border-rose-200',
        'inactive' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $lockedTenant = $tenants->firstWhere('id', (int) ($filters['tenant_id'] ?? 0));
@endphp

@section('content')
    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, username"
               class="rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900">
        <select name="tenant_id" class="rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900">
            <option value="">All hotels</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(($filters['tenant_id'] ?? '') == $tenant->id)>{{ $tenant->hotel_name ?? $tenant->tenant_name }}</option>
            @endforeach
        </select>
        <select name="role" class="rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900">
            <option value="">All roles</option>
            @foreach(['owner' => 'Owner', 'admin' => 'Admin', 'manager' => 'Manager', 'receptionist' => 'Receptionist', 'housekeeping' => 'Housekeeping'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900">
            <option value="">All statuses</option>
            @foreach(['active', 'inactive', 'suspended'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-teal-800 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-teal-900 md:col-span-4">Filter users</button>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
            @if($lockedTenant)
                <span class="font-semibold text-teal-800 dark:text-teal-300">Locked to {{ $lockedTenant->hotel_name ?? $lockedTenant->tenant_name }}</span>
                <a href="{{ route('super-admin.users', array_merge($filters, ['tenant_id' => null])) }}"
                   class="rounded-lg border border-slate-300 px-2.5 py-1 font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">Unlock hotel filter</a>
            @else
                <span>Hover a hotel to preview its staff. Click a hotel to lock the whole directory to it.</span>
            @endif
        </div>

        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3 font-bold">User Info</th>
                    <th class="px-4 py-3 font-bold">Hotel Property</th>
                    <th class="px-4 py-3 font-bold">Assigned Role</th>
                    <th class="px-4 py-3 font-bold">Last Login</th>
                    <th class="px-4 py-3 font-bold">Status</th>
                    <th class="px-4 py-3 text-right font-bold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($users as $user)
                    @php($roleKey = strtolower((string) $user->role))
                    @php($roleClass = $roleBadges[$roleKey] ?? 'bg-slate-100 text-slate-700 border-slate-200')
                    @php($statusClass = $statusBadges[$user->status] ?? $statusBadges['inactive'])
                    @php($isLocked = (string) ($filters['tenant_id'] ?? '') === (string) $user->tenant_id)
                    <tr class="text-slate-900 transition-colors duration-150 hover:bg-slate-50 dark:text-slate-100 dark:hover:bg-slate-800/80">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}@if($user->username) · {{ $user->username }}@endif</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="relative inline-block" x-data="hotelPreview({{ (int) $user->tenant_id }})" @mouseleave="close()">
                                <a href="{{ route('super-admin.users', array_merge($filters, ['tenant_id' => $isLocked ? null : $user->tenant_id])) }}"
                                   @mouseenter="open()" @focus="open()"
                                   class="font-medium transition-colors duration-150 {{ $isLocked ? 'font-bold text-teal-800 underline dark:text-teal-300' : 'text-slate-900 hover:text-teal-800 dark:text-slate-100 dark:hover:text-teal-300' }}">
                                    {{ $user->tenant?->hotel_name ?? $user->tenant?->tenant_name ?? 'No hotel' }}
                                </a>
                                <div x-show="visible" x-cloak class="absolute left-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl dark:border-slate-700 dark:bg-slate-800">
                                    <div class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Staff preview</div>
                                    <div x-show="loading" class="text-xs text-slate-500">Loading...</div>
                                    <template x-for="person in staff" :key="person.id">
                                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 py-1.5 text-xs dark:border-slate-700">
                                            <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="person.name"></span>
                                            <span class="text-slate-500 dark:text-slate-400" x-text="person.role_label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $roleClass }}">{{ str_replace('_', ' ', ucfirst($roleKey)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" aria-label="User actions"
                                        class="rounded-lg px-2 py-1 text-lg leading-none text-slate-600 transition-colors duration-150 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">&#8943;</button>
                                <div x-show="open" x-cloak class="absolute right-0 z-10 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-1 text-left shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                    <form method="POST" action="{{ route('super-admin.users.status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-900 transition-colors duration-150 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-700">
                                            {{ $user->status === 'suspended' ? 'Activate account' : 'Suspend account' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.users.password-reset', $user) }}">
                                        @csrf
                                        <button class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-900 transition-colors duration-150 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-700">Send password reset</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No users match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('hotelPreview', (tenantId) => ({
                visible: false,
                loading: false,
                loaded: false,
                staff: [],
                open() {
                    this.visible = true;
                    if (this.loaded) return;
                    this.loading = true;
                    fetch(`{{ route('super-admin.users.preview') }}?tenant_id=${tenantId}`, { headers: { Accept: 'application/json' } })
                        .then((response) => response.json())
                        .then((data) => { this.staff = data.users; this.loaded = true; })
                        .finally(() => { this.loading = false; });
                },
                close() { this.visible = false; },
            }));
        });
    </script>
@endsection
