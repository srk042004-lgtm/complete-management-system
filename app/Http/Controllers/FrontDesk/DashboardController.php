<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\HotelPayment;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $tenantId = (int) request()->user()->tenant_id;
        $today = Carbon::today();

        $tenant = Tenant::query()->findOrFail($tenantId);
        $rooms = Room::query()->where('tenant_id', $tenantId);
        $totalRooms = (clone $rooms)->count();
        $occupiedRooms = (clone $rooms)->where('occupancy_status', 'occupied')->count();
        $dirtyRooms = (clone $rooms)->whereIn('housekeeping_status', ['dirty', 'cleaning', 'inspection_pending'])->count();
        $maintenanceRooms = (clone $rooms)->where('housekeeping_status', 'maintenance')->count();

        $reservedRoomIds = DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->where('bookings.tenant_id', $tenantId)
            ->whereIn('bookings.booking_status', ['confirmed', 'checked_in'])
            ->whereDate('bookings.check_in_date', '<=', $today)
            ->whereDate('bookings.check_out_date', '>', $today)
            ->pluck('booking_rooms.room_id')
            ->unique();

        $reservedRooms = (clone $rooms)->whereIn('id', $reservedRoomIds)->count();
        $availableRooms = max(0, $totalRooms - $occupiedRooms - $reservedRooms);
        $occupancy = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        $bookingQuery = Booking::query()->where('tenant_id', $tenantId);
        $arrivals = (clone $bookingQuery)->with(['guest', 'rooms'])->whereDate('check_in_date', $today)->whereIn('booking_status', ['confirmed', 'checked_in'])->orderBy('check_in_date')->limit(8)->get();
        $departures = (clone $bookingQuery)->with(['guest', 'rooms'])->whereDate('check_out_date', $today)->whereIn('booking_status', ['confirmed', 'checked_in'])->orderBy('check_out_date')->limit(8)->get();
        $recentBookings = (clone $bookingQuery)->with(['guest', 'rooms'])->latest('created_at')->limit(8)->get();

        $todayRevenue = HotelPayment::query()->where('tenant_id', $tenantId)->whereDate('created_at', $today)->sum('amount');

        $revenuePeriod = in_array(request('revenue_period'), ['daily', 'weekly', 'monthly', 'annual', 'overall'], true)
            ? request('revenue_period')
            : 'monthly';
        $revenueRange = match ($revenuePeriod) {
            'daily' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'weekly' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'annual' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'overall' => null,
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
        $revenueQuery = HotelPayment::query()
            ->where('tenant_id', $tenantId)
            ->when($revenueRange !== null, fn ($query) => $query->whereBetween('created_at', $revenueRange));
        $periodRevenue = (clone $revenueQuery)->sum('amount');
        $revenueByDate = (clone $revenueQuery)
            ->selectRaw('DATE(created_at) as revenue_date, SUM(amount) as total')
            ->groupBy('revenue_date')
            ->orderByDesc('revenue_date')
            ->limit(14)
            ->get();
        $paymentDetails = (clone $revenueQuery)->with('booking.guest')->latest('created_at')->limit(10)->get();
        $revenueLabel = [
            'daily' => 'Today Revenue',
            'weekly' => 'This week Revenue',
            'monthly' => 'This month Revenue',
            'annual' => 'This year Revenue',
            'overall' => 'Overall Revenue',
        ][$revenuePeriod];
        $revenuePeriods = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'annual' => 'Annual',
            'overall' => 'Overall',
        ];
        $pendingPayments = (clone $bookingQuery)->whereIn('booking_status', ['confirmed', 'checked_in'])->selectRaw('COALESCE(SUM(CASE WHEN grand_total > paid_amount THEN grand_total - paid_amount ELSE 0 END), 0) as total')->value('total');
        $currentGuests = (clone $bookingQuery)->where('booking_status', 'checked_in')->sum('total_guests');

        $roomTypeStatuses = (clone $rooms)
            ->selectRaw("COALESCE(room_type, 'Unassigned') as room_type, COUNT(*) as total, SUM(occupancy_status = 'occupied') as occupied, SUM(housekeeping_status IN ('dirty', 'cleaning', 'inspection_pending')) as housekeeping, SUM(housekeeping_status = 'maintenance') as maintenance")
            ->groupBy('room_type')
            ->orderBy('room_type')
            ->get();

        $activity = $this->activity($tenantId, $recentBookings, $todayRevenue);

        return view('frontdesk.dashboard', compact(
            'tenant', 'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'dirtyRooms',
            'maintenanceRooms', 'occupancy', 'arrivals', 'departures', 'recentBookings',
            'todayRevenue', 'periodRevenue', 'revenueLabel', 'revenuePeriod', 'revenuePeriods', 'revenueByDate', 'paymentDetails',
            'pendingPayments', 'currentGuests', 'roomTypeStatuses', 'activity'
        ));
    }

    private function activity(int $tenantId, $recentBookings, $todayRevenue): array
    {
        $items = $recentBookings->map(fn (Booking $booking): array => [
            'title' => 'Reservation '.$booking->booking_code.' created',
            'detail' => $booking->guest?->full_name ?? 'Guest',
            'time' => $booking->created_at?->diffForHumans(),
        ])->values()->all();

        if ((float) $todayRevenue > 0) {
            array_unshift($items, ['title' => 'Payment received today', 'detail' => number_format((float) $todayRevenue, 2), 'time' => 'Today']);
        }

        return array_slice($items, 0, 6);
    }
}
