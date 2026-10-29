<?php

namespace Tests\Feature;

use App\Models\HotelRoleInvitation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_admin_can_create_a_48_hour_tenant_and_role_bound_invite(): void
    {
        $tenant = $this->tenant('invite-admin-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@invite-admin.test');

        $response = $this->actingAs($admin)
            ->from('/hotel/admin')
            ->post(route('hotel.team.invite.store'), [
                'email' => 'new-receptionist@example.test',
                'role' => 'receptionist',
            ]);

        $response->assertRedirect('/hotel/admin');
        $response->assertSessionHas('invitation_url');
        $response->assertSessionHas('invitation_code');

        $invitation = HotelRoleInvitation::query()->firstOrFail();
        $this->assertSame($tenant->id, $invitation->tenant_id);
        $this->assertSame($admin->id, $invitation->invited_by);
        $this->assertSame('receptionist', $invitation->role);
        $this->assertSame('new-receptionist@example.test', $invitation->email);
        $this->assertTrue($invitation->expires_at->between(now()->addHours(47)->addMinutes(59), now()->addHours(48)->addSeconds(1)));

        $rawToken = session('invitation_code');
        $this->assertNotSame($rawToken, $invitation->token, 'Only a token digest should be stored.');
        $this->assertSame(hash('sha256', $rawToken), $invitation->token);
        $this->assertStringContainsString('/register/invite?token='.$rawToken, session('invitation_url'));

        $response = $this->actingAs($admin)->get(route('hotel.role.dashboard', ['role' => 'admin']));
        $response->assertOk()
            ->assertSee('Invite staff')
            ->assertSee('Maintenance Engineer')
            ->assertSee('Copy Link');
    }

    public function test_hotel_admin_cannot_invite_a_separate_cashier_role(): void
    {
        $tenant = $this->tenant('invite-no-cashier-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@invite-no-cashier.test');

        $this->actingAs($admin)
            ->post(route('hotel.team.invite.store'), [
                'email' => 'cashier@example.test',
                'role' => 'cashier',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('hotel_role_invitations', 0);
    }

    public function test_invite_redemption_creates_bound_user_and_cannot_be_reused(): void
    {
        $tenant = $this->tenant('invite-redemption-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@invite-redemption.test');
        $this->actingAs($admin)->from('/hotel/admin')->post(route('hotel.team.invite.store'), [
            'email' => 'maintenance@example.test',
            'role' => 'maintenance',
        ])->assertRedirect('/hotel/admin');

        $rawToken = session('invitation_code');
        $url = route('hotel.team.accept.link', ['token' => $rawToken]);
        $this->get($url)->assertOk()
            ->assertSee('Maintenance Engineer')
            ->assertSee('maintenance@example.test')
            ->assertSee(route('hotel.team.accept.link.store'), false);

        $this->post(route('hotel.team.accept.link.store'), [
            'token' => $rawToken,
            'name' => 'Morgan Maintenance',
            'phone' => '03001234567',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'tenant_id' => $tenant->id + 1,
            'role' => 'admin',
        ])->assertRedirect(route('hotel.role.dashboard', ['role' => 'maintenance']))
            ->assertSessionHas('hotel_tenant_id', $tenant->id);

        $staff = User::query()->where('email', 'maintenance@example.test')->firstOrFail();
        $this->assertSame($tenant->id, $staff->tenant_id);
        $this->assertSame('maintenance', $staff->role);
        $this->assertNotNull(HotelRoleInvitation::query()->firstOrFail()->accepted_at);

        $this->get($url)->assertStatus(410);
        $this->post(route('hotel.team.accept.store', ['token' => $rawToken]), [
            'name' => 'Second Attempt',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertStatus(410);
        $this->assertSame(1, User::query()->where('tenant_id', $tenant->id)->where('email', 'maintenance@example.test')->count());
    }

    public function test_hotel_owner_can_only_invite_hotel_admins_and_managers(): void
    {
        $tenant = $this->tenant('invite-owner-denied-hotel');
        $owner = $this->user($tenant, 'owner', 'owner@invite-denied.test');

        $this->actingAs($owner)->from('/hotel/owner')
            ->post(route('hotel.team.invite.store'), [
                'email' => 'manager@example.test',
                'role' => 'manager',
            ])
            ->assertRedirect('/hotel/owner')
            ->assertSessionHas('invitation_url');

        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertDatabaseHas('hotel_role_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'manager@example.test',
            'role' => 'manager',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'manager@example.test']);

        $this->actingAs($owner)
            ->post(route('hotel.team.invite.store'), [
                'email' => 'receptionist@example.test',
                'role' => 'receptionist',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_hotel_admin_can_create_a_manual_link_and_staff_can_choose_their_email(): void
    {
        $tenant = $this->tenant('manual-invite-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@manual-invite.test');

        $this->actingAs($admin)->from('/hotel/admin')
            ->post(route('hotel.team.invite.store'), [
                'email' => '',
                'role' => 'receptionist',
            ])
            ->assertRedirect('/hotel/admin')
            ->assertSessionHas('invitation_url')
            ->assertSessionHas('invitation_code');

        $invitation = HotelRoleInvitation::query()->firstOrFail();
        $this->assertNull($invitation->email);
        $this->assertSame('receptionist', $invitation->role);

        $rawToken = session('invitation_code');
        $inviteUrl = session('invitation_url');
        $this->get('/hotel/admin')
            ->assertOk()
            ->assertSee('Manual Link')
            ->assertSee('Copy Link')
            ->assertSee($inviteUrl)
            ->assertSee('data-copy-invite', false);

        $this->get($inviteUrl)
            ->assertOk()
            ->assertSee('Enter your email')
            ->assertSee('name="email"', false);

        $this->post(route('hotel.team.accept.link.store'), [
            'token' => $rawToken,
            'name' => 'Manual Receptionist',
            'email' => 'manual-staff@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('hotel.role.dashboard', ['role' => 'receptionist']));

        $staff = User::query()->where('email', 'manual-staff@example.test')->firstOrFail();
        $this->assertSame($tenant->id, $staff->tenant_id);
        $this->assertSame('receptionist', $staff->role);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_invitation_expiry_and_locked_email_are_enforced(): void
    {
        $tenant = $this->tenant('invite-expired-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@invite-expired.test');
        $this->actingAs($admin)->from('/hotel/admin')->post(route('hotel.team.invite.store'), [
            'email' => 'expiry-target@example.test',
            'role' => 'housekeeping',
        ]);
        $rawToken = session('invitation_code');

        $this->get(route('hotel.team.accept', ['token' => $rawToken]))->assertOk();
        $this->post(route('hotel.team.accept.store', ['token' => $rawToken]), [
            'name' => 'Wrong Email Attempt',
            'email' => 'attacker@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('hotel.role.dashboard', ['role' => 'housekeeping']));

        $created = User::query()->where('tenant_id', $tenant->id)->where('role', 'housekeeping')->firstOrFail();
        $this->assertSame('expiry-target@example.test', $created->email);

        $expiredTenant = $this->tenant('invite-expiry-date-hotel');
        $expiredAdmin = $this->user($expiredTenant, 'admin', 'admin@expiry-date.test');
        $this->withSession(['hotel_tenant_id' => $expiredTenant->id])
            ->actingAs($expiredAdmin)->from('/hotel/admin')->post(route('hotel.team.invite.store'), [
                'email' => 'expired-target@example.test',
                'role' => 'receptionist',
            ]);
        $expiredToken = session('invitation_code');
        HotelRoleInvitation::query()->where('email', 'expired-target@example.test')->update(['expires_at' => now()->subSecond()]);

        $this->get(route('hotel.team.accept', ['token' => $expiredToken]))->assertStatus(410);
        $this->assertDatabaseMissing('users', ['email' => 'expired-target@example.test']);
    }

    public function test_invitation_link_is_not_visible_again_in_admin_listing(): void
    {
        $tenant = $this->tenant('invite-list-hotel');
        $admin = $this->user($tenant, 'admin', 'admin@invite-list.test');
        $this->actingAs($admin)->from('/hotel/admin')->post(route('hotel.team.invite.store'), [
            'email' => 'list-target@example.test',
            'role' => 'receptionist',
        ]);
        $rawToken = session('invitation_code');

        $this->withSession(['hotel_tenant_id' => $tenant->id])->get('/hotel/admin')
            ->assertOk()
            ->assertSee('Pending invitations')
            ->assertSee('list-target@example.test')
            ->assertSee($rawToken);

        $this->get('/hotel/admin')
            ->assertOk()
            ->assertSee('Pending invitations')
            ->assertSee('list-target@example.test')
            ->assertDontSee($rawToken);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'hotel_name' => 'Invite Hotel',
            'tenant_name' => 'Invite Hotel',
            'name' => 'Invite Hotel',
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'status' => 'active',
        ]);
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
