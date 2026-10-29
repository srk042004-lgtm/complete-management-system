<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\CashTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_handover_is_offered_and_opens_the_next_shift_automatically(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Handover Hotel',
            'tenant_name' => 'Handover Hotel',
            'name' => 'Handover Hotel',
            'slug' => 'handover-hotel',
            'email' => 'handover@example.test',
            'status' => 'active',
        ]);
        $morning = $this->user($tenant, 'receptionist', 'morning@example.test');
        $evening = $this->user($tenant, 'receptionist', 'evening@example.test');

        $this->actingAs($morning)->post(route('frontdesk.shift.open'), ['opening_float' => 1000])->assertSessionHasNoErrors();
        $shift = CashShift::query()->firstOrFail();

        $this->actingAs($morning)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'handover_to_id' => $evening->id,
        ])->assertSessionHasNoErrors();

        $handover = CashTransfer::query()->where('transfer_type', 'shift_handover')->firstOrFail();
        $this->assertSame('pending', $handover->status);
        $this->assertSame($evening->id, $handover->receiver_id);

        // The evening receptionist sees the handover and opens with the handed-over cash without typing it.
        $this->actingAs($evening)->get(route('frontdesk.shift'))->assertOk()->assertSee('handed-over');
        $this->actingAs($evening)->post(route('frontdesk.shift.open'), ['opening_float' => ''])->assertSessionHasNoErrors();

        $next = CashShift::query()->where('opened_by', $evening->id)->firstOrFail();
        $this->assertSame('1000.00', (string) $next->opening_float);
        $this->assertSame('handover', $next->opening_source);
        $this->assertSame('verified', $handover->fresh()->status);
        $this->assertSame($next->id, $handover->fresh()->consumed_by_shift_id);

        // A handover is drawer-to-drawer, so it never counts as verified revenue.
        $this->actingAs($evening)->get(route('hotel.cash-verification'))->assertForbidden();
    }

    private function user(Tenant $tenant, string $role, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' '.$email,
            'email' => $email,
            'password' => bcrypt('Password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
