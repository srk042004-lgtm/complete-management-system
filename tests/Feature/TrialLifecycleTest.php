<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TrialLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lapsed_trial_without_subscription_suspends_the_hotel(): void
    {
        $tenant = $this->tenant('lapsed');
        $sub = $this->trial($tenant, now()->subDay());

        $this->assertSame(1, TrialLifecycle::cancelLapsedTrials());

        $this->assertSame('expired', $sub->fresh()->status);
        $this->assertSame('suspended', $tenant->fresh()->status);
        $this->assertDatabaseHas('hotel_operation_logs', ['tenant_id' => $tenant->id, 'action' => 'subscription.trial_cancelled']);
    }

    public function test_running_trial_is_not_cancelled(): void
    {
        $tenant = $this->tenant('running');
        $sub = $this->trial($tenant, now()->addDays(3));

        $this->assertSame(0, TrialLifecycle::cancelLapsedTrials());
        $this->assertSame('trial', $sub->fresh()->status);
        $this->assertSame('active', $tenant->fresh()->status);
    }

    public function test_hotel_that_bought_a_plan_is_not_cancelled(): void
    {
        $tenant = $this->tenant('paid');
        $this->trial($tenant, now()->subDay());
        $this->paid($tenant);

        $this->assertSame(0, TrialLifecycle::cancelLapsedTrials());
        $this->assertSame('active', $tenant->fresh()->status);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug, 'tenant_name' => $slug, 'hotel_name' => $slug, 'slug' => $slug,
            'email' => $slug.'@example.test', 'status' => 'active', 'hotel_type' => 'large',
            'enabled_modules' => ['rooms' => true],
        ]);
    }

    private function trial(Tenant $tenant, \DateTimeInterface $end): Subscription
    {
        return $tenant->subscriptions()->create([
            'plan_type' => 'monthly', 'price' => 0, 'currency_code' => 'PKR', 'status' => 'trial',
            'starts_at' => (clone $end)->modify('-10 days'), 'ends_at' => $end, 'trial_start' => (clone $end)->modify('-10 days'),
            'trial_end' => $end, 'trial_duration_days' => 10, 'auto_renew' => false,
        ]);
    }

    private function paid(Tenant $tenant): Subscription
    {
        return $tenant->subscriptions()->create([
            'plan_type' => 'monthly', 'price' => 5000, 'currency_code' => 'PKR', 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addMonth(), 'auto_renew' => false,
        ]);
    }
}
