<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPlanFeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_plan_feature_flags_and_sync_tenant_modules(): void
    {
        $superAdmin = SuperAdmin::query()->create([
            'name' => 'Test Admin',
            'fname' => 'Test',
            'email' => 'admin@example.com',
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'cnic' => '12345-6789012-3',
            'address' => 'Lahore',
            'is_active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'tenant_name' => 'Skyline Hotel',
            'hotel_name' => 'Skyline Hotel',
            'owner_name' => 'Ali Khan',
            'owner_email' => 'owner@example.com',
            'email' => 'owner@example.com',
            'country' => 'Pakistan',
            'status' => 'active',
            'enabled_modules' => json_encode([
                'rooms' => true,
                'restaurant' => false,
            ]),
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_type' => 'monthly',
            'price' => 1500,
            'status' => 'active',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'auto_renew' => true,
            'has_rooms' => true,
            'has_restaurant_pos' => false,
            'restaurant_addon_price' => 0,
        ]);

        $this->actingAs($superAdmin, 'super_admin');

        $response = $this->post('/super-admin/plans/' . $subscription->id . '/update', [
            'plan_type' => 'monthly',
            'price' => '3000',
            'currency_code' => 'PKR',
            'has_rooms' => '1',
            'has_restaurant_pos' => '1',
            'restaurant_addon_price' => '600',
        ]);

        $response->assertRedirect(route('super-admin.subscriptions'));

        $subscription->refresh();
        $tenant->refresh();

        $this->assertTrue((bool) $subscription->has_rooms);
        $this->assertTrue((bool) $subscription->has_restaurant_pos);
        $this->assertSame('3000.00', (string) $subscription->price);
        $this->assertSame('600.00', (string) $subscription->restaurant_addon_price);
        $this->assertSame(['rooms' => true, 'restaurant' => true], $tenant->enabled_modules);
    }
}
