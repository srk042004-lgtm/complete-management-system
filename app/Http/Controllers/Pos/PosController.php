<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FoodCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;

        // Only available menu items, grouped by category, so the POS never offers something the kitchen cannot serve.
        $categories = FoodCategory::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->with(['items' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $guests = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_status', 'checked_in')
            ->with(['guest:id,first_name,last_name', 'rooms:id,room_number'])
            ->orderBy('check_out_date')
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'room_id' => $booking->rooms->first()?->id,
                'label' => trim(($booking->guest?->first_name ?? '').' '.($booking->guest?->last_name ?? '')).' · Room '.($booking->rooms->pluck('room_number')->join(', ') ?: '—'),
            ])
            ->filter(fn (array $guest): bool => $guest['room_id'] !== null)
            ->values();

        return view('pos.index', [
            'categories' => $categories,
            'guests' => $guests,
            'chargeRouteTemplate' => route('frontdesk.in-house.charges.store', ['booking' => '__BOOKING__']),
        ]);
    }
}
