<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TrialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustryRegisterViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_registration_page_loads_without_parse_error(): void
    {
        $response = $this->get('/register/hotel?currency=PKR&cycle=annual&plan=enterprise');

        $response->assertOk();
        $response->assertSee('Hotel Management System Registration');
    }

    public function test_hotel_registration_creates_an_owner_account(): void
    {
        $response = $this->post('/register/hotel', [
            'plan' => 'small',
            'cycle' => 'annual',
            'currency' => 'USD',
            'account_role' => 'owner',
            'business_name' => 'Test Hotel',
            'contact_name' => 'Test Owner',
            'email' => 'owner@example.test',
            'phone' => '03000000000',
            'city' => 'Peshawar',
            'country' => 'Pakistan',
            'address' => 'Test address',
            'details' => ['scale' => '12 rooms'],
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('account.subscription'));
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);
        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('owner', $owner->role);
        $this->assertSame('small', $tenant->hotel_type);
        $this->assertFalse($tenant->hasFeature('restaurant'));
        $this->assertSame('expired', $subscription->status);
        $this->assertNull($subscription->trial_end);
        $this->assertSame('yearly', $subscription->plan_type);
        $this->assertSame('USD', $subscription->currency_code);
        $this->assertDatabaseHas('hotel_registrations', ['tenant_id' => $tenant->id]);
        $this->assertDatabaseMissing('trial_accounts', ['tenant_id' => $tenant->id]);
    }

    public function test_luxury_industry_registration_saves_type_and_selected_features(): void
    {
        $response = $this->post('/register/hotel', [
            'plan' => 'enterprise',
            'cycle' => 'monthly',
            'currency' => 'USD',
            'hotel_type' => 'luxury',
            'features' => ['housekeeping', 'restaurant', 'pos', 'staff', 'finance', 'reports'],
            'account_role' => 'owner',
            'business_name' => 'Luxury Hotel',
            'contact_name' => 'Luxury Owner',
            'email' => 'luxury-owner@example.test',
            'phone' => '03000000001',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'address' => 'Luxury Road',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('account.subscription'));
        $tenant = Tenant::query()->where('email', 'luxury-owner@example.test')->firstOrFail();
        $this->assertSame('luxury', $tenant->hotel_type);
        $this->assertTrue($tenant->hasFeature('restaurant'));
        $this->assertTrue($tenant->hasFeature('pos'));
        $this->assertSame('expired', Subscription::query()->where('tenant_id', $tenant->id)->value('status'));
    }

    public function test_demo_request_redirects_to_selected_ten_day_trial_registration(): void
    {
        $response = $this->post('/portal/demo-request', [
            'business_name' => 'Demo Hotel',
            'business_type' => 'hotel',
            'contact_name' => 'Demo Owner',
            'phone' => '03001234567',
            'email' => 'demo-owner@example.test',
            'city' => 'Peshawar',
            'already_using' => 'No',
            'address' => 'Demo Road, Peshawar',
        ]);

        $trialAccount = TrialAccount::query()->firstOrFail();
        $registrationUrl = route('industry.register', ['system' => 'hotel', 'trial_account' => $trialAccount->id]);
        $response->assertRedirect($registrationUrl);
        $response->assertSessionHas('demo_success');
        $this->assertDatabaseHas('demo_requests', [
            'business_name' => 'Demo Hotel',
            'business_type' => 'Hotel',
            'contact_name' => 'Demo Owner',
            'city' => 'Peshawar',
        ]);

        $this->get($registrationUrl)
            ->assertOk()
            ->assertSee('10-day trial')
            ->assertSee('Demo Owner')
            ->assertSee('name="trial_account_id" value="'.$trialAccount->id.'"', false);

        $registrationResponse = $this->post('/register/hotel', [
            'trial_account_id' => $trialAccount->id,
            'account_role' => 'owner',
            'business_name' => 'Demo Hotel',
            'contact_name' => 'Demo Owner',
            'email' => 'demo-owner@example.test',
            'phone' => '03001234567',
            'city' => 'Peshawar',
            'country' => 'Pakistan',
            'address' => 'Demo Road, Peshawar',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $registrationResponse->assertOk()->assertSee('Your Hotel account is ready');
        $this->assertDatabaseMissing('hotel_registrations', [
            'tenant_id' => User::query()->where('email', 'demo-owner@example.test')->value('tenant_id'),
        ]);
        $this->assertDatabaseHas('trial_accounts', [
            'id' => $trialAccount->id,
            'status' => TrialAccount::STATUS_ACTIVE,
            'system' => 'hotel',
        ]);
        $this->assertNotNull($trialAccount->fresh()->tenant_id);

        $this->actingAs(User::query()->where('email', 'demo-owner@example.test')->firstOrFail())
            ->post(route('account.subscription.permanent'))
            ->assertRedirect(route('account.subscription'));
        $this->assertSame(TrialAccount::STATUS_ACTIVE, $trialAccount->fresh()->status);
    }

    public function test_demo_request_rejects_systems_that_are_not_live_yet(): void
    {
        $response = $this->from('/portal#demo')->post('/portal/demo-request', [
            'business_name' => 'Future School',
            'business_type' => 'school',
            'contact_name' => 'Future Owner',
            'phone' => '03001234567',
            'email' => 'future-owner@example.test',
            'city' => 'Peshawar',
            'address' => 'Future Road',
        ]);

        $response->assertRedirect('/portal#demo')
            ->assertSessionHasErrors('business_type');
        $this->assertDatabaseCount('demo_requests', 0);
        $this->assertDatabaseCount('trial_accounts', 0);
    }

    public function test_registration_shows_the_exact_error_when_email_is_already_registered(): void
    {
        User::query()->create([
            'name' => 'Existing Owner',
            'email' => 'existing-owner@example.test',
            'password' => bcrypt('Password123!'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->from('/register/hotel')->post('/register/hotel', [
            'account_role' => 'admin',
            'business_name' => 'Duplicate Hotel',
            'contact_name' => 'Duplicate Contact',
            'email' => 'existing-owner@example.test',
            'phone' => '03001234567',
            'city' => 'Peshawar',
            'country' => 'Pakistan',
            'address' => 'Test address',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/register/hotel');
        $response->assertSessionHasErrors(['email']);

        $this->get('/register/hotel?plan=small&cycle=monthly')
            ->assertOk()
            ->assertSee('The email has already been taken.')
            ->assertSee('If you already registered, use the login link above.');
    }

    public function test_expired_trial_is_blocked_from_the_selected_hotel_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Expired Trial Hotel',
            'tenant_name' => 'Expired Trial Hotel',
            'name' => 'Expired Trial Hotel',
            'slug' => 'expired-trial-hotel',
            'email' => 'expired-hotel@example.test',
            'status' => 'active',
        ]);
        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Trial Owner',
            'email' => 'trial-owner@example.test',
            'password' => bcrypt('Password123!'),
            'role' => 'owner',
            'status' => 'active',
        ]);
        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_type' => 'monthly',
            'price' => 0,
            'status' => 'trial',
            'trial_start' => now()->subDays(11),
            'trial_end' => now()->subDay(),
            'trial_duration_days' => 10,
            'auto_renew' => false,
        ]);

        $this->actingAs($owner)
            ->get('/hotel/owner')
            ->assertStatus(402);
        $this->assertSame('expired', $tenant->subscriptions()->latest('id')->value('status'));
    }
}
