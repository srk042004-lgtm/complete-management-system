<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelOperationLog;
use App\Models\HotelRoleInvitation;
use App\Models\User;
use App\Support\HotelFeatureCatalog;
use App\Support\HotelRoleCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    private const OWNER_ROLES = ['admin', 'manager'];

    private const ADMIN_ROLES = ['receptionist', 'housekeeping', 'accountant', 'maintenance'];

    public function store(Request $request): RedirectResponse
    {
        $actorRole = HotelRoleCatalog::normalize((string) $request->user()->role);
        abort_unless(in_array($actorRole, ['owner', 'admin'], true), 403);

        $tenant = $request->user()->tenant;
        abort_unless($tenant !== null, 403);
        $tier = HotelFeatureCatalog::operatingTier($tenant->hotel_type, $tenant->total_rooms);
        $roleScope = $actorRole === 'owner' ? self::OWNER_ROLES : self::ADMIN_ROLES;
        $availableRoles = array_values(array_filter(
            $roleScope,
            fn (string $role): bool => HotelRoleCatalog::allows($role, $tenant->moduleConfiguration(), $tier),
        ));

        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', $availableRoles)],
        ]);

        $rawToken = Str::random(64);
        $expiresAt = now()->addHours(48)->setMicroseconds(0);
        $invitation = HotelRoleInvitation::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'invited_by' => $request->user()->id,
            'email' => filled($validated['email'] ?? null) ? strtolower($validated['email']) : null,
            'role' => $validated['role'],
            // Store only a one-way digest; disclose the random credential only in this creation response.
            'token' => hash('sha256', $rawToken),
            'expires_at' => $expiresAt,
        ]);
        HotelOperationLog::query()->create([
            'tenant_id' => $tenant->id,
            'actor_id' => $request->user()->id,
            'action' => 'staff.invited',
            'subject_type' => HotelRoleInvitation::class,
            'subject_id' => $invitation->id,
            'details' => ['email' => $invitation->email, 'role' => $invitation->role],
            'ip_address' => $request->ip(),
        ]);

        $url = route('hotel.team.accept.link', ['token' => $rawToken]);

        return back()->with('success', 'Single-use staff invitation created. It expires in 48 hours.')
            ->with('invitation_url', $url)
            ->with('invitation_email', $invitation->email)
            ->with('invitation_code', $rawToken)
            ->with('invitation_id', $invitation->id);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $actorRole = HotelRoleCatalog::normalize((string) $actor->role);
        $managedRoles = $actorRole === 'owner' ? self::OWNER_ROLES : self::ADMIN_ROLES;

        abort_unless(in_array($actorRole, ['owner', 'admin'], true), 403);
        abort_unless((int) $user->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless(in_array(HotelRoleCatalog::normalize((string) $user->role), $managedRoles, true), 403);
        abort_unless((int) $user->id !== (int) $actor->id, 403, 'You cannot deactivate your own account.');

        $allowedRoles = $actorRole === 'owner' ? self::OWNER_ROLES : self::ADMIN_ROLES;
        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $user->forceFill($validated)->save();
        HotelOperationLog::query()->create([
            'tenant_id' => $actor->tenant_id,
            'actor_id' => $actor->id,
            'action' => $validated['status'] === 'suspended' ? 'staff.suspended' : 'staff.updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'details' => ['email' => $user->email, 'role' => $validated['role'], 'status' => $validated['status']],
            'ip_address' => $request->ip(),
        ]);

        return back()->withFragment('staff-management')->with('success', 'Staff access updated.');
    }

    /** Remove a staff account. Accounts with activity on record cannot be deleted, so the owner is pointed to revoking access. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertCanManageStaff($actor, $user);

        $userId = $user->id;
        $email = $user->email;
        $role = $user->role;

        try {
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', 'This account has activity on record, so it cannot be removed. Revoke its access instead.');
        }

        HotelOperationLog::query()->create([
            'tenant_id' => $actor->tenant_id,
            'actor_id' => $actor->id,
            'action' => 'staff.removed',
            'subject_type' => User::class,
            'subject_id' => $userId,
            'details' => ['email' => $email, 'role' => $role],
            'ip_address' => $request->ip(),
        ]);

        return back()->withFragment('staff-management')->with('success', 'Staff member removed.');
    }

    /** Cancel a pending invitation so its link stops working. */
    public function cancelInvitation(Request $request, HotelRoleInvitation $invitation): RedirectResponse
    {
        $actor = $request->user();
        $actorRole = HotelRoleCatalog::normalize((string) $actor->role);
        abort_unless(in_array($actorRole, ['owner', 'admin'], true), 403);
        abort_unless((int) $invitation->tenant_id === (int) $actor->tenant_id, 404);

        $managedRoles = $actorRole === 'owner' ? self::OWNER_ROLES : self::ADMIN_ROLES;
        abort_unless(in_array(HotelRoleCatalog::normalize((string) $invitation->role), $managedRoles, true), 403);
        abort_if($invitation->accepted_at !== null, 409, 'This invitation has already been accepted.');

        $invitationId = $invitation->id;
        $email = $invitation->email;
        $role = $invitation->role;
        $invitation->delete();

        HotelOperationLog::query()->create([
            'tenant_id' => $actor->tenant_id,
            'actor_id' => $actor->id,
            'action' => 'staff.invitation_cancelled',
            'subject_type' => HotelRoleInvitation::class,
            'subject_id' => $invitationId,
            'details' => ['email' => $email, 'role' => $role],
            'ip_address' => $request->ip(),
        ]);

        return back()->withFragment('staff-management')->with('success', 'Invitation cancelled. Its link no longer works.');
    }

    private function assertCanManageStaff(User $actor, User $member): void
    {
        $actorRole = HotelRoleCatalog::normalize((string) $actor->role);
        abort_unless(in_array($actorRole, ['owner', 'admin'], true), 403);
        abort_unless((int) $member->tenant_id === (int) $actor->tenant_id, 404);

        $managedRoles = $actorRole === 'owner' ? self::OWNER_ROLES : self::ADMIN_ROLES;
        abort_unless(in_array(HotelRoleCatalog::normalize((string) $member->role), $managedRoles, true), 403);
        abort_unless((int) $member->id !== (int) $actor->id, 403, 'You cannot remove your own account.');
    }

    public function showLink(Request $request): View
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
        ]);

        $invitation = $this->findValidInvitation($validated['token']);

        return view('hotel-role-accept', [
            'invitation' => $invitation,
            'token' => $validated['token'],
            'acceptRoute' => 'hotel.team.accept.link.store',
        ]);
    }

    public function show(string $token): View
    {
        $invitation = $this->findValidInvitation($token);

        return view('hotel-role-accept', [
            'invitation' => $invitation,
            'token' => $token,
            'acceptRoute' => 'hotel.team.accept.store',
        ]);
    }

    public function acceptLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
        ]);

        return $this->accept($request, $validated['token']);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findValidInvitation($token);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $invitation->email === null
                ? ['required', 'email', 'max:255', 'unique:users,email']
                : ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($token, $validated): User {
            $invitation = $this->queryByToken($token)->lockForUpdate()->firstOrFail();
            abort_if($invitation->accepted_at !== null || $invitation->expires_at->isPast(), 410, 'This invitation has expired or was already used.');
            abort_unless(in_array($invitation->role, array_merge(self::OWNER_ROLES, self::ADMIN_ROLES), true) || $invitation->role === 'cashier', 410, 'This invitation role is no longer supported.');
            $email = $invitation->email ?? strtolower($validated['email']);
            abort_if(User::query()->where('email', $email)->exists(), 409, 'An account already exists for this email address. Ask the hotel admin for a new invitation.');

            $user = User::query()->create([
                'tenant_id' => $invitation->tenant_id,
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'role' => HotelRoleCatalog::normalize($invitation->role),
                'status' => 'active',
            ]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('hotel_tenant_id', (int) $user->tenant_id);

        return redirect()->route('hotel.role.dashboard', $this->dashboardRole($user->role));
    }

    private function findValidInvitation(string $token): HotelRoleInvitation
    {
        $invitation = $this->queryByToken($token)->firstOrFail();
        abort_if($invitation->accepted_at !== null || $invitation->expires_at->isPast(), 410, 'This invitation has expired or was already used.');
        abort_unless(in_array($invitation->role, array_merge(self::OWNER_ROLES, self::ADMIN_ROLES), true) || $invitation->role === 'cashier', 410, 'This invitation role is no longer supported.');

        return $invitation->load('tenant:id,hotel_name,tenant_name');
    }

    private function queryByToken(string $token)
    {
        $digest = hash('sha256', $token);

        return HotelRoleInvitation::query()->where(function ($query) use ($digest, $token): void {
            // The second clause supports previously issued invitations whose tokens were stored in plaintext.
            $query->where('token', $digest)->orWhere('token', $token);
        });
    }

    private function dashboardRole(string $role): string
    {
        return HotelRoleCatalog::normalize($role);
    }
}
