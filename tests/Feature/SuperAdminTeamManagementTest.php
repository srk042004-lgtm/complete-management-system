<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_update_suspend_and_remove_team_members(): void
    {
        $admin = $this->createAdmin('owner@example.test');
        $this->assertSame('super_admin', $admin->fresh()->role);
        $this->assertSame('active', $admin->fresh()->status);
        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin, 'super_admin');
        $this->assertAuthenticatedAs($admin, 'super_admin');

        $this
            ->get(route('super-admin.team'))
            ->assertOk()
            ->assertSee('Team Management')
            ->assertSee('Add Team Member')
            ->assertSee('Finance Manager')
            ->assertSee('super-admin-role-badge', false)
            ->assertSee('aria-label="Signed in as Super Admin"', false)
            ->assertSee('super-admin-role-badge__status', false)
            ->assertSee('super-admin-theme-toggle', false);

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.team.store'), [
                'name' => 'Support Person',
                'email' => 'support@example.test',
                'number' => '03001234567',
                'role' => 'support_agent',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ])
            ->assertRedirect(route('super-admin.team'));

        $member = SuperAdmin::query()->where('email', 'support@example.test')->firstOrFail();
        $this->assertSame('support_agent', $member->role);
        $this->assertSame('active', $member->status);
        $this->assertTrue($member->is_active);

        $this->actingAs($admin, 'super_admin')
            ->put(route('super-admin.team.update', $member), [
                'name' => 'Support Lead',
                'email' => 'support@example.test',
                'number' => '03001234567',
                'role' => 'finance_manager',
                'status' => 'suspended',
            ])
            ->assertRedirect(route('super-admin.team'));

        $member->refresh();
        $this->assertSame('finance_manager', $member->role);
        $this->assertSame('suspended', $member->status);
        $this->assertFalse($member->is_active);

        $this->actingAs($admin, 'super_admin')
            ->delete(route('super-admin.team.destroy', $member))
            ->assertRedirect(route('super-admin.team'));
        $this->assertDatabaseMissing('super_admins', ['id' => $member->id]);
    }

    public function test_team_management_cannot_suspend_or_remove_the_last_active_super_admin(): void
    {
        $admin = $this->createAdmin('only-admin@example.test');

        $this->actingAs($admin, 'super_admin')
            ->put(route('super-admin.team.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'finance_manager',
                'status' => 'active',
            ])
            ->assertStatus(422);

        $this->actingAs($admin, 'super_admin')
            ->delete(route('super-admin.team.destroy', $admin))
            ->assertStatus(422);

        $this->assertDatabaseHas('super_admins', [
            'id' => $admin->id,
            'role' => 'super_admin',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_edit_validation_errors_reopen_the_correct_member_modal(): void
    {
        $admin = $this->createAdmin('owner@example.test');
        $member = $this->createMember('member@example.test');

        $this->actingAs($admin, 'super_admin')
            ->from(route('super-admin.team'))
            ->put(route('super-admin.team.update', $member), [
                '_team_member_id' => (string) $member->id,
                'name' => 'Updated Member',
                'email' => 'not-an-email',
                'role' => 'support_agent',
                'status' => 'active',
            ])
            ->assertRedirect(route('super-admin.team'))
            ->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input._team_member_id', (string) $member->id);

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.team'))
            ->assertOk()
            ->assertSee("const failedEditButton = document.querySelector('[data-member-id=\"{$member->id}\"]')", false)
            ->assertSee('openEditTeamModal(failedEditButton)', false);
    }

    public function test_team_management_routes_require_a_super_admin_session(): void
    {
        $this->get(route('super-admin.team'))->assertRedirect('/super-admin/login');
        $this->post(route('super-admin.team.store'))->assertRedirect('/super-admin/login');
    }

    private function createAdmin(string $email): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform Admin',
            'email' => $email,
            'number' => '03000000000',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function createMember(string $email): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Team Member',
            'fname' => 'Team Member',
            'email' => $email,
            'number' => '03000000001',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
            'role' => 'support_agent',
            'status' => 'active',
        ]);
    }
}
