<?php

namespace App\Http\Controllers\Reservations;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationsController extends Controller
{
    private const STATUSES = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : 'confirmed';

        $bookings = Booking::query()
            ->with(['guest', 'rooms'])
            ->where('tenant_id', $tenantId)
            ->where('booking_status', $status)
            ->orderBy('check_in_date')
            ->get();

        // Counts for the filter chips, computed in one query.
        $statusCounts = Booking::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('booking_status, COUNT(*) as total')
            ->groupBy('booking_status')
            ->pluck('total', 'booking_status');

        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';

        return view('reservations.index', compact('bookings', 'currencyCode', 'status', 'statusCounts'));
    }
}
