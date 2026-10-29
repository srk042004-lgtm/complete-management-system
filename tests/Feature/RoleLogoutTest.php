<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_role_logout_clears_hotel_and_super_admin_authentication(): void
    {
        $hotelUser = $this->createHotelUser();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($hotelUser, 'web')
            ->actingAs($superAdmin, 'super_admin')
            ->withSession(['hotel_tenant_id' => $hotelUser->tenant_id])
            ->post(route('hotel-admin.logout'))
            ->assertRedirect(route('hotel-admin.login'));

        $this->assertGuest('web');
        $this->assertGuest('super_admin');
        $this->assertFalse(session()->has('hotel_tenant_id'));
    }

    public function test_super_admin_logout_clears_both_authentication_guards(): void
    {
        $hotelUser = $this->createHotelUser();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($hotelUser, 'web')
            ->actingAs($superAdmin, 'super_admin')
            ->post(route('logout'))
            ->assertRedirect('/super-admin/login');

        $this->assertGuest('web');
        $this->assertGuest('super_admin');
    }

    public function test_super_admin_logout_is_available_in_mobile_navigation(): void
    {
        $this->actingAs($this->createSuperAdmin(), 'super_admin')
            ->get(route('super-admin.team'))
            ->assertOk()
            ->assertSee('super-admin-mobile-logout', false)
            ->assertSee('action="'.route('logout').'"', false);
    }

    private function createHotelUser(): User
    {
        $tenant = Tenant::query()->create([
            'name' => 'Logout Test Hotel',
            'tenant_name' => 'Logout Test Hotel',
            'hotel_name' => 'Logout Test Hotel',
            'owner_email' => 'logout-hotel@example.test',
            'email' => 'logout-hotel@example.test',
            'slug' => 'logout-test-hotel',
            'status' => 'active',
        ]);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hotel Admin',
            'email' => 'logout-user@example.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform Admin',
            'email' => 'logout-super-admin@example.test',
            'number' => '03000000000',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }
}
