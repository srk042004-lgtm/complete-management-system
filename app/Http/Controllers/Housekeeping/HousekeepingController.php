<?php

namespace App\Http\Controllers\Housekeeping;

use App\Http\Controllers\Controller;
use App\Models\AmenityLog;
use App\Models\Booking;
use App\Models\Folio;
use App\Models\HotelOperationLog;
use App\Models\HousekeepingTask;
use App\Models\LostAndFoundItem;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Support\HousekeepingStatus;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HousekeepingController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $role = HotelRoleCatalog::normalize((string) $request->user()->role);
        $floor = $request->query('floor');
        $status = $request->query('status');
        $categoryKey = $request->query('category');

        if ($categoryKey === null || $categoryKey === '') {
            return $this->categories($tenantId);
        }

        $category = $categoryKey === 'none'
            ? null
            : RoomCategory::query()->where('tenant_id', $tenantId)->findOrFail((int) $categoryKey);
        $scope = fn ($query) => $category
            ? $query->where('room_type_id', $category->id)
            : $query->whereNull('room_type_id');

        $rooms = Room::query()
            ->with(['category', 'bookings' => fn ($query) => $query
                ->where('bookings.tenant_id', $tenantId)
                ->where('booking_status', 'checked_in')
                ->whereDate('check_out_date', '>=', today())
                ->select('bookings.id', 'bookings.check_out_date', 'bookings.booking_status')])
            ->where('tenant_id', $tenantId)
            ->tap($scope)
            ->when($floor !== null && $floor !== '', fn ($query) => $query->where('floor', (int) $floor))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('housekeeping_status', $status))
            ->orderByDesc('priority_flag')
            ->orderBy('room_number')
            ->get();

        $counts = Room::query()->where('tenant_id', $tenantId)->tap($scope)->selectRaw('housekeeping_status, COUNT(*) as total')->groupBy('housekeeping_status')->pluck('total', 'housekeeping_status');
        $floors = Room::query()->where('tenant_id', $tenantId)->tap($scope)->whereNotNull('floor')->distinct()->orderBy('floor')->pluck('floor');
        $openIssues = MaintenanceTicket::query()->where('tenant_id', $tenantId)->where('status', 'open')->with('room:id,room_number')->latest('id')->limit(6)->get();

        return view('housekeeping.index', [
            'category' => $category,
            'categoryKey' => $categoryKey,
            'rooms' => $rooms,
            'counts' => $counts,
            'floors' => $floors,
            'floor' => $floor,
            'statusFilter' => $status,
            'role' => $role,
            'isAdmin' => $role === 'admin',
            'statuses' => HousekeepingStatus::allowedFor($role),
            'serviceTypes' => HousekeepingTask::SERVICE_TYPES,
            'minibarItems' => array_keys(config('housekeeping.minibar')),
            'amenityItems' => array_keys(config('housekeeping.amenities')),
            'maintenanceCategories' => MaintenanceTicket::CATEGORIES,
            'maintenanceSeverities' => MaintenanceTicket::SEVERITIES,
            'openIssues' => $openIssues,
        ]);
    }

    /** Housekeeper or admin sets a room's cleanliness status. */
    /** Keep the room's category on the board after an action, so the housekeeper stays in that category. */
    private function boardParams(Room $room): array
    {
        return array_filter(['category' => $room->room_type_id]);
    }

    private function categories(int $tenantId): View
    {
        $byCategory = Room::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('room_type_id, housekeeping_status, COUNT(*) as total')
            ->groupBy('room_type_id', 'housekeeping_status')
            ->get()
            ->groupBy(fn ($row) => $row->room_type_id ?? 'none');

        $cards = RoomCategory::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('type_name')
            ->get()
            ->map(fn (RoomCategory $category) => $this->categoryCard((string) $category->id, $category->title ?: $category->type_name, $byCategory->get($category->id, collect())));

        if ($byCategory->has('none')) {
            $cards->push($this->categoryCard('none', 'Uncategorised rooms', $byCategory->get('none')));
        }

        return view('housekeeping.categories', ['cards' => $cards]);
    }

    /** @return array{key: string, name: string, total: int, ready: int, attention: int, out: int} */
    private function categoryCard(string $key, string $name, $rows): array
    {
        $status = $rows->pluck('total', 'housekeeping_status');
        $count = fn (array $statuses): int => (int) collect($statuses)->sum(fn (string $s) => (int) ($status[$s] ?? 0));

        return [
            'key' => $key,
            'name' => $name,
            'total' => (int) $rows->sum('total'),
            'ready' => $count(['clean']),
            'attention' => $count(['dirty', 'cleaning', 'clean_uninspected', 'touch_up_needed']),
            'out' => $count(['maintenance', 'out_of_order']),
        ];
    }

    public function setStatus(Request $request, Room $room): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        abort_unless((int) $room->tenant_id === $tenantId, 404);
        $role = HotelRoleCatalog::normalize((string) $request->user()->role);

        $validated = $request->validate([
            'housekeeping_status' => ['required', Rule::in(HousekeepingStatus::allowedFor($role))],
        ]);
        $newStatus = $validated['housekeeping_status'];

        if ($room->housekeeping_status === 'maintenance' && $newStatus !== 'maintenance' && $role !== 'admin') {
            return back()->withErrors(['housekeeping_status' => 'This room is out of service until maintenance clears it.']);
        }

        DB::transaction(function () use ($room, $newStatus, $request, $tenantId): void {
            $updates = ['housekeeping_status' => $newStatus];
            if ($newStatus === 'clean') {
                $updates['last_cleaned_at'] = now();
            }
            $room->forceFill($updates)->save();

            if ($newStatus === 'cleaning') {
                HousekeepingTask::query()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'room_id' => $room->id, 'status' => 'open'],
                    ['service_type' => 'stayover', 'priority' => (bool) $room->priority_flag, 'assigned_to_user_id' => $request->user()->id],
                )->update(['status' => 'in_progress', 'assigned_to_user_id' => $request->user()->id]);
            }

            if ($newStatus === 'clean_uninspected') {
                HousekeepingTask::query()->where('tenant_id', $tenantId)->where('room_id', $room->id)
                    ->whereIn('status', ['open', 'in_progress', 'rejected'])
                    ->update(['status' => 'done', 'completed_at' => now()]);
            }
        });

        return redirect()->route('housekeeping.index', $this->boardParams($room))->with('success', 'Room '.$room->room_number.' is now '.HousekeepingStatus::label($newStatus).'.');
    }

    /** Admin inspection: a checklist that either releases the room or sends it back with a note. */
    public function inspect(Request $request, Room $room): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        abort_unless((int) $room->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'bedding' => ['required_if:decision,pass', 'accepted'],
            'bathroom' => ['required_if:decision,pass', 'accepted'],
            'trash_floors' => ['required_if:decision,pass', 'accepted'],
            'amenities' => ['required_if:decision,pass', 'accepted'],
            'decision' => ['required', Rule::in(['pass', 'fail'])],
            'notes' => ['nullable', 'string', 'max:500', 'required_if:decision,fail'],
        ], [
            'bedding.accepted' => 'Confirm bedding and linens were replaced.',
            'bathroom.accepted' => 'Confirm the bathroom is disinfected and restocked.',
            'trash_floors.accepted' => 'Confirm trash is emptied and floors vacuumed.',
            'amenities.accepted' => 'Confirm minibar and amenities were verified.',
            'notes.required_if' => 'Add a note explaining what needs re-cleaning.',
        ]);

        DB::transaction(function () use ($room, $validated, $request, $tenantId): void {
            $tasks = HousekeepingTask::query()->where('tenant_id', $tenantId)->where('room_id', $room->id)
                ->whereIn('status', ['done', 'open', 'in_progress', 'rejected']);

            if ($validated['decision'] === 'pass') {
                $room->forceFill(['housekeeping_status' => 'clean', 'last_cleaned_at' => now()])->save();
                $tasks->update(['status' => 'inspected', 'inspection_notes' => null]);
            } else {
                $room->forceFill(['housekeeping_status' => 'dirty'])->save();
                if ($tasks->update(['status' => 'rejected', 'inspection_notes' => $validated['notes']]) === 0) {
                    HousekeepingTask::query()->create(['tenant_id' => $tenantId, 'room_id' => $room->id, 'service_type' => 'stayover', 'status' => 'rejected', 'priority' => (bool) $room->priority_flag, 'inspection_notes' => $validated['notes']]);
                }
            }

            HotelOperationLog::query()->create([
                'tenant_id' => $tenantId,
                'actor_id' => $request->user()->id,
                'action' => $validated['decision'] === 'pass' ? 'room.inspection_passed' : 'room.inspection_failed',
                'subject_type' => 'room',
                'subject_id' => $room->id,
                'details' => ['room' => $room->room_number, 'notes' => $validated['notes'] ?? null],
                'ip_address' => $request->ip(),
            ]);
        });

        $message = $validated['decision'] === 'pass'
            ? 'Room '.$room->room_number.' passed inspection and is available.'
            : 'Room '.$room->room_number.' returned to housekeeping with your note.';

        return redirect()->route('housekeeping.index', $this->boardParams($room))->with('success', $message);
    }

    /** Logs minibar and amenity use. Minibar items can be charged to the in-house guest's folio. */
    public function logAmenity(Request $request, Room $room): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        abort_unless((int) $room->tenant_id === $tenantId, 404);

        $minibar = array_keys(config('housekeeping.minibar'));
        $amenities = array_keys(config('housekeeping.amenities'));
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.name' => ['required', Rule::in(array_merge($minibar, $amenities))],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'charge_to_folio' => ['sometimes', 'boolean'],
        ]);

        $booking = Booking::query()->where('tenant_id', $tenantId)->where('booking_status', 'checked_in')
            ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $room->id))->first();

        DB::transaction(function () use ($validated, $room, $tenantId, $request, $booking, $minibar): void {
            $charge = (bool) ($validated['charge_to_folio'] ?? false) && $booking !== null;

            foreach ($validated['items'] as $item) {
                $isMinibar = in_array($item['name'], $minibar, true);
                $unitPrice = $isMinibar ? (float) config('housekeeping.minibar.'.$item['name']) : 0.0;
                $shouldCharge = $charge && $isMinibar && $unitPrice > 0;

                AmenityLog::query()->create([
                    'tenant_id' => $tenantId,
                    'room_id' => $room->id,
                    'booking_id' => $booking?->id,
                    'logged_by_id' => $request->user()->id,
                    'item_name' => $item['name'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $unitPrice,
                    'charged_to_folio' => $shouldCharge,
                ]);

                if ($shouldCharge) {
                    $amount = round($unitPrice * (int) $item['quantity'], 2);
                    Folio::query()->create([
                        'tenant_id' => $tenantId,
                        'booking_id' => $booking->id,
                        'room_id' => $room->id,
                        'invoice_number' => 'MIB-'.Str::upper(Str::random(8)),
                        'type' => 'charge',
                        'item_category' => 'Minibar: '.$item['name'].' (Qty: '.$item['quantity'].') · Room '.$room->room_number,
                        'amount' => $amount,
                        'tax' => 0,
                        'total' => $amount,
                        'is_reversed' => false,
                        'created_at' => now(),
                    ]);
                    $booking->forceFill([
                        'subtotal_amount' => (float) $booking->subtotal_amount + $amount,
                        'grand_total' => (float) $booking->grand_total + $amount,
                    ])->save();
                    $booking->refresh();
                }
            }
        });

        $message = $booking !== null && ($validated['charge_to_folio'] ?? false)
            ? 'Items logged and added to the guest folio.'
            : 'Items logged for room '.$room->room_number.'.';

        return redirect()->route('housekeeping.index', $this->boardParams($room))->with('success', $message);
    }

    /** Maintenance report. Urgent issues take the room out of service and alert the hotel admin. */
    public function reportIssue(Request $request, Room $room): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        abort_unless((int) $room->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(MaintenanceTicket::CATEGORIES))],
            'severity' => ['required', Rule::in(array_keys(MaintenanceTicket::SEVERITIES))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $room, $tenantId, $request): void {
            MaintenanceTicket::query()->create([
                'tenant_id' => $tenantId,
                'room_id' => $room->id,
                'reported_by_id' => $request->user()->id,
                'category' => $validated['category'],
                'severity' => $validated['severity'],
                'description' => $validated['description'] ?? null,
                'status' => 'open',
            ]);

            if ($validated['severity'] === 'urgent') {
                $room->forceFill(['housekeeping_status' => 'maintenance'])->save();
            }

            HotelOperationLog::query()->create([
                'tenant_id' => $tenantId,
                'actor_id' => $request->user()->id,
                'action' => $validated['severity'] === 'urgent' ? 'maintenance.urgent' : 'maintenance.reported',
                'subject_type' => 'room',
                'subject_id' => $room->id,
                'details' => ['room' => $room->room_number, 'category' => $validated['category'], 'severity' => $validated['severity']],
                'ip_address' => $request->ip(),
            ]);
        });

        $message = $validated['severity'] === 'urgent'
            ? 'Urgent issue reported. Room '.$room->room_number.' is out of service and the admin has been alerted.'
            : 'Maintenance ticket created for room '.$room->room_number.'.';

        return redirect()->route('housekeeping.index', $this->boardParams($room))->with('success', $message);
    }

    /** Admin: float a room to the top of the housekeeping grid (VIP or priority arrival). */
    public function togglePriority(Request $request, Room $room): RedirectResponse
    {
        abort_unless((int) $room->tenant_id === (int) $request->user()->tenant_id, 404);
        $room->forceFill(['priority_flag' => ! $room->priority_flag])->save();

        return redirect()->route('housekeeping.index', $this->boardParams($room))->with('success', 'Room '.$room->room_number.($room->priority_flag ? ' is flagged as priority.' : ' priority flag removed.'));
    }

    public function lostFound(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $items = LostAndFoundItem::query()->where('tenant_id', $tenantId)->with('room:id,room_number')->latest('id')->paginate(20);

        return view('housekeeping.lost-found', [
            'items' => $items,
            'categories' => LostAndFoundItem::CATEGORIES,
            'rooms' => Room::query()->where('tenant_id', $tenantId)->orderBy('room_number')->get(['id', 'room_number']),
        ]);
    }

    public function storeLostFound(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;

        $validated = $request->validate([
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->where('tenant_id', $tenantId)],
            'item_description' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(LostAndFoundItem::CATEGORIES))],
            'location_found' => ['nullable', 'string', 'max:150'],
            'found_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        // Link the item to the guest who last stayed in that room, so the front desk can contact them.
        $guestId = $validated['room_id'] ? Booking::query()->where('tenant_id', $tenantId)
            ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $validated['room_id']))
            ->latest('check_out_date')->value('guest_id') : null;

        LostAndFoundItem::query()->create([
            'tenant_id' => $tenantId,
            'room_id' => $validated['room_id'] ?? null,
            'found_by_id' => $request->user()->id,
            'guest_id' => $guestId,
            'item_description' => $validated['item_description'],
            'category' => $validated['category'],
            'location_found' => $validated['location_found'] ?? null,
            'found_at' => $validated['found_at'] ?? now(),
            'photo_path' => $request->file('photo')?->store('lost-and-found', 'public'),
            'status' => 'unclaimed',
        ]);

        return redirect()->route('housekeeping.lost-found')->with('success', 'Item recorded in the lost and found registry.');
    }
}
