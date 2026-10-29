<?php

namespace Tests\Feature;

use App\Models\FoodCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_menu_categories_and_priced_items(): void
    {
        [$tenant, $admin] = $this->hotelWithReceptionist('Food Catalog Hotel', 'admin');
        $receptionist = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Operator',
            'email' => 'front-desk-operator@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);
        $this->actingAs($receptionist)->get(route('frontdesk.food-catalog'))->assertForbidden();

        $this->actingAs($admin)
            ->get(route('frontdesk.food-catalog'))
            ->assertOk()
            ->assertSee('Milkshakes')
            ->assertSee('Fast Food')
            ->assertSee('Laundry &amp; Pressing', false)
            ->assertSee('Minibar')
            ->assertSee('Housekeeping')
            ->assertSee('Shirt / Trouser Ironing')
            ->assertSee('Add menu item');

        $laundryCategory = FoodCategory::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Laundry & Pressing')
            ->firstOrFail();
        $ironingService = MenuItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('food_category_id', $laundryCategory->id)
            ->where('name', 'Shirt / Trouser Ironing')
            ->firstOrFail();
        $this->assertSame('150.00', $ironingService->price);
        $this->assertTrue($ironingService->is_available);

        $this->post(route('frontdesk.food-catalog.categories.store'), [
            'name' => 'Special Desserts',
            'icon' => 'cake',
            'sort_order' => 50,
        ])->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $category = FoodCategory::query()->where('tenant_id', $tenant->id)->where('name', 'Desserts')->firstOrFail();

        $this->post(route('frontdesk.food-catalog.items.store'), [
            'food_category_id' => $category->id,
            'name' => 'Chocolate Cake',
            'description' => 'Slice of chocolate cake',
            'price' => 450,
            'billing_type' => 'one_time_order',
            'is_available' => 1,
        ])->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $item = MenuItem::query()->where('name', 'Chocolate Cake')->firstOrFail();
        $this->assertSame($category->id, $item->food_category_id);
        $this->assertEquals(450, $item->price);

        $this->put(route('frontdesk.food-catalog.categories.update', $category->id), [
            'name' => 'Sweet Treats',
            'icon' => 'dessert',
            'sort_order' => 55,
        ])->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('food_categories', [
            'id' => $category->id,
            'tenant_id' => $tenant->id,
            'name' => 'Sweet Treats',
            'sort_order' => 55,
        ]);
        $this->from(route('frontdesk.food-catalog'))
            ->delete(route('frontdesk.food-catalog.categories.destroy', $category->id))
            ->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHasErrors('category');

        $this->put(route('frontdesk.food-catalog.items.update', $item->id), [
            'food_category_id' => $category->id,
            'name' => 'Chocolate Cake Slice',
            'description' => 'Large cake slice',
            'price' => 500,
            'billing_type' => 'per_guest_per_night',
            'is_available' => 0,
        ])->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => 'Chocolate Cake Slice',
            'billing_type' => 'per_guest_per_night',
            'is_available' => false,
        ]);

        $this->delete(route('frontdesk.food-catalog.items.destroy', $item->id))
            ->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
        $this->delete(route('frontdesk.food-catalog.categories.destroy', $category->id))
            ->assertRedirect(route('frontdesk.food-catalog'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('food_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $admin->id,
            'action' => 'menu_item.deleted',
            'subject_id' => $item->id,
        ]);
    }

    public function test_menu_catalog_and_item_changes_are_tenant_scoped(): void
    {
        [, $admin] = $this->hotelWithReceptionist('Food Catalog Hotel', 'admin');
        [$otherTenant] = $this->hotelWithReceptionist('Other Food Hotel', 'admin');
        $category = FoodCategory::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Beverages',
            'sort_order' => 1,
        ]);
        $item = MenuItem::query()->create([
            'tenant_id' => $otherTenant->id,
            'food_category_id' => $category->id,
            'name' => 'Tea',
            'price' => 100,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('frontdesk.food-catalog.items.update', $item->id), [
                'food_category_id' => $category->id,
                'name' => 'Changed Tea',
                'price' => 1,
                'billing_type' => 'one_time_order',
                'is_available' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('frontdesk.food-catalog.items.store'), [
                'food_category_id' => $category->id,
                'name' => 'Cross tenant item',
                'price' => 1,
                'billing_type' => 'one_time_order',
                'is_available' => 1,
            ])
            ->assertSessionHasErrors('food_category_id');
    }

    public function test_extra_service_defaults_are_added_without_overwriting_existing_categories_or_prices(): void
    {
        [$tenant, $admin] = $this->hotelWithReceptionist('Existing Service Catalog Hotel', 'admin');
        $category = FoodCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Laundry & Pressing',
            'sort_order' => 99,
        ]);
        MenuItem::query()->create([
            'tenant_id' => $tenant->id,
            'food_category_id' => $category->id,
            'name' => 'Shirt / Trouser Ironing',
            'price' => 175,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);
        $otherCategory = FoodCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Guest Amenities',
            'sort_order' => 100,
        ]);
        MenuItem::query()->create([
            'tenant_id' => $tenant->id,
            'food_category_id' => $otherCategory->id,
            'name' => 'Mineral Water (Large 1.5L)',
            'price' => 125,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);

        $this->actingAs($admin)->get(route('frontdesk.food-catalog'))->assertOk();

        $this->assertSame(1, FoodCategory::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Laundry & Pressing')
            ->count());
        $this->assertSame(1, MenuItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('food_category_id', $category->id)
            ->where('name', 'Shirt / Trouser Ironing')
            ->count());
        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $tenant->id,
            'food_category_id' => $category->id,
            'name' => 'Shirt / Trouser Ironing',
            'price' => 175,
        ]);
        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $tenant->id,
            'food_category_id' => $category->id,
            'name' => 'Suit Dry Cleaning (2-Piece)',
        ]);
        $this->assertSame(1, MenuItem::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['mineral water (large 1.5l)'])
            ->count());
        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $tenant->id,
            'food_category_id' => $otherCategory->id,
            'name' => 'Mineral Water (Large 1.5L)',
            'price' => 125,
        ]);
    }

    private function hotelWithReceptionist(string $name = 'Food Catalog Hotel', string $role = 'receptionist'): array
    {
        $slug = strtolower(str_replace(' ', '-', $name));
        $tenant = Tenant::query()->create([
            'name' => $name,
            'tenant_name' => $name,
            'hotel_name' => $name,
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'hotel_type' => 'large',
            'enabled_modules' => [
                'rooms' => true,
                'reservations' => true,
                'front_desk' => true,
            ],
            'status' => 'active',
        ]);
        $receptionist = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role),
            'email' => $role.'-'.$slug.'@example.test',
            'password' => bcrypt('Password123'),
            'role' => $role,
            'status' => 'active',
        ]);

        return [$tenant, $receptionist];
    }
}
