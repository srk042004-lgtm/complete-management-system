<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/super-admin');

        $response->assertRedirect('/super-admin/login');
    }

    public function test_super_admin_dashboard_renders_only_the_shared_mobile_navigation(): void
    {
        $superAdmin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform',
            'email' => 'platform-mobile-navigation@example.test',
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin, 'super_admin')
            ->get(route('super-admin.dashboard'))
            ->assertOk();

        $this->assertSame(
            1,
            substr_count((string) $response->getContent(), 'data-super-admin-mobile-navigation')
        );
    }

    public function test_super_admin_login_clears_hotel_session_and_redirects_to_super_admin_dashboard(): void
    {
        $hotelUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $superAdmin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform',
            'email' => 'platform-admin@example.test',
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'cnic' => '12345-6789012-3',
            'address' => 'Lahore',
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($hotelUser, 'web')
            ->withSession([
                'url.intended' => url('/frontdesk/in-house'),
                'hotel_tenant_id' => 1,
            ])
            ->post('/super-admin/login', [
                'email' => $superAdmin->email,
                'password' => 'Password123!',
            ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticatedAs($superAdmin, 'super_admin');
        $this->assertGuest('web');
        $this->assertFalse(session()->has('hotel_tenant_id'));
        $this->assertFalse(session()->has('url.intended'));
    }
}
