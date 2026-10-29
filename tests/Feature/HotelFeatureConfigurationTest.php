<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HotelFeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelFeatureConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_admin_can_update_only_their_tenant_feature_configuration(): void
    {
        [$tenant, $admin] = $this->hotelWithAdmin('Hotel A', 'admin-a@example.test');
        $otherTenant = $this->hotelWithAdmin('Hotel B', 'admin-b@example.test')[0];

        $this->actingAs($admin)->put(route('home.settings.update'), [
            'features_present' => '1',
            'features' => ['housekeeping', 'staff', 'finance', 'reports'],
        ])->assertRedirect();

        $tenant->refresh();
        $otherTenant->refresh();

        $this->assertFalse($tenant->hasFeature('restaurant'));
        $this->assertFalse($tenant->hasFeature('pos'));
        $this->assertTrue($tenant->hasFeature('housekeeping'));
        $this->assertTrue($otherTenant->hasFeature('restaurant'));
    }

    public function test_existing_hotel_settings_page_lists_configurable_and_future_features(): void
    {
        [, $admin] = $this->hotelWithAdmin('Settings Hotel', 'settings@example.test');

        $this->actingAs($admin)
            ->get(route('home.settings'))
            ->assertOk()
            ->assertSee('Hotel Features')
            ->assertSee('hotel-feature-settings__group', false)
            ->assertSee('hotel-feature-settings__option', false)
            ->assertSee('data-theme-switcher aria-label="Switch to dark theme"', false)
            ->assertSee(asset('css/theme-switcher.css'), false)
            ->assertSee('Restaurant')
            ->assertSee('Coming soon');
    }

    public function test_disabled_optional_module_is_hidden_and_direct_routes_are_forbidden(): void
    {
        [$tenant, $admin] = $this->hotelWithAdmin('No Restaurant Hotel', 'no-restaurant@example.test', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'restaurant' => false,
            'pos' => false,
            'housekeeping' => true,
            'staff' => true,
            'finance' => true,
            'reports' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('hotel.role.dashboard', ['role' => 'admin']))
            ->assertOk()
            ->assertDontSee('Outlets &amp; POS');

        $this->get(route('pos.index'))->assertForbidden();
        $this->get('/cashier')->assertNotFound();
    }

    public function test_disabled_housekeeping_staff_finance_and_reports_routes_are_forbidden(): void
    {
        [, $admin] = $this->hotelWithAdmin('Restricted Hotel', 'restricted@example.test', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'restaurant' => false,
            'pos' => false,
            'housekeeping' => false,
            'staff' => false,
            'finance' => false,
            'reports' => false,
        ]);

        $this->actingAs($admin);
        $this->get(route('housekeeping.index'))->assertForbidden();
        $this->get(route('home.reports'))->assertForbidden();
        $this->get(route('audit.night'))->assertForbidden();
        $this->post(route('hotel.team.invite.store'), [
            'email' => 'staff@example.test',
            'role' => 'receptionist',
        ])->assertForbidden();
    }

    public function test_enabled_optional_module_route_remains_available(): void
    {
        [, $admin] = $this->hotelWithAdmin('Restaurant Hotel', 'restaurant@example.test', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'restaurant' => true,
            'pos' => true,
        ]);

        $this->actingAs($admin)->get(route('pos.index'))->assertOk();
    }

    public function test_standard_operating_tier_is_derived_from_type_not_room_count(): void
    {
        [$tenant] = $this->hotelWithAdmin('Large Standard Hotel', 'large-standard@example.test');
        $tenant->hotel_type = 'large';
        $tenant->total_rooms = 500;
        $tenant->save();

        $this->assertSame('STANDARD', HotelFeatureCatalog::operatingTier($tenant->hotel_type, $tenant->total_rooms));

        $tenant->hotel_type = 'luxury';
        $tenant->total_rooms = 10;
        $tenant->save();

        $this->assertSame('LUXURY', HotelFeatureCatalog::operatingTier($tenant->hotel_type, $tenant->total_rooms));
    }

    /** @return array{Tenant, User} */
    private function hotelWithAdmin(string $name, string $email, ?array $modules = null): array
    {
        $tenant = Tenant::query()->create([
            'name' => $name,
            'tenant_name' => $name,
            'hotel_name' => $name,
            'owner_name' => 'Owner',
            'owner_email' => $email,
            'email' => $email,
            'hotel_type' => 'small',
            'country' => 'Pakistan',
            'status' => 'active',
            'enabled_modules' => $modules ?? [
                'rooms' => true,
                'reservations' => true,
                'front_desk' => true,
                'guest_management' => true,
                'housekeeping' => true,
                'restaurant' => true,
                'pos' => true,
                'staff' => true,
                'finance' => true,
                'reports' => true,
            ],
        ]);
        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hotel Admin',
            'email' => $email,
            'password' => bcrypt('Password123!'),
            'role' => 'hotel_admin',
            'status' => 'active',
        ]);
        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_type' => 'monthly',
            'price' => 0,
            'status' => 'trial',
            'trial_start' => now(),
            'trial_end' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        return [$tenant, $admin];
    }
}
