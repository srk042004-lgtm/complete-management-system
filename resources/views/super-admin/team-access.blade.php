@extends('super-admin.layouts.app')

@section('title', 'Team Management | Artflow')
@section('heading', 'Team Management')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-600"><span class="sr-only" aria-label="Signed in as Super Admin">Super Admin</span>Manage Super Admin console accounts and prepare future team roles.</p>
            <p class="mt-1 text-xs text-slate-500">All listed roles currently receive Super Admin console access.</p>
        </div>
        <button type="button" onclick="openTeamModal('team-create-modal')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-teal-900 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            Add Team Member
        </button>
    </div>

    <section class="super-admin-team-table overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Assigned Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($team as $member)
                        @php
                            $roleColor = match ($member->role) {
                                'super_admin' => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
                                'owner' => 'bg-teal-50 text-teal-800 ring-teal-200',
                                'finance_manager' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                'support_agent' => 'bg-sky-50 text-sky-800 ring-sky-200',
                                'developer' => 'bg-violet-50 text-violet-800 ring-violet-200',
                                'compliance_officer' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                default => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <tr class="transition-colors duration-150 ease-in-out hover:bg-slate-100">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $member->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 super-admin-role-badge {{ $roleColor }}">{{ $roles[$member->role] ?? ucfirst(str_replace('_', ' ', $member->role)) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="super-admin-role-badge__status inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $member->status === 'active' && $member->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                    {{ $member->status === 'active' && $member->is_active ? 'Active' : 'Suspended' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="button"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-all duration-150 hover:border-teal-500 hover:bg-slate-50 hover:text-slate-900 active:scale-95"
                                            data-member-id="{{ $member->id }}"
                                            data-member-name="{{ $member->name }}"
                                            data-member-email="{{ $member->email }}"
                                            data-member-number="{{ $member->number }}"
                                            data-member-role="{{ $member->role }}"
                                            data-member-status="{{ $member->status }}"
                                            onclick="openEditTeamModal(this)">
                                        Edit Role
                                    </button>
                                    @if ($member->id !== auth('super_admin')->id())
                                        <form method="POST" action="{{ route('super-admin.team.update', $member) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $member->name }}">
                                            <input type="hidden" name="email" value="{{ $member->email }}">
                                            <input type="hidden" name="number" value="{{ $member->number }}">
                                            <input type="hidden" name="role" value="{{ $member->role }}">
                                            <input type="hidden" name="status" value="{{ $member->status === 'active' && $member->is_active ? 'suspended' : 'active' }}">
                                            <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150 active:scale-95 {{ $member->status === 'active' && $member->is_active ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                                                {{ $member->status === 'active' && $member->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.team.destroy', $member) }}" onsubmit="return confirm('Remove {{ addslashes($member->name) }} from the Super Admin team?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 transition-all duration-150 hover:bg-rose-100 active:scale-95">Remove</button>
                                        </form>
                                    @else
                                        <span class="px-2 py-1.5 text-xs text-slate-500">Current account</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No team members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($team->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $team->links() }}</div>
        @endif
    </section>

    <div id="team-create-modal" class="super-admin-team-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="team-create-title">
        <div class="super-admin-team-modal-card w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 id="team-create-title" class="text-lg font-semibold text-slate-900">Add Team Member</h2>
                    <p class="mt-1 text-xs text-slate-500">New roles currently receive full Super Admin console access.</p>
                </div>
                <button type="button" onclick="closeTeamModal('team-create-modal')" aria-label="Close" class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900">✕</button>
            </div>
            <form method="POST" action="{{ route('super-admin.team.store') }}" class="grid gap-4 p-5">
                @csrf
                <input type="hidden" name="_team_form" value="create">
                <label class="grid gap-1 text-sm font-medium text-slate-700">
                    Name
                    <input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </label>
                <label class="grid gap-1 text-sm font-medium text-slate-700">
                    Email
                    <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </label>
                <label class="grid gap-1 text-sm font-medium text-slate-700">
                    Phone (optional)
                    <input name="number" value="{{ old('number') }}" maxlength="30" autocomplete="tel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </label>
                <label class="grid gap-1 text-sm font-medium text-slate-700">
                    Role
                    <select name="role" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'super_admin') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-medium text-slate-700">
                        Initial password
                        <input name="password" type="password" required minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">
                        Confirm password
                        <input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </label>
                </div>
                <p class="text-xs text-slate-500">Set a temporary password and share it through a secure channel.</p>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onclick="closeTeamModal('team-create-modal')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-150 hover:bg-slate-100 active:scale-95">Cancel</button>
                    <button type="submit" class="rounded-lg bg-teal-800 px-4 py-2 text-sm font-semibold text-white transition-all duration-150 hover:bg-teal-900 active:scale-95">Create member</button>
                </div>
            </form>
        </div>
    </div>

    <div id="team-edit-modal" class="super-admin-team-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="team-edit-title">
        <div class="super-admin-team-modal-card w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 id="team-edit-title" class="text-lg font-semibold text-slate-900">Edit Team Member</h2>
                    <p class="mt-1 text-xs text-slate-500">Update account details, role, status, or password.</p>
                </div>
                <button type="button" onclick="closeTeamModal('team-edit-modal')" aria-label="Close" class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900">✕</button>
            </div>
            <form id="team-edit-form" method="POST" class="grid gap-4 p-5">
                @csrf
                @method('PUT')
                <input id="edit-member-id" type="hidden" name="_team_member_id" value="">
                <label class="grid gap-1 text-sm font-medium text-slate-700">Name
                    <input id="edit-member-name" name="name" required maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-medium text-slate-700">Email
                    <input id="edit-member-email" name="email" type="email" required maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-medium text-slate-700">Phone (optional)
                    <input id="edit-member-number" name="number" maxlength="30" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Role
                        <select id="edit-member-role" name="role" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Status
                        <select id="edit-member-status" name="status" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </label>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-medium text-slate-700">New password (optional)
                        <input name="password" type="password" minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Confirm new password
                        <input name="password_confirmation" type="password" minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-teal-700 focus:outline-none">
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onclick="closeTeamModal('team-edit-modal')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-150 hover:bg-slate-100 active:scale-95">Cancel</button>
                    <button type="submit" class="rounded-lg bg-teal-800 px-4 py-2 text-sm font-semibold text-white transition-all duration-150 hover:bg-teal-900 active:scale-95">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openTeamModal(id) {
            const modal = document.getElementById(id);
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }

        function closeTeamModal(id) {
            const modal = document.getElementById(id);
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }

        function openEditTeamModal(button) {
            const form = document.getElementById('team-edit-form');
            form.action = '{{ url('/super-admin/team') }}/' + encodeURIComponent(button.dataset.memberId);
            document.getElementById('edit-member-id').value = button.dataset.memberId || '';
            document.getElementById('edit-member-name').value = button.dataset.memberName || '';
            document.getElementById('edit-member-email').value = button.dataset.memberEmail || '';
            document.getElementById('edit-member-number').value = button.dataset.memberNumber || '';
            document.getElementById('edit-member-role').value = button.dataset.memberRole || 'super_admin';
            document.getElementById('edit-member-status').value = button.dataset.memberStatus || 'active';
            openTeamModal('team-edit-modal');
        }

        document.querySelectorAll('[role="dialog"]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeTeamModal(modal.id);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeTeamModal('team-create-modal');
                closeTeamModal('team-edit-modal');
            }
        });

        @if ($errors->any() && old('_team_member_id'))
            const failedEditButton = document.querySelector('[data-member-id="{{ old('_team_member_id') }}"]');
            if (failedEditButton) {
                openEditTeamModal(failedEditButton);
                document.getElementById('edit-member-name').value = @json(old('name', ''));
                document.getElementById('edit-member-email').value = @json(old('email', ''));
                document.getElementById('edit-member-number').value = @json(old('number', ''));
                document.getElementById('edit-member-role').value = @json(old('role', 'super_admin'));
                document.getElementById('edit-member-status').value = @json(old('status', 'active'));
            }
        @elseif ($errors->any())
            openTeamModal('team-create-modal');
        @endif
    </script>
@endpush
