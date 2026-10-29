<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\HotelOperationLog;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validatedFilter = $request->validate([
            'category' => ['nullable', 'integer', Rule::exists('room_types', 'id')->where('tenant_id', $tenantId)],
        ]);
        $categories = RoomCategory::query()
            ->withCount('rooms')
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get();
        $roomsQuery = Room::query()
            ->leftJoin('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->where('rooms.tenant_id', $tenantId)
            ->select([
                'rooms.id',
                'rooms.room_number',
                'rooms.room_type_id',
                'rooms.floor',
                'rooms.occupancy_status',
                'rooms.housekeeping_status',
                DB::raw('COALESCE(room_types.title, room_types.type_name, rooms.room_type) as category_name'),
            ]);
        if (isset($validatedFilter['category'])) {
            $roomsQuery->where('rooms.room_type_id', $validatedFilter['category']);
        }
        $rooms = $roomsQuery
            ->orderBy('rooms.floor')
            ->orderBy('rooms.room_number')
            ->get();
        $selectedCategory = $validatedFilter['category'] ?? null;

        $role = HotelRoleCatalog::normalize((string) $request->user()->role);
        $canManageRooms = in_array('inventory.manage', HotelRoleCatalog::allowedCapabilities($role), true);

        return view('frontdesk.inventory', compact('categories', 'rooms', 'selectedCategory', 'canManageRooms'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $request->merge(['code' => $request->input('code') ?: Str::upper(Str::slug((string) $request->input('title'), '-'))]);
        $initialRoomInput = $request->input('initial_room_numbers', '');
        $initialRoomNumbers = collect(is_string($initialRoomInput) ? preg_split('/[\r\n,]+/', $initialRoomInput) : [])
            ->map(fn (string $roomNumber): string => trim($roomNumber))
            ->filter()
            ->values()
            ->all();
        $validated = $request->validate($this->categoryRules($tenantId));
        $request->validate([
            'initial_room_numbers' => ['nullable', 'string', 'max:5000'],
            'initial_room_floor' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);
        if (count($initialRoomNumbers) > 100) {
            return back()->withErrors(['initial_room_numbers' => 'You can add at most 100 rooms with one category.'])->withInput();
        }
        if ($initialRoomNumbers !== []) {
            Validator::make(['room_numbers' => $initialRoomNumbers], [
                'room_numbers' => ['array', 'max:100'],
                'room_numbers.*' => [
                    'required',
                    'string',
                    'max:50',
                    'distinct',
                    Rule::unique('rooms', 'room_number')->where('tenant_id', $tenantId),
                ],
            ])->validate();
            $request->validate([
                'initial_room_floor' => ['required', 'integer', 'min:0', 'max:200'],
            ]);
        }
        $this->validateCategoryCapacity($validated);
        $this->validateAmenities($validated['amenities_text'] ?? '');
        $propertyId = $this->propertyId($request);
        $attributes = $this->categoryAttributes($request, $validated, $tenantId) + [
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'type_name' => $validated['title'],
            'title' => $validated['title'],
            'base_capacity' => $validated['base_occupancy'],
            'max_capacity' => $validated['max_total_occupancy'],
            'adults' => $validated['max_adults'],
            'children' => $validated['max_children'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::transaction(function () use ($request, $attributes, $tenantId, $initialRoomNumbers): void {
            $category = RoomCategory::query()->create($attributes);
            $this->audit($request, 'room_category.created', $category::class, (int) $category->id, [
                'title' => $category->title,
                'code' => $category->code,
            ], $tenantId);
            foreach ($initialRoomNumbers as $roomNumber) {
                $room = Room::query()->create([
                    'tenant_id' => $tenantId,
                    'room_number' => $roomNumber,
                    'room_type_id' => $category->id,
                    'floor' => (int) $request->input('initial_room_floor'),
                    'occupancy_status' => 'vacant',
                    'housekeeping_status' => 'clean',
                ]);
                $this->audit($request, 'room.created', $room::class, (int) $room->id, [
                    'room_number' => $room->room_number,
                    'floor' => $room->floor,
                    'room_type_id' => $room->room_type_id,
                ], $tenantId);
            }
        });

        $message = 'Room category added.';
        if ($initialRoomNumbers !== []) {
            $message .= ' '.count($initialRoomNumbers).' rooms added to the room status board.';
        }

        return redirect()->route('frontdesk.inventory')->with('success', $message);
    }

    public function editCategory(Request $request, int $categoryId): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $category = RoomCategory::query()->where('tenant_id', $tenantId)->findOrFail($categoryId);

        return view('frontdesk.category-edit', compact('category'));
    }

    public function updateCategory(Request $request, int $categoryId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $category = RoomCategory::query()->where('tenant_id', $tenantId)->findOrFail($categoryId);
        $validated = $request->validate($this->categoryRules($tenantId, $categoryId));
        $this->validateCategoryCapacity($validated);
        $this->validateAmenities($validated['amenities_text'] ?? '');
        $request->validate([
            'title' => [Rule::unique('room_types', 'type_name')
                ->where('property_id', $category->property_id)
                ->ignore($categoryId)],
        ]);
        if (count($category->gallery_images ?? []) - count($validated['remove_gallery_images'] ?? []) + count($request->file('gallery_uploads', [])) > 12) {
            return back()->withErrors(['gallery_uploads' => 'A category can have at most 12 gallery images.'])->withInput();
        }
        $oldCover = $category->cover_image;
        $oldGallery = $category->gallery_images ?? [];
        $attributes = $this->categoryAttributes($request, $validated, $tenantId, $category);
        $oldRate = (float) $category->base_price_per_night;
        $newRate = (float) $attributes['base_price_per_night'];
        $attributes += [
            'type_name' => $validated['title'],
            'title' => $validated['title'],
            'base_capacity' => $validated['base_occupancy'],
            'max_capacity' => $validated['max_total_occupancy'],
            'adults' => $validated['max_adults'],
            'children' => $validated['max_children'],
            'updated_at' => now(),
        ];

        DB::transaction(function () use ($request, $attributes, $tenantId, $category, $oldRate, $newRate): void {
            $category->forceFill($attributes)->save();
            $this->audit($request, 'room_category.updated', $category::class, (int) $category->id, [
                'title' => $category->title,
                'code' => $category->code,
            ], $tenantId);
            if ($oldRate !== $newRate) {
                $this->audit($request, 'room_rate.overridden', $category::class, (int) $category->id, [
                    'title' => $category->title,
                    'previous_rate' => $oldRate,
                    'new_rate' => $newRate,
                ], $tenantId);
            }
        });

        if ($oldCover !== null && $oldCover !== ($attributes['cover_image'] ?? null)) {
            if (! Storage::disk('public')->delete($oldCover)) {
                throw new \RuntimeException('The previous category cover image could not be removed from storage.');
            }
        }
        $removedGallery = array_diff($oldGallery, $attributes['gallery_images'] ?? $oldGallery);
        foreach ($removedGallery as $image) {
            if (! Storage::disk('public')->delete($image)) {
                throw new \RuntimeException('A removed category gallery image could not be deleted from storage.');
            }
        }

        return redirect()->route('frontdesk.inventory')->with('success', 'Room category updated.');
    }

    public function destroyCategory(Request $request, int $categoryId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $category = RoomCategory::query()->where('tenant_id', $tenantId)->findOrFail($categoryId);
        if ($category->rooms()->exists()) {
            return back()->withErrors([
                'category' => 'This category still has rooms assigned. Reassign or remove those rooms before deleting it.',
            ]);
        }

        DB::transaction(function () use ($request, $tenantId, $category): void {
            $categoryId = (int) $category->id;
            $category->delete();
            $this->audit($request, 'room_category.deleted', RoomCategory::class, $categoryId, [
                'title' => $category->title,
                'code' => $category->code,
            ], $tenantId);
        });

        $media = array_filter(array_merge([$category->cover_image], $category->gallery_images ?? []));
        foreach ($media as $image) {
            if (! Storage::disk('public')->delete($image)) {
                throw new \RuntimeException('A deleted category image could not be removed from storage.');
            }
        }

        return redirect()->route('frontdesk.inventory')->with('success', 'Room category deleted.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate($this->roomRules($tenantId));

        DB::transaction(function () use ($request, $validated, $tenantId): void {
            $room = Room::query()->create([
                'tenant_id' => $tenantId,
                'room_number' => $validated['room_number'],
                'room_type_id' => $validated['room_type_id'],
                'floor' => $validated['floor'],
                'occupancy_status' => 'vacant',
                'housekeeping_status' => 'clean',
            ]);

            $this->audit($request, 'room.created', $room::class, (int) $room->id, [
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'room_type_id' => $room->room_type_id,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.inventory')->with('success', 'Room added as vacant and clean.');
    }

    public function updateRoom(Request $request, int $roomId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $room = Room::query()->where('tenant_id', $tenantId)->findOrFail($roomId);
        $validated = $request->validate($this->roomRules($tenantId, $roomId));

        DB::transaction(function () use ($request, $validated, $tenantId, $room): void {
            $room->forceFill([
                'room_number' => $validated['room_number'],
                'room_type_id' => $validated['room_type_id'],
                'floor' => $validated['floor'],
            ])->save();

            $this->audit($request, 'room.updated', $room::class, (int) $room->id, [
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'room_type_id' => $room->room_type_id,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.inventory')->with('success', 'Room details updated.');
    }

    public function editRoom(Request $request, int $roomId): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $room = Room::query()->where('tenant_id', $tenantId)->findOrFail($roomId);
        $categories = RoomCategory::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get();

        return view('frontdesk.room-edit', compact('room', 'categories'));
    }

    private function categoryRules(int $tenantId, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('room_types', 'code')->where('tenant_id', $tenantId);
        if ($ignoreId !== null) {
            $codeRule->ignore($ignoreId);
        }

        return [
            'title' => [
                'required',
                'string',
                'max:120',
                Rule::unique('room_types', 'type_name')
                    ->where('tenant_id', $tenantId)
                    ->ignore($ignoreId),
            ],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', $codeRule],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_occupancy' => ['required', 'integer', 'min:1', 'max:50'],
            'max_adults' => ['required', 'integer', 'min:1', 'max:50'],
            'max_children' => ['required', 'integer', 'min:0', 'max:50'],
            'max_total_occupancy' => ['required', 'integer', 'min:1', 'max:100', 'gte:base_occupancy', 'gte:max_adults', 'gte:max_children'],
            'base_price_per_night' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'extra_adult_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'extra_child_rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'weekend_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'size_sq_ft' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'bed_type' => ['required', Rule::in(['SINGLE', 'TWIN', 'DOUBLE', 'QUEEN', 'KING', 'SUPER_KING', 'SOFA_BED', 'BUNK_BED'])],
            'bed_count' => ['required', 'integer', 'min:1', 'max:20'],
            'has_balcony' => ['sometimes', 'boolean'],
            'view_type' => ['required', Rule::in(['CITY_VIEW', 'GARDEN_VIEW', 'POOL_VIEW', 'OCEAN_VIEW', 'MOUNTAIN_VIEW', 'COURTYARD_VIEW', 'NO_VIEW'])],
            'amenities_text' => ['nullable', 'string', 'max:5000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_uploads' => ['sometimes', 'array', 'max:12'],
            'gallery_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover_image' => ['sometimes', 'boolean'],
            'remove_gallery_images' => ['sometimes', 'array'],
            'remove_gallery_images.*' => ['string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'allow_extra_bed' => ['required', 'boolean'],
            'extra_bed_fee' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    private function validateCategoryCapacity(array $validated): void
    {
        if ($validated['base_occupancy'] > $validated['max_total_occupancy']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'base_occupancy' => 'Base occupancy cannot exceed maximum total occupancy.',
            ]);
        }
    }

    private function validateAmenities(string $amenitiesText): void
    {
        $amenities = collect(preg_split('/[\r\n,]+/', $amenitiesText))
            ->map(fn (string $amenity): string => trim($amenity))
            ->filter()
            ->unique(fn (string $amenity): string => Str::lower($amenity));
        if ($amenities->count() > 30 || $amenities->contains(fn (string $amenity): bool => mb_strlen($amenity) > 80)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amenities_text' => 'Enter no more than 30 amenities, with each name at most 80 characters.',
            ]);
        }
    }

    private function categoryAttributes(Request $request, array $validated, int $tenantId, ?RoomCategory $category = null): array
    {
        $amenities = collect(preg_split('/[\r\n,]+/', (string) ($validated['amenities_text'] ?? '')))
            ->map(fn (string $amenity): string => trim($amenity))
            ->filter()
            ->unique(fn (string $amenity): string => Str::lower($amenity))
            ->values()
            ->all();
        $gallery = array_values(array_diff(
            $category?->gallery_images ?? [],
            $validated['remove_gallery_images'] ?? [],
        ));
        foreach ($request->file('gallery_uploads', []) as $upload) {
            $path = $upload->store("room-categories/{$tenantId}/gallery", 'public');
            if ($path === false) {
                throw new \RuntimeException('A room category gallery image could not be saved.');
            }
            $gallery[] = $path;
        }

        $coverImage = $category?->cover_image;
        if ($request->boolean('remove_cover_image')) {
            $coverImage = null;
        }
        if ($request->hasFile('cover_image')) {
            $coverImage = $request->file('cover_image')->store("room-categories/{$tenantId}/covers", 'public');
            if ($coverImage === false) {
                throw new \RuntimeException('The room category cover image could not be saved.');
            }
        }

        return [
            'code' => Str::upper($validated['code']),
            'description' => $validated['description'] ?? null,
            'base_occupancy' => $validated['base_occupancy'],
            'max_adults' => $validated['max_adults'],
            'max_children' => $validated['max_children'],
            'max_total_occupancy' => $validated['max_total_occupancy'],
            'base_price_per_night' => $validated['base_price_per_night'],
            'extra_adult_rate' => $validated['extra_adult_rate'],
            'extra_child_rate' => $validated['extra_child_rate'],
            'weekend_rate' => $validated['weekend_rate'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'size_sq_ft' => $validated['size_sq_ft'] ?? null,
            'bed_type' => $validated['bed_type'],
            'bed_count' => $validated['bed_count'],
            'has_balcony' => $request->boolean('has_balcony'),
            'view_type' => $validated['view_type'],
            'amenities' => $amenities,
            'cover_image' => $coverImage,
            'gallery_images' => $gallery,
            'is_active' => (bool) $validated['is_active'],
            'allow_extra_bed' => (bool) $validated['allow_extra_bed'],
            'extra_bed_fee' => $validated['extra_bed_fee'],
        ];
    }

    private function roomRules(int $tenantId, ?int $ignoreRoomId = null): array
    {
        $uniqueRoomNumber = Rule::unique('rooms', 'room_number')->where('tenant_id', $tenantId);
        if ($ignoreRoomId !== null) {
            $uniqueRoomNumber->ignore($ignoreRoomId);
        }

        return [
            'room_number' => ['required', 'string', 'max:50', $uniqueRoomNumber],
            'room_type_id' => ['required', Rule::exists('room_types', 'id')->where('tenant_id', $tenantId)],
            'floor' => ['required', 'integer', 'min:0', 'max:200'],
        ];
    }

    private function propertyId(Request $request): int
    {
        $tenantId = (int) $request->user()->tenant_id;
        $propertyId = DB::table('room_types')
            ->where('tenant_id', $tenantId)
            ->value('property_id');

        if ($propertyId !== null) {
            return (int) $propertyId;
        }

        return (int) DB::table('properties')->insertGetId([
            'property_name' => ($request->user()->tenant->hotel_name ?: $request->user()->tenant->name).' (Workspace '.$tenantId.')',
            'currency_code' => $request->user()->tenant->currency_code ?: 'PKR',
            'default_tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function audit(Request $request, string $action, string $subjectType, int $subjectId, array $details, int $tenantId): void
    {
        HotelOperationLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => $details,
            'ip_address' => $request->ip(),
        ]);
    }
}
