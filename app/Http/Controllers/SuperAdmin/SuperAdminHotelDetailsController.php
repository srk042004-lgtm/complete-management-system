<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Room;
use App\Models\SaasPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

/** Read-only profile of one hotel for the Software House team. */
class SuperAdminHotelDetailsController extends Controller
{
    public function show(Tenant $tenant): View
    {
        $subscription = $tenant->subscriptions()->latest('id')->first();

        $staff = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'last_login_at']);

        $payments = SaasPayment::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->limit(8)
            ->get();

        $activity = ActivityLog::query()
            ->where('target_type', $tenant->getMorphClass())
            ->where('target_id', $tenant->id)
            ->latest('id')
            ->limit(8)
            ->get();

        $stats = [
            'rooms' => Room::query()->where('tenant_id', $tenant->id)->count(),
            'bookings' => Booking::query()->where('tenant_id', $tenant->id)->count(),
            'in_house' => Booking::query()->where('tenant_id', $tenant->id)->where('booking_status', 'checked_in')->count(),
            'revenue' => (float) SaasPayment::query()->where('tenant_id', $tenant->id)->where('payment_status', 'paid')->sum('amount'),
        ];

        return view('super-admin.hotel-details', compact('tenant', 'subscription', 'staff', 'payments', 'activity', 'stats'));
    }
}
