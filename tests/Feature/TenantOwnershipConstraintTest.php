<?php

namespace Tests\Feature;

use App\Models\FoodCategory;
use App\Models\Guest;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantOwnershipConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_a_menu_item_linked_to_another_tenants_category(): void
    {
        $tenantA = $this->tenant('constraint-menu-a');
        $tenantB = $this->tenant('constraint-menu-b');
        $category = FoodCategory::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Other Hotel Food',
        ]);

        $this->expectException(QueryException::class);
        DB::table('menu_items')->insert([
            'tenant_id' => $tenantA->id,
            'food_category_id' => $category->id,
            'name' => 'Cross-tenant item',
            'price' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_a_booking_linked_to_another_tenants_guest(): void
    {
        $tenantA = $this->tenant('constraint-booking-a');
        $tenantB = $this->tenant('constraint-booking-b');
        $guest = Guest::query()->create([
            'tenant_id' => $tenantB->id,
            'first_name' => 'Other',
            'last_name' => 'Tenant',
            'phone' => '03000000000',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
        ]);

        $this->expectException(QueryException::class);
        DB::table('bookings')->insert([
            'tenant_id' => $tenantA->id,
            'guest_id' => $guest->id,
            'booking_code' => 'CROSS-TENANT-BOOKING',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_a_booking_room_linked_to_another_tenants_room(): void
    {
        $tenantA = $this->tenant('constraint-pivot-a');
        $tenantB = $this->tenant('constraint-pivot-b');
        $booking = $this->booking($tenantA, 'PIVOT-A');
        $roomId = $this->room($tenantB, 'PIVOT-B');

        $this->expectException(QueryException::class);
        DB::table('booking_rooms')->insert([
            'tenant_id' => $tenantA->id,
            'booking_id' => $booking->id,
            'room_id' => $roomId,
            'price_per_night' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_a_folio_charge_linked_to_another_tenants_room(): void
    {
        $tenantA = $this->tenant('constraint-folio-room-a');
        $tenantB = $this->tenant('constraint-folio-room-b');
        $booking = $this->booking($tenantA, 'FOLIO-ROOM-A');
        $roomId = $this->room($tenantB, 'FOLIO-ROOM-B');

        $this->expectException(QueryException::class);
        DB::table('folios')->insert([
            'tenant_id' => $tenantA->id,
            'booking_id' => $booking->id,
            'room_id' => $roomId,
            'invoice_number' => 'CROSS-TENANT-ROOM-FOLIO',
            'type' => 'charge',
            'item_category' => 'Cross tenant charge',
            'amount' => 10,
            'tax' => 0,
            'total' => 10,
            'is_reversed' => false,
            'created_at' => now(),
        ]);
    }

    public function test_database_rejects_a_payment_linked_to_another_tenants_booking(): void
    {
        $tenantA = $this->tenant('constraint-payment-a');
        $tenantB = $this->tenant('constraint-payment-b');
        $booking = $this->booking($tenantB, 'PAYMENT-B');

        $this->expectException(QueryException::class);
        DB::table('hotel_payments')->insert([
            'tenant_id' => $tenantA->id,
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_a_reservation_linked_to_another_tenants_room(): void
    {
        $tenantA = $this->tenant('constraint-reservation-a');
        $tenantB = $this->tenant('constraint-reservation-b');
        $roomId = $this->room($tenantB, 'RESERVATION-B');

        $this->expectException(QueryException::class);
        DB::table('reservations')->insert([
            'tenant_id' => $tenantA->id,
            'room_id' => $roomId,
            'guest_name' => 'Cross Tenant Reservation',
            'room_number' => 'RESERVATION-B',
            'status' => 'Inhouse',
            'base_rate' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_a_folio_linked_to_another_tenants_reservation(): void
    {
        $tenantA = $this->tenant('constraint-folio-a');
        $tenantB = $this->tenant('constraint-folio-b');
        $roomId = $this->room($tenantB, 'FOLIO-B');
        $reservationId = DB::table('reservations')->insertGetId([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomId,
            'guest_name' => 'Tenant Guest',
            'room_number' => 'FOLIO-B',
            'status' => 'Inhouse',
            'base_rate' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('folios')->insert([
            'tenant_id' => $tenantA->id,
            'reservation_id' => $reservationId,
            'invoice_number' => 'CROSS-TENANT-FOLIO',
            'type' => 'charge',
            'item_category' => 'Room accommodation',
            'amount' => 10,
            'total' => 10,
            'created_at' => now(),
        ]);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'hotel_name' => $slug,
            'tenant_name' => $slug,
            'name' => $slug,
            'slug' => $slug,
            'email' => "{$slug}@example.test",
            'status' => 'active',
        ]);
    }

    private function booking(Tenant $tenant, string $suffix): object
    {
        $guest = Guest::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Tenant',
            'last_name' => 'Guest',
            'phone' => '03000000000',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
        ]);

        $bookingId = DB::table('bookings')->insertGetId([
            'tenant_id' => $tenant->id,
            'guest_id' => $guest->id,
            'booking_code' => "TENANT-{$suffix}",
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['id' => $bookingId];
    }

    private function room(Tenant $tenant, string $suffix): int
    {
        $propertyId = DB::table('properties')->insertGetId([
            'property_name' => "Tenant Property {$suffix}",
            'currency_code' => 'PKR',
            'default_tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $propertyId,
            'tenant_id' => $tenant->id,
            'type_name' => "Standard {$suffix}",
            'title' => "Standard {$suffix}",
            'base_capacity' => 2,
            'max_capacity' => 2,
            'base_price_per_night' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rooms')->insertGetId([
            'tenant_id' => $tenant->id,
            'room_type_id' => $roomTypeId,
            'room_number' => $suffix,
            'floor' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
