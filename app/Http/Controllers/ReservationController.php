<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservationController extends Controller
{
    public function checkout(Request $request, Reservation $reservation): JsonResponse
    {
        return $this->processCheckout($request, (int) $reservation->id);
    }

    public function processCheckout(Request $request, int $reservationId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $reservation = Reservation::with('folios', 'room')
            ->whereHas('room', fn ($query) => $query->where('tenant_id', $tenantId))
            ->find($reservationId);

        if (! $reservation instanceof Reservation) {
            return response()->json([
                'message' => 'Reservation not found.',
            ], 404);
        }

        if ($reservation->status !== 'Inhouse') {
            return response()->json([
                'message' => 'Reservations can only be checked out from the Inhouse state.',
            ], 422);
        }

        $charges = $reservation->folios
            ->where('type', 'charge')
            ->sum('total');

        $receipts = $reservation->folios
            ->where('type', 'receipt')
            ->sum('total');

        $balance = round((float) ($charges - $receipts), 2);

        if ($balance !== 0.00) {
            Log::warning('Checkout blocked due to outstanding folio balance.', [
                'reservation_id' => $reservation->id,
                'balance' => $balance,
            ]);

            return response()->json([
                'message' => 'The folio balance must be zero before checkout can proceed.',
                'balance' => number_format($balance, 2, '.', ''),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($reservation, $tenantId, $balance): JsonResponse {
                $reservation->status = 'Checkedout';
                $reservation->actual_checkout_at = now();
                $reservation->save();

                $room = Room::where('tenant_id', $tenantId)
                    ->where('room_number', $reservation->room_number)
                    ->first();

                if ($room instanceof Room) {
                    $room->occupancy_status = 'vacant';
                    $room->housekeeping_status = 'dirty';
                    $room->save();
                } else {
                    Log::warning('Checkout completed without a matching room record.', [
                        'reservation_id' => $reservation->id,
                        'room_number' => $reservation->room_number,
                    ]);
                }

                return response()->json([
                    'status' => $reservation->status,
                    'checked_out_at' => $reservation->actual_checkout_at?->toIso8601String(),
                    'room_number' => $reservation->room_number,
                    'balance' => number_format($balance, 2, '.', ''),
                    'room_status' => $room?->occupancy_status,
                    'housekeeping_status' => $room?->housekeeping_status,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Log::error('Checkout transaction failed.', [
                'reservation_id' => $reservation->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function calculateActiveFolioBalance(int $reservationId): float
    {
        $balance = DB::table('folios')
            ->where('reservation_id', $reservationId)
            ->where('is_reversed', false)
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN COALESCE(total, 0) ELSE 0 END) - SUM(CASE WHEN type = ? THEN COALESCE(total, 0) ELSE 0 END) AS balance',
                ['charge', 'receipt']
            )
            ->value('balance');

        return round((float) ($balance ?? 0), 2);
    }
}
