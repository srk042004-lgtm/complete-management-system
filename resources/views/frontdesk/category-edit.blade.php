<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit {{ $category->title ?: $category->type_name }} | {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; margin: 0; background: #f4f7fb; color: #173149; font: 14px/1.5 system-ui, sans-serif; }
        .category-edit-shell { max-width: 1040px; margin: 0 auto; padding: 24px 18px 40px; }
        .category-edit-card { padding: 22px; border: 1px solid #dce5ee; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(20, 43, 66, .06); }
        .category-edit-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .category-edit-head h1 { margin: 0; font-size: 1.35rem; }
        .category-edit-head p { margin: 4px 0 0; color: #637589; }
        .category-edit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .category-edit-field label { display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700; }
        .category-edit-field input, .category-edit-field select, .category-edit-field textarea { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid #cbd7e2; border-radius: 8px; background: #fff; color: inherit; font: inherit; }
        .category-edit-field textarea { min-height: 76px; resize: vertical; }
        .category-edit-field.full { grid-column: 1 / -1; }
        .category-edit-field label.category-check { display: flex; align-items: center; gap: 8px; min-height: 32px; margin: 0; font-size: .76rem; line-height: 1.3; }
        .category-edit-field input[type="checkbox"], .category-gallery input[type="checkbox"] { width: 14px; height: 14px; min-height: 14px; flex: 0 0 14px; margin: 0; padding: 0; accent-color: #176f68; }
        .category-edit-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
        .category-edit-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; padding: 7px 13px; border: 1px solid #c7d8e8; border-radius: 8px; background: #f3f8fc; color: #214969; font: inherit; font-size: .8rem; font-weight: 700; text-decoration: none; cursor: pointer; }
        .category-edit-button.primary { border-color: #176f68; background: #176f68; color: #fff; }
        .category-edit-image { display: block; width: min(100%, 150px); max-height: 110px; margin: 8px 0; border: 1px solid #dce5ee; border-radius: 8px; object-fit: cover; }
        .category-gallery { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .category-gallery label { display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 500; }
        .category-edit-errors { margin-bottom: 16px; padding: 10px 13px; border-radius: 8px; background: #fff0f0; color: #8a2930; }
        html[data-theme="dark"] body { background: var(--theme-bg); color: var(--theme-text); }
        html[data-theme="dark"] .category-edit-card { border-color: var(--theme-border); background: var(--theme-surface); }
        html[data-theme="dark"] .category-edit-head p { color: var(--theme-muted); }
        html[data-theme="dark"] .category-edit-field input, html[data-theme="dark"] .category-edit-field select, html[data-theme="dark"] .category-edit-field textarea { border-color: #45566a; background: #202c3a; color: #f1f5f9; }
        html[data-theme="dark"] .category-edit-button { border-color: #4a5b6e; background: #293747; color: #edf3f8; }
        html[data-theme="dark"] .category-edit-button.primary { border-color: #2d8d82; background: #176f68; color: #f4fffd; }
        html[data-theme="dark"] .category-edit-image { border-color: var(--theme-border); }
        html[data-theme="dark"] .category-edit-errors { background: #48272b; color: #ffd2d2; }
        @media (max-width: 700px) { .category-edit-shell { padding: 14px 12px 28px; } .category-edit-card { padding: 16px; } .category-edit-grid { grid-template-columns: 1fr; } .category-edit-field.full { grid-column: auto; } }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
<main class="category-edit-shell">
    <section class="category-edit-card">
        <header class="category-edit-head">
            <div>
                <h1>Edit category: {{ $category->title ?: $category->type_name }}</h1>
                <p>Unique code: {{ $category->code }}</p>
            </div>
            <a class="category-edit-button" href="{{ route('frontdesk.inventory') }}">Back to rooms</a>
        </header>

        @if($errors->any())
            <div class="category-edit-errors" role="alert">
                <strong>Please review the category details.</strong>
                <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('frontdesk.inventory.categories.update', $category->id) }}">
            @csrf
            @method('PUT')
            <div class="category-edit-grid">
                <div class="category-edit-field"><label for="category-title">Category name</label><input id="category-title" name="title" value="{{ old('title', $category->title ?: $category->type_name) }}" required maxlength="120"></div>
                <div class="category-edit-field"><label for="category-code">Unique category code</label><input id="category-code" name="code" value="{{ old('code', $category->code) }}" required maxlength="30" pattern="[A-Za-z0-9_-]+"></div>
                <div class="category-edit-field"><label for="category-rate">Base rate per night</label><input id="category-rate" name="base_price_per_night" type="number" min="0" step="0.01" value="{{ old('base_price_per_night', $category->base_price_per_night) }}" required></div>
                <div class="category-edit-field"><label for="category-base-occupancy">Base occupancy</label><input id="category-base-occupancy" name="base_occupancy" type="number" min="1" max="50" value="{{ old('base_occupancy', $category->base_occupancy) }}" required></div>
                <div class="category-edit-field"><label for="category-adults">Maximum adults</label><input id="category-adults" name="max_adults" type="number" min="1" max="50" value="{{ old('max_adults', $category->max_adults) }}" required></div>
                <div class="category-edit-field"><label for="category-children">Maximum children</label><input id="category-children" name="max_children" type="number" min="0" max="50" value="{{ old('max_children', $category->max_children) }}" required></div>
                <div class="category-edit-field"><label for="category-total">Maximum total occupancy</label><input id="category-total" name="max_total_occupancy" type="number" min="1" max="100" value="{{ old('max_total_occupancy', $category->max_total_occupancy) }}" required></div>
                <div class="category-edit-field"><label for="category-extra-adult">Extra adult rate / night</label><input id="category-extra-adult" name="extra_adult_rate" type="number" min="0" step="0.01" value="{{ old('extra_adult_rate', $category->extra_adult_rate) }}" required></div>
                <div class="category-edit-field"><label for="category-extra-child">Extra child rate / night</label><input id="category-extra-child" name="extra_child_rate" type="number" min="0" step="0.01" value="{{ old('extra_child_rate', $category->extra_child_rate) }}" required></div>
                <div class="category-edit-field"><label for="category-weekend">Weekend rate / night</label><input id="category-weekend" name="weekend_rate" type="number" min="0" step="0.01" value="{{ old('weekend_rate', $category->weekend_rate) }}"></div>
                <div class="category-edit-field"><label for="category-hourly">Hourly rate</label><input id="category-hourly" name="hourly_rate" type="number" min="0" step="0.01" value="{{ old('hourly_rate', $category->hourly_rate) }}"></div>
                <div class="category-edit-field"><label for="category-size">Room size (sq ft)</label><input id="category-size" name="size_sq_ft" type="number" min="0" step="0.01" value="{{ old('size_sq_ft', $category->size_sq_ft) }}"></div>
                <div class="category-edit-field"><label for="category-bed-type">Bed type</label><select id="category-bed-type" name="bed_type" required>@foreach(['SINGLE','TWIN','DOUBLE','QUEEN','KING','SUPER_KING','SOFA_BED','BUNK_BED'] as $bed)<option value="{{ $bed }}" @selected(old('bed_type', $category->bed_type) === $bed)>{{ str_replace('_', ' ', $bed) }}</option>@endforeach</select></div>
                <div class="category-edit-field"><label for="category-bed-count">Bed count</label><input id="category-bed-count" name="bed_count" type="number" min="1" max="20" value="{{ old('bed_count', $category->bed_count) }}" required></div>
                <div class="category-edit-field"><label for="category-view">View</label><select id="category-view" name="view_type" required>@foreach(['CITY_VIEW','GARDEN_VIEW','POOL_VIEW','OCEAN_VIEW','MOUNTAIN_VIEW','COURTYARD_VIEW','NO_VIEW'] as $view)<option value="{{ $view }}" @selected(old('view_type', $category->view_type) === $view)>{{ str_replace('_', ' ', $view) }}</option>@endforeach</select></div>
                <div class="category-edit-field"><label for="category-extra-bed-fee">Extra bed fee</label><input id="category-extra-bed-fee" name="extra_bed_fee" type="number" min="0" step="0.01" value="{{ old('extra_bed_fee', $category->extra_bed_fee) }}" required></div>
                <div class="category-edit-field full"><label for="category-description">Description</label><textarea id="category-description" name="description" maxlength="5000">{{ old('description', $category->description) }}</textarea></div>
                <div class="category-edit-field full"><label for="category-amenities">Amenities (one per line or comma-separated)</label><textarea id="category-amenities" name="amenities_text">{{ old('amenities_text', implode("\n", $category->amenities ?? [])) }}</textarea></div>
                <div class="category-edit-field">
                    <label for="category-cover">Replace cover image</label>
                    <input id="category-cover" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    @if($category->cover_image)
                        <img class="category-edit-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->cover_image) }}" alt="{{ $category->title }} cover image">
                        <label class="category-check"><input type="checkbox" name="remove_cover_image" value="1"> Remove cover image</label>
                    @endif
                </div>
                <div class="category-edit-field">
                    <label for="category-gallery">Add gallery images (up to 12)</label>
                    <input id="category-gallery" name="gallery_uploads[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                    @if($category->gallery_images)
                        <div class="category-gallery">@foreach($category->gallery_images as $image)
                            <label><img class="category-edit-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" alt="{{ $category->title }} gallery image"><span><input type="checkbox" name="remove_gallery_images[]" value="{{ $image }}"> Remove</span></label>
                        @endforeach</div>
                    @endif
                </div>
                <div class="category-edit-field"><input type="hidden" name="is_active" value="0"><label class="category-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Active for reservations</label></div>
                <div class="category-edit-field"><input type="hidden" name="allow_extra_bed" value="0"><label class="category-check"><input type="checkbox" name="allow_extra_bed" value="1" @checked(old('allow_extra_bed', $category->allow_extra_bed))> Allow extra bed</label></div>
                <div class="category-edit-field"><input type="hidden" name="has_balcony" value="0"><label class="category-check"><input type="checkbox" name="has_balcony" value="1" @checked(old('has_balcony', $category->has_balcony))> Has balcony</label></div>
            </div>
            <div class="category-edit-actions">
                <button class="category-edit-button primary" type="submit">Save changes</button>
                <a class="category-edit-button" href="{{ route('frontdesk.inventory') }}">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
