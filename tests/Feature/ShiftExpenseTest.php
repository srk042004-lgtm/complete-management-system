<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\ShiftExpense;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_reduces_the_drawer_and_a_short_count_is_flagged_until_acknowledged(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Expense Hotel',
            'tenant_name' => 'Expense Hotel',
            'name' => 'Expense Hotel',
            'slug' => 'expense-hotel',
            'email' => 'expense@example.test',
            'status' => 'active',
        ]);
        $owner = $this->user($tenant, 'owner', 'owner-exp@example.test');
        $receptionist = $this->user($tenant, 'receptionist', 'reception-exp@example.test');

        $this->actingAs($receptionist)->post(route('frontdesk.shift.open'), ['opening_float' => 1000])->assertSessionHasNoErrors();
        $shift = CashShift::query()->firstOrFail();

        $this->actingAs($receptionist)->post(route('frontdesk.shift.expenses.store', $shift), [
            'amount' => 200,
            'category' => 'guest_refreshments',
            'description' => 'Tea for guest in room 204',
        ]);

        $this->assertEquals(800.0, (float) $shift->fresh()->expected_cash);
        $this->assertEquals(200.0, (float) ShiftExpense::query()->firstOrFail()->amount);

        // Refuse an expense larger than the drawer.
        $this->actingAs($receptionist)->post(route('frontdesk.shift.expenses.store', $shift), [
            'amount' => 5000,
            'category' => 'other',
            'description' => 'Too much',
        ])->assertSessionHasErrors('amount');

        // Count 700 against 800 expected: a 100 shortage that the owner must see.
        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 700,
            'notes' => 'Short 100, to be checked',
        ])->assertSessionHasNoErrors();

        $this->actingAs($owner)->get(route('hotel.cash-verification'))
            ->assertOk()
            ->assertSee('Cash shortage detected')
            ->assertSee('Tea for guest in room 204');

        $this->actingAs($owner)->post(route('hotel.cash-shortages.acknowledge', $shift->fresh()))->assertSessionHas('success');
        $this->assertNotNull($shift->fresh()->shortage_acknowledged_at);
        $this->actingAs($owner)->get(route('hotel.cash-verification'))->assertDontSee('Cash shortage detected');
    }

    private function user(Tenant $tenant, string $role, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' Account',
            'email' => $email,
            'password' => bcrypt('Password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
