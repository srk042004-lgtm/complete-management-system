<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminHotelRegistryStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_store_new_hotel_and_is_redirected_to_registry_with_latest_record_first(): void
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
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin, 'super_admin');

        $response = $this->post(route('super-admin.hotel-registry.store'), [
            'hotel_name' => 'Skyline Hotel',
            'property_type' => 'Hotel',
            'owner_name' => 'Ali Khan',
            'email' => 'skyline@example.com',
            'phone' => '03001234567',
            'country' => 'Pakistan',
            'city' => 'Lahore',
            'address' => 'Main Road',
            'website' => 'https://skyline.example',
            'status' => 'active',
            'notes' => 'Newly created entry',
        ]);

        $response->assertRedirect(route('super-admin.hotel-registry'));

        $this->assertDatabaseHas('tenants', [
            'hotel_name' => 'Skyline Hotel',
            'email' => 'skyline@example.com',
        ]);

        $latestTenant = Tenant::query()->latest('created_at')->first();

        $this->assertNotNull($latestTenant);
        $this->assertSame('Skyline Hotel', $latestTenant->hotel_name);

        $this->get(route('super-admin.hotel-registry'))->assertSee('Skyline Hotel');
    }
}
