<?php

namespace Tests\Feature;

use App\Models\AmenityLog;
use App\Models\Booking;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\LostAndFoundItem;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HousekeepingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeper_cleans_and_admin_inspection_releases_the_room(): void
    {
        [$tenant, $admin, $housekeeper, $room] = $this->hotel();

        $this->actingAs($housekeeper)->patch(route('housekeeping.rooms.status', $room), ['housekeeping_status' => 'cleaning'])->assertRedirect(route('housekeeping.index'));
        $this->assertSame('cleaning', $room->fresh()->housekeeping_status);

        $this->actingAs($housekeeper)->patch(route('housekeeping.rooms.status', $room), ['housekeeping_status' => 'clean_uninspected']);
        $this->assertSame('clean_uninspected', $room->fresh()->housekeeping_status);

        $this->actingAs($admin)->post(route('housekeeping.rooms.inspect', $room), $this->checklist('pass'))->assertRedirect(route('housekeeping.index'));
        $this->assertSame('clean', $room->fresh()->housekeeping_status);
        $this->assertNotNull($room->fresh()->last_cleaned_at);
    }

    public function test_failed_inspection_returns_the_room_with_a_note(): void
    {
        [, $admin, $housekeeper, $room] = $this->hotel();
        $this->actingAs($housekeeper)->patch(route('housekeeping.rooms.status', $room), ['housekeeping_status' => 'clean_uninspected']);

        $this->actingAs($admin)->post(route('housekeeping.rooms.inspect', $room), array_merge($this->checklist('fail'), ['notes' => 'Bathroom mirror needs re-wiping']))->assertRedirect();

        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
        $this->assertDatabaseHas('housekeeping_tasks', ['room_id' => $room->id, 'status' => 'rejected', 'inspection_notes' => 'Bathroom mirror needs re-wiping']);
    }

    public function test_housekeeper_cannot_inspect_rooms(): void
    {
        [, , $housekeeper, $room] = $this->hotel();

        $this->actingAs($housekeeper)->post(route('housekeeping.rooms.inspect', $room), $this->checklist('pass'))->assertForbidden();
    }

    public function test_urgent_maintenance_takes_the_room_out_of_service(): void
    {
        [$tenant, , $housekeeper, $room] = $this->hotel();

        $this->actingAs($housekeeper)->post(route('housekeeping.rooms.issues', $room), ['category' => 'plumbing', 'severity' => 'urgent', 'description' => 'Leaking tap'])->assertRedirect();

        $this->assertSame('maintenance', $room->fresh()->housekeeping_status);
        $this->assertDatabaseHas('maintenance_tickets', ['room_id' => $room->id, 'severity' => 'urgent', 'status' => 'open']);
    }

    public function test_minibar_consumption_is_charged_to_the_guest_folio(): void
    {
        [, , $housekeeper, $room, $booking] = $this->hotel(withGuest: true);

        $this->actingAs($housekeeper)->post(route('housekeeping.rooms.amenities', $room), [
            'items' => [['name' => 'Water bottle', 'quantity' => 2]],
            'charge_to_folio' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('amenity_logs', ['room_id' => $room->id, 'item_name' => 'Water bottle', 'quantity' => 2, 'charged_to_folio' => true]);
        $this->assertDatabaseHas('folios', ['booking_id' => $booking->id, 'type' => 'charge', 'total' => 300]);
        $this->assertSame('300.00', (string) $booking->fresh()->grand_total);
    }

    public function test_checkout_creates_a_high_priority_checkout_clean_task(): void
    {
        [, , , $room] = $this->hotel();
        $this->assertDatabaseMissing('housekeeping_tasks', ['room_id' => $room->id, 'service_type' => 'checkout_clean']);

        HousekeepingTask::query()->create(['tenant_id' => $room->tenant_id, 'room_id' => $room->id, 'service_type' => 'checkout_clean', 'status' => 'open', 'priority' => true]);

        $this->assertDatabaseHas('housekeeping_tasks', ['room_id' => $room->id, 'service_type' => 'checkout_clean', 'priority' => true]);
    }

    public function test_lost_and_found_entry_is_recorded(): void
    {
        [$tenant, , $housekeeper, $room] = $this->hotel();

        $this->actingAs($housekeeper)->post(route('housekeeping.lost-found.store'), [
            'room_id' => $room->id,
            'category' => 'electronics',
            'item_description' => 'Black phone charger',
            'location_found' => 'Under the bed',
        ])->assertRedirect(route('housekeeping.lost-found'));

        $this->assertDatabaseHas('lost_and_found_items', ['tenant_id' => $tenant->id, 'item_description' => 'Black phone charger', 'status' => 'unclaimed']);
    }

    public function test_board_is_grouped_by_room_category(): void
    {
        [$tenant, $admin, , $room] = $this->hotel();
        $propertyId = DB::table('properties')->insertGetId(['property_name' => 'HK Property', 'currency_code' => 'PKR', 'default_tax_rate' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $standard = RoomCategory::query()->create(['property_id' => $propertyId, 'tenant_id' => $tenant->id, 'type_name' => 'Standard', 'base_price_per_night' => 5000, 'is_active' => true]);
        $deluxe = RoomCategory::query()->create(['property_id' => $propertyId, 'tenant_id' => $tenant->id, 'type_name' => 'Deluxe', 'base_price_per_night' => 9000, 'is_active' => true]);
        $room->update(['room_type_id' => $standard->id]);
        Room::query()->create(['tenant_id' => $tenant->id, 'room_number' => 'H-20', 'floor' => 2, 'room_type_id' => $deluxe->id, 'occupancy_status' => 'vacant', 'housekeeping_status' => 'clean']);

        $this->actingAs($admin)->get(route('housekeeping.index'))->assertOk()->assertSee('Room categories')->assertSee('Deluxe')->assertDontSee('H-20');

        RoomCategory::query()->create(['property_id' => $propertyId, 'tenant_id' => $tenant->id, 'type_name' => 'Suite', 'title' => 'Grand Suite', 'base_price_per_night' => 15000, 'is_active' => true]);
        $this->actingAs($admin)->get(route('housekeeping.index'))->assertOk()->assertSee('Grand Suite');
        $this->actingAs($admin)->get(route('housekeeping.index', ['category' => $standard->id]))->assertOk()->assertSee('H-10')->assertDontSee('H-20');
        $this->actingAs($admin)->get(route('housekeeping.index', ['category' => $deluxe->id]))->assertOk()->assertSee('H-20')->assertDontSee('H-10');

        $this->actingAs($admin)->patch(route('housekeeping.rooms.status', $room), ['housekeeping_status' => 'cleaning'])
            ->assertRedirect(route('housekeeping.index', ['category' => $standard->id]));
    }

    /** @return array{0: Tenant, 1: User, 2: User, 3: Room, 4?: Booking} */
    private function hotel(bool $withGuest = false): array
    {
        $tenant = Tenant::query()->create(['name' => 'HK', 'tenant_name' => 'HK', 'hotel_name' => 'HK Hotel', 'slug' => 'hk-hotel-'.uniqid(), 'email' => 'hk-'.uniqid().'@example.test', 'status' => 'active', 'hotel_type' => 'large', 'total_rooms' => 20, 'enabled_modules' => ['rooms' => true, 'housekeeping' => true, 'reservations' => true, 'finance' => true]]);
        $tenant->subscriptions()->create(['plan_type' => 'monthly', 'price' => 0, 'currency_code' => 'PKR', 'status' => 'trial', 'starts_at' => now(), 'ends_at' => now()->addDays(30), 'trial_start' => now(), 'trial_end' => now()->addDays(30), 'trial_duration_days' => 30, 'auto_renew' => false]);

        $admin = User::query()->create(['tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'hk-admin-'.uniqid().'@example.test', 'password' => bcrypt('Password123'), 'role' => 'admin', 'status' => 'active']);
        $housekeeper = User::query()->create(['tenant_id' => $tenant->id, 'name' => 'Housekeeper', 'email' => 'hk-staff-'.uniqid().'@example.test', 'password' => bcrypt('Password123'), 'role' => 'housekeeping', 'status' => 'active']);
        $room = Room::query()->create(['tenant_id' => $tenant->id, 'room_number' => 'H-10', 'floor' => 1, 'occupancy_status' => 'vacant', 'housekeeping_status' => 'dirty']);

        if (! $withGuest) {
            return [$tenant, $admin, $housekeeper, $room];
        }

        $guest = Guest::query()->create(['tenant_id' => $tenant->id, 'first_name' => 'Guest', 'last_name' => 'One', 'phone' => '03001234567', 'id_type' => 'cnic_national_id', 'id_number' => '12345-1234567-1', 'opening_balance' => 0]);
        $booking = Booking::query()->create(['tenant_id' => $tenant->id, 'guest_id' => $guest->id, 'booking_code' => 'HK-1', 'check_in_date' => now()->toDateString(), 'check_out_date' => now()->addDay()->toDateString(), 'booking_status' => 'checked_in', 'total_guests' => 1, 'adults' => 1, 'children' => 0, 'subtotal_amount' => 0, 'tax_amount' => 0, 'grand_total' => 0, 'paid_amount' => 0]);
        $booking->rooms()->attach($room->id, ['price_per_night' => 5000, 'tenant_id' => $tenant->id]);

        return [$tenant, $admin, $housekeeper, $room, $booking];
    }

    /** @return array<string, mixed> */
    private function checklist(string $decision): array
    {
        return [
            'bedding' => '1',
            'bathroom' => '1',
            'trash_floors' => '1',
            'amenities' => '1',
            'decision' => $decision,
        ];
    }
}
