<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontDesk\DashboardController;
use App\Http\Controllers\Housekeeping\HousekeepingController;
use App\Models\Booking;
use App\Models\CashShift;
use App\Models\HotelOperationLog;
use App\Models\HotelPayment;
use App\Models\HotelRoleInvitation;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\HotelFeatureCatalog;
use App\Support\HotelRoleCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleDashboardController extends Controller
{
    public function index(Request $request, string $role): View
    {
        $user = $request->user();
        $tenant = $user->tenant;
        abort_if($tenant === null, 403, 'An active hotel workspace is required.');

        $role = HotelRoleCatalog::normalize($role);
        $tier = HotelFeatureCatalog::operatingTier($tenant->hotel_type, $tenant->total_rooms);
        $inviteableRoles = match ($role) {
            'owner' => ['admin', 'manager'],
            'admin' => ['receptionist', 'housekeeping', 'accountant', 'maintenance'],
            default => [],
        };
        $availableRoles = array_filter(
            HotelRoleCatalog::definitions(),
            fn (array $definition, string $roleName): bool => in_array($roleName, $inviteableRoles, true)
                && HotelRoleCatalog::allows($roleName, $tenant->moduleConfiguration(), $tier),
            ARRAY_FILTER_USE_BOTH,
        );
        $pendingInvitations = $inviteableRoles === []
            ? collect()
            : HotelRoleInvitation::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('role', $inviteableRoles)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get();
        $managedRoles = $role === 'owner'
            ? ['admin', 'manager', 'hotel_admin']
            : ($role === 'admin' ? ['receptionist', 'housekeeping', 'housekeeper', 'cashier', 'accountant', 'maintenance'] : []);
        $managedStaff = $managedRoles === []
            ? collect()
            : $tenant->users()->whereIn('role', $managedRoles)->orderBy('name')->get();

        if ($role === 'owner') {
            return view('hotel.owner-dashboard', $this->ownerDashboardData($tenant, $availableRoles, $pendingInvitations) + [
                'managedStaff' => $managedStaff,
                'cashShifts' => $this->cashShiftSummary($tenant),
            ]);
        }

        if ($role === 'admin') {
            return view('hotel.admin-dashboard', $this->adminDashboardData($tenant, $availableRoles, $pendingInvitations) + [
                'managedStaff' => $managedStaff,
                'cashShifts' => $this->cashShiftSummary($tenant),
            ]);
        }

        if ($role === 'receptionist') {
            return app(DashboardController::class)->index();
        }

        if ($role === 'housekeeping') {
            return app(HousekeepingController::class)->index($request);
        }

        return view('hotel.role-dashboard', [
            'role' => $role,
            'roleLabel' => HotelRoleCatalog::label($role),
            'tenant' => $tenant,
            'availableRoles' => $availableRoles,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $logs = HotelOperationLog::query()
            ->with('actor:id,name')
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query): void {
                $query->whereIn('action', [
                    'booking.price_overridden',
                    'booking.deleted',
                    'payment.refunded',
                    'room_rate.overridden',
                    'staff.suspended',
                ])->orWhere('action', 'like', '%price%')
                    ->orWhere('action', 'like', '%refund%')
                    ->orWhere('action', 'like', 'booking.deleted%');
            })
            ->latest()
            ->limit(100)
            ->get();

        return view('hotel.audit-logs', compact('logs'));
    }

    /**
     * @param  array<string, array{label: string, feature: ?string, luxury_only?: bool}>  $availableRoles
     * @return array<string, mixed>
     */
    private function ownerDashboardData(Tenant $tenant, array $availableRoles, Collection $pendingInvitations): array
    {
        $tenantId = (int) $tenant->id;
        $now = now();
        $yearStart = $now->copy()->startOfYear();
        $yearEnd = $now->copy()->endOfYear();
        $bookings = Booking::query()->where('tenant_id', $tenantId);
        $rooms = Room::query()->where('tenant_id', $tenantId);
        $totalRooms = (clone $rooms)->count();
        $occupiedRooms = (clone $rooms)->where('occupancy_status', 'occupied')->count();
        $subscription = $tenant->subscriptions()->with('plan')->latest('id')->first();
        if ($subscription?->status === 'trial') {
            $subscription->expireIfNeeded();
        }

        $monthlyPayments = HotelPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth()])
            ->get(['amount', 'created_at'])
            ->groupBy(fn (HotelPayment $payment): string => $payment->created_at->format('Y-m'));
        $monthlyRevenue = collect(range(11, 0))->map(function (int $monthsAgo) use ($now, $monthlyPayments): array {
            $month = $now->copy()->subMonths($monthsAgo)->startOfMonth();

            return [
                'label' => $month->format('M Y'),
                'amount' => (float) ($monthlyPayments->get($month->format('Y-m'))?->sum('amount') ?? 0),
            ];
        });

        $yearlyPayments = HotelPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$now->copy()->subYears(4)->startOfYear(), $yearEnd])
            ->get(['amount', 'created_at'])
            ->groupBy(fn (HotelPayment $payment): string => $payment->created_at->format('Y'));
        $yearlyRevenue = collect(range(4, 0))->map(function (int $yearsAgo) use ($now, $yearlyPayments): array {
            $year = (string) ($now->year - $yearsAgo);

            return [
                'label' => $year,
                'amount' => (float) ($yearlyPayments->get($year)?->sum('amount') ?? 0),
            ];
        });

        $netRevenue = (clone $bookings)
            ->whereIn('booking_status', ['checked_in', 'checked_out'])
            ->sum('subtotal_amount');
        $averageDailyRate = DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->where('bookings.tenant_id', $tenantId)
            ->whereIn('bookings.booking_status', ['checked_in', 'checked_out'])
            ->whereBetween('bookings.check_in_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->avg('booking_rooms.price_per_night');
        $taxSummary = (clone $bookings)
            ->whereIn('booking_status', ['checked_in', 'checked_out'])
            ->whereBetween('check_in_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->sum('tax_amount');
        $auditFeed = HotelOperationLog::query()
            ->with('actor:id,name')
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query): void {
                $query->whereIn('action', [
                    'booking.price_overridden',
                    'booking.deleted',
                    'payment.refunded',
                    'room_rate.overridden',
                    'staff.suspended',
                ])->orWhere('action', 'like', '%price%')
                    ->orWhere('action', 'like', '%refund%')
                    ->orWhere('action', 'like', 'booking.deleted%');
            })
            ->latest()
            ->limit(8)
            ->get();

        return compact(
            'tenant',
            'availableRoles',
            'pendingInvitations',
            'subscription',
            'totalRooms',
            'occupiedRooms',
            'monthlyRevenue',
            'yearlyRevenue',
            'auditFeed',
            'taxSummary',
        ) + [
            'netRevenue' => (float) $netRevenue,
            'profitMargin' => null,
            'averageDailyRate' => (float) ($averageDailyRate ?? 0),
            'occupancyRate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0,
        ];
    }

    /**
     * @param  array<string, array{label: string, feature: ?string, luxury_only?: bool}>  $availableRoles
     * @return array<string, mixed>
     */
    private function adminDashboardData(Tenant $tenant, array $availableRoles, Collection $pendingInvitations): array
    {
        $tenantId = (int) $tenant->id;
        $today = today();
        $bookings = fn (): Builder => Booking::query()->where('tenant_id', $tenantId);
        $rooms = Room::query()->where('tenant_id', $tenantId);
        $arrivals = $bookings()
            ->with(['guest', 'rooms'])
            ->whereDate('check_in_date', $today)
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->orderBy('check_in_date')
            ->get();
        $departures = $bookings()
            ->with(['guest', 'rooms'])
            ->whereDate('check_out_date', $today)
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->orderBy('check_out_date')
            ->get();
        $unassignedArrivals = $bookings()
            ->with('guest')
            ->whereDate('check_in_date', $today)
            ->where('booking_status', 'confirmed')
            ->whereDoesntHave('rooms')
            ->get();
        $roomGrid = (clone $rooms)
            ->with(['category', 'bookings' => fn ($query) => $query
                ->where('bookings.tenant_id', $tenantId)
                ->whereIn('booking_status', ['confirmed', 'checked_in'])
                ->whereDate('check_in_date', '<=', $today)
                ->whereDate('check_out_date', '>', $today)
                ->with('guest')])
            ->orderBy('room_number')
            ->get();
        $dirtyRooms = (clone $rooms)
            ->whereIn('housekeeping_status', ['dirty', 'cleaning', 'inspection_pending'])
            ->orderBy('room_number')
            ->limit(10)
            ->get();
        $cleanRooms = (clone $rooms)->where('housekeeping_status', 'clean')->count();
        $occupiedRooms = (clone $rooms)->where('occupancy_status', 'occupied')->count();
        $expectedShiftCash = HotelPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('payment_method', 'cash')
            ->whereDate('created_at', $today)
            ->sum('amount');

        return compact(
            'tenant',
            'availableRoles',
            'pendingInvitations',
            'arrivals',
            'departures',
            'unassignedArrivals',
            'roomGrid',
            'dirtyRooms',
            'cleanRooms',
            'occupiedRooms',
        ) + [
            'expectedShiftCash' => (float) $expectedShiftCash,
        ];
    }

    /**
     * Shift drawer totals for the owner ledger and the admin shift overview.
     *
     * @return array{open: ?CashShift, recent: Collection, monthCollected: float, monthVariance: float}
     */
    private function cashShiftSummary(Tenant $tenant): array
    {
        $closed = CashShift::query()->where('tenant_id', $tenant->id)->where('status', CashShift::STATUS_CLOSED);
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'open' => CashShift::query()->where('tenant_id', $tenant->id)->open()->with('opener:id,name')->latest('opened_at')->first(),
            'recent' => (clone $closed)->with(['opener:id,name', 'closer:id,name'])->latest('closed_at')->limit(10)->get(),
            'monthCollected' => (float) (clone $closed)->whereBetween('closed_at', $monthRange)->sum('cash_collected'),
            'monthVariance' => (float) (clone $closed)->whereBetween('closed_at', $monthRange)->sum('variance'),
        ];
    }
}
