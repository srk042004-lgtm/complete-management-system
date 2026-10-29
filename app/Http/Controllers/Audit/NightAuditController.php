<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CashShift;
use App\Models\HotelOperationLog;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NightAuditController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $today = now()->toDateString();

        $bookings = Booking::query()->where('tenant_id', $tenantId);
        $rooms = Room::query()->where('tenant_id', $tenantId);

        $summary = [
            'arrivals_pending' => (clone $bookings)->whereDate('check_in_date', $today)->where('booking_status', 'confirmed')->count(),
            'departures_pending' => (clone $bookings)->whereDate('check_out_date', '<=', $today)->where('booking_status', 'checked_in')->count(),
            'in_house' => (clone $bookings)->where('booking_status', 'checked_in')->count(),
            'in_house_balance' => (float) (clone $bookings)->where('booking_status', 'checked_in')
                ->selectRaw("COALESCE(SUM(CASE WHEN grand_total > paid_amount THEN grand_total - paid_amount ELSE 0 END), 0) as balance")
                ->value('balance'),
            'dirty_rooms' => (clone $rooms)->whereIn('housekeeping_status', ['dirty', 'cleaning', 'inspection_pending'])->count(),
            'maintenance_rooms' => (clone $rooms)->where('housekeeping_status', 'maintenance')->count(),
            'open_shifts' => CashShift::query()->where('tenant_id', $tenantId)->open()->count(),
        ];

        $lastRuns = HotelOperationLog::query()
            ->where('tenant_id', $tenantId)
            ->where('action', 'night_audit.run')
            ->with('actor:id,name')
            ->latest('id')
            ->limit(6)
            ->get();

        $checks = [
            ['label' => 'Cash shifts closed', 'ok' => $summary['open_shifts'] === 0, 'detail' => $summary['open_shifts'] === 0 ? 'No open drawer' : $summary['open_shifts'].' shift(s) still open'],
            ['label' => 'Departures settled', 'ok' => $summary['departures_pending'] === 0, 'detail' => $summary['departures_pending'] === 0 ? 'Nothing outstanding' : $summary['departures_pending'].' guest(s) still in house past checkout'],
            ['label' => 'In-house balances', 'ok' => $summary['in_house_balance'] <= 0, 'detail' => $summary['in_house_balance'] <= 0 ? 'All folios settled' : 'Outstanding: '.number_format($summary['in_house_balance'], 2)],
            ['label' => 'Rooms ready', 'ok' => $summary['dirty_rooms'] === 0 && $summary['maintenance_rooms'] === 0, 'detail' => $summary['dirty_rooms'] === 0 && $summary['maintenance_rooms'] === 0 ? 'All rooms clean' : $summary['dirty_rooms'].' dirty, '.$summary['maintenance_rooms'].' under maintenance'],
        ];

        return view('audit.night', compact('summary', 'lastRuns', 'checks'));
    }

    /** Closes the operating day: refuses while a drawer is open, otherwise records a signed audit entry. */
    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Confirm the checks before running the night audit.']);
        $tenantId = (int) $request->user()->tenant_id;

        $openShifts = CashShift::query()->where('tenant_id', $tenantId)->open()->count();
        if ($openShifts > 0) {
            return back()->withErrors(['confirm' => 'Close every open cash shift before running the night audit.']);
        }

        DB::transaction(function () use ($request, $tenantId): void {
            HotelOperationLog::query()->create([
                'tenant_id' => $tenantId,
                'actor_id' => $request->user()->id,
                'action' => 'night_audit.run',
                'subject_type' => 'tenant',
                'subject_id' => $tenantId,
                'details' => ['business_date' => now()->toDateString(), 'confirmed' => (bool) $validated['confirm']],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('audit.night')->with('success', 'Night audit completed and recorded for '.now()->format('d M Y').'.');
    }
}
