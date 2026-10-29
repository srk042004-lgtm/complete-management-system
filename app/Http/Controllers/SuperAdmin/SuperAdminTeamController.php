<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Support\SuperAdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminTeamController extends Controller
{
    public function index(): View
    {
        $team = SuperAdmin::query()->latest('id')->paginate(15);

        return view('super-admin.team-access', [
            'team' => $team,
            'roles' => SuperAdmin::teamRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:super_admins,email'],
            'number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(SuperAdmin::teamRoles()))],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $member = SuperAdmin::query()->create([
            'name' => $validated['name'],
            'fname' => $validated['name'],
            'email' => $validated['email'],
            'number' => $validated['number'] ?? '',
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => 'active',
            'is_active' => true,
        ]);

        SuperAdminActivity::log('team_member.created', $member, "{$member->name} was added to the Super Admin team as {$member->role}.");

        return redirect()->route('super-admin.team')
            ->with('success', 'Team member created. Share their initial password securely.');
    }

    public function update(Request $request, SuperAdmin $member): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('super_admins', 'email')->ignore($member->id)],
            'number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(SuperAdmin::teamRoles()))],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        abort_if(
            $member->is(auth('super_admin')->user()) && $validated['status'] !== 'active',
            422,
            'You cannot suspend your own account.'
        );

        if ($member->role === 'super_admin' && $member->status === 'active'
            && ($validated['role'] !== 'super_admin' || $validated['status'] !== 'active')) {
            $this->ensureAnotherActiveSuperAdminExists($member);
        }

        $update = [
            'name' => $validated['name'],
            'fname' => $validated['name'],
            'email' => $validated['email'],
            'number' => $validated['number'] ?? '',
            'role' => $validated['role'],
            'status' => $validated['status'],
            'is_active' => $validated['status'] === 'active',
        ];

        if (($validated['password'] ?? '') !== '') {
            $update['password'] = $validated['password'];
        }

        $member->update($update);
        SuperAdminActivity::log('team_member.updated', $member, "{$member->name}'s Super Admin team access was updated.");

        return redirect()->route('super-admin.team')->with('success', 'Team member details updated.');
    }

    public function destroy(SuperAdmin $member): RedirectResponse
    {
        abort_if($member->is(auth('super_admin')->user()), 422, 'You cannot remove your own account.');

        if ($member->role === 'super_admin' && $member->status === 'active') {
            $this->ensureAnotherActiveSuperAdminExists($member);
        }

        $name = $member->name;
        SuperAdminActivity::log('team_member.removed', $member, "{$name} was removed from the Super Admin team.");
        $member->delete();

        return redirect()->route('super-admin.team')->with('success', 'Team member access removed.');
    }

    private function ensureAnotherActiveSuperAdminExists(SuperAdmin $member): void
    {
        abort_if(
            SuperAdmin::query()
                ->where('role', 'super_admin')
                ->where('status', 'active')
                ->where('is_active', true)
                ->whereKeyNot($member->id)
                ->doesntExist(),
            422,
            'At least one active Super Admin account must remain.'
        );
    }
}
