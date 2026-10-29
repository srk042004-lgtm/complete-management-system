<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminHotelDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_full_hotel_details(): void
    {
        $admin = SuperAdmin::query()->create(['name' => 'Platform Admin', 'fname' => 'Platform', 'email' => 'platform-admin@example.test', 'number' => '03001234567', 'password' => bcrypt('Password123!'), 'cnic' => '12345-6789012-3', 'address' => 'Lahore', 'is_active' => true, 'role' => 'super_admin', 'status' => 'active']);
        $tenant = Tenant::query()->create(['name' => 'Harbor', 'tenant_name' => 'Harbor Co', 'hotel_name' => 'Harbor Grand', 'slug' => 'harbor-grand', 'email' => 'harbor@example.test', 'owner_name' => 'Ali Owner', 'owner_email' => 'ali@example.test', 'status' => 'active', 'hotel_type' => 'large', 'total_rooms' => 30, 'enabled_modules' => ['rooms' => true]]);
        $tenant->subscriptions()->create(['plan_type' => 'monthly', 'price' => 5000, 'currency_code' => 'PKR', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'auto_renew' => false]);

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.hotels.show', $tenant))
            ->assertOk()
            ->assertSee('Harbor Grand')
            ->assertSee('Ali Owner')
            ->assertSee('ali@example.test')
            ->assertSee('Subscription')
            ->assertSee('Staff accounts');
    }

    public function test_hotel_details_require_super_admin(): void
    {
        $tenant = Tenant::query()->create(['name' => 'X', 'tenant_name' => 'X', 'hotel_name' => 'X', 'slug' => 'x-hotel', 'email' => 'x@example.test', 'status' => 'active', 'hotel_type' => 'large', 'enabled_modules' => ['rooms' => true]]);

        $this->get(route('super-admin.hotels.show', $tenant))->assertRedirect();
    }
}
