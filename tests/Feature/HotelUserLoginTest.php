<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HotelRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_supported_hotel_roles_can_sign_in_with_the_hotel_login(): void
    {
        $roles = [
            'hotel_admin',
            'owner',
            'admin',
            'manager',
            'receptionist',
            'housekeeping',
            'accountant',
            'maintenance',
            'corporate',
            'housekeeper',
            'cashier',
        ];

        foreach ($roles as $index => $role) {
            $tenant = Tenant::query()->create([
                'name' => "Login Test Hotel {$index}",
                'hotel_name' => "Login Test Hotel {$index}",
                'tenant_name' => "Login Test Hotel {$index}",
                'slug' => "login-test-hotel-{$index}",
                'email' => "hotel-{$index}@example.test",
                'hotel_type' => $role === 'corporate' ? 'luxury' : 'small',
                'status' => 'active',
            ]);

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => "Hotel User {$index}",
                'email' => "user-{$index}@example.test",
                'password' => 'Password123!',
                'role' => $role,
                'status' => 'active',
            ]);

            $dashboardRole = HotelRoleCatalog::normalize($role);
            $this->post(route('hotel-admin.login.store'), [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
                ->assertRedirect(route('hotel.role.dashboard', ['role' => $dashboardRole]));

            $this->assertAuthenticatedAs($user, 'web');
            $this->get(route('hotel.role.dashboard', ['role' => $dashboardRole]))->assertOk();
            if (in_array($role, ['hotel_admin', 'admin', 'manager', 'receptionist', 'cashier'], true)) {
                $this->get(route('frontdesk.dashboard'))->assertOk();
            } else {
                $this->get(route('frontdesk.dashboard'))->assertForbidden();
            }

            $this->post(route('hotel-admin.logout'))
                ->assertRedirect(route('hotel-admin.login'));

            $this->assertGuest('web');
        }
    }

    public function test_hotel_sign_in_does_not_keep_a_super_admin_session(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Guard Separation Hotel',
            'hotel_name' => 'Guard Separation Hotel',
            'tenant_name' => 'Guard Separation Hotel',
            'slug' => 'guard-separation-hotel',
            'email' => 'guard-separation@example.test',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hotel Admin',
            'email' => 'hotel-admin@guard-separation.test',
            'password' => 'Password123!',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $superAdmin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform',
            'email' => 'platform@guard-separation.test',
            'number' => '03001234567',
            'password' => 'Password123!',
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin, 'super_admin')
            ->withSession(['hotel_tenant_id' => $tenant->id])
            ->post(route('hotel-admin.login.store'), [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('hotel.role.dashboard', ['role' => 'admin']));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('super_admin');
        $this->assertSame($tenant->id, session('hotel_tenant_id'));
    }

    public function test_hotel_user_can_sign_in_with_a_username(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Username Login Hotel',
            'hotel_name' => 'Username Login Hotel',
            'tenant_name' => 'Username Login Hotel',
            'slug' => 'username-login-hotel',
            'email' => 'username-login@example.test',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Receptionist',
            'email' => 'receptionist@username-login.test',
            'username' => 'receptionist_user',
            'password' => 'Password123!',
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->post(route('hotel-admin.login.store'), [
            'identifier' => $user->username,
            'password' => 'Password123!',
        ])->assertRedirect(route('hotel.role.dashboard', ['role' => 'receptionist']));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_inactive_hotel_user_cannot_sign_in(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Inactive Login Hotel',
            'hotel_name' => 'Inactive Login Hotel',
            'tenant_name' => 'Inactive Login Hotel',
            'slug' => 'inactive-login-hotel',
            'email' => 'inactive-login@example.test',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Inactive Hotel User',
            'email' => 'inactive-user@example.test',
            'password' => 'Password123!',
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $this->from(route('hotel-admin.login'))
            ->post(route('hotel-admin.login.store'), [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('hotel-admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }
}
