<?php

namespace App\Http\Controllers\FrontOffice;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function executeCheckout(Request $request, int $reservationId): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $reservation = Reservation::query()
            ->where('tenant_id', $tenantId)
            ->find($reservationId);

        if (! $reservation instanceof Reservation) {
            throw ValidationException::withMessages([
                'reservation' => ['Reservation not found.'],
            ]);
        }

        $balance = $reservation->live_balance;

        if (round((float) $balance, 2) !== 0.00) {
            Log::warning('Checkout blocked due to outstanding balance.', [
                'reservation_id' => $reservation->id,
                'balance' => $balance,
            ]);

            throw ValidationException::withMessages([
                'balance' => ['The balance must be zero before checkout can proceed.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($reservation, $tenantId): JsonResponse {
                $reservation->status = 'Checkedout';
                $reservation->actual_checkout_at = now();
                $reservation->save();

                $room = Room::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($reservation->room_id)
                    ->first();
                if ($room instanceof Room) {
                    $room->occupancy_status = 'vacant';
                    $room->housekeeping_status = 'dirty';
                    $room->save();
                }

                return response()->json([
                    'status' => 'Checkedout',
                    'reservation_id' => $reservation->id,
                    'checked_out_at' => $reservation->actual_checkout_at->toIso8601String(),
                    'room_number' => $reservation->room_number,
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
}
