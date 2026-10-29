<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SuperAdminUsersDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_filter_users_by_search_hotel_role_and_status(): void
    {
        $this->actingAs($this->createAdmin(), 'super_admin');
        $hotel = $this->createHotel('Harbor Hotel');
        $matchingUser = $this->createUser($hotel, [
            'name' => 'Harbor Reception',
            'email' => 'frontdesk@harbor.test',
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $this->createUser($hotel, [
            'name' => 'Suspended Owner',
            'email' => 'owner@harbor.test',
            'role' => 'owner',
            'status' => 'suspended',
        ]);
        $otherHotel = $this->createHotel('Garden Hotel');
        $this->createUser($otherHotel, [
            'name' => 'Garden Reception',
            'email' => 'frontdesk@garden.test',
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->get(route('super-admin.users', [
            'search' => 'frontdesk',
            'tenant_id' => $hotel->id,
            'role' => 'receptionist',
            'status' => 'active',
        ]))
            ->assertOk()
            ->assertSee($matchingUser->email)
            ->assertDontSee('owner@harbor.test')
            ->assertDontSee('frontdesk@garden.test');
    }

    public function test_hotel_preview_returns_only_users_for_the_selected_hotel(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'super_admin');
        $hotel = $this->createHotel('Harbor Hotel');
        $user = $this->createUser($hotel, ['role' => 'hotel_admin']);
        $this->createUser($this->createHotel('Garden Hotel'));

        $this->getJson(route('super-admin.users.preview', ['tenant_id' => $hotel->id]))
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.id', $user->id)
            ->assertJsonPath('users.0.hotel_name', 'Harbor Hotel')
            ->assertJsonPath('users.0.role_label', 'Hotel Manager / Admin');
    }

    public function test_super_admin_can_toggle_user_status_and_send_password_reset_link(): void
    {
        $this->actingAs($this->createAdmin(), 'super_admin');
        $user = $this->createUser($this->createHotel('Harbor Hotel'), ['status' => 'active']);

        $this->patchJson(route('super-admin.users.status', $user))
            ->assertOk()
            ->assertJsonPath('status', 'suspended');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'suspended']);

        Notification::fake();
        $this->postJson(route('super-admin.users.password-reset', $user))
            ->assertOk()
            ->assertJsonPath('message', 'Password reset link sent to the user email address.');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_hotel_user_can_complete_password_reset_from_the_sent_route(): void
    {
        $user = $this->createUser($this->createHotel('Harbor Hotel'));
        $token = Password::broker('users')->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Set a new password');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'A-strong-password-123',
            'password_confirmation' => 'A-strong-password-123',
        ])->assertRedirect(route('hotel-admin.login'));

        $this->assertTrue(password_verify('A-strong-password-123', $user->fresh()->password));
    }

    private function createAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Directory Admin',
            'fname' => 'Directory',
            'email' => 'directory-admin@example.test',
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'cnic' => '12345-6789012-3',
            'address' => 'Lahore',
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function createHotel(string $name): Tenant
    {
        return Tenant::query()->create([
            'hotel_name' => $name,
            'tenant_name' => $name,
            'owner_name' => 'Hotel Owner',
            'owner_email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
            'status' => 'active',
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createUser(Tenant $tenant, array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'tenant_id' => $tenant->id,
            'name' => 'Hotel User',
            'email' => 'hotel-user-'.$tenant->id.'-'.uniqid().'@example.test',
            'role' => 'manager',
            'status' => 'active',
            'password' => bcrypt('Password123!'),
        ]);
    }
}
