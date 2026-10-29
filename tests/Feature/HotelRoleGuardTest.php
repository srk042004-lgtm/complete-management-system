<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HotelRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelRoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_access_their_own_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Lakeview Hotel',
            'tenant_name' => 'Lakeview Hotel',
            'name' => 'Lakeview Hotel',
            'slug' => 'lakeview-hotel',
            'email' => 'hotel@example.com',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/hotel/receptionist')
            ->assertOk()
            ->assertSessionHas('hotel_tenant_id', $tenant->id)
            ->assertSee('header-role-badge--receptionist', false)
            ->assertSee('Receptionist');
        $this->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertSee('Receptionist');

        $this->actingAs($user)
            ->get(route('frontdesk.dashboard'))
            ->assertOk()
            ->assertSee('data-layout-mode="sidebar"', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-layout-toggle', false)
            ->assertSee('Switch to menu view')
            ->assertSee('class="quick-actions-heading"', false)
            ->assertSee('header-role-badge--receptionist', false)
            ->assertSee('Receptionist &amp; POS', false)
            ->assertSee('New Reservation')
            ->assertSee('Add Payment')
            ->assertSee('data-theme-switcher-slot="sidebar"', false)
            ->assertSee('data-theme-switcher aria-label="Switch to dark theme"', false)
            ->assertSee(asset('css/theme-switcher.css'), false)
            ->assertSee(asset('js/theme-switcher.js'), false)
            ->assertSee('frontdesk-logout-button', false)
            ->assertSee(route('hotel-admin.logout'), false)
            ->assertSee('href="#today-arrivals"', false)
            ->assertSee('href="#recent-reservations"', false);

        $this->actingAs($user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('data-hotel-sidebar', false)
            ->assertSee('class="hotel-sidebar-page"', false)
            ->assertSee('data-hotel-sidebar-toggle', false)
            ->assertSee('data-hotel-topbar', false)
            ->assertSee('data-hotel-layout-toggle', false)
            ->assertSee('Switch to menu view')
            ->assertSee('Switch to sidebar')
            ->assertSee('hotel-logout-form', false)
            ->assertSee(route('hotel-admin.logout'), false)
            ->assertSee('class="hotel-topbar__heading"', false)
            ->assertSee('hotel-role-badge--receptionist', false)
            ->assertSee('Front Desk Agent')
            ->assertSee('class="hotel-topbar__link"', false)
            ->assertSee(route('frontdesk.dashboard'))
            ->assertSee(route('reservations.booking'))
            ->assertDontSee(route('home.settings'));

        $this->actingAs($user)
            ->get(route('frontdesk.guests.create'))
            ->assertOk()
            ->assertSee('data-hotel-sidebar', false)
            ->assertSee(route('frontdesk.check-in'));
    }

    public function test_receptionist_is_redirected_when_trying_to_access_admin_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Lakeview Hotel',
            'tenant_name' => 'Lakeview Hotel',
            'name' => 'Lakeview Hotel',
            'slug' => 'lakeview-hotel-2',
            'email' => 'hotel2@example.com',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Agent',
            'email' => 'agent2@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/hotel/admin')
            ->assertRedirect('/hotel/receptionist');
    }

    public function test_hotel_workspace_rejects_a_session_bound_to_another_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Session Tenant Hotel',
            'tenant_name' => 'Session Tenant Hotel',
            'name' => 'Session Tenant Hotel',
            'slug' => 'session-tenant-hotel',
            'email' => 'session-tenant@example.com',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Session Tenant Receptionist',
            'email' => 'session-receptionist@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->withSession(['hotel_tenant_id' => $tenant->id + 1])
            ->actingAs($user)
            ->get('/hotel/receptionist')
            ->assertForbidden();
    }

    public function test_owner_can_access_the_shared_dashboard_in_read_only_executive_mode(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Grand Lakeview Resort',
            'tenant_name' => 'Grand Lakeview Resort',
            'name' => 'Grand Lakeview Resort',
            'slug' => 'grand-lakeview-resort',
            'email' => 'resort@example.com',
            'city' => 'Peshawar',
            'status' => 'active',
        ]);

        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Resort Owner',
            'email' => 'owner@resort.example',
            'password' => bcrypt('Password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get('/hotel/owner')
            ->assertOk()
            ->assertSee('Executive financial overview')
            ->assertSee('Owner Account')
            ->assertSee('Manage subscription')
            ->assertSee('Owner staff management')
            ->assertDontSee(route('frontdesk.dashboard'));
    }

    public function test_owner_can_access_existing_hotel_operations_routes(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Operations Access Hotel',
            'tenant_name' => 'Operations Access Hotel',
            'name' => 'Operations Access Hotel',
            'slug' => 'operations-access-hotel',
            'email' => 'operations@example.com',
            'status' => 'active',
        ]);

        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations Owner',
            'email' => 'operations-owner@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get('/frontdesk/dashboard?role=admin')
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('reservations.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('frontdesk.inventory'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('frontdesk.inventory.categories.store'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('hotel.audit-logs'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('audit.night'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get('/reservations/booking')
            ->assertForbidden();
        $this->actingAs($owner)
            ->post('/hotel/team/invite', [
                'email' => 'new-admin@operations.example',
                'role' => 'admin',
            ])
            ->assertRedirect();
    }

    public function test_legacy_cashier_accounts_use_receptionist_access_without_a_separate_role_dashboard(): void
    {
        $tenant = $this->tenantWithModules('Cashier Role Hotel', 'cashier-role-hotel', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'restaurant' => true,
            'pos' => true,
            'housekeeping' => true,
            'staff' => true,
            'finance' => true,
            'reports' => true,
            'maintenance' => true,
        ]);
        $cashier = $this->userWithRole($tenant, 'cashier', 'cashier-role@example.test');

        $this->actingAs($cashier)->get(route('hotel.role.dashboard', ['role' => 'receptionist']))
            ->assertOk()
            ->assertSee('Receptionist / Front Desk')
            ->assertSee(route('pos.index'));
        $this->assertArrayNotHasKey('cashier', HotelRoleCatalog::definitions());
        $this->get(route('pos.index'))->assertOk();
        $this->get('/hotel/cashier')->assertNotFound();
        $this->get(route('reservations.booking'))->assertOk();
        $this->get(route('audit.night'))->assertForbidden();
    }

    public function test_manager_has_operations_access_but_not_staff_settings_finance_write_or_pos(): void
    {
        $tenant = $this->tenantWithModules('Manager Permissions Hotel', 'manager-permissions-hotel', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'restaurant' => true,
            'pos' => true,
            'housekeeping' => true,
            'staff' => true,
            'finance' => true,
            'reports' => true,
            'maintenance' => true,
        ]);
        $manager = $this->userWithRole($tenant, 'manager', 'manager-permissions@example.test');

        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Operations Manager Dashboard')
            ->assertSee('Manager')
            ->assertSee('header-role-badge--manager', false);
        $this->get(route('frontdesk.rooms.status'))->assertOk();
        $this->get(route('frontdesk.in-house'))->assertOk();
        $this->get(route('frontdesk.check-out'))->assertOk();
        $this->get(route('housekeeping.index'))->assertOk();
        $this->get(route('home.reports'))->assertOk();
        $this->get(route('home.settings'))->assertForbidden();
        $this->get(route('pos.index'))->assertForbidden();
        $this->get(route('frontdesk.payments.create'))->assertForbidden();
        $this->post(route('hotel.team.invite.store'), [
            'email' => 'manager-invite@example.test',
            'role' => 'receptionist',
        ])->assertForbidden();
        $this->get(route('account.subscription'))->assertForbidden();
        $this->get(route('frontdesk.dashboard'))->assertOk();
    }

    public function test_disabled_hotelkeeping_feature_blocks_role_dashboard(): void
    {
        $tenant = $this->tenantWithModules('No Housekeeping Hotel', 'no-housekeeping-hotel', [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'housekeeping' => false,
            'restaurant' => false,
            'pos' => false,
            'staff' => true,
            'finance' => true,
            'reports' => true,
            'maintenance' => false,
        ]);
        $housekeeper = $this->userWithRole($tenant, 'housekeeping', 'disabled-housekeeping@example.test');

        $this->actingAs($housekeeper)
            ->get(route('hotel.role.dashboard', ['role' => 'housekeeping']))
            ->assertForbidden();
    }

    public function test_housekeeping_dashboard_and_room_task_board_are_role_scoped(): void
    {
        $tenant = $this->tenantWithModules('Housekeeping Board Hotel', 'housekeeping-board-hotel', [
            'rooms' => true,
            'reservations' => true,
            'housekeeping' => true,
            'staff' => true,
            'finance' => true,
            'reports' => true,
        ]);
        $housekeeper = $this->userWithRole($tenant, 'housekeeping', 'housekeeping-board@example.test');
        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'H-10',
            'room_type' => 'Standard',
            'occupancy_status' => 'vacant',
            'housekeeping_status' => 'dirty',
        ]);

        $this->actingAs($housekeeper)->get(route('housekeeping.dashboard'))
            ->assertOk()
            ->assertSee('Room categories')
            ->assertSee('Housekeeping Staff')
            ->assertDontSee(route('frontdesk.dashboard'));
        $this->get(route('housekeeping.index', ['category' => 'none']))
            ->assertOk()
            ->assertSee('Room H-10')
            ->assertSee('Dirty');
        $this->patch(route('frontdesk.rooms.status.update', $room), [
            'housekeeping_status' => 'clean',
            'return_to' => 'housekeeping',
        ])->assertRedirect(route('housekeeping.index'));
        $this->assertSame('clean', $room->fresh()->housekeeping_status);

        $this->patch(route('frontdesk.rooms.status.update', $room), [
            'housekeeping_status' => 'out_of_order',
        ])->assertSessionHasErrors('housekeeping_status');
    }

    private function tenantWithModules(string $name, string $slug, array $modules): Tenant
    {
        return Tenant::query()->create([
            'hotel_name' => $name,
            'tenant_name' => $name,
            'name' => $name,
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'hotel_type' => 'large',
            'status' => 'active',
            'enabled_modules' => $modules,
        ]);
    }

    private function userWithRole(Tenant $tenant, string $role, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('Password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_receptionist_can_open_pos_when_the_restaurant_module_is_enabled(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Front Desk Permissions Hotel',
            'tenant_name' => 'Front Desk Permissions Hotel',
            'name' => 'Front Desk Permissions Hotel',
            'slug' => 'front-desk-permissions-hotel',
            'email' => 'front-desk-permissions@example.com',
            'status' => 'active',
            'enabled_modules' => [
                'rooms' => true,
                'reservations' => true,
                'front_desk' => true,
                'guest_management' => true,
                'restaurant' => true,
                'pos' => true,
            ],
        ]);
        $receptionist = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Front Desk Receptionist',
            'email' => 'front-desk-permissions-user@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $this->actingAs($receptionist)
            ->get('/reservations/booking')
            ->assertOk();

        $this->actingAs($receptionist)
            ->get('/pos')
            ->assertOk();
    }

    public function test_hotel_admin_uses_the_same_dashboard_with_control_navigation(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Admin Control Hotel',
            'tenant_name' => 'Admin Control Hotel',
            'name' => 'Admin Control Hotel',
            'slug' => 'admin-control-hotel',
            'email' => 'admin-control@example.com',
            'status' => 'active',
        ]);
        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Hotel Admin',
            'email' => 'hotel-admin-control@example.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/hotel/admin')
            ->assertOk()
            ->assertSee('Daily operations')
            ->assertSee('Administrator')
            ->assertSee('Live room status grid')
            ->assertSee('Expected shift cash')
            ->assertSee('Receptionists &amp; Housekeeping', false)
            ->assertDontSee('Net revenue')
            ->assertDontSee('Manage SaaS subscription');
    }

    public function test_admin_can_manage_operations_but_cannot_open_owner_finance_or_audit_pages(): void
    {
        $tenant = Tenant::query()->create([
            'hotel_name' => 'Separated Duties Hotel',
            'tenant_name' => 'Separated Duties Hotel',
            'name' => 'Separated Duties Hotel',
            'slug' => 'separated-duties-hotel',
            'email' => 'separated-duties@example.test',
            'status' => 'active',
        ]);
        $admin = $this->userWithRole($tenant, 'admin', 'separated-admin@example.test');

        $this->actingAs($admin)->get(route('reservations.index'))->assertOk();
        $this->get(route('frontdesk.inventory'))->assertOk()->assertSee('Add room category');
        $this->get(route('hotel.audit-logs'))->assertForbidden();
        $this->get(route('home.reports'))->assertForbidden();
        $this->get(route('account.subscription'))->assertForbidden();
        $this->get(route('audit.night'))->assertOk();
    }
}
