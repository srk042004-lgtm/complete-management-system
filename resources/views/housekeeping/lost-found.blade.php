<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lost &amp; Found | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        .lf-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end; }
        .lf-form .full { grid-column: 1 / -1; }
        .lf-form input[type="file"] { width: 100%; font: inherit; font-size: 13px; color: var(--st-text); }
        @media (max-width: 640px) { .lf-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page">
    <div class="page-head">
        <div>
            <p class="eyebrow">Housekeeping</p>
            <h1>Lost &amp; found</h1>
            <p>Record items left behind. Each item links to the last guest who stayed in that room.</p>
        </div>
        <a href="{{ route('housekeeping.index') }}" class="badge-soft text-decoration-none">Back to room board</a>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="notice-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="surface" style="padding:22px;margin-bottom:20px">
        <div class="section-head"><div><h2>Record a found item</h2><p class="muted">Add a photo if you can; it helps the front desk identify the item.</p></div></div>
        <form method="POST" action="{{ route('housekeeping.lost-found.store') }}" enctype="multipart/form-data" class="lf-form">
            @csrf
            <div>
                <label class="field-label" for="lf-room">Room</label>
                <select id="lf-room" name="room_id" class="select">
                    <option value="">Not sure / public area</option>
                    @foreach($rooms as $room)<option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>Room {{ $room->room_number }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="lf-category">Category</label>
                <select id="lf-category" name="category" class="select" required>
                    @foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="lf-location">Where it was found</label>
                <input id="lf-location" name="location_found" class="input" maxlength="150" placeholder="e.g. Under the bed">
            </div>
            <div>
                <label class="field-label" for="lf-found-at">Date &amp; time found</label>
                <input id="lf-found-at" name="found_at" type="datetime-local" class="input">
            </div>
            <div class="full">
                <label class="field-label" for="lf-desc">Item description</label>
                <input id="lf-desc" name="item_description" class="input" maxlength="255" required placeholder="e.g. Black phone charger, USB-C">
            </div>
            <div class="full">
                <label class="field-label" for="lf-photo">Photo (optional)</label>
                <input id="lf-photo" name="photo" type="file" accept="image/*">
            </div>
            <div class="full" style="display:flex;justify-content:flex-end">
                <button type="submit" class="btn-primary" style="width:auto;padding:10px 20px">Record item</button>
            </div>
        </form>
    </section>

    <section class="surface" style="overflow:hidden">
        <div style="padding:20px 22px 0"><h2 style="margin:0">Registry</h2></div>
        <div class="table-wrap" style="border:0;margin:14px 0 0">
            <table>
                <thead>
                    <tr><th>Found</th><th>Item</th><th>Category</th><th>Room</th><th>Where</th><th>Last guest</th><th>Status</th></tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->found_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td>
                            <strong>{{ $item->item_description }}</strong>
                            @if($item->photo_path)<div><a href="{{ asset('storage/'.$item->photo_path) }}" target="_blank" rel="noopener" class="muted" style="font-size:12px">View photo</a></div>@endif
                        </td>
                        <td>{{ \App\Models\LostAndFoundItem::CATEGORIES[$item->category] ?? $item->category }}</td>
                        <td>{{ $item->room?->room_number ?? '—' }}</td>
                        <td>{{ $item->location_found ?: '—' }}</td>
                        <td>{{ $item->guest_id ? 'Linked to guest record' : '—' }}</td>
                        <td><span class="pill {{ $item->status === 'claimed' ? 'pill--clean' : 'pill--maint' }}">{{ ucfirst($item->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No items recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:14px 22px">{{ $items->links() }}</div>
    </section>
</main>
@include('partials.hotel-shell-end')
</body>
</html>
