<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RoomInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_add_and_edit_room_categories_and_rooms(): void
    {
        [$tenant, $receptionist] = $this->hotelWithReceptionist();
        Storage::fake('public');

        $this->actingAs($receptionist)
            ->get(route('frontdesk.inventory'))
            ->assertOk()
            ->assertSee('Add room category')
            ->assertSee('Unique category code')
            ->assertSee('Maximum total occupancy')
            ->assertSee('Amenities')
            ->assertSee('Gallery images')
            ->assertSee('Add room');

        $this->post(route('frontdesk.inventory.categories.store'), [
            'title' => 'Deluxe',
            'code' => 'DEL-KNG',
            'base_occupancy' => 2,
            'max_adults' => 2,
            'max_children' => 1,
            'max_total_occupancy' => 3,
            'base_price_per_night' => 180,
            'extra_adult_rate' => 30,
            'extra_child_rate' => 15,
            'weekend_rate' => 210,
            'hourly_rate' => 35,
            'size_sq_ft' => 420,
            'bed_type' => 'KING',
            'bed_count' => 1,
            'has_balcony' => 1,
            'view_type' => 'CITY_VIEW',
            'amenities_text' => "WiFi\nMini Bar",
            'is_active' => 1,
            'allow_extra_bed' => 1,
            'extra_bed_fee' => 20,
            'description' => 'City view room',
            'cover_image' => $this->fakePng('cover.png'),
            'gallery_uploads' => [$this->fakePng('gallery.png')],
        ])->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success');

        $category = DB::table('room_types')->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($category);
        $this->assertSame('Deluxe', $category->title);
        $this->assertEquals(180, $category->base_price_per_night);
        $this->assertSame('DEL-KNG', $category->code);
        $this->assertSame(['WiFi', 'Mini Bar'], json_decode($category->amenities, true));
        $this->assertNotEmpty($category->cover_image);
        $this->assertCount(1, json_decode($category->gallery_images, true));
        Storage::disk('public')->assertExists($category->cover_image);
        Storage::disk('public')->assertExists(json_decode($category->gallery_images, true)[0]);
        $this->get(route('frontdesk.inventory'))
            ->assertOk()
            ->assertSee('Edit category')
            ->assertSee(route('frontdesk.inventory.categories.edit', $category->id), false)
            ->assertDontSee('id="category-code-'.$category->id.'"', false);

        $this->post(route('frontdesk.inventory.rooms.store'), [
            'room_number' => '301',
            'room_type_id' => $category->id,
            'floor' => 3,
        ])->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success');

        $room = Room::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('301', $room->room_number);
        $this->assertSame(3, $room->floor);
        $this->assertSame('vacant', $room->occupancy_status);
        $this->assertSame('clean', $room->housekeeping_status);

        $this->actingAs($receptionist)
            ->get(route('frontdesk.inventory'))
            ->assertOk()
            ->assertSee('Filter by room category')
            ->assertSee('All categories')
            ->assertSee('301')
            ->assertSee(route('frontdesk.inventory.rooms.edit', $room->id), false);
        $this->get(route('frontdesk.inventory', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('301')
            ->assertSee('Deluxe');
        $this->get(route('frontdesk.inventory.rooms.edit', $room->id))
            ->assertOk()
            ->assertSee('Edit room 301')
            ->assertSee('name="room_number"', false)
            ->assertSee('name="room_type_id"', false);

        $this->put(route('frontdesk.inventory.rooms.update', $room), [
            'room_number' => '302',
            'room_type_id' => $category->id,
            'floor' => 4,
        ])->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success');
        $this->assertSame('302', $room->fresh()->room_number);
        $this->assertSame(4, $room->fresh()->floor);

        $this->put(route('frontdesk.inventory.categories.update', $category->id), [
            'title' => 'Deluxe Plus',
            'code' => 'DEL-PLUS',
            'base_occupancy' => 2,
            'max_adults' => 3,
            'max_children' => 1,
            'max_total_occupancy' => 4,
            'base_price_per_night' => 225,
            'extra_adult_rate' => 35,
            'extra_child_rate' => 15,
            'weekend_rate' => '',
            'hourly_rate' => '',
            'size_sq_ft' => '',
            'bed_type' => 'QUEEN',
            'bed_count' => 2,
            'has_balcony' => 0,
            'view_type' => 'GARDEN_VIEW',
            'amenities_text' => 'WiFi, Safe Deposit Box',
            'is_active' => 1,
            'allow_extra_bed' => 0,
            'extra_bed_fee' => 0,
            'remove_cover_image' => 1,
            'remove_gallery_images' => json_decode($category->gallery_images, true),
            'description' => 'Updated category',
        ])->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('room_types', [
            'id' => $category->id,
            'tenant_id' => $tenant->id,
            'title' => 'Deluxe Plus',
            'max_adults' => 3,
            'max_children' => 1,
            'base_price_per_night' => 225,
        ]);
        Storage::disk('public')->assertMissing($category->cover_image);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $receptionist->id,
            'action' => 'room.created',
            'subject_id' => $room->id,
        ]);

        $this->from(route('frontdesk.inventory'))
            ->delete(route('frontdesk.inventory.categories.destroy', $category->id))
            ->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHasErrors('category');
        $this->assertDatabaseHas('room_types', ['id' => $category->id, 'tenant_id' => $tenant->id]);
    }

    public function test_category_creation_registers_rooms_and_provides_a_tenant_scoped_edit_page(): void
    {
        [$tenant, $receptionist] = $this->hotelWithReceptionist();

        $this->actingAs($receptionist)->post(route('frontdesk.inventory.categories.store'), [
            'title' => 'Executive Suite',
            'code' => 'EXEC-SUITE',
            'base_occupancy' => 2,
            'max_adults' => 2,
            'max_children' => 2,
            'max_total_occupancy' => 4,
            'base_price_per_night' => 300,
            'extra_adult_rate' => 0,
            'extra_child_rate' => 0,
            'bed_type' => 'KING',
            'bed_count' => 1,
            'view_type' => 'CITY_VIEW',
            'is_active' => 1,
            'allow_extra_bed' => 1,
            'extra_bed_fee' => 0,
            'initial_room_numbers' => "801, 802\n803",
            'initial_room_floor' => 8,
        ])->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success', 'Room category added. 3 rooms added to the room status board.');

        $category = DB::table('room_types')->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertDatabaseCount('rooms', 3);
        $this->assertDatabaseHas('rooms', [
            'tenant_id' => $tenant->id,
            'room_number' => '802',
            'room_type_id' => $category->id,
            'floor' => 8,
            'occupancy_status' => 'vacant',
            'housekeeping_status' => 'clean',
        ]);
        $this->assertSame(3, DB::table('hotel_operation_logs')->where('action', 'room.created')->where('tenant_id', $tenant->id)->count());

        $this->get(route('frontdesk.rooms.status'))
            ->assertOk()
            ->assertSee('801')
            ->assertSee('Executive Suite')
            ->assertSee('name="category"', false);
        $this->get(route('frontdesk.rooms.status', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('802')
            ->assertDontSee('901');
        $this->get(route('frontdesk.inventory.categories.edit', $category->id))
            ->assertOk()
            ->assertSee('Edit category: Executive Suite')
            ->assertSee('name="base_price_per_night"', false);

        $this->post(route('frontdesk.inventory.categories.store'), [
            'title' => 'Standard',
            'code' => 'STANDARD',
            'base_occupancy' => 2,
            'max_adults' => 2,
            'max_children' => 0,
            'max_total_occupancy' => 2,
            'base_price_per_night' => 120,
            'extra_adult_rate' => 0,
            'extra_child_rate' => 0,
            'bed_type' => 'QUEEN',
            'bed_count' => 1,
            'view_type' => 'CITY_VIEW',
            'is_active' => 1,
            'allow_extra_bed' => 1,
            'extra_bed_fee' => 0,
            'initial_room_numbers' => '901',
            'initial_room_floor' => 9,
        ])->assertRedirect(route('frontdesk.inventory'));
        $standardCategoryId = (int) DB::table('room_types')->where('tenant_id', $tenant->id)->where('code', 'STANDARD')->value('id');
        $this->get(route('frontdesk.inventory', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('801')
            ->assertDontSee('901');
        $this->get(route('frontdesk.rooms.status', ['category' => $standardCategoryId]))
            ->assertOk()
            ->assertSee('901')
            ->assertDontSee('<strong>801</strong>', false);

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Hotel',
            'tenant_name' => 'Other Hotel',
            'hotel_name' => 'Other Hotel',
            'slug' => 'category-edit-other-hotel',
            'email' => 'category-edit-other@example.test',
            'status' => 'active',
        ]);
        $otherUser = User::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Receptionist',
            'email' => 'category-edit-other-user@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->withSession(['hotel_tenant_id' => $otherTenant->id])
            ->actingAs($otherUser)
            ->get(route('frontdesk.inventory.categories.edit', $category->id))
            ->assertNotFound();
    }

    public function test_receptionist_inventory_is_tenant_scoped_and_other_roles_cannot_manage_it(): void
    {
        [$tenant, $receptionist] = $this->hotelWithReceptionist();
        $otherTenant = Tenant::query()->create([
            'name' => 'Other Hotel',
            'tenant_name' => 'Other Hotel',
            'hotel_name' => 'Other Hotel',
            'slug' => 'other-hotel',
            'email' => 'other-hotel@example.test',
            'status' => 'active',
        ]);
        $otherUser = User::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Receptionist',
            'email' => 'other-receptionist@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->actingAs($receptionist)->post(route('frontdesk.inventory.categories.store'), [
            'title' => 'Standard',
            'code' => 'STANDARD',
            'base_occupancy' => 2,
            'max_adults' => 2,
            'max_children' => 0,
            'max_total_occupancy' => 2,
            'base_price_per_night' => 100,
            'extra_adult_rate' => 0,
            'extra_child_rate' => 0,
            'bed_type' => 'KING',
            'bed_count' => 1,
            'view_type' => 'CITY_VIEW',
            'is_active' => 1,
            'allow_extra_bed' => 1,
            'extra_bed_fee' => 0,
        ])->assertRedirect(route('frontdesk.inventory'));
        $categoryId = (int) DB::table('room_types')->where('tenant_id', $tenant->id)->value('id');
        $this->actingAs($receptionist)->post(route('frontdesk.inventory.rooms.store'), [
            'room_number' => '401',
            'room_type_id' => $categoryId,
            'floor' => 4,
        ])->assertRedirect(route('frontdesk.inventory'));
        $roomId = (int) Room::query()->where('tenant_id', $tenant->id)->value('id');

        $this->withSession(['hotel_tenant_id' => $otherTenant->id])
            ->actingAs($otherUser)
            ->put(route('frontdesk.inventory.categories.update', $categoryId), [
                'title' => 'Changed',
                'adults' => 2,
                'children' => 0,
                'base_price_per_night' => 100,
            ])
            ->assertNotFound();
        $this->get(route('frontdesk.inventory.categories.edit', $categoryId))->assertNotFound();
        $this->get(route('frontdesk.inventory.rooms.edit', $roomId))->assertNotFound();
        Room::query()->where('tenant_id', $tenant->id)->whereKey($roomId)->delete();

        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Read Only Owner',
            'email' => 'inventory-owner@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->withSession(['hotel_tenant_id' => $tenant->id])
            ->actingAs($owner)->get(route('frontdesk.inventory'))->assertForbidden();

        $this->withSession(['hotel_tenant_id' => $tenant->id])
            ->actingAs($receptionist)
            ->delete(route('frontdesk.inventory.categories.destroy', $categoryId))
            ->assertRedirect(route('frontdesk.inventory'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('room_types', ['id' => $categoryId]);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $receptionist->id,
            'action' => 'room_category.deleted',
            'subject_id' => $categoryId,
        ]);
    }

    public function test_booking_form_provides_floor_filter_and_inventory_link_when_categories_are_missing(): void
    {
        [, $receptionist] = $this->hotelWithReceptionist();

        $this->actingAs($receptionist)
            ->get(route('reservations.booking'))
            ->assertOk()
            ->assertSee('id="floor_filter"', false)
            ->assertSee('All floors')
            ->assertSee('Add room categories and rooms');
    }

    private function hotelWithReceptionist(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Inventory Test Hotel',
            'tenant_name' => 'Inventory Test Hotel',
            'hotel_name' => 'Inventory Test Hotel',
            'slug' => 'inventory-test-hotel',
            'email' => 'inventory-test@example.test',
            'hotel_type' => 'large',
            'currency_code' => 'PKR',
            'enabled_modules' => [
                'rooms' => true,
                'reservations' => true,
                'front_desk' => true,
                'housekeeping' => true,
            ],
            'status' => 'active',
        ]);
        $receptionist = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Inventory Receptionist',
            'email' => 'inventory-receptionist@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        return [$tenant, $receptionist];
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lZkAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
