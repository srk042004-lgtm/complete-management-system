<?php

use App\Http\Controllers\Account\SubscriptionCheckoutController;
use App\Http\Controllers\Account\TrialAccountController;
use App\Http\Controllers\AposMenuController;
use App\Http\Controllers\Audit\NightAuditController;
use App\Http\Controllers\Auth\HotelAdminAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FrontDesk\DashboardController;
use App\Http\Controllers\FrontDesk\FoodCatalogController;
use App\Http\Controllers\FrontDesk\InHouseGuestsController;
use App\Http\Controllers\FrontDesk\InventoryController;
use App\Http\Controllers\FrontDesk\QuickActionController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Home\ReportsController;
use App\Http\Controllers\Home\SettingsController;
use App\Http\Controllers\Hotel\RoleDashboardController;
use App\Http\Controllers\Hotel\StaffInvitationController;
use App\Http\Controllers\Housekeeping\HousekeepingController;
use App\Http\Controllers\IndustryRegistrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Portal\DemoRequestController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Reservations\BookingController;
use App\Http\Controllers\Reservations\ReservationsController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SuperAdminActivityLogController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminHotelRegistryController;
use App\Http\Controllers\SuperAdmin\SuperAdminLedgerController;
use App\Http\Controllers\SuperAdmin\SuperAdminNotificationController;
use App\Http\Controllers\SuperAdmin\SuperAdminPaymentsController;
use App\Http\Controllers\SuperAdmin\SuperAdminProfileController;
use App\Http\Controllers\SuperAdmin\SuperAdminRegistrationController;
use App\Http\Controllers\SuperAdmin\SuperAdminReportController;
use App\Http\Controllers\SuperAdmin\SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\SuperAdminSubscriptionsController;
use App\Http\Controllers\SuperAdmin\SuperAdminTeamController;
use App\Http\Controllers\SuperAdmin\SuperAdminUsersController;
use App\Http\Controllers\SuperAdminFinancialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'index'])->name('portal');
Route::get('/portal', [PortalController::class, 'index'])->name('portal.page');
Route::get('/why-artflow', [PortalController::class, 'whyArtflow'])->name('why-artflow');
Route::get('/pricing', [PortalController::class, 'pricing'])->name('pricing');
Route::get('/features', [PortalController::class, 'features'])->name('features');
Route::get('/pricing/calculator', [PortalController::class, 'pricingCalculator'])->name('pricing.calculator');
Route::post('/portal/demo-request', [DemoRequestController::class, 'store'])->name('portal.demo-request');
Route::get('/register/{system}', [IndustryRegistrationController::class, 'create'])->where('system', '(?!invite$)[A-Za-z0-9_-]+')->name('industry.register');
Route::post('/register/{system}', [IndustryRegistrationController::class, 'store'])->where('system', '(?!invite$)[A-Za-z0-9_-]+')->name('industry.register.store');

Route::get('/apos/customers-with-phone', [AposMenuController::class, 'show'])->defaults('page', 'customers-with-phone')->name('apos.customers');
Route::get('/apos/products', [AposMenuController::class, 'show'])->defaults('page', 'products')->name('apos.products');
Route::get('/apos/invoice-templates', [AposMenuController::class, 'show'])->defaults('page', 'invoice-templates')->name('apos.invoices');
Route::get('/apos/essential-softwares', [AposMenuController::class, 'show'])->defaults('page', 'essential-softwares')->name('apos.softwares');
Route::get('/apos/software-requirements', [AposMenuController::class, 'show'])->defaults('page', 'software-requirements')->name('apos.requirements');
Route::get('/apos/payments-return-policy', [AposMenuController::class, 'show'])->defaults('page', 'payments-return-policy')->name('apos.payments');
Route::get('/apos/privacy-policy', [AposMenuController::class, 'show'])->defaults('page', 'privacy-policy')->name('apos.privacy');
Route::get('/apos/terms-of-service', [AposMenuController::class, 'show'])->defaults('page', 'terms-of-service')->name('apos.terms');

Route::get('/super-admin/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/super-admin/register', [SuperAdminRegistrationController::class, 'create'])->name('super-admin.register');
Route::post('/super-admin/register', [SuperAdminRegistrationController::class, 'store'])->name('super-admin.register.store');
Route::post('/super-admin/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/theme-preference', [\App\Http\Controllers\ThemePreferenceController::class, 'update'])->middleware('web')->name('theme.preference');
Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\UserPasswordResetController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\Auth\UserPasswordResetController::class, 'store'])->name('password.update');

