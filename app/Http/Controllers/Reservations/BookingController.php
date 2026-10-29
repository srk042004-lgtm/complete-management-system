<?php

namespace App\Http\Controllers\Reservations;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FoodCategory;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        foreach ([
            ['Food', 'utensils', 10],
            ['Milkshakes', 'milkshake', 20],
            ['Fast Food', 'burger', 30],
            ['Beverages', 'coffee', 40],
            ['Desserts', 'cake', 50],
        ] as [$name, $icon, $sortOrder]) {
            FoodCategory::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name],
                ['icon' => $icon, 'sort_order' => $sortOrder],
            );
        }
        $foodCategories = FoodCategory::query()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('name', ['Laundry & Pressing', 'Minibar', 'Housekeeping'])
            ->with(['items' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $roomTypes = DB::table('room_types')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByRaw('COALESCE(title, type_name)')
            ->get();

        $rooms = Room::query()
            ->leftJoin('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->where('rooms.tenant_id', $tenantId)
            ->where('room_types.is_active', true)
            ->whereNotIn('rooms.housekeeping_status', ['maintenance', 'out_of_order'])
            ->select([
                'rooms.id',
                'rooms.room_number',
                'rooms.room_type_id',
                'rooms.floor',
                'rooms.occupancy_status',
                'rooms.housekeeping_status',
                DB::raw('COALESCE(room_types.title, room_types.type_name, rooms.room_type) as category_name'),
                'room_types.max_adults as category_adults',
                'room_types.max_children as category_children',
                'room_types.max_total_occupancy as category_total_occupancy',
                'room_types.base_occupancy',
                'room_types.base_capacity',
                'room_types.max_capacity',
                'room_types.base_price_per_night',
                'room_types.bed_type',
                'room_types.bed_count',
                'room_types.view_type',
                'room_types.has_balcony',
                'room_types.amenities',
                'room_types.cover_image',
                'room_types.description as category_description',
            ])
            ->orderBy('rooms.room_number')
            ->get();

        $currencyCode = $request->user()->tenant->currency_code ?: 'PKR';

        return view('reservations.booking', compact('roomTypes', 'rooms', 'currencyCode', 'foodCategories'));
    }

    /** Find a saved guest by exact ID type and number, for the "recognise returning guest" prompt. */
    public function lookupGuest(Request $request): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'id_type' => ['required', Rule::in(['cnic_national_id', 'passport', 'drivers_license'])],
            'id_number' => ['required', 'string', 'max:255'],
        ]);

        $guest = Guest::query()
            ->where('tenant_id', $tenantId)
            ->where('id_type', $validated['id_type'])
            ->where('id_number', trim($validated['id_number']))
            ->first();

        if ($guest === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'guest' => [
                'id' => $guest->id,
                'name' => trim($guest->first_name.' '.$guest->last_name),
                'phone' => $guest->phone,
                'email' => $guest->email,
                'address' => $guest->address,
            ],
        ]);
    }

    /**
     * Reuse the saved guest only when the receptionist confirmed the match and the ID type and number still match.
     * Otherwise a new guest record is created, so a different person is never merged into an existing record.
     */
    private function resolveGuest(int $tenantId, array $validated, array $guestData): Guest
    {
        if (! empty($validated['guest_id'])) {
            $existing = Guest::query()
                ->where('tenant_id', $tenantId)
                ->whereKey((int) $validated['guest_id'])
                ->where('id_type', $validated['id_type'])
                ->where('id_number', trim($validated['id_number']))
                ->first();

            if ($existing !== null) {
                $documents = array_filter([
                    'photo_path' => $guestData['photo_path'] ?? null,
                    'id_document_path' => $guestData['id_document_path'] ?? null,
                ]);
                if ($documents !== []) {
                    $existing->forceFill($documents)->save();
                }

                return $existing;
            }
        }

        return Guest::query()->create($guestData);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        // The "Custom item" option is sent as the text 'custom'; it has no catalogue id, so store it as null.
        if (is_array($request->input('food_packages'))) {
            $packages = $request->input('food_packages');
            foreach ($packages as $index => $package) {
                if (is_array($package) && ($package['menu_item_id'] ?? null) === 'custom') {
                    $packages[$index]['menu_item_id'] = null;
                }
            }
            $request->merge(['food_packages' => $packages]);
        }

        $validated = $request->validate([
            'guest_id' => ['nullable', 'integer'],
            'guest_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'id_type' => ['required', Rule::in(['cnic_national_id', 'passport', 'drivers_license'])],
            'id_number' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'opening_balance' => ['required', 'numeric', 'between:-9999999999.99,9999999999.99'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'room_type_id' => ['required', Rule::exists('room_types', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'room_id' => ['required', Rule::exists('rooms', 'id')->where('tenant_id', $tenantId)],
            'check_in_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['required', 'integer', 'min:0'],
            'food_packages' => ['sometimes', 'array', 'max:10'],
            'food_packages.*.menu_item_id' => ['nullable', 'integer'],
            'food_packages.*.is_custom' => ['sometimes', 'boolean'],
            'food_packages.*.name' => ['nullable', 'string', 'max:160'],
            'food_packages.*.billing_type' => ['nullable', Rule::in(['per_guest_per_night', 'one_time_order'])],
            'food_packages.*.guest_count' => ['nullable', 'integer', 'min:1'],
            'food_packages.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'food_packages.*.price_per_person_per_night' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'food_packages.*.kitchen_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $roomType = DB::table('room_types')
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['room_type_id'])
            ->firstOrFail();
        $room = Room::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($validated['room_id'])
            ->firstOrFail();

        if ((int) $room->room_type_id !== (int) $roomType->id) {
            return back()->withErrors(['room_id' => 'Choose a room belonging to the selected category.'])->withInput();
        }

        if ((int) $validated['adults'] > (int) $roomType->max_adults) {
            return back()->withErrors(['adults' => "This category allows up to {$roomType->max_adults} adults."])->withInput();
        }

        if ((int) $validated['children'] > (int) $roomType->max_children) {
            return back()->withErrors(['children' => "This category allows up to {$roomType->max_children} children."])->withInput();
        }
        if ((int) $validated['adults'] + (int) $validated['children'] > (int) $roomType->max_total_occupancy) {
            return back()->withErrors(['adults' => "This category allows up to {$roomType->max_total_occupancy} guests in total."])->withInput();
        }

        $totalGuests = (int) $validated['adults'] + (int) $validated['children'];
        $foodLines = [];
        foreach ($validated['food_packages'] ?? [] as $index => $foodPackage) {
            if (! empty($foodPackage['menu_item_id'])) {
                $menuItem = MenuItem::query()
                    ->whereHas('category', fn ($query) => $query->where('tenant_id', $tenantId))
                    ->where('is_available', true)
                    ->find($foodPackage['menu_item_id']);
                if ($menuItem === null) {
                    return back()->withErrors([
                        "food_packages.{$index}.menu_item_id" => 'Choose an available menu item from your hotel menu.',
                    ])->withInput();
                }
                $foodCategory = $menuItem->category;
                $billingType = $menuItem->billing_type;
                $guestCount = (int) ($foodPackage['guest_count'] ?? $totalGuests);
                $quantity = (int) ($foodPackage['quantity'] ?? 1);
                $unitPrice = (float) $menuItem->price;
                if ($billingType === 'per_guest_per_night' && $guestCount > $totalGuests) {
                    return back()->withErrors([
                        "food_packages.{$index}.guest_count" => 'Package guest quantity cannot exceed the reservation guest count.',
                    ])->withInput();
                }
                $foodLines[] = [
                    'menu_item_id' => $menuItem->id,
                    'food_category_id' => $foodCategory->id,
                    'category_name' => $foodCategory->name,
                    'name' => $menuItem->name,
                    'billing_type' => $billingType,
                    'guest_count' => $guestCount,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'kitchen_notes' => $foodPackage['kitchen_notes'] ?? null,
                ];
                continue;
            }

            if (trim((string) ($foodPackage['name'] ?? '')) === ''
                || ! isset($foodPackage['price_per_person_per_night'])) {
                return back()->withErrors([
                    "food_packages.{$index}.menu_item_id" => 'Select a catalog item or choose the custom item option and enter its name and price.',
                ])->withInput();
            }
            if ((int) ($foodPackage['guest_count'] ?? 0) > $totalGuests) {
                return back()->withErrors([
                    "food_packages.{$index}.guest_count" => 'Food package guest quantity cannot exceed the reservation guest count.',
                ])->withInput();
            }
            $foodLines[] = [
                'menu_item_id' => null,
                'food_category_id' => null,
                'category_name' => 'Food',
                'name' => $foodPackage['name'],
                'billing_type' => $foodPackage['billing_type'] ?? 'per_guest_per_night',
                'guest_count' => (int) ($foodPackage['guest_count'] ?? $totalGuests),
                'quantity' => (int) ($foodPackage['quantity'] ?? 1),
                'unit_price' => (float) $foodPackage['price_per_person_per_night'],
                'kitchen_notes' => $foodPackage['kitchen_notes'] ?? null,
            ];
        }

        $guestNameParts = preg_split('/\s+/', trim($validated['guest_name']), 2);
        $guestData = [
            'tenant_id' => $tenantId,
            'first_name' => $guestNameParts[0],
            'last_name' => $guestNameParts[1] ?? '',
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'id_type' => $validated['id_type'],
            'id_number' => $validated['id_number'],
            'address' => $validated['address'] ?? null,
            'opening_balance' => $validated['opening_balance'],
        ];

        $booking = DB::transaction(function () use ($tenantId, $validated, $foodLines, $guestData, $room, $roomType, $request): Booking {
            $lockedRoom = Room::query()->where('tenant_id', $tenantId)->whereKey($room->id)->lockForUpdate()->firstOrFail();
            $overlap = Booking::query()
                ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $lockedRoom->id))
                ->whereIn('booking_status', ['confirmed', 'checked_in'])
                ->whereDate('check_in_date', '<', $validated['check_out_date'])
                ->whereDate('check_out_date', '>', $validated['check_in_date'])
                ->exists();

            if (($validated['check_in_date'] <= now()->toDateString() && Str::lower($lockedRoom->occupancy_status) !== 'vacant')
                || in_array(Str::lower($lockedRoom->housekeeping_status), ['maintenance', 'out_of_order'], true)
                || $overlap) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'room_id' => 'This room is not available for the selected dates. Choose another room.',
                ]);
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('guest-photos', 'public');
                if ($photoPath === false) {
                    throw new \RuntimeException('The guest photo could not be saved.');
                }
                $guestData['photo_path'] = $photoPath;
            }
            if ($request->hasFile('id_document')) {
                $documentPath = $request->file('id_document')->store('guest-documents', 'local');
                if ($documentPath === false) {
                    throw new \RuntimeException('The guest identity document could not be saved.');
                }
                $guestData['id_document_path'] = $documentPath;
            }

            $guest = $this->resolveGuest($tenantId, $validated, $guestData);
            $nights = Carbon::parse($validated['check_in_date'])
                ->diffInDays(Carbon::parse($validated['check_out_date']));
            $nightlyRate = (float) $roomType->base_price_per_night;
            $roomTotal = round($nights * $nightlyRate, 2);
            $foodPackageTotal = 0.0;
            foreach ($foodLines as $foodLine) {
                $foodTotal = $foodLine['billing_type'] === 'per_guest_per_night'
                    ? round($foodLine['guest_count'] * $foodLine['unit_price'] * $nights, 2)
                    : round($foodLine['quantity'] * $foodLine['unit_price'], 2);
                $foodPackageTotal += $foodTotal;
            }
            $subtotal = round($roomTotal + $foodPackageTotal, 2);
            $booking = Booking::query()->create([
                'tenant_id' => $tenantId,
                'guest_id' => $guest->id,
                'booking_code' => 'BK-'.Str::upper(Str::random(10)),
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'adults' => $validated['adults'],
                'children' => $validated['children'],
                'total_guests' => (int) $validated['adults'] + (int) $validated['children'],
                'adults' => (int) $validated['adults'],
                'children' => (int) $validated['children'],
                'booking_status' => 'confirmed',
                'subtotal_amount' => $subtotal,
                'tax_amount' => 0,
                'grand_total' => $subtotal,
                'paid_amount' => 0,
            ]);
            $booking->rooms()->attach($lockedRoom->id, [
                'tenant_id' => $tenantId,
                'price_per_night' => $nightlyRate,
            ]);

            foreach ($foodLines as $foodLine) {
                $booking->foodPackages()->create([
                    'tenant_id' => $tenantId,
                    'menu_item_id' => $foodLine['menu_item_id'],
                    'food_category_id' => $foodLine['food_category_id'],
                    'package_name' => $foodLine['name'],
                    'category_name' => $foodLine['category_name'],
                    'billing_type' => $foodLine['billing_type'],
                    'guest_count' => $foodLine['guest_count'],
                    'quantity' => $foodLine['quantity'],
                    'price_per_person_per_night' => $foodLine['unit_price'],
                    'nights' => $nights,
                    'total_amount' => $foodLine['billing_type'] === 'per_guest_per_night'
                        ? round($foodLine['guest_count'] * $foodLine['unit_price'] * $nights, 2)
                        : round($foodLine['quantity'] * $foodLine['unit_price'], 2),
                    'kitchen_notes' => $foodLine['kitchen_notes'],
                ]);
            }

            return $booking;
        });

        // After a successful booking, go to the signed-in role's dashboard.
        $role = \App\Support\HotelRoleCatalog::normalize((string) $request->user()->role);
        $dashboard = $role === 'receptionist' ? route('frontdesk.dashboard') : route('hotel.role.dashboard', ['role' => $role]);

        return redirect($dashboard)
            ->with('success', "Reservation {$booking->booking_code} was created successfully.");
    }
}
