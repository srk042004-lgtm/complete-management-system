<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room inventory | {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; margin: 0; background: #f4f7fb; color: #173149; font: 14px/1.5 system-ui, sans-serif; }
        .inventory-shell { max-width: 1180px; margin: 0 auto; padding: 24px 18px 40px; }
        .inventory-head, .inventory-panel { border: 1px solid #dce5ee; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(20, 43, 66, .06); }
        .inventory-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; padding: 18px 20px; }
        .inventory-head h1 { margin: 0; font-size: 1.45rem; }
        .inventory-head p { margin: 4px 0 0; color: #637589; }
        .inventory-panel { margin-bottom: 18px; padding: 18px; }
        .inventory-panel h2 { margin: 0 0 14px; font-size: 1.08rem; }
        .inventory-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .inventory-field label { display: block; margin-bottom: 5px; font-size: .82rem; font-weight: 700; }
        .inventory-field input, .inventory-field select, .inventory-field textarea { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid #cbd7e2; border-radius: 8px; background: #fff; color: inherit; font: inherit; }
        .inventory-field label.inventory-check-label { display: flex; align-items: center; gap: 8px; min-height: 32px; margin: 0; font-size: .76rem; line-height: 1.3; }
        .inventory-field input[type="checkbox"],
        .inventory-gallery input[type="checkbox"] { width: 14px; height: 14px; min-height: 14px; flex: 0 0 14px; margin: 0; padding: 0; accent-color: #176f68; }
        .inventory-gallery label { display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; }
        .inventory-field textarea { min-height: 76px; resize: vertical; }
        .inventory-field.full { grid-column: 1 / -1; }
        .inventory-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .inventory-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; padding: 7px 13px; border: 1px solid #c7d8e8; border-radius: 8px; background: #f3f8fc; color: #214969; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .inventory-button.primary { border-color: #176f68; background: #176f68; color: #fff; }
        .inventory-button:hover { background: #e7f2fa; color: #17466d; }
        .inventory-button.primary:hover { background: #125e58; color: #fff; }
        .inventory-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .room-filter { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; margin-bottom: 14px; }
        .room-filter .inventory-field { min-width: min(100%, 260px); }
        .room-table-wrap { overflow-x: auto; }
        .room-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .room-table th, .room-table td { padding: 10px 12px; border-bottom: 1px solid #e5ebf0; text-align: left; white-space: nowrap; }
        .room-table th { color: #60758a; font-size: .68rem; letter-spacing: .07em; text-transform: uppercase; }
        .room-table tr:last-child td { border-bottom: 0; }
        .inventory-category { min-width: 0; padding: 14px; border: 1px solid #e0e8ef; border-radius: 12px; background: #fbfdff; }
        .inventory-category > .inventory-item { padding: 0; border: 0; background: transparent; }
        .inventory-item { min-width: 0; padding: 14px; border: 1px solid #e0e8ef; border-radius: 12px; background: #fbfdff; }
        .inventory-item h3 { margin: 0 0 10px; font-size: .96rem; }
        .inventory-meta { margin-bottom: 10px; color: #617488; font-size: .82rem; }
        .inventory-image { display: block; width: min(100%, 150px); max-height: 110px; margin: 8px 0; border: 1px solid #dce5ee; border-radius: 8px; object-fit: cover; }
        .inventory-gallery { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .inventory-gallery label { font-size: .75rem; font-weight: 500; }
        .inventory-delete-form { display: flex; justify-content: flex-end; margin-top: -8px; }
        .inventory-button.danger { border-color: #f0c5c5; background: #fff4f4; color: #9b2c36; }
        .inventory-button:disabled { cursor: not-allowed; opacity: .55; }
        .inventory-alert { margin-bottom: 16px; padding: 10px 13px; border-radius: 8px; background: #e9f7f1; color: #175d48; }
        .inventory-errors { background: #fff0f0; color: #8a2930; }
        .inventory-empty { color: #65778a; }
        html[data-theme="dark"] body { background: var(--theme-bg); color: var(--theme-text); }
        html[data-theme="dark"] .inventory-head, html[data-theme="dark"] .inventory-panel { border-color: var(--theme-border); background: var(--theme-surface); }
        html[data-theme="dark"] .inventory-head p, html[data-theme="dark"] .inventory-meta, html[data-theme="dark"] .inventory-empty { color: var(--theme-muted); }
        html[data-theme="dark"] .inventory-item { border-color: var(--theme-border); background: var(--theme-surface-raised); }
        html[data-theme="dark"] .inventory-category { border-color: var(--theme-border); background: var(--theme-surface-raised); }
        html[data-theme="dark"] .inventory-category > .inventory-item { background: transparent; }
        html[data-theme="dark"] .inventory-field input, html[data-theme="dark"] .inventory-field select, html[data-theme="dark"] .inventory-field textarea { border-color: #45566a; background: #202c3a; color: #f1f5f9; }
        html[data-theme="dark"] .inventory-button { border-color: #4a5b6e; background: #293747; color: #edf3f8; }
        html[data-theme="dark"] .inventory-button.primary { border-color: #2d8d82; background: #176f68; color: #f4fffd; }
        html[data-theme="dark"] .inventory-button.danger { border-color: #79434a; background: #48272b; color: #ffd2d2; }
        html[data-theme="dark"] .inventory-image { border-color: var(--theme-border); }
        html[data-theme="dark"] .inventory-alert { background: #173b35; color: #b9f2df; }
        html[data-theme="dark"] .inventory-errors { background: #48272b; color: #ffd2d2; }
        @media (max-width: 700px) { .inventory-shell { padding: 14px 12px 28px; } .inventory-head { align-items: flex-start; flex-direction: column; } .inventory-grid, .inventory-list { grid-template-columns: 1fr; } }
        /* Compact pass: smaller header and fields, three form columns on wide screens. */
        .inventory-shell { padding: 14px 14px 28px; }
        .inventory-head { margin-bottom: 12px; padding: 10px 14px; border-radius: 12px; }
        .inventory-head h1 { font-size: 1.1rem; }
        .inventory-head p { margin-top: 2px; font-size: .8rem; }
        .inventory-head .inventory-button { min-height: 32px; padding: 5px 11px; font-size: .8rem; }
        .inventory-panel { margin-bottom: 12px; padding: 12px 14px; border-radius: 12px; }
        .inventory-panel h2 { margin-bottom: 10px; font-size: .95rem; }
        .inventory-grid { gap: 10px 12px; }
        @media (min-width: 900px) { .inventory-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .inventory-field label { margin-bottom: 3px; font-size: .76rem; }
        .inventory-field input, .inventory-field select, .inventory-field textarea { min-height: 34px; padding: 6px 9px; font-size: .86rem; border-radius: 7px; }
        .inventory-field textarea { min-height: 54px; }
        .inventory-actions { margin-top: 10px; }
        .inventory-button { min-height: 32px; padding: 5px 11px; font-size: .8rem; }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
<main class="inventory-shell">
    <header class="inventory-head">
        <div>
            <h1>Rooms &amp; categories</h1>
            <p>{{ $canManageRooms ? 'Set up room categories and add or edit the rooms in your hotel.' : 'Review your hotel room inventory and rate structures.' }}</p>
        </div>
        <div class="inventory-actions">
            @if($canManageRooms)
                <a class="inventory-button" href="{{ route('frontdesk.food-catalog') }}">Manage food menu</a>
                <a class="inventory-button" href="{{ route('frontdesk.dashboard') }}">Back to dashboard</a>
            @else
                <a class="inventory-button" href="{{ route('hotel.role.dashboard', ['role' => 'owner']) }}">Back to dashboard</a>
            @endif
        </div>
    </header>

    @if(session('success'))
        <div class="inventory-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="inventory-alert inventory-errors" role="alert">
            <strong>Please review the details below.</strong>
            <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($canManageRooms)
    <section class="inventory-panel">
        <h2>Add room category</h2>
        <form method="POST" action="{{ route('frontdesk.inventory.categories.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="inventory-grid">
                <div class="inventory-field">
                    <label for="new-category-title">Category name</label>
                    <input id="new-category-title" name="title" value="{{ old('title') }}" placeholder="e.g. Standard, Deluxe, Suite" required maxlength="120">
                </div>
                <div class="inventory-field"><label for="new-category-code">Unique category code</label><input id="new-category-code" name="code" value="{{ old('code') }}" placeholder="e.g. DEL-KNG" required maxlength="30" pattern="[A-Za-z0-9_-]+"></div>
                <div class="inventory-field full">
                    <label for="new-category-room-numbers">Initial room numbers (optional)</label>
                    <textarea id="new-category-room-numbers" name="initial_room_numbers" maxlength="5000" placeholder="For example: 101, 102, 103">{{ old('initial_room_numbers') }}</textarea>
                    <small>Separate room numbers with commas or new lines. Up to 100 rooms can be added to the Room Status board.</small>
                </div>
                <div class="inventory-field"><label for="new-category-room-floor">Floor for these rooms</label><input id="new-category-room-floor" name="initial_room_floor" type="number" min="0" max="200" value="{{ old('initial_room_floor', 1) }}"></div>
                <div class="inventory-field">
                    <label for="new-category-rate">Base rate per night</label>
                    <input id="new-category-rate" name="base_price_per_night" type="number" min="0" step="0.01" value="{{ old('base_price_per_night', '0.00') }}" required>
                </div>
                <div class="inventory-field"><label for="new-category-base-occupancy">Base occupancy</label><input id="new-category-base-occupancy" name="base_occupancy" type="number" min="1" max="50" value="{{ old('base_occupancy', 2) }}" required></div>
                <div class="inventory-field"><label for="new-category-adults">Maximum adults</label><input id="new-category-adults" name="max_adults" type="number" min="1" max="50" value="{{ old('max_adults', 2) }}" required></div>
                <div class="inventory-field"><label for="new-category-children">Maximum children</label><input id="new-category-children" name="max_children" type="number" min="0" max="50" value="{{ old('max_children', 2) }}" required></div>
                <div class="inventory-field"><label for="new-category-total">Maximum total occupancy</label><input id="new-category-total" name="max_total_occupancy" type="number" min="1" max="100" value="{{ old('max_total_occupancy', 4) }}" required></div>
                <div class="inventory-field"><label for="new-category-extra-adult">Extra adult rate / night</label><input id="new-category-extra-adult" name="extra_adult_rate" type="number" min="0" step="0.01" value="{{ old('extra_adult_rate', '0.00') }}" required></div>
                <div class="inventory-field"><label for="new-category-extra-child">Extra child rate / night</label><input id="new-category-extra-child" name="extra_child_rate" type="number" min="0" step="0.01" value="{{ old('extra_child_rate', '0.00') }}" required></div>
                <div class="inventory-field"><label for="new-category-weekend">Weekend rate / night</label><input id="new-category-weekend" name="weekend_rate" type="number" min="0" step="0.01" value="{{ old('weekend_rate') }}"></div>
                <div class="inventory-field"><label for="new-category-hourly">Hourly rate</label><input id="new-category-hourly" name="hourly_rate" type="number" min="0" step="0.01" value="{{ old('hourly_rate') }}"></div>
                <div class="inventory-field"><label for="new-category-size">Room size (sq ft)</label><input id="new-category-size" name="size_sq_ft" type="number" min="0" step="0.01" value="{{ old('size_sq_ft') }}"></div>
                <div class="inventory-field"><label for="new-category-bed-type">Bed type</label><select id="new-category-bed-type" name="bed_type" required>@foreach(['SINGLE','TWIN','DOUBLE','QUEEN','KING','SUPER_KING','SOFA_BED','BUNK_BED'] as $bed)<option value="{{ $bed }}" @selected(old('bed_type', 'KING') === $bed)>{{ str_replace('_', ' ', $bed) }}</option>@endforeach</select></div>
                <div class="inventory-field"><label for="new-category-bed-count">Bed count</label><input id="new-category-bed-count" name="bed_count" type="number" min="1" max="20" value="{{ old('bed_count', 1) }}" required></div>
                <div class="inventory-field"><label for="new-category-view">View</label><select id="new-category-view" name="view_type" required>@foreach(['CITY_VIEW','GARDEN_VIEW','POOL_VIEW','OCEAN_VIEW','MOUNTAIN_VIEW','COURTYARD_VIEW','NO_VIEW'] as $view)<option value="{{ $view }}" @selected(old('view_type', 'CITY_VIEW') === $view)>{{ str_replace('_', ' ', $view) }}</option>@endforeach</select></div>
                <div class="inventory-field"><label for="new-category-extra-bed-fee">Extra bed fee</label><input id="new-category-extra-bed-fee" name="extra_bed_fee" type="number" min="0" step="0.01" value="{{ old('extra_bed_fee', '0.00') }}" required></div>
                <div class="inventory-field full">
                    <label for="new-category-description">Description</label>
                    <textarea id="new-category-description" name="description" maxlength="5000" placeholder="Optional category details">{{ old('description') }}</textarea>
                </div>
                <div class="inventory-field full"><label for="new-category-amenities">Amenities (one per line or comma-separated)</label><textarea id="new-category-amenities" name="amenities_text" placeholder="WiFi, Air conditioning, Mini bar">{{ old('amenities_text') }}</textarea></div>
                <div class="inventory-field"><label for="new-category-cover">Cover image</label><input id="new-category-cover" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
                <div class="inventory-field"><label for="new-category-gallery">Gallery images (up to 12)</label><input id="new-category-gallery" name="gallery_uploads[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple></div>
                <div class="inventory-field"><input type="hidden" name="is_active" value="0"><label class="inventory-check-label"><input type="checkbox" name="is_active" value="1" checked> Active for reservations</label></div>
                <div class="inventory-field"><input type="hidden" name="allow_extra_bed" value="0"><label class="inventory-check-label"><input type="checkbox" name="allow_extra_bed" value="1" checked> Allow extra bed</label></div>
                <div class="inventory-field"><input type="hidden" name="has_balcony" value="0"><label class="inventory-check-label"><input type="checkbox" name="has_balcony" value="1"> Has balcony</label></div>
            </div>
            <div class="inventory-actions"><button class="inventory-button primary" type="submit">Add category</button></div>
        </form>
    </section>
    @endif

    <section class="inventory-panel">
        <h2>Room categories</h2>
        @if($categories->isEmpty())
            <p class="inventory-empty mb-0">No categories yet. Add a category above to make it available when creating reservations.</p>
        @else
            <div class="inventory-list">
                @foreach($categories as $category)
                    <div class="inventory-category">
                        <h3>{{ $category->title ?: $category->type_name }} <span class="inventory-meta">({{ $category->code }})</span></h3>
                        <p class="inventory-meta">{{ $category->rooms_count }} rooms · {{ $category->is_active ? 'Active' : 'Inactive' }} · {{ $category->base_price_per_night }} per night</p>
                        <p class="inventory-meta">Extra adult: {{ $category->extra_adult_rate ?? 0 }} · Extra child: {{ $category->extra_child_rate ?? 0 }} · Weekend: {{ $category->weekend_rate ?? 'Not set' }} · Hourly: {{ $category->hourly_rate ?? 'Not set' }} · Extra bed: {{ $category->extra_bed_fee ?? 0 }}</p>
                        @if($canManageRooms)
                        <div class="inventory-actions">
                            <a class="inventory-button primary" href="{{ route('frontdesk.inventory.categories.edit', $category->id) }}">Edit category</a>
                            <form method="POST" action="{{ route('frontdesk.inventory.categories.destroy', $category->id) }}" onsubmit="return confirm('Delete this room category? Categories assigned to rooms cannot be deleted.')">
                                @csrf
                                @method('DELETE')
                                <button class="inventory-button danger" type="submit" @disabled($category->rooms_count > 0)>Delete category</button>
                            </form>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if($canManageRooms)
    <section class="inventory-panel">
        <h2>Add room</h2>
        @if($categories->isEmpty())
            <p class="inventory-empty mb-0">Add a category before creating rooms.</p>
        @else
            <form method="POST" action="{{ route('frontdesk.inventory.rooms.store') }}">
                @csrf
                <div class="inventory-grid">
                    <div class="inventory-field">
                        <label for="new-room-number">Room number</label>
                        <input id="new-room-number" name="room_number" value="{{ old('room_number') }}" maxlength="50" required>
                    </div>
                    <div class="inventory-field">
                        <label for="new-room-category">Room category</label>
                        <select id="new-room-category" name="room_type_id" required>
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('room_type_id') === (string) $category->id)>{{ $category->title ?: $category->type_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inventory-field">
                        <label for="new-room-floor">Floor number</label>
                        <input id="new-room-floor" name="floor" type="number" min="0" max="200" value="{{ old('floor', 1) }}" required>
                    </div>
                </div>
                <div class="inventory-actions"><button class="inventory-button primary" type="submit">Add room</button></div>
            </form>
        @endif
    </section>
    @endif

    <section class="inventory-panel">
        <h2>Rooms</h2>
        <form class="room-filter" method="GET" action="{{ route('frontdesk.inventory') }}">
            <div class="inventory-field">
                <label for="room-category-filter">Filter by room category</label>
                <select id="room-category-filter" name="category">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->title ?: $category->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="inventory-button primary" type="submit">Filter rooms</button>
            @if($selectedCategory)
                <a class="inventory-button" href="{{ route('frontdesk.inventory') }}">Clear filter</a>
            @endif
        </form>
        @if($rooms->isEmpty())
            <p class="inventory-empty mb-0">{{ $selectedCategory ? 'No rooms match this category.' : 'No rooms have been added yet.' }}</p>
        @else
            <div class="room-table-wrap">
                <table class="room-table">
                    <thead><tr><th>Room</th><th>Category</th><th>Floor</th><th>Occupancy</th><th>Housekeeping</th>@if($canManageRooms)<th>Action</th>@endif</tr></thead>
                    <tbody>
                    @foreach($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->category_name ?: 'Uncategorized' }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $room->occupancy_status)) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $room->housekeeping_status)) }}</td>
                            @if($canManageRooms)<td><a class="inventory-button" href="{{ route('frontdesk.inventory.rooms.edit', $room->id) }}">Edit</a></td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
</body>
</html>
