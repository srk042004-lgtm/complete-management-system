<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelAdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_registration_creates_tenant_admin_trial_and_settings(): void
    {
        $response = $this->post(route('hotel-admin.register.store'), $this->registrationData());

        $response->assertOk()->assertSee('Hotel Registration Successful');
        $this->assertAuthenticated();

        $tenant = Tenant::query()->where('email', 'owner@lakeside.example')->firstOrFail();
        $user = User::query()->where('username', 'lakeside_admin')->firstOrFail();
        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame('HTL-' . str_pad((string) $tenant->id, 6, '0', STR_PAD_LEFT), $tenant->hotel_code);
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('hotel_admin', $user->role);
        $this->assertSame('trial', $subscription->status);
        $this->assertNotNull($subscription->trial_start);
        $this->assertNotNull($subscription->trial_end);
        $this->assertSame((int) config('saas.trial_duration_days'), $subscription->trial_duration_days);
        $this->assertDatabaseHas('tenant_settings', [
            'tenant_id' => $tenant->id,
            'key' => 'currency_code',
        ]);
        $this->assertSame(9, TenantSetting::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_registration_requires_both_agreements(): void
    {
        $response = $this->post(route('hotel-admin.register.store'), $this->registrationData([
            'terms_accepted' => false,
            'privacy_accepted' => false,
        ]));

        $response->assertSessionHasErrors(['terms_accepted', 'privacy_accepted']);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'lakeside_admin']);

        $response = $this->post(route('hotel-admin.register.store'), $this->registrationData());

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_website_is_optional_and_protocol_is_added_for_domains(): void
    {
        $response = $this->post(route('hotel-admin.register.store'), $this->registrationData([
            'website' => 'www.sapphire.com.pk',
            'hotel_email' => 'sapphire@example.com',
            'admin_email' => 'sapphire-admin@example.com',
            'username' => 'sapphire_admin',
        ]));

        $response->assertOk()->assertSee('Hotel Registration Successful');
        $this->assertDatabaseHas('tenants', [
            'email' => 'sapphire@example.com',
            'website' => 'https://www.sapphire.com.pk',
        ]);
    }

    public function test_website_can_be_left_blank(): void
    {
        $response = $this->post(route('hotel-admin.register.store'), $this->registrationData([
            'website' => '',
            'hotel_email' => 'blank-website@example.com',
            'admin_email' => 'blank-website-admin@example.com',
            'username' => 'blank_website_admin',
        ]));

        $response->assertOk()->assertSee('Hotel Registration Successful');
        $this->assertDatabaseHas('tenants', [
            'email' => 'blank-website@example.com',
            'website' => null,
        ]);
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'hotel_name' => 'Lakeside Hotel',
            'hotel_type' => 'Hotel',
            'total_rooms' => 42,
            'address' => '1 Lake Road',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'postal_code' => '54000',
            'hotel_phone' => '03001234567',
            'hotel_email' => 'owner@lakeside.example',
            'website' => 'https://lakeside.example',
            'description' => 'A test property.',
            'admin_name' => 'Lakeside Admin',
            'admin_email' => 'admin@lakeside.example',
            'admin_phone' => '03007654321',
            'username' => 'lakeside_admin',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Lakeside Hospitality',
            'registration_number' => 'REG-001',
            'tax_number' => 'TAX-001',
            'currency_code' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'Y-m-d',
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'reception_phone' => '03001111111',
            'reservation_phone' => '03002222222',
            'contact_email' => 'contact@lakeside.example',
            'emergency_contact' => '03003333333',
            'whatsapp' => '03004444444',
            'contact_address' => '1 Lake Road',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ], $overrides);
    }
}
