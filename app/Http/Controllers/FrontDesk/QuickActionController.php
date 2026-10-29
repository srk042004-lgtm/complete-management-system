<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\HotelOperationLog;
use App\Models\HotelPayment;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Support\HotelRoleCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuickActionController extends Controller
{
    public function createGuest(): View
    {
        return view('frontdesk.quick-actions.add-guest');
    }

    public function storeGuest(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'between:-9999999999.99,9999999999.99'],
            'address' => ['nullable', 'string', 'max:2000'],
            'id_type' => ['required', Rule::in(['cnic_national_id', 'passport', 'drivers_license'])],
            'id_number' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $name = preg_split('/\s+/', trim($validated['name']), 2);
        $guestData = [
            'tenant_id' => $tenantId,
            'first_name' => $name[0],
            'last_name' => $name[1] ?? '',
            'phone' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'opening_balance' => $validated['opening_balance'],
            'address' => $validated['address'] ?? null,
            'id_type' => $validated['id_type'],
            'id_number' => $validated['id_number'],
        ];

        foreach (['photo' => 'guest-photos', 'id_document' => 'guest-documents'] as $input => $directory) {
            if ($request->hasFile($input)) {
                $disk = $input === 'id_document' ? 'local' : 'public';
                $path = $request->file($input)->store($directory, $disk);
                if ($path === false) {
                    throw new \RuntimeException("The guest {$input} could not be saved.");
                }
                $guestData[$input === 'photo' ? 'photo_path' : 'id_document_path'] = $path;
            }
        }

        $guest = DB::transaction(function () use ($request, $guestData, $tenantId): Guest {
            $guest = Guest::query()->create($guestData);
            $this->audit($request, 'guest.created', $guest, [
                'name' => $guest->full_name,
                'email' => $guest->email,
            ], $tenantId);

            return $guest;
        });

        return redirect()->route('frontdesk.guests.create')
            ->with('success', "Guest {$guest->full_name} was added.");
    }

    public function checkInIndex(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['guest', 'rooms'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('booking_status', 'confirmed')
            ->whereDate('check_in_date', '<=', today())
            ->orderBy('check_in_date')
            ->get();

        return view('frontdesk.quick-actions.check-in', compact('bookings'));
    }

    public function checkIn(Request $request, int $bookingId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId, $bookingId): void {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->booking_status !== 'confirmed' || $booking->check_in_date->isFuture()) {
                throw ValidationException::withMessages([
                    'booking' => 'Only confirmed reservations due today or earlier can be checked in.',
                ]);
            }

            $rooms = $booking->rooms()->where('rooms.tenant_id', $tenantId)->lockForUpdate()->get();
            if ($rooms->isEmpty()) {
                throw ValidationException::withMessages(['booking' => 'The reservation has no assigned room.']);
            }
            foreach ($rooms as $room) {
                if (Str::lower($room->occupancy_status) !== 'vacant'
                    || in_array(Str::lower($room->housekeeping_status), ['maintenance', 'out_of_order'], true)) {
                    throw ValidationException::withMessages([
                        'booking' => "Room {$room->room_number} is not available for check-in.",
                    ]);
                }
            }

            $booking->forceFill([
                'booking_status' => 'checked_in',
                'actual_check_in' => now(),
            ])->save();

            foreach ($rooms as $room) {
                $room->forceFill(['occupancy_status' => 'occupied'])->save();
                $nights = max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date)));
                $roomTotal = round((float) $room->pivot->price_per_night * $nights, 2);
                $this->createBookingFolioCharge($booking, 'Room accommodation', $roomTotal);
            }

            foreach ($booking->foodPackages as $package) {
                $description = $package->billing_type === 'one_time_order' ? 'Room service: ' : 'Food package: ';
                $this->createBookingFolioCharge($booking, $description.$package->package_name, (float) $package->total_amount);
            }

            $this->audit($request, 'booking.checked_in', $booking, [
                'booking_code' => $booking->booking_code,
                'room_ids' => $rooms->modelKeys(),
            ], $tenantId);
        });

        return redirect()->route('frontdesk.check-in')->with('success', 'Guest checked in and room status updated.');
    }

    public function checkOutIndex(Request $request): View
    {
        $bookings = Booking::query()
            ->with([
                'guest',
                'rooms',
                'folioEntries' => fn ($query) => $query->where('is_reversed', false)->orderBy('id'),
            ])
            ->withSum(['folioEntries as folio_charges' => fn ($query) => $query->where('type', 'charge')->where('is_reversed', false)], 'total')
            ->withSum(['folioEntries as folio_receipts' => fn ($query) => $query->where('type', 'receipt')->where('is_reversed', false)], 'total')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('booking_status', 'checked_in')
            ->orderBy('check_out_date')
            ->get();
        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';

        return view('frontdesk.quick-actions.check-out', compact('bookings', 'currencyCode'));
    }

    public function checkOutHistory(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['guest', 'rooms'])
            ->withSum(['folioEntries as folio_charges' => fn ($query) => $query->where('type', 'charge')->where('is_reversed', false)], 'total')
            ->withSum(['folioEntries as folio_receipts' => fn ($query) => $query->where('type', 'receipt')->where('is_reversed', false)], 'total')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('booking_status', 'checked_out')
            ->orderByDesc('actual_check_out')
            ->get();
        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';

        return view('frontdesk.quick-actions.check-out-history', compact('bookings', 'currencyCode'));
    }

    public function checkOut(Request $request, int $bookingId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer'])],
            // Card and bank transfers are online payments, so they need the transaction ID from the gateway or bank.
            'transaction_reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn (): bool => in_array($request->input('payment_method'), ['card', 'bank_transfer'], true)),
            ],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        DB::transaction(function () use ($request, $tenantId, $bookingId, $validated): void {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->booking_status !== 'checked_in') {
                throw ValidationException::withMessages(['booking' => 'Only checked-in reservations can be checked out.']);
            }

            $balance = $this->bookingFolioBalance($booking->id);
            if ($balance > 0.009 && empty($validated['payment_method'])) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Select a payment method to settle the outstanding folio balance.',
                ]);
            }

            if ($balance > 0.009) {
                $reference = filled($validated['transaction_reference'] ?? null) ? trim((string) $validated['transaction_reference']) : null;
                if ($reference !== null && HotelPayment::query()->where('tenant_id', $tenantId)->where('transaction_reference', $reference)->exists()) {
                    throw ValidationException::withMessages([
                        'transaction_reference' => 'This transaction ID has already been recorded for this hotel.',
                    ]);
                }

                $proofPath = $request->file('payment_proof')?->store('payment_proofs/'.$tenantId, 'public');

                $payment = HotelPayment::query()->create([
                    'tenant_id' => $tenantId,
                    'booking_id' => $booking->id,
                    'payment_method' => $validated['payment_method'],
                    'transaction_reference' => $reference,
                    'proof_path' => $proofPath,
                    'amount' => $balance,
                    'recorded_by' => $request->user()->id,
                ]);
                $booking->forceFill([
                    'paid_amount' => round((float) $booking->paid_amount + $balance, 2),
                ])->save();
                $this->createBookingFolioEntry(
                    $booking,
                    'receipt',
                    'Checkout settlement - '.$payment->payment_method,
                    $balance
                );
                $this->audit($request, 'payment.recorded', $payment, [
                    'booking_code' => $booking->booking_code,
                    'amount' => $balance,
                    'payment_method' => $payment->payment_method,
                    'transaction_reference' => null,
                    'reason' => 'checkout_settlement',
                ], $tenantId);
            }

            $rooms = $booking->rooms()->where('rooms.tenant_id', $tenantId)->lockForUpdate()->get();
            $booking->forceFill([
                'booking_status' => 'checked_out',
                'actual_check_out' => now(),
            ])->save();
            foreach ($rooms as $room) {
                $room->forceFill([
                    'occupancy_status' => 'vacant',
                    'housekeeping_status' => 'dirty',
                ])->save();
                // Checkout turns the room into a high-priority housekeeping task for the next guest.
                \App\Models\HousekeepingTask::query()->create([
                    'tenant_id' => $tenantId,
                    'room_id' => $room->id,
                    'service_type' => 'checkout_clean',
                    'status' => 'open',
                    'priority' => true,
                ]);
            }

            $this->audit($request, 'booking.checked_out', $booking, [
                'booking_code' => $booking->booking_code,
                'room_ids' => $rooms->modelKeys(),
                'folio_balance' => $balance,
                'settled_amount' => max($balance, 0),
            ], $tenantId);
        }, 3);

        return redirect()->route('frontdesk.check-out.history')->with('success', 'Folio settled and guest checked out. Assigned room(s) are now marked dirty.');
    }

    public function roomStatusIndex(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validatedFilter = $request->validate([
            'category' => ['nullable', 'integer', Rule::exists('room_types', 'id')->where('tenant_id', $tenantId)],
        ]);
        $categories = RoomCategory::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get();
        $rooms = Room::query()
            ->with(['category', 'bookings' => fn ($query) => $query
                ->whereIn('booking_status', ['confirmed', 'checked_in'])
                ->whereDate('check_in_date', '<=', today())
                ->whereDate('check_out_date', '>', today())
                ->with('guest')])
            ->where('tenant_id', $tenantId)
            ->when(isset($validatedFilter['category']), fn ($query) => $query->where('room_type_id', $validatedFilter['category']))
            ->orderBy('room_number')
            ->get();
        $selectedCategory = $validatedFilter['category'] ?? null;

        return view('frontdesk.quick-actions.room-status', compact('rooms', 'categories', 'selectedCategory'));
    }

    public function updateRoomStatus(Request $request, int $roomId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $allowedStatuses = HotelRoleCatalog::normalize((string) $request->user()->role) === 'housekeeping'
            ? ['clean', 'dirty', 'cleaning', 'inspection_pending', 'maintenance']
            : ['clean', 'dirty', 'cleaning', 'inspection_pending', 'maintenance', 'out_of_order'];
        $validated = $request->validate([
            'housekeeping_status' => ['required', Rule::in($allowedStatuses)],
            'category' => ['nullable', 'integer', Rule::exists('room_types', 'id')->where('tenant_id', $tenantId)],
            'return_to' => ['nullable', Rule::in(['housekeeping'])],
        ]);

        DB::transaction(function () use ($request, $tenantId, $roomId, $validated): void {
            $room = Room::query()->where('tenant_id', $tenantId)->whereKey($roomId)->lockForUpdate()->firstOrFail();
            if (Str::lower($room->occupancy_status) === 'occupied'
                && in_array($validated['housekeeping_status'], ['maintenance', 'out_of_order'], true)) {
                throw ValidationException::withMessages([
                    'housekeeping_status' => 'An occupied room cannot be set to maintenance or out of order.',
                ]);
            }

            $previous = $room->housekeeping_status;
            $room->forceFill(['housekeeping_status' => $validated['housekeeping_status']])->save();
            $this->audit($request, 'room.status_updated', $room, [
                'previous' => $previous,
                'current' => $room->housekeeping_status,
            ], $tenantId);
        });

        $filter = isset($validated['category']) ? ['category' => $validated['category']] : [];

        $destination = ($validated['return_to'] ?? null) === 'housekeeping'
            ? 'housekeeping.index'
            : 'frontdesk.rooms.status';

        return redirect()->route($destination, $filter)->with('success', 'Room status updated.');
    }

    public function createPayment(Request $request): View
    {
        $bookings = Booking::query()
            ->with('guest')
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->orderBy('check_in_date')
            ->get();
        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';

        return view('frontdesk.quick-actions.add-payment', compact('bookings', 'currencyCode'));
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'booking_id' => ['required', Rule::exists('bookings', 'id')->where('tenant_id', $tenantId)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer'])],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $tenantId, $validated): void {
            $booking = Booking::query()->where('tenant_id', $tenantId)->whereKey($validated['booking_id'])
                ->lockForUpdate()->firstOrFail();
            if (! in_array($booking->booking_status, ['confirmed', 'checked_in'], true)) {
                throw ValidationException::withMessages(['booking_id' => 'Payments can only be added to active reservations.']);
            }

            $outstanding = round((float) $booking->grand_total - (float) $booking->paid_amount, 2);
            if ((float) $validated['amount'] > $outstanding + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the remaining reservation balance.',
                ]);
            }

            $payment = HotelPayment::query()->create([
                'tenant_id' => $tenantId,
                'booking_id' => $booking->id,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'recorded_by' => $request->user()->id,
            ]);
            $booking->forceFill([
                'paid_amount' => round((float) $booking->paid_amount + (float) $validated['amount'], 2),
            ])->save();
            $this->createBookingFolioEntry($booking, 'receipt', 'Payment received - '.$payment->payment_method, (float) $payment->amount);
            $this->audit($request, 'payment.recorded', $payment, [
                'booking_code' => $booking->booking_code,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_reference' => $payment->transaction_reference,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.payments.create')->with('success', 'Payment recorded and reservation balance updated.');
    }

    private function bookingFolioBalance(int $bookingId): float
    {
        $charges = (float) Folio::query()->where('booking_id', $bookingId)->where('type', 'charge')
            ->where('is_reversed', false)->sum('total');
        $receipts = (float) Folio::query()->where('booking_id', $bookingId)->where('type', 'receipt')
            ->where('is_reversed', false)->sum('total');

        return round($charges - $receipts, 2);
    }

    private function createBookingFolioCharge(Booking $booking, string $description, float $total): void
    {
        $this->createBookingFolioEntry($booking, 'charge', $description, $total);
    }

    private function createBookingFolioEntry(Booking $booking, string $type, string $description, float $total): void
    {
        Folio::query()->create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'invoice_number' => 'FOL-'.Str::upper(Str::random(12)),
            'type' => $type,
            'item_category' => $description,
            'amount' => $total,
            'tax' => 0,
            'total' => $total,
            'is_reversed' => false,
            'created_at' => now(),
        ]);
    }

    private function audit(Request $request, string $action, Model $subject, array $details, int $tenantId): void
    {
        HotelOperationLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'details' => $details,
            'ip_address' => $request->ip(),
        ]);
    }
}
