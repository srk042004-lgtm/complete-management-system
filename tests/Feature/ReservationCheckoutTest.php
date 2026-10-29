<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_succeeds_when_folio_balance_is_zero(): void
    {
        $admin = $this->createHotelAdmin();
        $this->actingAs($admin);

        $room = Room::create([
            'tenant_id' => $admin->tenant_id,
            'room_number' => '101',
            'room_type' => 'Deluxe',
            'occupancy_status' => 'occupied',
            'housekeeping_status' => 'clean',
        ]);

        $reservation = Reservation::create([
            'tenant_id' => $admin->tenant_id,
            'guest_name' => 'Ada Lovelace',
            'guest_id' => null,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'status' => 'Inhouse',
            'base_rate' => 120.00,
        ]);

        DB::table('folios')->insert([
            [
                'tenant_id' => $admin->tenant_id,
                'reservation_id' => $reservation->id,
                'invoice_number' => 'INV-001',
                'type' => 'charge',
                'item_category' => 'room_rent',
                'amount' => 120.00,
                'tax' => 0.00,
                'total' => 120.00,
                'is_reversed' => false,
                'created_at' => now(),
            ],
            [
                'tenant_id' => $admin->tenant_id,
                'reservation_id' => $reservation->id,
                'invoice_number' => 'INV-002',
                'type' => 'receipt',
                'item_category' => 'room_rent',
                'amount' => 120.00,
                'tax' => 0.00,
                'total' => 120.00,
                'is_reversed' => false,
                'created_at' => now(),
            ],
        ]);

        $response = $this->postJson(route('reservations.checkout', ['reservation' => $reservation->id]));

        $response->assertOk()
            ->assertJsonPath('status', 'Checkedout');

        $this->assertSame('vacant', $room->fresh()->occupancy_status);
        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
        $this->assertSame('Checkedout', $reservation->fresh()->status);
    }

    public function test_checkout_rejects_when_folio_balance_is_not_zero(): void
    {
        $admin = $this->createHotelAdmin();
        $this->actingAs($admin);

        $room = Room::create([
            'tenant_id' => $admin->tenant_id,
            'room_number' => '102',
            'room_type' => 'Standard',
            'occupancy_status' => 'occupied',
            'housekeeping_status' => 'clean',
        ]);

        $reservation = Reservation::create([
            'tenant_id' => $admin->tenant_id,
            'guest_name' => 'Grace Hopper',
            'guest_id' => null,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'status' => 'Inhouse',
            'base_rate' => 150.00,
        ]);

        DB::table('folios')->insert([
            [
                'tenant_id' => $admin->tenant_id,
                'reservation_id' => $reservation->id,
                'invoice_number' => 'INV-003',
                'type' => 'charge',
                'item_category' => 'room_rent',
                'amount' => 150.00,
                'tax' => 0.00,
                'total' => 150.00,
                'is_reversed' => false,
                'created_at' => now(),
            ],
            [
                'tenant_id' => $admin->tenant_id,
                'reservation_id' => $reservation->id,
                'invoice_number' => 'INV-004',
                'type' => 'receipt',
                'item_category' => 'room_rent',
                'amount' => 50.00,
                'tax' => 0.00,
                'total' => 50.00,
                'is_reversed' => false,
                'created_at' => now(),
            ],
        ]);

        $response = $this->postJson(route('reservations.checkout', ['reservation' => $reservation->id]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The folio balance must be zero before checkout can proceed.')
            ->assertJsonPath('balance', '100.00');
    }

    private function createHotelAdmin(): User
    {
        $tenant = Tenant::create([
            'name' => 'Checkout Test Hotel',
            'slug' => 'checkout-test-hotel-' . uniqid(),
            'email' => 'hotel-' . uniqid() . '@example.com',
            'country' => 'Pakistan',
            'currency_code' => 'PKR',
            'status' => 'active',
            'enabled_modules' => ['rooms' => true],
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Checkout Admin',
            'email' => 'admin-' . uniqid() . '@example.com',
            'username' => 'checkout_' . uniqid(),
            'password' => 'Password123',
            'role' => 'hotel_admin',
            'status' => 'active',
        ]);
    }
}
