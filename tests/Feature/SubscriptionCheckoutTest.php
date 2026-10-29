<?php

namespace Tests\Feature;

use App\Models\IndustryRegistration;
use App\Models\Plan;
use App\Models\SaasLedger;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\SuperAdminSetting;
use App\Models\Tenant;
use App\Models\TrialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_admin_can_submit_payment_proof_for_server_priced_plan(): void
    {
        Storage::fake('local');
        [$tenant, $owner, $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('yearly', 50000);
        SuperAdminSetting::query()->create([
            'key' => 'subscription_payment_accounts',
            'value' => ['value' => ['easypaisa' => [
                'account_name' => 'Verified ArtFlow Account',
                'account_number' => '03000000000',
                'instructions' => 'Use the displayed reference.',
            ]]],
        ]);

        $this->actingAs($owner)->get(route('account.subscription'))
            ->assertOk()
            ->assertSee('Subscription &amp; billing', false)
            ->assertSee('Verified ArtFlow Account')
            ->assertSee('data-payment-account-name="Verified ArtFlow Account"', false)
            ->assertSee('data-payment-account-number="03000000000"', false)
            ->assertSee('Annual billing');

        $this->actingAs($owner)->post(route('account.subscription.payment-request'), [
            'plan_id' => $plan->id,
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-SUB-10001',
            'amount' => 1,
            'screenshot' => UploadedFile::fake()->create('receipt.png', 10, 'image/png'),
        ])->assertRedirect(route('account.subscription'))
            ->assertSessionHas('success');

        $payment = SaasPayment::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame($plan->id, $payment->requested_plan_id);
        $this->assertSame('50000.00', $payment->amount);
        $this->assertSame('PKR', $payment->currency_code);
        $this->assertSame('trial', $subscription->fresh()->status);
        $this->assertTrue(Storage::disk('local')->exists($payment->proof_path));
        $this->assertDatabaseHas('super_admin_notifications', [
            'type' => 'subscription.payment_submitted',
            'target_id' => $payment->id,
        ]);
        $this->actingAs($owner)->get(route('account.subscription'))
            ->assertOk()
            ->assertSee('data-payment-watch', false)
            ->assertSee(route('account.subscription.payment-status', $payment->payment_reference), false)
            ->assertSee('Congratulations!', false)
            ->assertSee('}, 5000);', false);
    }

    public function test_permanent_registration_keeps_checkout_on_the_selected_billing_cycle(): void
    {
        [$tenant, $owner, $subscription] = $this->hotelWithTrial();
        $subscription->forceFill(['status' => 'expired', 'plan_type' => 'yearly'])->save();
        $registration = new IndustryRegistration;
        $registration->setTable('hotel_registrations');
        $registration->fill([
            'tenant_id' => $tenant->id,
            'business_name' => 'Subscription Test Hotel',
            'contact_name' => 'Hotel Owner',
            'email' => $owner->email,
            'phone' => '03000000000',
            'address' => 'Test Road',
            'city' => 'Peshawar',
            'country' => 'Pakistan',
            'details' => ['account_type' => 'permanent', 'billing_cycle' => 'annual'],
        ]);
        $registration->save();
        $monthlyPlan = $this->plan('monthly', 5000);
        $annualPlan = $this->plan('yearly', 50000);
        $otherAnnualPlan = $this->plan('yearly', 60000);
        $otherAnnualPlan->forceFill(['name' => 'Alternate yearly plan'])->save();
        $registration->forceFill([
            'details' => [
                'account_type' => 'permanent',
                'billing_cycle' => 'annual',
                'plan_id' => $annualPlan->id,
            ],
        ])->save();

        $this->actingAs($owner)->get(route('account.subscription'))
            ->assertOk()
            ->assertSee($annualPlan->name)
            ->assertDontSee($monthlyPlan->name)
            ->assertDontSee($otherAnnualPlan->name);

        Storage::fake('local');
        $this->post(route('account.subscription.payment-request'), [
            'plan_id' => $monthlyPlan->id,
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-WRONG-CYCLE-001',
            'screenshot' => UploadedFile::fake()->create('receipt.png', 10, 'image/png'),
        ])->assertSessionHasErrors('plan_id');

        $this->assertDatabaseCount('saas_payments', 0);

        $this->post(route('account.subscription.payment-request'), [
            'plan_id' => $otherAnnualPlan->id,
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-WRONG-PLAN-001',
            'screenshot' => UploadedFile::fake()->create('receipt.png', 10, 'image/png'),
        ])->assertSessionHasErrors('plan_id');

        SuperAdminSetting::query()->create([
            'key' => 'subscription_payment_accounts',
            'value' => ['value' => ['easypaisa' => [
                'account_name' => 'Verified ArtFlow Account',
                'account_number' => '03000000000',
                'instructions' => 'Use the displayed reference.',
            ]]],
        ]);
        $this->post(route('account.subscription.payment-request'), [
            'plan_id' => $annualPlan->id,
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-PERMANENT-APPROVAL-001',
            'screenshot' => UploadedFile::fake()->create('receipt.png', 10, 'image/png'),
        ])->assertRedirect(route('account.subscription'));
        $payment = SaasPayment::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->actingAs($this->superAdmin(), 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertRedirect();

        $this->assertSame('approved', data_get($registration->fresh()->details, 'payment_status'));
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_receptionist_cannot_manage_subscription_billing(): void
    {
        [$tenant, , $subscription] = $this->hotelWithTrial();
        $demoUser = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Receptionist',
            'email' => 'demo-receptionist-'.uniqid().'@example.test',
            'password' => bcrypt('Password123!'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);
        $this->actingAs($demoUser)->get(route('account.subscription'))
            ->assertForbidden();
        $this->post(route('account.subscription.payment-request'))
            ->assertForbidden();
        $this->assertSame('trial', $subscription->fresh()->status);
        $this->assertDatabaseCount('saas_payments', 0);
    }

    public function test_super_admin_approval_activates_subscription_and_adds_revenue_ledger_entry(): void
    {
        [$tenant, $owner, $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('monthly', 5000);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'requested_plan_id' => $plan->id,
            'payment_reference' => 'SUB-APPROVE-001',
            'amount' => $plan->price,
            'currency_code' => $plan->currency_code,
            'payment_status' => 'pending',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX-APPROVE-001',
        ]);
        $admin = $this->superAdmin();

        $this->actingAs($owner)
            ->get(route('account.subscription.payment-status', $payment->payment_reference))
            ->assertOk()
            ->assertJsonPath('payment_status', 'pending');

        $this->actingAs($admin, 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertRedirect()
            ->assertSessionHas('success');

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame('monthly', $subscription->plan_type);
        $this->assertNull($subscription->trial_end);
        $this->assertTrue($subscription->ends_at->isFuture());
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->starts_at->isToday());
        $this->assertTrue($subscription->ends_at->equalTo($subscription->starts_at->copy()->addMonthNoOverflow()));
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertDatabaseHas('saas_ledger', [
            'tenant_id' => $tenant->id,
            'transaction_type' => 'subscription_base',
            'payment_method' => 'bank_transfer',
            'gross_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'successful',
        ]);
        $this->assertSame(1, SaasLedger::query()->where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment.approved',
            'target_id' => $payment->id,
        ]);
        $this->actingAs($owner)
            ->get(route('account.subscription.payment-status', $payment->payment_reference))
            ->assertOk()
            ->assertJsonPath('payment_status', 'paid')
            ->assertJsonPath('subscription_status', 'active')
            ->assertJsonPath('billing_cycle', 'monthly')
            ->assertJsonPath('ends_at', $subscription->ends_at->toIso8601String());

        $this->actingAs($admin, 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertStatus(409);
        $this->assertSame(1, SaasLedger::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_annual_approval_activates_the_plan_for_twelve_months_from_approval(): void
    {
        [$tenant, , $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('yearly', 50000);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'requested_plan_id' => $plan->id,
            'payment_reference' => 'SUB-ANNUAL-APPROVE-001',
            'amount' => $plan->price,
            'currency_code' => $plan->currency_code,
            'payment_status' => 'pending',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX-ANNUAL-APPROVE-001',
        ]);

        $this->actingAs($this->superAdmin(), 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame('yearly', $subscription->plan_type);
        $this->assertTrue($subscription->starts_at->isToday());
        $this->assertTrue($subscription->ends_at->equalTo($subscription->starts_at->copy()->addYearNoOverflow()));
    }

    public function test_lifetime_approval_activates_without_an_expiry_date(): void
    {
        [$tenant, , $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('lifetime', 90000);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'requested_plan_id' => $plan->id,
            'payment_reference' => 'SUB-LIFETIME-APPROVE-001',
            'amount' => $plan->price,
            'currency_code' => $plan->currency_code,
            'payment_status' => 'pending',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX-LIFETIME-APPROVE-001',
        ]);

        $this->actingAs($this->superAdmin(), 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertSame('lifetime', $subscription->plan_type);
        $this->assertTrue($subscription->starts_at->isToday());
        $this->assertNull($subscription->ends_at);
    }

    public function test_active_subscription_shows_dismissible_expiry_timer_only_in_final_three_days(): void
    {
        [$tenant, $owner, $subscription] = $this->hotelWithTrial();
        $subscription->forceFill([
            'status' => 'active',
            'trial_start' => null,
            'trial_end' => null,
            'ends_at' => now()->addDays(2),
        ])->save();

        $this->actingAs($owner)->view('partials.subscription-trial-banner')
            ->assertSee('Renewal due in')
            ->assertSee('data-dismiss-expiry', false)
            ->assertSee('data-banner-countdown', false);

        $subscription->forceFill(['ends_at' => now()->addDays(4)])->save();
        $this->actingAs($owner)->view('partials.subscription-trial-banner')
            ->assertDontSee('Renewal due in')
            ->assertDontSee('data-expiry-reminder data-dismiss-key=', false);
    }

    public function test_refunding_a_subscription_payment_marks_its_revenue_entry_refunded(): void
    {
        [$tenant, , $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('monthly', 5000);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'requested_plan_id' => $plan->id,
            'payment_reference' => 'SUB-REFUND-001',
            'amount' => $plan->price,
            'currency_code' => $plan->currency_code,
            'payment_status' => 'pending',
            'payment_method' => 'cash',
        ]);
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'super_admin')
            ->patch(route('super-admin.payments.mark-paid', $payment))
            ->assertRedirect();
        $this->patch(route('super-admin.payments.refund', $payment))
            ->assertRedirect();

        $this->assertSame('refunded', $payment->fresh()->payment_status);
        $this->assertDatabaseHas('saas_ledger', [
            'tenant_id' => $tenant->id,
            'invoice_number' => $payment->payment_reference,
            'payment_status' => 'refunded',
        ]);
    }

    public function test_super_admin_can_reject_payment_without_changing_subscription(): void
    {
        [$tenant, , $subscription] = $this->hotelWithTrial();
        $plan = $this->plan('monthly', 5000);
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'requested_plan_id' => $plan->id,
            'payment_reference' => 'SUB-REJECT-001',
            'amount' => $plan->price,
            'currency_code' => $plan->currency_code,
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'transaction_id' => 'TRX-REJECT-001',
        ]);

        $this->actingAs($this->superAdmin(), 'super_admin')
            ->patch(route('super-admin.payments.reject', $payment), ['review_notes' => 'Receipt was unreadable.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('failed', $payment->fresh()->payment_status);
        $this->assertStringContainsString('Receipt was unreadable.', $payment->fresh()->notes);
        $this->assertSame('trial', $subscription->fresh()->status);
        $this->assertDatabaseCount('saas_ledger', 0);
    }

    public function test_payment_proof_is_only_available_to_super_admin(): void
    {
        Storage::fake('local');
        [$tenant, $owner, $subscription] = $this->hotelWithTrial();
        $path = UploadedFile::fake()->create('private-proof.png', 10, 'image/png')->store('subscription-payment-proofs', 'local');
        $payment = SaasPayment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'payment_reference' => 'SUB-PROOF-001',
            'amount' => 5000,
            'currency_code' => 'PKR',
            'payment_status' => 'pending',
            'proof_path' => $path,
        ]);

        $this->actingAs($owner)->get(route('super-admin.payments.proof', $payment))->assertRedirect(route('login'));
        $this->actingAs($this->superAdmin(), 'super_admin')->get(route('super-admin.payments.proof', $payment))->assertOk();
    }

    private function hotelWithTrial(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Subscription Test Hotel',
            'tenant_name' => 'Subscription Test Hotel',
            'hotel_name' => 'Subscription Test Hotel',
            'slug' => 'subscription-test-hotel-'.uniqid(),
            'owner_name' => 'Hotel Owner',
            'owner_email' => 'subscription-owner-'.uniqid().'@example.test',
            'email' => 'subscription-hotel-'.uniqid().'@example.test',
            'currency_code' => 'PKR',
            'status' => 'active',
        ]);
        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hotel Owner',
            'email' => 'subscription-user-'.uniqid().'@example.test',
            'password' => bcrypt('Password123!'),
            'role' => 'owner',
            'status' => 'active',
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_type' => 'monthly',
            'price' => 5000,
            'currency_code' => 'PKR',
            'status' => 'trial',
            'starts_at' => now(),
            'trial_start' => now(),
            'trial_end' => now()->addDays(5),
            'trial_duration_days' => 10,
            'ends_at' => now()->addDays(5),
            'auto_renew' => false,
            'has_rooms' => true,
            'has_restaurant_pos' => false,
        ]);

        TrialAccount::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'subscription_id' => $subscription->id,
            'system' => 'hotel',
            'business_name' => $tenant->name,
            'status' => TrialAccount::STATUS_ACTIVE,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays(5),
        ]);

        return [$tenant, $owner, $subscription];
    }

    private function plan(string $cycle, float $price): Plan
    {
        return Plan::query()->create([
            'name' => ucfirst($cycle).' Hotel Plan',
            'billing_cycle' => $cycle,
            'price' => $price,
            'currency_code' => 'PKR',
            'trial_days' => 0,
            'has_rooms' => true,
            'has_restaurant_pos' => true,
            'is_active' => true,
        ]);
    }

    private function superAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Subscription Reviewer',
            'fname' => 'Subscription',
            'email' => 'subscription-reviewer-'.uniqid().'@example.test',
            'number' => '03000000000',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);
    }
}
