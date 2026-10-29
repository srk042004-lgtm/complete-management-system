<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit room {{ $room->room_number }} | {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; margin: 0; background: #f4f7fb; color: #173149; font: 14px/1.5 system-ui, sans-serif; }
        .room-edit-shell { max-width: 760px; margin: 0 auto; padding: 24px 18px 40px; }
        .room-edit-card { padding: 22px; border: 1px solid #dce5ee; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(20, 43, 66, .06); }
        .room-edit-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .room-edit-head h1 { margin: 0; font-size: 1.35rem; }
        .room-edit-head p { margin: 4px 0 0; color: #637589; }
        .room-edit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .room-edit-field label { display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700; }
        .room-edit-field input, .room-edit-field select { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid #cbd7e2; border-radius: 8px; background: #fff; color: inherit; font: inherit; }
        .room-edit-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
        .room-edit-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; padding: 7px 13px; border: 1px solid #c7d8e8; border-radius: 8px; background: #f3f8fc; color: #214969; font: inherit; font-size: .8rem; font-weight: 700; text-decoration: none; cursor: pointer; }
        .room-edit-button.primary { border-color: #176f68; background: #176f68; color: #fff; }
        .room-edit-errors { margin-bottom: 16px; padding: 10px 13px; border-radius: 8px; background: #fff0f0; color: #8a2930; }
        html[data-theme="dark"] body { background: var(--theme-bg); color: var(--theme-text); }
        html[data-theme="dark"] .room-edit-card { border-color: var(--theme-border); background: var(--theme-surface); }
        html[data-theme="dark"] .room-edit-head p { color: var(--theme-muted); }
        html[data-theme="dark"] .room-edit-field input, html[data-theme="dark"] .room-edit-field select { border-color: #45566a; background: #202c3a; color: #f1f5f9; }
        html[data-theme="dark"] .room-edit-button { border-color: #4a5b6e; background: #293747; color: #edf3f8; }
        html[data-theme="dark"] .room-edit-button.primary { border-color: #2d8d82; background: #176f68; color: #f4fffd; }
        html[data-theme="dark"] .room-edit-errors { background: #48272b; color: #ffd2d2; }
        @media (max-width: 700px) { .room-edit-shell { padding: 14px 12px 28px; } .room-edit-card { padding: 16px; } .room-edit-grid { grid-template-columns: 1fr; } }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
<main class="room-edit-shell">
    <section class="room-edit-card">
        <header class="room-edit-head">
            <div>
                <h1>Edit room {{ $room->room_number }}</h1>
                <p>Update the room number, category, or floor.</p>
            </div>
            <a class="room-edit-button" href="{{ route('frontdesk.inventory') }}">Back to rooms</a>
        </header>

        @if($errors->any())
            <div class="room-edit-errors" role="alert">
                <strong>Please review the room details.</strong>
                <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('frontdesk.inventory.rooms.update', $room->id) }}">
            @csrf
            @method('PUT')
            <div class="room-edit-grid">
                <div class="room-edit-field">
                    <label for="room-number">Room number</label>
                    <input id="room-number" name="room_number" value="{{ old('room_number', $room->room_number) }}" maxlength="50" required>
                </div>
                <div class="room-edit-field">
                    <label for="room-category">Room category</label>
                    <select id="room-category" name="room_type_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('room_type_id', $room->room_type_id) === (string) $category->id)>{{ $category->title ?: $category->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="room-edit-field">
                    <label for="room-floor">Floor number</label>
                    <input id="room-floor" name="floor" type="number" min="0" max="200" value="{{ old('floor', $room->floor) }}" required>
                </div>
            </div>
            <div class="room-edit-actions">
                <button class="room-edit-button primary" type="submit">Save room</button>
                <a class="room-edit-button" href="{{ route('frontdesk.inventory') }}">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
