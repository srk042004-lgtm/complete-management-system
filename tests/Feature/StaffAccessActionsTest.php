<?php

namespace Tests\Feature;

use App\Models\HotelRoleInvitation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revoke_and_restore_a_receptionist_without_deleting_the_record(): void
    {
        [$tenant, $admin] = $this->hotel('revoke-hotel');
        $receptionist = $this->user($tenant, 'receptionist', 'reception-revoke@example.test');

        $this->actingAs($admin)->patch(route('hotel.team.update', $receptionist), ['role' => 'receptionist', 'status' => 'suspended'])
            ->assertSessionHas('success');
        $this->assertSame('suspended', $receptionist->fresh()->status);

        $this->actingAs($admin)->patch(route('hotel.team.update', $receptionist), ['role' => 'receptionist', 'status' => 'active']);
        $this->assertSame('active', $receptionist->fresh()->status);
        $this->assertDatabaseHas('hotel_operation_logs', ['tenant_id' => $tenant->id, 'action' => 'staff.suspended', 'subject_id' => $receptionist->id]);
    }

    public function test_admin_can_remove_a_staff_member_and_it_is_logged(): void
    {
        [$tenant, $admin] = $this->hotel('remove-hotel');
        $housekeeper = $this->user($tenant, 'housekeeping', 'housekeeper-remove@example.test');

        $this->actingAs($admin)->delete(route('hotel.team.destroy', $housekeeper))->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $housekeeper->id]);
        $this->assertDatabaseHas('hotel_operation_logs', ['tenant_id' => $tenant->id, 'action' => 'staff.removed']);
    }

    public function test_admin_cannot_remove_an_owner_managed_admin_or_themselves(): void
    {
        [$tenant, $admin] = $this->hotel('guard-hotel');
        $otherAdmin = $this->user($tenant, 'admin', 'other-admin@example.test');

        $this->actingAs($admin)->delete(route('hotel.team.destroy', $admin))->assertForbidden();
        $this->actingAs($admin)->delete(route('hotel.team.destroy', $otherAdmin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_cancelling_an_invitation_removes_it_so_its_link_stops_working(): void
    {
        [$tenant, $admin] = $this->hotel('cancel-hotel');
        $invitation = HotelRoleInvitation::query()->create([
            'tenant_id' => $tenant->id,
            'invited_by' => $admin->id,
            'email' => 'pending@example.test',
            'role' => 'receptionist',
            'token' => hash('sha256', 'cancel-token'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)->delete(route('hotel.team.invitations.cancel', $invitation))->assertSessionHas('success');

        $this->assertDatabaseMissing('hotel_role_invitations', ['id' => $invitation->id]);
        $this->assertDatabaseHas('hotel_operation_logs', ['tenant_id' => $tenant->id, 'action' => 'staff.invitation_cancelled']);
    }

    public function test_staff_panel_shows_remove_revoke_and_cancel_actions(): void
    {
        [$tenant, $admin] = $this->hotel('panel-hotel');
        $this->user($tenant, 'receptionist', 'panel-reception@example.test');

        $this->actingAs($admin)->get(route('hotel.role.dashboard', ['role' => 'admin']))
            ->assertOk()
            ->assertSee('Revoke access')
            ->assertSee('Remove')
            ->assertSee('Team members');
    }

    /** @return array{0: Tenant, 1: User} */
    private function hotel(string $slug): array
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Staff Hotel',
            'tenant_name' => 'Staff Hotel',
            'name' => 'Staff Hotel',
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'status' => 'active',
        ]);

        return [$tenant, $this->user($tenant, 'admin', $slug.'-admin@example.test')];
    }

    private function user(Tenant $tenant, string $role, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' Account',
            'email' => $email,
            'password' => bcrypt('Password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
