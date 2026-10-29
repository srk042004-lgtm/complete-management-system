<?php

namespace Tests\Feature;

use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSubscriptionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_subscription_and_record_payment(): void
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
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_type' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'auto_renew' => true,
            'price' => 1000,
        ]);

        $this->actingAs($superAdmin, 'super_admin');

        $response = $this->post('/superadmin/tenants/' . $tenant->id . '/subscription/update', [
            'plan_tier' => 'Lifetime',
            'ends_at' => '',
            'record_payment' => '1',
            'amount_received' => '2000',
            'reference_number' => 'REF-001',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));

        $subscription->refresh();
        $this->assertSame('lifetime', $subscription->plan_type);
        $this->assertNull($subscription->ends_at);
        $this->assertSame('active', $subscription->status);

        $this->assertDatabaseHas('saas_payments', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'payment_reference' => 'REF-001',
            'payment_status' => 'paid',
            'gateway' => 'Manual Admin Adjustment',
        ]);

        $this->assertSame(1, SaasPayment::query()->where('tenant_id', $tenant->id)->count());
    }
}
