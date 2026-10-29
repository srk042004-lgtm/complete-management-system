<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FoodCategory;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationBookingFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_reservation_form_displays_guest_and_room_fields(): void
    {
        [$tenant, $admin] = $this->hotelWithRoom();

        $this->actingAs($admin)
            ->get(route('reservations.booking'))
            ->assertOk()
            ->assertSee('Guest information')
            ->assertSee('Full legal name')
            ->assertSee('Opening balance')
            ->assertSee('CNIC / ID document upload')
            ->assertSee('Stay and room')
            ->assertSee('Room category')
            ->assertSee('Maximum adults')
            ->assertSee('Food packages')
            ->assertSee('Unit price')
            ->assertSee('Kitchen notes')
            ->assertSee('Fast Food')
            ->assertSee('Estimated reservation total');
    }

    public function test_returning_guest_is_recognised_by_id_and_reused_only_when_confirmed(): void
    {
        [$tenant, $admin, $roomType, $room] = $this->hotelWithRoom();
        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(2)->toDateString();
        $payload = fn (array $extra = []) => array_merge([
            'guest_name' => 'Amina Khan',
            'email' => 'amina@example.test',
            'phone' => '03001234567',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
            'address' => 'Peshawar, Pakistan',
            'opening_balance' => '0',
            'room_type_id' => $roomType,
            'room_id' => $room->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ], $extra);

        $this->actingAs($admin)->post(route('reservations.booking.store'), $payload())->assertSessionHasNoErrors();
        $guest = Guest::query()->where('id_number', '12345-1234567-1')->firstOrFail();

        $this->actingAs($admin)->getJson(route('reservations.guest-lookup', ['id_type' => 'cnic_national_id', 'id_number' => '12345-1234567-1']))
            ->assertOk()
            ->assertJson(['found' => true, 'guest' => ['id' => $guest->id, 'name' => 'Amina Khan']]);
        $this->actingAs($admin)->getJson(route('reservations.guest-lookup', ['id_type' => 'passport', 'id_number' => '12345-1234567-1']))
            ->assertJson(['found' => false]);

        // Confirmed match: the saved guest is reused, no duplicate record is created.
        $this->actingAs($admin)->post(route('reservations.booking.store'), $payload(['guest_id' => $guest->id, 'check_in_date' => now()->addDays(5)->toDateString(), 'check_out_date' => now()->addDays(6)->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Guest::query()->where('id_number', '12345-1234567-1')->count());
        $this->assertSame(2, Booking::query()->where('guest_id', $guest->id)->count());

        // Not confirmed: a new record is created for the different person.
        $this->actingAs($admin)->post(route('reservations.booking.store'), $payload(['check_in_date' => now()->addDays(10)->toDateString(), 'check_out_date' => now()->addDays(11)->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Guest::query()->where('id_number', '12345-1234567-1')->count());
    }

    public function test_admin_can_create_reservation_with_guest_and_room_details(): void
    {
        [$tenant, $admin, $roomType, $room] = $this->hotelWithRoom();
        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(3)->toDateString();

        $this->actingAs($admin)
            ->post(route('reservations.booking.store'), [
                'guest_name' => 'Amina Khan',
                'email' => 'amina@example.test',
                'phone' => '03001234567',
                'id_type' => 'cnic_national_id',
                'id_number' => '12345-1234567-1',
                'address' => 'Peshawar, Pakistan',
                'opening_balance' => '125.50',
                'room_type_id' => $roomType,
                'room_id' => $room->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => 2,
                'children' => 1,
                'food_packages' => [
                    [
                        'name' => 'Breakfast',
                        'guest_count' => 2,
                        'price_per_person_per_night' => 15,
                    ],
                ],
            ])
            ->assertRedirect(route('hotel.role.dashboard', ['role' => 'admin']))
            ->assertSessionHas('success');

        $guest = Guest::query()->where('email', 'amina@example.test')->firstOrFail();
        $booking = Booking::query()->where('guest_id', $guest->id)->firstOrFail();

        $this->assertSame($tenant->id, $guest->tenant_id);
        $this->assertSame('Amina', $guest->first_name);
        $this->assertSame('Khan', $guest->last_name);
        $this->assertSame('125.50', $guest->opening_balance);
        $this->assertSame(3, $booking->total_guests);
        $this->assertSame(2, $booking->adults);
        $this->assertSame(1, $booking->children);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('300.00', $booking->grand_total);
        $this->assertSame($room->id, $booking->rooms()->firstOrFail()->id);
        $this->assertDatabaseHas('booking_food_packages', [
            'booking_id' => $booking->id,
            'package_name' => 'Breakfast',
            'guest_count' => 2,
            'price_per_person_per_night' => 15,
            'nights' => 2,
            'total_amount' => 60,
        ]);
    }

    public function test_reservation_rejects_guest_counts_over_room_category_capacity(): void
    {
        [, $admin, $roomType, $room] = $this->hotelWithRoom();

        $this->actingAs($admin)
            ->from(route('reservations.booking'))
            ->post(route('reservations.booking.store'), [
                'guest_name' => 'Amina Khan',
                'phone' => '03001234567',
                'id_type' => 'cnic_national_id',
                'id_number' => '12345-1234567-1',
                'opening_balance' => '0',
                'room_type_id' => $roomType,
                'room_id' => $room->id,
                'check_in_date' => now()->addDay()->toDateString(),
                'check_out_date' => now()->addDays(2)->toDateString(),
                'adults' => 3,
                'children' => 0,
            ])
            ->assertRedirect(route('reservations.booking'))
            ->assertSessionHasErrors('adults');

        $this->assertSame(0, Booking::query()->count());
        $this->assertSame(0, Guest::query()->count());
    }

    public function test_food_package_quantity_cannot_exceed_booking_guest_count(): void
    {
        [, $admin, $roomType, $room] = $this->hotelWithRoom();

        $this->actingAs($admin)
            ->from(route('reservations.booking'))
            ->post(route('reservations.booking.store'), [
                'guest_name' => 'Amina Khan',
                'phone' => '03001234567',
                'id_type' => 'cnic_national_id',
                'id_number' => '12345-1234567-1',
                'opening_balance' => '0',
                'room_type_id' => $roomType,
                'room_id' => $room->id,
                'check_in_date' => now()->addDay()->toDateString(),
                'check_out_date' => now()->addDays(2)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'food_packages' => [
                    [
                        'name' => 'Dinner',
                        'guest_count' => 2,
                        'price_per_person_per_night' => 25,
                    ],
                ],
            ])
            ->assertRedirect(route('reservations.booking'))
            ->assertSessionHasErrors('food_packages.0.guest_count');

        $this->assertSame(0, Booking::query()->count());
        $this->assertSame(0, Guest::query()->count());
    }

    public function test_catalog_items_support_one_time_and_per_guest_billing(): void
    {
        [$tenant, $admin, $roomType, $room] = $this->hotelWithRoom();
        $this->actingAs($admin)->get(route('reservations.booking'))->assertOk();
        $food = FoodCategory::query()->where('tenant_id', $tenant->id)->where('name', 'Food')->firstOrFail();
        $beverages = FoodCategory::query()->where('tenant_id', $tenant->id)->where('name', 'Beverages')->firstOrFail();
        $orderItem = MenuItem::query()->create([
            'tenant_id' => $tenant->id,
            'food_category_id' => $beverages->id,
            'name' => 'Mango Milkshake',
            'price' => 25,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);
        $packageItem = MenuItem::query()->create([
            'tenant_id' => $tenant->id,
            'food_category_id' => $food->id,
            'name' => 'Breakfast Package',
            'price' => 10,
            'billing_type' => 'per_guest_per_night',
            'is_available' => true,
        ]);

        $this->post(route('reservations.booking.store'), [
            'guest_name' => 'Nadia Khan',
            'phone' => '03001234567',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
            'opening_balance' => 0,
            'room_type_id' => $roomType,
            'room_id' => $room->id,
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 1,
            'food_packages' => [
                ['menu_item_id' => $orderItem->id, 'quantity' => 3, 'kitchen_notes' => 'Less sugar'],
                ['menu_item_id' => $packageItem->id, 'guest_count' => 2, 'quantity' => 1],
            ],
        ])->assertRedirect(route('hotel.role.dashboard', ['role' => 'admin']))->assertSessionHas('success');

        $booking = Booking::query()->whereHas('guest', fn ($query) => $query->where('first_name', 'Nadia'))->firstOrFail();
        $this->assertSame('355.00', $booking->grand_total);
        $this->assertDatabaseHas('booking_food_packages', [
            'booking_id' => $booking->id,
            'menu_item_id' => $orderItem->id,
            'food_category_id' => $beverages->id,
            'package_name' => 'Mango Milkshake',
            'category_name' => 'Beverages',
            'billing_type' => 'one_time_order',
            'quantity' => 3,
            'price_per_person_per_night' => 25,
            'total_amount' => 75,
            'kitchen_notes' => 'Less sugar',
        ]);
        $this->assertDatabaseHas('booking_food_packages', [
            'booking_id' => $booking->id,
            'menu_item_id' => $packageItem->id,
            'billing_type' => 'per_guest_per_night',
            'guest_count' => 2,
            'quantity' => 1,
            'total_amount' => 40,
        ]);
    }

    private function hotelWithRoom(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Reservation Test Hotel',
            'tenant_name' => 'Reservation Test Hotel',
            'hotel_name' => 'Reservation Test Hotel',
            'slug' => 'reservation-test-hotel',
            'email' => 'reservation-test@example.test',
            'status' => 'active',
        ]);
        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Reservation Admin',
            'email' => 'reservation-admin@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $propertyId = DB::table('properties')->insertGetId([
            'property_name' => 'Reservation Test Property',
            'currency_code' => 'PKR',
            'default_tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $propertyId,
            'tenant_id' => $tenant->id,
            'type_name' => 'Deluxe',
            'title' => 'Deluxe Room',
            'base_capacity' => 2,
            'max_capacity' => 3,
            'adults' => 2,
            'children' => 1,
            'base_price_per_night' => 120,
            'description' => 'Comfortable deluxe room.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'tenant_id' => $tenant->id,
            'room_type_id' => $roomTypeId,
            'room_number' => '101',
            'floor' => 1,
            'occupancy_status' => 'vacant',
            'housekeeping_status' => 'clean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, $admin, $roomTypeId, Room::query()->findOrFail($roomId)];
    }
}
