<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\CashTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashVaultDropTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_sends_the_rest_to_the_owner_and_it_stays_unverified_until_approved(): void
    {
        [$tenant, $owner, $receptionist] = $this->hotel('vault-hotel');
        $shift = $this->openShift($tenant, $receptionist, 1000);

        // Count 1000, keep 200 for the morning, so 800 goes to the owner.
        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'retained_float' => 200,
        ])->assertSessionHasNoErrors();

        $transfer = CashTransfer::query()->where('transfer_type', 'vault_drop')->firstOrFail();
        $this->assertSame('800.00', (string) $transfer->amount);
        $this->assertSame('200.00', (string) $transfer->retained_float);
        $this->assertSame('pending', $transfer->status);
        $this->assertSame('closed', $shift->fresh()->status);

        // Still in reception: not verified revenue.
        $this->actingAs($owner)->get(route('hotel.cash-verification'))
            ->assertOk()
            ->assertSee('Unverified cash');

        $this->actingAs($owner)->post(route('hotel.cash-verification.approve', $transfer))->assertSessionHas('success');
        $this->assertSame('verified', $transfer->fresh()->status);
        $this->assertSame($owner->id, $transfer->fresh()->receiver_id);
        $this->assertNotNull($transfer->fresh()->verified_at);
    }

    public function test_receptionist_cannot_approve_and_cannot_keep_more_than_was_counted(): void
    {
        [$tenant, $owner, $receptionist] = $this->hotel('vault-limit-hotel');
        $shift = $this->openShift($tenant, $receptionist, 1000);

        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'retained_float' => 1200,
        ])->assertSessionHasErrors('retained_float');
        $this->assertDatabaseCount('cash_transfers', 0);
        $this->assertSame('open', $shift->fresh()->status);

        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'retained_float' => 0,
        ])->assertSessionHasNoErrors();
        $transfer = CashTransfer::query()->firstOrFail();

        $this->actingAs($receptionist)->post(route('hotel.cash-verification.approve', $transfer))->assertForbidden();
        $this->assertSame('pending', $transfer->fresh()->status);
    }

    public function test_non_cash_drop_needs_a_reference_and_keeps_the_receipt_once(): void
    {
        Storage::fake('public');
        [$tenant, $owner, $receptionist] = $this->hotel('channel-hotel');
        $shift = $this->openShift($tenant, $receptionist, 1000);

        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'retained_float' => 0,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors('transaction_reference');
        $this->assertDatabaseCount('cash_transfers', 0);

        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $shift), [
            'counted_cash' => 1000,
            'retained_float' => 0,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-AUTH-7781',
            'payment_proof' => UploadedFile::fake()->createWithContent('slip.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')),
        ])->assertSessionHasNoErrors();

        $transfer = CashTransfer::query()->firstOrFail();
        $this->assertSame('bank_transfer', $transfer->payment_method);
        $this->assertSame('BANK-AUTH-7781', $transfer->transaction_reference);
        Storage::disk('public')->assertExists($transfer->proof_image_path);

        $this->actingAs($owner)->get(route('hotel.cash-verification'))
            ->assertOk()
            ->assertSee('BANK-AUTH-7781')
            ->assertSee('View receipt');

        $otherShift = $this->openShift($tenant, $receptionist, 500);
        $this->actingAs($receptionist)->post(route('frontdesk.shift.close', $otherShift), [
            'counted_cash' => 500,
            'payment_method' => 'online_wallet',
            'transaction_reference' => 'BANK-AUTH-7781',
        ])->assertSessionHasErrors('transaction_reference');
    }

    private function openShift(Tenant $tenant, User $receptionist, float $float): CashShift
    {
        return CashShift::query()->create([
            'tenant_id' => $tenant->id,
            'opened_by' => $receptionist->id,
            'opening_float' => $float,
            'expected_cash' => $float,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    /** @return array{0: Tenant, 1: User, 2: User} */
    private function hotel(string $slug): array
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Vault Hotel',
            'tenant_name' => 'Vault Hotel',
            'name' => 'Vault Hotel',
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'status' => 'active',
        ]);

        return [
            $tenant,
            $this->user($tenant, 'owner', $slug.'-owner@example.test'),
            $this->user($tenant, 'receptionist', $slug.'-reception@example.test'),
        ];
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
