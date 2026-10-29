<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\TrialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTrialAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_separate_demo_and_trial_account_registry(): void
    {
        $admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform Admin',
            'email' => 'platform-admin@example.test',
            'number' => '03001234567',
            'password' => 'StrongPassword123!',
            'address' => 'Test office',
            'is_active' => true,
        ]);

        TrialAccount::query()->create([
            'system' => 'hotel',
            'business_name' => 'Harbor Hotel',
            'contact_name' => 'Hotel Owner',
            'email' => 'hotel-owner@example.test',
            'phone' => '03000000000',
            'source' => 'demo_request',
            'status' => TrialAccount::STATUS_PENDING,
        ]);

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.trial-accounts'))
            ->assertOk()
            ->assertSee('Demo & Trial Accounts')
            ->assertSee('Harbor Hotel')
            ->assertSee('Awaiting registration');
    }
}
