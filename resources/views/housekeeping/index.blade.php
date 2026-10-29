<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Housekeeping Room Board | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        .hk-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .hk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        .hk-card { background: var(--st-card); border: 1px solid var(--st-line); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 12px; box-shadow: var(--st-shadow); transition: box-shadow .2s ease, transform .2s ease; }
        .hk-card:hover { box-shadow: var(--st-shadow-hover); transform: translateY(-2px); }
        .hk-card.is-priority { border-color: var(--st-accent); box-shadow: 0 0 0 2px var(--st-accent-soft), var(--st-shadow); }
        .hk-card__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .hk-card__head h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--st-text); }
        .hk-meta { font-size: 12.5px; color: var(--st-muted); }
        .hk-tags { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .hk-tag { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; background: var(--st-accent-soft); color: var(--st-accent-text); }
        .hk-tag--warn { background: #fff7ed; color: #c2410c; }
        .pill--cleaning { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
        .pill--service { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        html[data-theme="dark"] .pill--cleaning { background: rgba(234, 179, 8, .16); color: #fde68a; border-color: rgba(234, 179, 8, .35); }
        html[data-theme="dark"] .pill--service { background: rgba(139, 92, 246, .18); color: #ddd6fe; border-color: rgba(139, 92, 246, .35); }
        .hk-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .hk-actions-grid form { margin: 0; }
        .hk-btn { min-height: 44px; width: 100%; padding: 8px 10px; border-radius: 10px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: background-color .15s ease, border-color .15s ease; }
        .hk-btn:hover:not([disabled]) { background: var(--st-hover); }
        .hk-btn[disabled] { opacity: .45; cursor: default; }
        .hk-btn--primary { background: var(--st-accent); border-color: var(--st-accent); color: #fff; }
        .hk-btn--primary:hover:not([disabled]) { background: var(--st-accent-hover); }
        .hk-btn--danger { background: #e11d48; border-color: #e11d48; color: #fff; }
        .hk-btn--wide { grid-column: 1 / -1; }
        .hk-modal-backdrop { position: fixed; inset: 0; z-index: 9500; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; padding: 16px; }
        .hk-modal { width: min(520px, 100%); max-height: calc(100vh - 32px); overflow-y: auto; background: var(--st-card); color: var(--st-text); border-radius: 18px; padding: 22px; box-shadow: 0 24px 60px rgba(15, 23, 42, .25); }
        .hk-modal__head { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .hk-modal__head h2 { margin: 0; font-size: 17px; }
        .hk-modal__head p { margin: 4px 0 0; font-size: 12.5px; color: var(--st-muted); }
        .hk-close { border: 0; background: transparent; color: var(--st-muted); font-size: 24px; line-height: 1; cursor: pointer; }
        .hk-form { display: grid; gap: 12px; }
        .hk-check { display: flex; align-items: center; gap: 10px; font-size: 14px; padding: 10px 12px; border: 1px solid var(--st-line); border-radius: 10px; }
        .hk-check input { width: 18px; height: 18px; accent-color: var(--st-accent); }
        .hk-item { display: grid; grid-template-columns: 1fr 80px; gap: 8px; align-items: center; padding: 10px 12px; border: 1px solid var(--st-line); border-radius: 10px; font-size: 14px; }
        .hk-item label { display: flex; align-items: center; gap: 8px; }
        .hk-section-title { margin: 6px 0 0; font-size: 13px; font-weight: 700; color: var(--st-text); }
        .hk-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
        .hk-banner { padding: 8px 12px; border-radius: 10px; background: #fefce8; color: #a16207; font-size: 12.5px; font-weight: 700; }
        html[data-theme="dark"] .hk-banner { background: rgba(234, 179, 8, .16); color: #fde68a; }
        .hk-empty { padding: 40px; text-align: center; color: var(--st-muted); }
        @media (max-width: 640px) { .hk-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page" x-data="housekeepingBoard()">
    <div class="page-head">
        <div>
            <p class="eyebrow">Housekeeping</p>
            <h1>Housekeeping Room Board</h1>
            <p>{{ $category ? ($category->title ?: $category->type_name) : 'Uncategorised rooms' }} · {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }} · {{ $rooms->count() }} room{{ $rooms->count() === 1 ? '' : 's' }} shown</p>
        </div>
        <a href="{{ route('housekeeping.lost-found') }}" class="badge-soft text-decoration-none">Lost &amp; found</a>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="notice-err" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Categories: back to the category picker --}}
    <div class="hk-filters" aria-label="Room categories">
        <a href="{{ route('housekeeping.index') }}" class="chip">← Back to categories</a>
    </div>

    {{-- Filters: floors and statuses --}}
    <div class="hk-filters" aria-label="Filter by floor">
        <a href="{{ route('housekeeping.index', array_filter(['category' => $categoryKey, 'status' => $statusFilter])) }}" class="chip {{ $floor === null || $floor === '' ? 'is-active' : '' }}">All floors</a>
        @foreach($floors as $f)
            <a href="{{ route('housekeeping.index', array_filter(['category' => $categoryKey, 'floor' => $f, 'status' => $statusFilter])) }}" class="chip {{ (string) $floor === (string) $f ? 'is-active' : '' }}">Floor {{ $f }}</a>
        @endforeach
    </div>
    <div class="hk-filters" aria-label="Filter by status">
        <a href="{{ route('housekeeping.index', array_filter(['category' => $categoryKey, 'floor' => $floor])) }}" class="chip {{ $statusFilter ? '' : 'is-active' }}">Any status</a>
        @foreach(['dirty' => 'Dirty', 'cleaning' => 'Cleaning', 'clean_uninspected' => 'Awaiting inspection', 'clean' => 'Clean', 'touch_up_needed' => 'Touch-up', 'maintenance' => 'Out of service', 'do_not_disturb' => 'Do not disturb'] as $key => $label)
            <a href="{{ route('housekeeping.index', array_filter(['category' => $categoryKey, 'floor' => $floor, 'status' => $key])) }}" class="chip {{ $statusFilter === $key ? 'is-active' : '' }}">{{ $label }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    @if($rooms->isEmpty())
        <div class="surface hk-empty">
            <h2>No rooms match this filter</h2>
            <p>Try another floor or status.</p>
        </div>
    @else
        <section class="hk-grid" aria-label="Rooms">
            @foreach($rooms as $room)
                @php
                    $checkingOut = $room->bookings->isNotEmpty() && $room->bookings->first()->check_out_date?->isToday();
                @endphp
                <article class="hk-card {{ $room->priority_flag ? 'is-priority' : '' }}">
                    <div class="hk-card__head">
                        <div>
                            <h3>Room {{ $room->room_number }}</h3>
                            <p class="hk-meta">{{ $room->category?->title ?: $room->category?->type_name ?: $room->room_type ?: 'Standard room' }} · Floor {{ $room->floor ?? '—' }}</p>
                        </div>
                        <span class="pill {{ \App\Support\HousekeepingStatus::pill($room->housekeeping_status) }}">{{ \App\Support\HousekeepingStatus::label($room->housekeeping_status) }}</span>
                    </div>

                    <div class="hk-tags">
                        @if($room->priority_flag)<span class="hk-tag">VIP / priority</span>@endif
                        @if($checkingOut)<span class="hk-tag hk-tag--warn">Checkout clean</span>@endif
                        @if($room->last_cleaned_at)<span class="hk-meta">Last cleaned {{ $room->last_cleaned_at->diffForHumans() }}</span>@endif
                    </div>

                    @if(in_array($room->housekeeping_status, ['clean_uninspected', 'inspection_pending'], true))
                        <div class="hk-banner" role="status">Waiting for supervisor inspection</div>
                    @endif

                    <div class="hk-actions-grid">
                        @if(in_array('cleaning', $statuses, true) && in_array($room->housekeeping_status, ['dirty', 'touch_up_needed'], true))
                            <form method="POST" action="{{ route('housekeeping.rooms.status', $room) }}">@csrf @method('PATCH')<input type="hidden" name="housekeeping_status" value="cleaning"><button class="hk-btn" type="submit">Start cleaning</button></form>
                        @endif
                        @if(in_array('clean_uninspected', $statuses, true) && in_array($room->housekeeping_status, ['dirty', 'cleaning', 'touch_up_needed'], true))
                            <form method="POST" action="{{ route('housekeeping.rooms.status', $room) }}">@csrf @method('PATCH')<input type="hidden" name="housekeeping_status" value="clean_uninspected"><button class="hk-btn hk-btn--primary" type="submit">Finished · request inspection</button></form>
                        @endif
                        @if($isAdmin && in_array($room->housekeeping_status, ['clean_uninspected', 'inspection_pending'], true))
                            <button type="button" class="hk-btn hk-btn--primary hk-btn--wide" @click="open('inspect', {{ $room->id }}, '{{ $room->room_number }}')">Pass or fail inspection</button>
                        @endif
                        @if(in_array('touch_up_needed', $statuses, true) && $room->housekeeping_status === 'clean')
                            <form method="POST" action="{{ route('housekeeping.rooms.status', $room) }}">@csrf @method('PATCH')<input type="hidden" name="housekeeping_status" value="touch_up_needed"><button class="hk-btn" type="submit">Needs touch-up</button></form>
                        @endif
                        @if(in_array('do_not_disturb', $statuses, true) && $room->housekeeping_status === 'cleaning')
                            <form method="POST" action="{{ route('housekeeping.rooms.status', $room) }}">@csrf @method('PATCH')<input type="hidden" name="housekeeping_status" value="do_not_disturb"><button class="hk-btn" type="submit">Do not disturb</button></form>
                        @endif
                        @if($isAdmin)
                            <form method="POST" action="{{ route('housekeeping.rooms.priority', $room) }}">@csrf @method('PATCH')<button class="hk-btn" type="submit">{{ $room->priority_flag ? 'Remove priority' : 'Flag priority' }}</button></form>
                        @endif

                        <button type="button" class="hk-btn" @click="open('amenities', {{ $room->id }}, '{{ $room->room_number }}')">Log amenities</button>
                        @if($room->housekeeping_status === 'dirty')
                            <button type="button" class="hk-btn" @click="open('lost', {{ $room->id }}, '{{ $room->room_number }}')">Log lost &amp; found</button>
                        @endif
                        <button type="button" class="hk-btn" @click="open('issue', {{ $room->id }}, '{{ $room->room_number }}')">Report issue</button>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    {{-- Lost & found quick entry: saves to the room's lost and found registry --}}
    <div x-show="modal === 'lost'" x-cloak class="hk-modal-backdrop" @click.self="close()" @keydown.escape.window="close()" role="dialog" aria-modal="true" aria-labelledby="lost-title">
        <div class="hk-modal">
            <div class="hk-modal__head">
                <div><h2 id="lost-title">Log lost &amp; found · Room <span x-text="roomNumber"></span></h2><p>Recorded against this room's last guest.</p></div>
                <button type="button" class="hk-close" @click="close()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('housekeeping.lost-found.store') }}" enctype="multipart/form-data" class="hk-form">
                @csrf
                <input type="hidden" name="room_id" :value="roomId">
                <div>
                    <label class="field-label" for="lost-desc">Item description</label>
                    <input id="lost-desc" name="item_description" class="input" maxlength="255" required placeholder="e.g. Black phone charger, USB-C">
                </div>
                <div>
                    <label class="field-label" for="lost-category">Category</label>
                    <select id="lost-category" name="category" class="select" required>
                        @foreach(\App\Models\LostAndFoundItem::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="lost-location">Location found in room</label>
                    <input id="lost-location" name="location_found" class="input" maxlength="150" placeholder="e.g. Under the bed">
                </div>
                <div>
                    <label class="field-label" for="lost-photo">Photo (optional)</label>
                    <input id="lost-photo" name="photo" type="file" accept="image/*">
                </div>
                <div class="hk-actions">
                    <button type="button" class="hk-btn" style="width:auto" @click="close()">Cancel</button>
                    <button type="submit" class="hk-btn hk-btn--primary" style="width:auto">Save item</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Amenity and minibar log --}}
    <div x-show="modal === 'amenities'" x-cloak class="hk-modal-backdrop" @click.self="close()" @keydown.escape.window="close()" role="dialog" aria-modal="true">
        <div class="hk-modal">
            <div class="hk-modal__head">
                <div><h2>Log amenities · Room <span x-text="roomNumber"></span></h2><p>Enter how many of each item were used or restocked.</p></div>
                <button type="button" class="hk-close" @click="close()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" :action="actionFor('amenities')" class="hk-form">
                @csrf
                <p class="hk-section-title">Minibar</p>
                @foreach($minibarItems as $item)
                    <div class="hk-item">
                        <label><input type="checkbox" name="items[{{ $loop->index }}][name]" value="{{ $item }}"> {{ $item }}</label>
                        <input class="input" type="number" min="0" max="50" name="items[{{ $loop->index }}][quantity]" placeholder="Qty">
                    </div>
                @endforeach
                <p class="hk-section-title">Linen &amp; toiletries</p>
                @foreach($amenityItems as $item)
                    <div class="hk-item">
                        <label><input type="checkbox" name="items[{{ count($minibarItems) + $loop->index }}][name]" value="{{ $item }}"> {{ $item }}</label>
                        <input class="input" type="number" min="0" max="50" name="items[{{ count($minibarItems) + $loop->index }}][quantity]" placeholder="Qty">
                    </div>
                @endforeach
                <label class="hk-check"><input type="checkbox" name="charge_to_folio" value="1"> Charge minibar items to the guest folio</label>
                <div class="hk-actions">
                    <button type="button" class="hk-btn" style="width:auto" @click="close()">Cancel</button>
                    <button type="submit" class="hk-btn hk-btn--primary" style="width:auto">Save log</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Maintenance report --}}
    <div x-show="modal === 'issue'" x-cloak class="hk-modal-backdrop" @click.self="close()" @keydown.escape.window="close()" role="dialog" aria-modal="true">
        <div class="hk-modal">
            <div class="hk-modal__head">
                <div><h2>Report maintenance · Room <span x-text="roomNumber"></span></h2><p>Urgent issues take the room out of service and alert the admin.</p></div>
                <button type="button" class="hk-close" @click="close()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" :action="actionFor('issue')" class="hk-form">
                @csrf
                <div><label class="field-label" for="issue-category">Category</label>
                    <select id="issue-category" name="category" class="select" required>
                        @foreach($maintenanceCategories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select></div>
                <div><label class="field-label" for="issue-severity">Severity</label>
                    <select id="issue-severity" name="severity" class="select" required>
                        @foreach($maintenanceSeverities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select></div>
                <div><label class="field-label" for="issue-desc">Description</label>
                    <textarea id="issue-desc" name="description" rows="3" class="input" placeholder="What is wrong and where"></textarea></div>
                <div class="hk-actions">
                    <button type="button" class="hk-btn" style="width:auto" @click="close()">Cancel</button>
                    <button type="submit" class="hk-btn hk-btn--primary" style="width:auto">Submit report</button>
                </div>
            </form>
        </div>
    </div>

    @if($isAdmin)
        @include('housekeeping.inspection-checklist')
    @endif

    @if($isAdmin && $openIssues->isNotEmpty())
        <section class="surface" style="margin-top:24px;padding:20px">
            <h2 style="margin:0 0 10px;font-size:15px">Open maintenance tickets</h2>
            <ul class="check-list">
                @foreach($openIssues as $ticket)
                    <li><div><strong>Room {{ $ticket->room?->room_number }}</strong> · {{ \App\Models\MaintenanceTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}<div class="detail">{{ $ticket->description ?: 'No description' }}</div></div>
                        <span class="pill {{ $ticket->severity === 'urgent' ? 'pill--dirty' : 'pill--maint' }}">{{ ucfirst($ticket->severity) }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</main>
@include('partials.hotel-shell-end')
@include('partials.dev-role-switcher')
<script>
    function housekeepingBoard() {
        return {
            modal: null,
            roomId: null,
            roomNumber: '',
            open(type, roomId, roomNumber) {
                this.modal = type;
                this.roomId = roomId;
                this.roomNumber = roomNumber;
            },
            close() { this.modal = null; },
            actionFor(type) {
                const map = {
                    inspect: @js(route('housekeeping.rooms.inspect', ['room' => '__ROOM__'])),
                    amenities: @js(route('housekeeping.rooms.amenities', ['room' => '__ROOM__'])),
                    issue: @js(route('housekeeping.rooms.issues', ['room' => '__ROOM__'])),
                };
                return map[type].replace('__ROOM__', this.roomId);
            },
        };
    }
</script>
</body>
</html>
