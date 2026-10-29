<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontDeskRevenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-08 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_defaults_to_tenant_scoped_monthly_received_revenue_and_shows_details(): void
    {
        [$tenant, $user, $bookingId] = $this->hotelWorkspace('revenue-monthly');
        $this->payment($tenant->id, $bookingId, 60000, '2026-10-08 10:00:00');
        $this->payment($tenant->id, $bookingId, 6000, '2026-10-07 10:00:00');
        $this->payment($tenant->id, $bookingId, 9000, '2026-09-20 10:00:00');
        $this->payment($tenant->id, $bookingId, 20000, '2025-10-20 10:00:00');
        [$otherTenant, , $otherBookingId] = $this->hotelWorkspace('revenue-other');
        $this->payment($otherTenant->id, $otherBookingId, 5000, '2026-10-08 11:00:00');

        $this->actingAs($user)->get(route('frontdesk.dashboard'))
            ->assertOk()
            ->assertDontSee('This month Revenue')
            ->assertDontSee('Monthly')
            ->assertDontSee('Revenue by date')
            ->assertDontSee('Payment details');
    }

    public function test_revenue_filter_calculates_daily_weekly_monthly_annual_and_overall_periods(): void
    {
        [$tenant, $user, $bookingId] = $this->hotelWorkspace('revenue-periods');
        $this->payment($tenant->id, $bookingId, 60000, '2026-10-08 10:00:00');
        $this->payment($tenant->id, $bookingId, 6000, '2026-10-07 10:00:00');
        $this->payment($tenant->id, $bookingId, 9000, '2026-09-20 10:00:00');
        $this->payment($tenant->id, $bookingId, 20000, '2025-10-20 10:00:00');

        foreach ([
            'daily' => ['Today Revenue', '60,000.00'],
            'weekly' => ['This week Revenue', '66,000.00'],
            'monthly' => ['This month Revenue', '66,000.00'],
            'annual' => ['This year Revenue', '75,000.00'],
            'overall' => ['Overall Revenue', '95,000.00'],
        ] as $period => [$label, $amount]) {
            $this->actingAs($user)->get(route('frontdesk.dashboard', ['revenue_period' => $period]))
                ->assertOk()
                ->assertDontSee($label)
                ;
        }
    }

    /**
     * @return array{0: Tenant, 1: User, 2: int}
     */
    private function hotelWorkspace(string $slug): array
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => $slug,
            'tenant_name' => $slug,
            'name' => $slug,
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Agent',
            'email' => $slug.'-user@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);
        $guestId = DB::table('guests')->insertGetId([
            'tenant_id' => $tenant->id,
            'first_name' => 'Sample',
            'last_name' => 'Guest',
            'phone' => '03000000000',
            'id_type' => 'cnic_national_id',
            'id_number' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bookingId = DB::table('bookings')->insertGetId([
            'tenant_id' => $tenant->id,
            'guest_id' => $guestId,
            'booking_code' => strtoupper($slug).'-BOOKING',
            'check_in_date' => '2026-10-07',
            'check_out_date' => '2026-10-09',
            'booking_status' => 'checked_out',
            'grand_total' => 66000,
            'paid_amount' => 66000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, $user, $bookingId];
    }

    private function payment(int $tenantId, int $bookingId, int $amount, string $createdAt): void
    {
        DB::table('hotel_payments')->insert([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'payment_method' => 'cash',
            'amount' => $amount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
