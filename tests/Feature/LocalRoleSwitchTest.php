<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalRoleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn (): string => 'local');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_super_admin_can_switch_between_local_role_accounts_without_losing_super_admin_access(): void
    {
        $admin = $this->createSuperAdmin();
        $tenant = $this->createTenant();
        $this->actingAs($admin, 'super_admin')
            ->withSession(['impersonated_tenant_id' => $tenant->id]);

        $this->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Local role switcher');

        $this->postSwitchRole('admin')
            ->assertRedirect(route('admin.dashboard'));

        $adminUser = User::query()->where('tenant_id', $tenant->id)->where('role', 'admin')->firstOrFail();
        $this->assertAuthenticatedAs($adminUser, 'web');
        $this->assertAuthenticatedAs($admin, 'super_admin');
        $this->assertSame($tenant->id, session('hotel_tenant_id'));

        $this->postSwitchRole('housekeeping')
            ->assertRedirect(route('housekeeping.dashboard'));

        $housekeepingUser = User::query()->where('tenant_id', $tenant->id)->where('role', 'housekeeping')->firstOrFail();
        $this->assertAuthenticatedAs($housekeepingUser, 'web');
        $this->assertAuthenticatedAs($admin, 'super_admin');
        $this->assertSame($tenant->id, session('hotel_tenant_id'));

        $this->get(route('housekeeping.dashboard'))
            ->assertOk()
            ->assertSee('Local role switcher')
            ->assertSee(route('dev.switch-role', 'manager'), false);
    }

    public function test_role_switcher_is_not_available_outside_local_environment(): void
    {
        $admin = $this->createSuperAdmin();
        $tenant = $this->createTenant();
        $this->actingAs($admin, 'super_admin')
            ->withSession(['impersonated_tenant_id' => $tenant->id]);
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->postSwitchRole('admin')->assertNotFound();
        $this->assertGuest('web');
    }

    public function test_role_switch_route_requires_an_authenticated_super_admin(): void
    {
        $this->postSwitchRole('admin')->assertRedirect('/super-admin/login');
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Local Developer',
            'fname' => 'Local Developer',
            'email' => 'local-developer@example.test',
            'number' => '03000000000',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function postSwitchRole(string $role)
    {
        return $this->post(route('dev.switch-role', $role));
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Local Role Switch Hotel',
            'tenant_name' => 'Local Role Switch Hotel',
            'hotel_name' => 'Local Role Switch Hotel',
            'owner_email' => 'local-role-switch@example.test',
            'email' => 'local-role-switch@example.test',
            'slug' => 'local-role-switch-hotel',
            'hotel_type' => 'large',
            'status' => 'active',
        ]);
    }
}