Route::get('/hotel-admin/login', [HotelAdminAuthController::class, 'loginForm'])->name('hotel-admin.login');
Route::post('/hotel-admin/login', [HotelAdminAuthController::class, 'login'])->name('hotel-admin.login.store');
Route::get('/hotel-admin/register', [HotelAdminAuthController::class, 'create'])->name('hotel-admin.register');
Route::post('/hotel-admin/register', [HotelAdminAuthController::class, 'store'])->name('hotel-admin.register.store');
Route::post('/hotel-admin/logout', [HotelAdminAuthController::class, 'logout'])->name('hotel-admin.logout');

Route::get('/register/invite', [StaffInvitationController::class, 'showLink'])->name('hotel.team.accept.link');
Route::post('/register/invite', [StaffInvitationController::class, 'acceptLink'])->name('hotel.team.accept.link.store');
Route::get('/register/invite/{token}', [StaffInvitationController::class, 'show'])->name('hotel.team.accept');
Route::post('/register/invite/{token}', [StaffInvitationController::class, 'accept'])->name('hotel.team.accept.store');

Route::middleware(['hotel-admin', 'hotel-tenant'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->middleware('hotel-capability:dashboard.read')->name('home.index');
    Route::get('/hotel/{role}', [RoleDashboardController::class, 'index'])
        ->whereIn('role', ['owner', 'admin', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'])
        ->middleware('hotel-role')
        ->name('hotel.role.dashboard');
    foreach (['owner', 'admin', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'] as $role) {
        Route::get("/hotel/{$role}/dashboard", [RoleDashboardController::class, 'index'])
            ->defaults('role', $role)
            ->middleware("hotel-role:{$role}")
            ->name("{$role}.dashboard");
    }
    Route::get('/hotel/audit-logs', [RoleDashboardController::class, 'auditLogs'])
        ->middleware('hotel-capability:audit.read')
        ->name('hotel.audit-logs');
    Route::post('/hotel/team/invite', [StaffInvitationController::class, 'store'])
        ->middleware('hotel-capability:staff.manage')
        ->name('hotel.team.invite.store');
    Route::patch('/hotel/team/{user}', [StaffInvitationController::class, 'update'])
        ->middleware('hotel-capability:staff.manage')
        ->name('hotel.team.update');
    Route::delete('/hotel/team/{user}', [StaffInvitationController::class, 'destroy'])
        ->middleware('hotel-capability:staff.manage')
        ->name('hotel.team.destroy');
    Route::delete('/hotel/team/invitations/{invitation}', [StaffInvitationController::class, 'cancelInvitation'])
        ->middleware('hotel-capability:staff.manage')
        ->name('hotel.team.invitations.cancel');

    Route::get('/home/reports', [ReportsController::class, 'index'])->middleware('hotel-capability:reports.read')->name('home.reports');
    Route::get('/home/settings', [SettingsController::class, 'index'])->middleware('hotel-capability:policy.manage')->name('home.settings');
    Route::put('/home/settings', [SettingsController::class, 'update'])->middleware('hotel-capability:policy.manage')->name('home.settings.update');
    Route::get('/frontdesk/dashboard', [DashboardController::class, 'index'])->middleware('hotel-capability:frontdesk.dashboard')->name('frontdesk.dashboard');
    Route::get('/frontdesk/guests/create', [QuickActionController::class, 'createGuest'])->middleware('hotel-capability:reservations.write')->name('frontdesk.guests.create');
    Route::post('/frontdesk/guests', [QuickActionController::class, 'storeGuest'])->middleware('hotel-capability:reservations.write')->name('frontdesk.guests.store');
    Route::get('/frontdesk/check-in', [QuickActionController::class, 'checkInIndex'])->middleware('hotel-capability:reservations.write')->name('frontdesk.check-in');
    Route::post('/frontdesk/bookings/{booking}/check-in', [QuickActionController::class, 'checkIn'])->middleware('hotel-capability:reservations.write')->name('frontdesk.check-in.store');
    Route::get('/frontdesk/check-out', [QuickActionController::class, 'checkOutIndex'])->middleware('hotel-capability:reservations.write')->name('frontdesk.check-out');
    Route::get('/frontdesk/check-out-history', [QuickActionController::class, 'checkOutHistory'])->middleware('hotel-capability:reservations.read')->name('frontdesk.check-out.history');
    Route::post('/frontdesk/bookings/{booking}/check-out', [QuickActionController::class, 'checkOut'])->middleware('hotel-capability:reservations.write')->name('frontdesk.check-out.store');
    Route::get('/frontdesk/rooms/status', [QuickActionController::class, 'roomStatusIndex'])->middleware('hotel-capability:room.status')->name('frontdesk.rooms.status');
    Route::patch('/frontdesk/rooms/{room}/status', [QuickActionController::class, 'updateRoomStatus'])->middleware('hotel-capability:room.status')->name('frontdesk.rooms.status.update');
    Route::get('/frontdesk/payments/create', [QuickActionController::class, 'createPayment'])->middleware('hotel-capability:finance.write')->name('frontdesk.payments.create');
    Route::post('/frontdesk/payments', [QuickActionController::class, 'storePayment'])->middleware('hotel-capability:finance.write')->name('frontdesk.payments.store');
    Route::get('/frontdesk/shift', [\App\Http\Controllers\FrontDesk\CashShiftController::class, 'index'])->middleware(['hotel-capability:finance.write', 'check-role:receptionist,admin'])->name('frontdesk.shift');
    Route::post('/frontdesk/shift/open', [\App\Http\Controllers\FrontDesk\CashShiftController::class, 'open'])->middleware(['hotel-capability:finance.write', 'check-role:receptionist,admin'])->name('frontdesk.shift.open');
    Route::post('/frontdesk/shift/{shift}/close', [\App\Http\Controllers\FrontDesk\CashShiftController::class, 'close'])->middleware(['hotel-capability:finance.write', 'check-role:receptionist,admin'])->name('frontdesk.shift.close');
    Route::post('/frontdesk/shift/{shift}/expenses', [\App\Http\Controllers\FrontDesk\ShiftExpenseController::class, 'store'])->middleware(['hotel-capability:finance.write', 'check-role:receptionist'])->name('frontdesk.shift.expenses.store');
    Route::get('/hotel/cash-verification', [\App\Http\Controllers\FrontDesk\CashTransferController::class, 'index'])->middleware(['hotel-capability:dashboard.read', 'check-role:owner,admin'])->name('hotel.cash-verification');
    Route::post('/hotel/cash-verification/{transfer}/approve', [\App\Http\Controllers\FrontDesk\CashTransferController::class, 'approve'])->middleware(['hotel-capability:dashboard.read', 'check-role:owner,admin'])->name('hotel.cash-verification.approve');
    Route::post('/hotel/cash-verification/{transfer}/reject', [\App\Http\Controllers\FrontDesk\CashTransferController::class, 'reject'])->middleware(['hotel-capability:dashboard.read', 'check-role:owner,admin'])->name('hotel.cash-verification.reject');
    Route::post('/hotel/cash-shortages/{shift}/acknowledge', [\App\Http\Controllers\FrontDesk\CashTransferController::class, 'acknowledgeShortage'])->middleware(['hotel-capability:dashboard.read', 'check-role:owner,admin'])->name('hotel.cash-shortages.acknowledge');
    Route::get('/frontdesk/in-house', [InHouseGuestsController::class, 'index'])->middleware('hotel-capability:folio.manage')->name('frontdesk.in-house');
    Route::post('/frontdesk/in-house/{booking}/charges', [InHouseGuestsController::class, 'storeCharge'])->middleware('hotel-capability:folio.manage')->name('frontdesk.in-house.charges.store');
    Route::get('/frontdesk/inventory', [InventoryController::class, 'index'])->middleware('hotel-capability:inventory.read')->name('frontdesk.inventory');
    Route::post('/frontdesk/inventory/categories', [InventoryController::class, 'storeCategory'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.categories.store');
    Route::get('/frontdesk/inventory/categories/{category}/edit', [InventoryController::class, 'editCategory'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.categories.edit');
    Route::put('/frontdesk/inventory/categories/{category}', [InventoryController::class, 'updateCategory'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.categories.update');
    Route::delete('/frontdesk/inventory/categories/{category}', [InventoryController::class, 'destroyCategory'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.categories.destroy');
    Route::post('/frontdesk/inventory/rooms', [InventoryController::class, 'storeRoom'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.rooms.store');
    Route::get('/frontdesk/inventory/rooms/{room}/edit', [InventoryController::class, 'editRoom'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.rooms.edit');
    Route::put('/frontdesk/inventory/rooms/{room}', [InventoryController::class, 'updateRoom'])->middleware('hotel-capability:inventory.manage')->name('frontdesk.inventory.rooms.update');
    Route::get('/frontdesk/food-catalog', [FoodCatalogController::class, 'index'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog');
    Route::post('/frontdesk/food-catalog/categories', [FoodCatalogController::class, 'storeCategory'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.categories.store');
    Route::put('/frontdesk/food-catalog/categories/{category}', [FoodCatalogController::class, 'updateCategory'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.categories.update');
    Route::delete('/frontdesk/food-catalog/categories/{category}', [FoodCatalogController::class, 'deleteCategory'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.categories.destroy');
    Route::post('/frontdesk/food-catalog/items', [FoodCatalogController::class, 'storeItem'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.items.store');
    Route::put('/frontdesk/food-catalog/items/{item}', [FoodCatalogController::class, 'updateItem'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.items.update');
    Route::delete('/frontdesk/food-catalog/items/{item}', [FoodCatalogController::class, 'deleteItem'])->middleware('hotel-capability:rooms.manage')->name('frontdesk.food-catalog.items.destroy');
    Route::get('/reservations', [ReservationsController::class, 'index'])->middleware('hotel-capability:reservations.read')->name('reservations.index');
    Route::get('/reservations/booking', [BookingController::class, 'create'])->middleware('hotel-capability:reservations.write')->name('reservations.booking');
    Route::get('/reservations/guest-lookup', [BookingController::class, 'lookupGuest'])->middleware('hotel-capability:reservations.write')->name('reservations.guest-lookup');
    Route::post('/reservations/booking', [BookingController::class, 'store'])->middleware('hotel-capability:reservations.write')->name('reservations.booking.store');
    Route::post('/reservations/{reservation}/checkout', [ReservationController::class, 'checkout'])->middleware('hotel-capability:reservations.write')->name('reservations.checkout');
    Route::get('/reservations/{reservation}/invoice', [InvoiceController::class, 'show'])->middleware('hotel-capability:reservations.read')->name('reservations.invoice');
    Route::get('/pos', [PosController::class, 'index'])->middleware('hotel-capability:pos.access')->name('pos.index');
    Route::get('/cashier', fn () => abort(404))->middleware('hotel-capability:finance.write')->name('cashier.index');
    Route::get('/night-audit', [NightAuditController::class, 'index'])->middleware('hotel-capability:night-audit.run')->name('audit.night');
    Route::post('/night-audit', [NightAuditController::class, 'run'])->middleware('hotel-capability:night-audit.run')->name('audit.night.run');
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->middleware('hotel-capability:housekeeping.access')->name('housekeeping.index');

    // Housekeeping operations: housekeepers and hotel admins only.
    Route::middleware(['hotel-capability:housekeeping.access', 'check-role:housekeeping,admin'])->prefix('housekeeping')->name('housekeeping.')->group(function () {
        Route::patch('/rooms/{room}/status', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'setStatus'])->name('rooms.status');
        Route::post('/rooms/{room}/inspect', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'inspect'])->middleware('check-role:admin')->name('rooms.inspect');
        Route::post('/rooms/{room}/amenities', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'logAmenity'])->name('rooms.amenities');
        Route::post('/rooms/{room}/issues', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'reportIssue'])->name('rooms.issues');
        Route::patch('/rooms/{room}/priority', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'togglePriority'])->middleware('check-role:admin')->name('rooms.priority');
        Route::get('/lost-found', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'lostFound'])->name('lost-found');
        Route::post('/lost-found', [\App\Http\Controllers\Housekeeping\HousekeepingController::class, 'storeLostFound'])->name('lost-found.store');
    });

    Route::get('/account/subscription', [SubscriptionCheckoutController::class, 'index'])->middleware('hotel-billing')->name('account.subscription');
    Route::post('/account/subscription/payment-request', [SubscriptionCheckoutController::class, 'storePaymentRequest'])->middleware('hotel-billing')->name('account.subscription.payment-request');
    Route::get('/account/subscription/payment-status/{paymentReference}', [SubscriptionCheckoutController::class, 'paymentStatus'])->middleware('hotel-billing')->name('account.subscription.payment-status');
    Route::get('/account/subscription/status', [TrialAccountController::class, 'status'])->middleware('hotel-billing')->name('account.subscription.status');
    Route::post('/account/subscription/permanent', [TrialAccountController::class, 'activatePermanent'])->middleware('hotel-billing')->name('account.subscription.permanent');
    Route::get('/account/trial-status', [TrialAccountController::class, 'status'])->middleware('hotel-billing')->name('account.trial-status');
});

Route::middleware(['auth:super_admin', 'super-admin'])->group(function () {
    Route::get('/super-admin', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');
    Route::get('/super-admin/profile', [SuperAdminProfileController::class, 'index'])->name('super-admin.profile');
    Route::put('/super-admin/profile', [SuperAdminProfileController::class, 'update'])->name('super-admin.profile.update');
    Route::get('/super-admin/hotels', [SuperAdminHotelRegistryController::class, 'index'])->name('super-admin.hotel-registry');
    Route::get('/super-admin/hotels/{tenant}', [\App\Http\Controllers\SuperAdmin\SuperAdminHotelDetailsController::class, 'show'])->whereNumber('tenant')->name('super-admin.hotels.show');
    Route::get('/super-admin/hotels/create', [SuperAdminHotelRegistryController::class, 'create'])->name('super-admin.hotel-registry.create');
    Route::post('/super-admin/hotels', [SuperAdminHotelRegistryController::class, 'store'])->name('super-admin.hotel-registry.store');
    Route::post('/super-admin/hotels/{tenant}/impersonate', [SuperAdminHotelRegistryController::class, 'impersonate'])->name('super-admin.hotel-registry.impersonate');
    Route::post('/super-admin/hotels/{tenant}/toggle-status', [SuperAdminHotelRegistryController::class, 'toggleStatus'])->name('super-admin.hotel-registry.toggle-status');
    Route::post('/super-admin/hotels/{tenant}/approve', [SuperAdminHotelRegistryController::class, 'approve'])->name('super-admin.hotel-registry.approve');
    Route::post('/super-admin/hotels/{tenant}/reject', [SuperAdminHotelRegistryController::class, 'reject'])->name('super-admin.hotel-registry.reject');
    Route::post('/super-admin/hotels/{tenant}/suspend', [SuperAdminHotelRegistryController::class, 'suspend'])->name('super-admin.hotel-registry.suspend');
    Route::post('/super-admin/hotels/{tenant}/activate', [SuperAdminHotelRegistryController::class, 'activate'])->name('super-admin.hotel-registry.activate');
    Route::post('/super-admin/hotels/{tenant}/manage-subscription', [SuperAdminHotelRegistryController::class, 'manageSubscription'])->name('super-admin.hotel-registry.manage-subscription');
    Route::post('/superadmin/tenants/{id}/subscription/update', [SubscriptionController::class, 'update'])->name('super-admin.subscription.update');
    Route::get('/super-admin/subscriptions', [SuperAdminSubscriptionsController::class, 'index'])->name('super-admin.subscriptions');
    Route::get('/super-admin/trial-accounts', [\App\Http\Controllers\SuperAdmin\SuperAdminTrialAccountController::class, 'index'])->name('super-admin.trial-accounts');
    Route::post('/super-admin/trial-accounts/{trialAccount}/convert', [\App\Http\Controllers\SuperAdmin\SuperAdminTrialAccountController::class, 'convert'])->name('super-admin.trial-accounts.convert');
    // Local-only developer shortcut: a plain link (no CSRF token), guarded by the super admin session and the local environment.
    Route::match(['get', 'post'], '/dev/switch-role/{role}', [\App\Http\Controllers\Dev\RoleSwitchController::class, 'switchRole'])->name('dev.switch-role');
    Route::get('/super-admin/users', [SuperAdminUsersController::class, 'index'])->name('super-admin.users');
    Route::get('/super-admin/users/preview', [SuperAdminUsersController::class, 'preview'])->name('super-admin.users.preview');
    Route::patch('/super-admin/users/{user}/status', [SuperAdminUsersController::class, 'updateStatus'])->name('super-admin.users.status');
    Route::post('/super-admin/users/{user}/password-reset', [SuperAdminUsersController::class, 'sendPasswordReset'])->name('super-admin.users.password-reset');
    Route::get('/super-admin/payments', [SuperAdminPaymentsController::class, 'index'])->name('super-admin.payments');
    Route::patch('/super-admin/payments/{payment}/mark-paid', [SuperAdminPaymentsController::class, 'markPaid'])->name('super-admin.payments.mark-paid');
    Route::patch('/super-admin/payments/{payment}/reject', [SuperAdminPaymentsController::class, 'reject'])->name('super-admin.payments.reject');
    Route::get('/super-admin/payments/{payment}/proof', [SuperAdminPaymentsController::class, 'proof'])->name('super-admin.payments.proof');
    Route::patch('/super-admin/payments/{payment}/refund', [SuperAdminPaymentsController::class, 'refund'])->name('super-admin.payments.refund');
    Route::get('/super-admin/activity-logs', [SuperAdminActivityLogController::class, 'index'])->name('super-admin.activity-logs');
    Route::get('/super-admin/reports', [SuperAdminReportController::class, 'index'])->name('super-admin.reports');
    Route::get('/super-admin/notifications', [SuperAdminNotificationController::class, 'index'])->name('super-admin.notifications');
    Route::patch('/super-admin/notifications/{notification}/read', [SuperAdminNotificationController::class, 'markRead'])->name('super-admin.notifications.read');
    Route::patch('/super-admin/notifications/read-all', [SuperAdminNotificationController::class, 'markAllRead'])->name('super-admin.notifications.read-all');
    Route::get('/super-admin/notifications/unread-count', [SuperAdminNotificationController::class, 'unreadCount'])->name('super-admin.notifications.unread-count');
    Route::patch('/super-admin/notifications/{notification}/seen', [SuperAdminNotificationController::class, 'seen'])->name('super-admin.notifications.seen');
    Route::get('/super-admin/plans', [PlanController::class, 'index'])->name('super-admin.plan-manager');
    Route::post('/super-admin/subscription-plans', [PlanController::class, 'storePlan'])->name('super-admin.plans.store');
    Route::put('/super-admin/subscription-plans/{plan}', [PlanController::class, 'updatePlan'])->name('super-admin.plans.update');
    Route::patch('/super-admin/subscription-plans/{plan}/toggle-status', [PlanController::class, 'togglePlan'])->name('super-admin.plans.toggle-status');
    Route::match(['POST', 'PUT'], '/super-admin/plans/{subscription}/update', [PlanController::class, 'update'])->name('super-admin.plan.update');
    Route::get('/super-admin/billing', [SuperAdminLedgerController::class, 'index'])->name('super-admin.billing');
    Route::get('/super-admin/team', [SuperAdminTeamController::class, 'index'])->name('super-admin.team');
    Route::post('/super-admin/team', [SuperAdminTeamController::class, 'store'])->name('super-admin.team.store');
    Route::put('/super-admin/team/{member}', [SuperAdminTeamController::class, 'update'])->name('super-admin.team.update');
    Route::delete('/super-admin/team/{member}', [SuperAdminTeamController::class, 'destroy'])->name('super-admin.team.destroy');
    Route::get('/super-admin/team-access', [SuperAdminTeamController::class, 'index'])->name('super-admin.team-access');
    Route::get('/super-admin/settings', [SuperAdminSettingsController::class, 'index'])->name('super-admin.settings');
    Route::put('/super-admin/settings', [SuperAdminSettingsController::class, 'update'])->name('super-admin.settings.update');
    Route::post('/super-admin/tenants/{tenant}/impersonate', [SuperAdminDashboardController::class, 'impersonateAccess'])->name('super-admin.impersonate');
    Route::post('/super-admin/tenants/{tenant}/extend', [SuperAdminDashboardController::class, 'extendAccess'])->name('super-admin.extend');
    Route::post('/super-admin/tenants/{tenant}/suspend', [SuperAdminDashboardController::class, 'suspendAccess'])->name('super-admin.suspend');
    // Super Admin Financials
    Route::get('/super-admin/financials', [SuperAdminFinancialController::class, 'index'])->name('super-admin.financials');
    Route::post('/super-admin/financials/tenant-payment', [SuperAdminFinancialController::class, 'storeTenantPayment'])->name('super-admin.financials.tenant-payment');
    Route::post('/super-admin/financials/salary-payment', [SuperAdminFinancialController::class, 'storeSalaryPayment'])->name('super-admin.financials.salary-payment');
});
