<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Folio;
use App\Models\FoodCategory;
use App\Models\HotelOperationLog;
use App\Models\MenuItem;
use App\Models\Room;
use App\Support\HotelExtraServiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InHouseGuestsController extends Controller
{
    private const CATEGORIES = [
        'food_beverage' => 'Food & Beverage / Room Service',
        'laundry' => 'Laundry',
        'minibar' => 'Minibar',
        'late_checkout_extra_bed' => 'Late Checkout / Extra Bed',
        'damage_miscellaneous' => 'Damage / Miscellaneous',
    ];

    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');
        app(HotelExtraServiceCatalog::class)->ensureForTenant($tenantId);
        $bookings = Booking::query()
            ->with([
                'guest',
                'rooms.category',
                'folioEntries' => fn ($query) => $query->where('is_reversed', false)->orderBy('created_at'),
            ])
            ->withSum(['folioEntries as folio_charges' => fn ($query) => $query->where('type', 'charge')->where('is_reversed', false)], 'total')
            ->withSum(['folioEntries as folio_receipts' => fn ($query) => $query->where('type', 'receipt')->where('is_reversed', false)], 'total')
            ->where('tenant_id', $tenantId)
            ->where('booking_status', 'checked_in')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($bookingQuery) use ($search): void {
                    $bookingQuery->whereHas('guest', fn ($guestQuery) => $guestQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('rooms', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('check_out_date')
            ->paginate(15)
            ->withQueryString();
        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';
        $menuCategories = FoodCategory::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->with(['items' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $serviceGroups = [
            'food' => ['label' => 'Food & Dining', 'icon' => '🍽️', 'categories' => collect()],
            'laundry' => ['label' => 'Laundry & Pressing', 'icon' => '🧺', 'categories' => collect()],
            'minibar' => ['label' => 'Minibar & Drinks', 'icon' => '🥤', 'categories' => collect()],
            'housekeeping' => ['label' => 'Housekeeping & Extra Amenities', 'icon' => '🛏️', 'categories' => collect()],
            'misc' => ['label' => 'Miscellaneous', 'icon' => '✨', 'categories' => collect()],
        ];
        foreach ($menuCategories as $menuCategory) {
            $serviceGroups[$this->serviceGroup($menuCategory->name)]['categories']->push($menuCategory);
        }
        $serviceGroups = array_filter($serviceGroups, fn (array $group): bool => $group['categories']->isNotEmpty());

        return view('frontdesk.quick-actions.in-house', compact('bookings', 'currencyCode', 'search', 'menuCategories', 'serviceGroups'));
    }

    private function serviceGroup(string $categoryName): string
    {
        $name = strtolower($categoryName);

        return match (true) {
            str_contains($name, 'laundry'), str_contains($name, 'cleaning'), str_contains($name, 'press') => 'laundry',
            str_contains($name, 'food'), str_contains($name, 'dining'), str_contains($name, 'restaurant') => 'food',
            str_contains($name, 'minibar'), str_contains($name, 'drink'), str_contains($name, 'beverage') => 'minibar',
            str_contains($name, 'housekeep'), str_contains($name, 'extra bed'), str_contains($name, 'late checkout') => 'housekeeping',
            str_contains($name, 'misc'), str_contains($name, 'service fee'), str_contains($name, 'amenit') => 'misc',
            default => 'food',
        };
    }

    public function storeCharge(Request $request, int $bookingId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('booking_rooms', 'room_id')->where('booking_id', $bookingId)->where('tenant_id', $tenantId)],
            'payment_option' => ['required', Rule::in(['post_to_room', 'paid_immediately'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer'])],
            'cart_items' => ['nullable', 'required_without:description', 'json', 'max:20000'],
            'description' => ['nullable', 'required_without:cart_items', 'string', 'max:255'],
            'amount' => ['nullable', 'required_without:cart_items', 'numeric', 'gt:0', 'max:99999999.99'],
            'quantity' => ['nullable', 'required_without:cart_items', 'integer', 'min:1', 'max:1000'],
            'category' => ['nullable', 'required_without:cart_items', Rule::in(array_keys(self::CATEGORIES))],
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $cartItems = isset($validated['cart_items'])
            ? json_decode($validated['cart_items'], true, 512, JSON_THROW_ON_ERROR)
            : [[
                'type' => 'custom',
                'name' => $validated['description'],
                'unit_price' => $validated['amount'],
                'category' => $validated['category'],
                'quantity' => $validated['quantity'],
            ]];
        $itemsValidated = Validator::make(['items' => $cartItems], [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.type' => ['required', Rule::in(['menu', 'custom'])],
            'items.*.id' => ['required_if:items.*.type,menu', 'nullable', 'integer'],
            'items.*.name' => ['required_if:items.*.type,custom', 'nullable', 'string', 'max:255'],
            'items.*.unit_price' => ['required_if:items.*.type,custom', 'nullable', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.category' => ['required_if:items.*.type,custom', 'nullable', Rule::in(array_keys(self::CATEGORIES))],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ])->validate()['items'];

        DB::transaction(function () use ($request, $tenantId, $bookingId, $validated, $itemsValidated, $paymentMethod): void {
            $booking = Booking::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($booking->booking_status === 'checked_in', 409, 'Charges can only be added to an in-house guest.');

            $room = Room::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($validated['room_id'])
                ->whereHas('bookings', fn ($query) => $query->whereKey($booking->id))
                ->firstOrFail();

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $total = 0.0;
            $firstFolio = null;
            $lineDetails = [];
            foreach ($itemsValidated as $item) {
                if ($item['type'] === 'menu') {
                    $menuItem = MenuItem::query()
                        ->where('tenant_id', $tenantId)
                        ->where('is_available', true)
                        ->with('category')
                        ->lockForUpdate()
                        ->findOrFail($item['id']);
                    abort_unless($menuItem->category?->tenant_id === $tenantId, 404);
                    $name = $menuItem->name;
                    $unitPrice = (float) $menuItem->price;
                    $categoryLabel = $menuItem->category->name;
                } else {
                    $name = trim($item['name']);
                    $unitPrice = (float) $item['unit_price'];
                    $categoryLabel = self::CATEGORIES[$item['category']];
                }

                $lineTotal = round($unitPrice * (int) $item['quantity'], 2);
                abort_if($lineTotal <= 0 || $lineTotal > 99999999.99, 422, 'A calculated charge amount is outside the allowed range.');
                $lineTax = round($lineTotal * 0.10, 2);
                $lineGrandTotal = round($lineTotal + $lineTax, 2);
                $subtotal = round($subtotal + $lineTotal, 2);
                $taxTotal = round($taxTotal + $lineTax, 2);
                $total = round($total + $lineGrandTotal, 2);
                abort_if($total > 99999999.99, 422, 'The order total is outside the allowed range.');

                $lineDescription = "{$categoryLabel}: {$name} (Qty: {$item['quantity']}) · Room {$room->room_number}";
                $note = trim($item['note'] ?? '');
                if ($note !== '') {
                    $lineDescription .= ' · Note: '.$note;
                }
                $lineDescription = \Illuminate\Support\Str::limit($lineDescription, 255, '');
                $folio = Folio::query()->create([
                    'tenant_id' => $tenantId,
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'invoice_number' => 'FOL-'.strtoupper(bin2hex(random_bytes(6))),
                    'type' => 'charge',
                    'item_category' => $lineDescription,
                    'amount' => $lineTotal,
                    'tax' => $lineTax,
                    'total' => $lineGrandTotal,
                    'is_reversed' => false,
                    'created_at' => now(),
                ]);
                $firstFolio ??= $folio;
                $lineDetails[] = [
                    'name' => $name,
                    'category' => $categoryLabel,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $unitPrice,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                    'grand_total' => $lineGrandTotal,
                    'note' => $note !== '' ? $note : null,
                ];
            }

            $updates = [
                'subtotal_amount' => round((float) $booking->subtotal_amount + $subtotal, 2),
                'tax_amount' => round((float) $booking->tax_amount + $taxTotal, 2),
                'grand_total' => round((float) $booking->grand_total + $total, 2),
            ];
            if ($validated['payment_option'] === 'paid_immediately') {
                $payment = $booking->payments()->create([
                    'tenant_id' => $tenantId,
                    'payment_method' => $paymentMethod,
                    'amount' => $total,
                    'recorded_by' => $request->user()->id,
                ]);
                $updates['paid_amount'] = round((float) $booking->paid_amount + $total, 2);
                Folio::query()->create([
                    'tenant_id' => $tenantId,
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'invoice_number' => 'FOL-'.strtoupper(bin2hex(random_bytes(6))),
                    'type' => 'receipt',
                    'item_category' => 'Immediate room-service payment - '.$paymentMethod,
                    'amount' => $total,
                    'tax' => $taxTotal,
                    'total' => $total,
                    'is_reversed' => false,
                    'created_at' => now(),
                ]);
                HotelOperationLog::query()->create([
                    'tenant_id' => $tenantId,
                    'actor_id' => $request->user()->id,
                    'action' => 'payment.recorded',
                    'subject_type' => $payment::class,
                    'subject_id' => $payment->id,
                    'details' => [
                        'booking_code' => $booking->booking_code,
                        'amount' => $total,
                        'subtotal' => $subtotal,
                        'tax' => $taxTotal,
                        'tax_rate' => 0.10,
                        'payment_method' => $paymentMethod,
                        'reason' => 'immediate_room_charge',
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }
            $booking->forceFill($updates)->save();

            HotelOperationLog::query()->create([
                'tenant_id' => $tenantId,
                'actor_id' => $request->user()->id,
                'action' => 'folio.charge_added',
                'subject_type' => $firstFolio::class,
                'subject_id' => $firstFolio->id,
                'details' => [
                    'booking_code' => $booking->booking_code,
                    'room_id' => $room->id,
                    'items' => $lineDetails,
                    'amount' => $total,
                    'payment_option' => $validated['payment_option'],
                    'payment_method' => $paymentMethod,
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('frontdesk.in-house', $request->only('search'))
            ->with('success', 'Charge added to the guest folio.');
    }
}
