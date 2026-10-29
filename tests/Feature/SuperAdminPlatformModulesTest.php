<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\SaasPayment;
use App\Models\SuperAdmin;
use App\Models\SuperAdminNotification;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPlatformModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_approve_hotel_and_audit_the_action(): void
    {
        $admin = $this->admin();
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Pending Hotel',
            'tenant_name' => 'Pending Hotel',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'email' => 'owner@example.com',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.hotel-registry.approve', $tenant));

        $response->assertRedirect();
        $tenant->refresh();
        $this->assertSame('active', $tenant->status);
        $this->assertNotNull($tenant->approved_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'hotel.approved', 'target_id' => $tenant->id]);
        $this->assertDatabaseHas('super_admin_notifications', ['type' => 'hotel.approved', 'target_id' => $tenant->id]);
    }

    public function test_super_admin_can_create_and_deactivate_reusable_plan(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin, 'super_admin')->post(route('super-admin.plans.store'), [
            'name' => 'Professional',
            'description' => 'Growing hotels',
            'billing_cycle' => 'monthly',
            'price' => '2500',
            'currency_code' => 'pkr',
            'trial_days' => 14,
            'max_rooms' => 100,
            'max_users' => 25,
            'has_rooms' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $plan = Plan::query()->where('name', 'Professional')->firstOrFail();
        $this->assertSame('PKR', $plan->currency_code);
        $this->assertTrue($plan->is_active);

        $this->actingAs($admin, 'super_admin')->patch(route('super-admin.plans.toggle-status', $plan));
        $this->assertFalse($plan->refresh()->is_active);
    }

    public function test_super_admin_can_persist_settings_and_mark_payment_paid(): void
    {
        $admin = $this->admin();
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Paying Hotel',
            'tenant_name' => 'Paying Hotel',
            'owner_name' => 'Owner',
            'owner_email' => 'payer@example.com',
            'email' => 'payer@example.com',
            'status' => 'active',
        ]);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'payment_reference' => 'PENDING-001',
            'amount' => 2500,
            'currency_code' => 'PKR',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($admin, 'super_admin')->put(route('super-admin.settings.update'), [
            'application_name' => 'Artflow SaaS',
            'default_currency' => 'PKR',
            'support_email' => 'support@example.com',
            'support_phone' => '03000000000',
            'trial_duration_days' => 14,
            'registration_enabled' => '1',
            'approval_required' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('super_admin_settings', ['key' => 'application_name']);

        $this->actingAs($admin, 'super_admin')->patch(route('super-admin.payments.mark-paid', $payment));
        $this->assertSame('paid', $payment->refresh()->payment_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.approved', 'target_id' => $payment->id]);
    }

    private function admin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform',
            'email' => fake()->unique()->safeEmail(),
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);
    }
}
