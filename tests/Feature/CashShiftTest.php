<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\HotelPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_shift_collects_cash_and_reconciles_variance(): void
    {
        [$tenant, $receptionist, $owner] = $this->hotelStaff();

        $this->actingAs($receptionist)
            ->post(route('frontdesk.shift.open'), ['opening_float' => 1000])
            ->assertRedirect(route('frontdesk.shift'));

        $shift = CashShift::query()->where('tenant_id', $tenant->id)->open()->firstOrFail();

        $guest = \App\Models\Guest::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Shift',
            'last_name' => 'Guest',
            'phone' => '03001234567',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
            'opening_balance' => 0,
        ]);
        $booking = \App\Models\Booking::query()->create([
            'tenant_id' => $tenant->id,
            'guest_id' => $guest->id,
            'booking_code' => 'SHIFT-1',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'booking_status' => 'checked_in',
            'total_guests' => 1,
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
            'paid_amount' => 0,
        ]);
        HotelPayment::query()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'payment_method' => 'cash',
            'amount' => 500,
            'recorded_by' => $receptionist->id,
        ]);
        HotelPayment::query()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'payment_method' => 'card',
            'amount' => 300,
            'recorded_by' => $receptionist->id,
        ]);

        $this->assertSame(1, HotelPayment::query()->where('cash_shift_id', $shift->id)->count());

        $this->actingAs($receptionist)
            ->post(route('frontdesk.shift.close', $shift), ['counted_cash' => 1400, 'notes' => 'Short 100, change given to guest'])
            ->assertRedirect(route('frontdesk.shift'));

        $shift->refresh();
        $this->assertSame(CashShift::STATUS_CLOSED, $shift->status);
        $this->assertSame('1500.00', (string) $shift->expected_cash);
        $this->assertSame('-100.00', (string) $shift->variance);
    }

    public function test_owner_sees_shift_reconciliation_but_cannot_open_the_shift_desk(): void
    {
        [$tenant, $receptionist, $owner] = $this->hotelStaff();

        $this->actingAs($receptionist)->post(route('frontdesk.shift.open'), ['opening_float' => 200]);
        $shift = CashShift::query()->where('tenant_id', $tenant->id)->open()->firstOrFail();
        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), ['counted_cash' => 200]);

        $this->actingAs($owner)
            ->get(route('hotel.role.dashboard', ['role' => 'owner']))
            ->assertOk()
            ->assertSee('Cash shift reconciliation');

        $this->actingAs($owner)->get(route('frontdesk.shift'))->assertForbidden();
    }

    public function test_only_one_shift_can_be_open_per_hotel(): void
    {
        [, $receptionist] = $this->hotelStaff();

        $this->actingAs($receptionist)->post(route('frontdesk.shift.open'), ['opening_float' => 100])->assertRedirect();
        $this->actingAs($receptionist)->post(route('frontdesk.shift.open'), ['opening_float' => 100])->assertStatus(409);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User}
     */
    private function hotelStaff(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Shift Hotel',
            'tenant_name' => 'Shift Hotel',
            'hotel_name' => 'Shift Hotel',
            'slug' => 'shift-hotel',
            'email' => 'shift-hotel@example.test',
            'hotel_type' => 'large',
            'enabled_modules' => ['rooms' => true, 'reservations' => true, 'front_desk' => true, 'finance' => true, 'housekeeping' => true],
            'status' => 'active',
        ]);
        $receptionist = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Shift Receptionist',
            'email' => 'shift-receptionist@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);
        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Shift Owner',
            'email' => 'shift-owner@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        return [$tenant, $receptionist, $owner];
    }
}
