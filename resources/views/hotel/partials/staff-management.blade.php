{{-- Shared staff panel for the owner and admin dashboards: invite, change role, revoke or restore access, remove, cancel invitations. --}}
<section class="staff-panel" id="staff-management" aria-labelledby="staff-management-title">
    <style>
        .staff-panel { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .staff-panel__title { margin: 0; font-size: 17px; font-weight: 700; }
        .staff-panel__intro { margin: 4px 0 0; font-size: 13px; color: #64748b; }
        .staff-panel__sub { margin: 22px 0 10px; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
        .staff-invite { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; gap: 12px; align-items: end; }
        .staff-invite label { display: grid; gap: 6px; font-size: 12px; font-weight: 600; color: #475569; }
        .staff-input, .staff-select { width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; font: inherit; font-size: 14px; }
        .staff-input:focus, .staff-select:focus { outline: 2px solid #5eead4; outline-offset: 1px; border-color: #0f766e; }
        .staff-row { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) auto; gap: 14px; align-items: center; padding: 14px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; }
        .staff-row + .staff-row { margin-top: 10px; }
        .staff-row__who { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .staff-row__who strong, .staff-row__who span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .staff-row__who strong { font-size: 14px; font-weight: 700; }
        .staff-row__who span { font-size: 12px; color: #64748b; }
        .staff-avatar { display: grid; place-items: center; flex: 0 0 38px; width: 38px; height: 38px; border-radius: 50%; background: #ccfbf1; color: #115e59; font-weight: 800; font-size: 14px; }
        .staff-avatar--invite { background: #e0e7ff; color: #3730a3; }
        .staff-row__meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .staff-badge, .staff-status { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .staff-badge { background: #f1f5f9; color: #334155; }
        .staff-status.is-active { background: #dcfce7; color: #166534; }
        .staff-status.is-suspended { background: #fee2e2; color: #991b1b; }
        .staff-status.is-pending { background: #fef9c3; color: #854d0e; }
        .staff-row__controls { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; }
        .staff-inline { display: flex; gap: 6px; align-items: center; margin: 0; }
        .staff-inline .staff-select { width: auto; min-width: 150px; min-height: 36px; padding: 6px 10px; font-size: 13px; }
        .staff-btn { min-height: 36px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background-color .15s ease, border-color .15s ease; }
        .staff-btn:hover { background: #f8fafc; }
        .staff-btn--primary { background: #0f766e; border-color: #0f766e; color: #fff; }
        .staff-btn--primary:hover { background: #115e59; }
        .staff-btn--warn { border-color: #fcd34d; background: #fffbeb; color: #92400e; }
        .staff-btn--warn:hover { background: #fef3c7; }
        .staff-btn--ok { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .staff-btn--ok:hover { background: #dcfce7; }
        .staff-btn--danger { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .staff-btn--danger:hover { background: #ffe4e6; }
        .staff-btn:focus-visible { outline: 3px solid #5eead4; outline-offset: 2px; }
        .staff-invite-link { grid-column: 1 / -1; display: flex; gap: 8px; align-items: center; }
        .staff-invite-link input { min-width: 0; flex: 1; }
        .staff-empty { padding: 18px; border: 1px dashed #cbd5e1; border-radius: 14px; font-size: 13px; color: #64748b; text-align: center; }
        .staff-footer { display: flex; justify-content: flex-end; margin-top: 18px; }
        @media (max-width: 860px) {
            .staff-invite { grid-template-columns: 1fr; }
            .staff-row { grid-template-columns: 1fr; }
            .staff-row__controls { justify-content: flex-start; }
            .staff-inline { flex-wrap: wrap; }
        }
        html[data-theme="dark"] .staff-panel, html[data-theme="dark"] .staff-row { background: #172331; border-color: #334155; color: #f1f5f9; }
        html[data-theme="dark"] .staff-panel__intro, html[data-theme="dark"] .staff-panel__sub, html[data-theme="dark"] .staff-row__who span, html[data-theme="dark"] .staff-empty { color: #94a3b8; }
        html[data-theme="dark"] .staff-input, html[data-theme="dark"] .staff-select, html[data-theme="dark"] .staff-btn { background: #1e293b; border-color: #475569; color: #e2e8f0; }
        html[data-theme="dark"] .staff-badge { background: #263746; color: #cbd5e1; }
        html[data-theme="dark"] .staff-btn--danger { background: rgba(190, 18, 60, .18); border-color: rgba(251, 113, 133, .4); color: #fecdd3; }
        html[data-theme="dark"] .staff-btn--warn { background: rgba(245, 158, 11, .14); border-color: rgba(251, 191, 36, .4); color: #fde68a; }
        html[data-theme="dark"] .staff-btn--ok { background: rgba(34, 197, 94, .14); border-color: rgba(74, 222, 128, .4); color: #bbf7d0; }
        html[data-theme="dark"] .staff-status.is-active { background: rgba(34, 197, 94, .18); color: #bbf7d0; }
        html[data-theme="dark"] .staff-status.is-suspended { background: rgba(244, 63, 94, .18); color: #fecdd3; }
        html[data-theme="dark"] .staff-status.is-pending { background: rgba(234, 179, 8, .16); color: #fde68a; }
    </style>

    <h2 id="staff-management-title" class="staff-panel__title">{{ $title }}</h2>
    <p class="staff-panel__intro">{{ $intro }}</p>

    @if(session('success'))<div class="notice-ok" role="status" style="margin-top:12px">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="notice-err" role="alert" style="margin-top:12px">{{ session('error') }}</div>@endif

    @if($availableRoles)
        <form class="staff-invite" method="POST" action="{{ route('hotel.team.invite.store') }}" style="margin-top:18px">
            @csrf
            <label>Email address<input class="staff-input" type="email" name="email" value="{{ old('email') }}" placeholder="name@hotel.com"></label>
            <label>Role<select class="staff-select" name="role" required>@foreach($availableRoles as $staffRole => $definition)<option value="{{ $staffRole }}">{{ $definition['label'] }}</option>@endforeach</select></label>
            <button type="submit" class="staff-btn staff-btn--primary">Invite staff</button>
        </form>
    @endif

    <h3 class="staff-panel__sub">Pending invitations</h3>
    @forelse($pendingInvitations as $invitation)
        @php($hasFreshLink = (string) session('invitation_id') === (string) $invitation->id && session('invitation_url'))
        <div class="staff-row">
            <div class="staff-row__who">
                <span class="staff-avatar staff-avatar--invite" aria-hidden="true">@</span>
                <div>
                    <strong>{{ $invitation->email ?? 'Manual Link' }}</strong>
                    <span>{{ \App\Support\HotelRoleCatalog::label($invitation->role) }} · expires {{ $invitation->expires_at->diffForHumans() }}</span>
                </div>
            </div>
            <div class="staff-row__meta"><span class="staff-status is-pending">Awaiting sign-up</span></div>
            <div class="staff-row__controls">
                @if($hasFreshLink)
                    <button type="button" class="staff-btn" data-copy-invite>Copy Link</button>
                @endif
                <form method="POST" action="{{ route('hotel.team.invitations.cancel', $invitation) }}" class="staff-inline" data-confirm="Cancel this invitation? Its link will stop working." onsubmit="return confirm(this.dataset.confirm)">
                    @csrf @method('DELETE')
                    <button type="submit" class="staff-btn staff-btn--danger">Cancel invitation</button>
                </form>
            </div>
            @if($hasFreshLink)
                <div class="staff-invite-link">
                    <input class="staff-input" type="text" value="{{ session('invitation_url') }}" aria-label="Staff invitation link" readonly data-invite-link>
                    <span role="status" aria-live="polite" data-copy-status></span>
                </div>
            @endif
        </div>
    @empty
        <div class="staff-empty">No pending invitations.</div>
    @endforelse

    <h3 class="staff-panel__sub">Team members</h3>
    @forelse($managedStaff as $member)
        @php($memberRole = \App\Support\HotelRoleCatalog::normalize((string) $member->role))
        @php($isActive = $member->status === 'active')
        <div class="staff-row">
            <div class="staff-row__who">
                <span class="staff-avatar" aria-hidden="true">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                <div>
                    <strong>{{ $member->name }}</strong>
                    <span>{{ $member->email }}</span>
                </div>
            </div>
            <div class="staff-row__meta">
                <span class="staff-badge">{{ \App\Support\HotelRoleCatalog::label($memberRole) }}</span>
                <span class="staff-status {{ $isActive ? 'is-active' : 'is-suspended' }}">{{ $isActive ? 'Active' : 'Access revoked' }}</span>
            </div>
            <div class="staff-row__controls">
                <form method="POST" action="{{ route('hotel.team.update', $member) }}" class="staff-inline">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $member->status }}">
                    <select class="staff-select" name="role" aria-label="Role for {{ $member->name }}">
                        @foreach($memberRoleOptions as $value => $label)
                            <option value="{{ $value }}" @selected($memberRole === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="staff-btn">Save role</button>
                </form>

                <form method="POST" action="{{ route('hotel.team.update', $member) }}" class="staff-inline">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="{{ $memberRole }}">
                    <input type="hidden" name="status" value="{{ $isActive ? 'suspended' : 'active' }}">
                    <button type="submit" class="staff-btn {{ $isActive ? 'staff-btn--warn' : 'staff-btn--ok' }}">{{ $isActive ? 'Revoke access' : 'Restore access' }}</button>
                </form>

                <form method="POST" action="{{ route('hotel.team.destroy', $member) }}" class="staff-inline" data-confirm="Remove {{ $member->name }}? The account is deleted. Use Revoke access to keep the record instead." onsubmit="return confirm(this.dataset.confirm)">
                    @csrf @method('DELETE')
                    <button type="submit" class="staff-btn staff-btn--danger">Remove</button>
                </form>
            </div>
        </div>
    @empty
        <div class="staff-empty">No team members yet. Send an invitation above to add one.</div>
    @endforelse

    @if($footerLink)
        <div class="staff-footer"><a class="button" href="{{ route('account.subscription') }}">Manage subscription</a></div>
    @endif
</section>
