<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Food menu catalog | {{ auth()->user()->tenant->hotel_name ?? auth()->user()->tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; margin: 0; background: #f4f7fb; color: #173149; font: 14px/1.5 system-ui, sans-serif; }
        .catalog-shell { max-width: 1180px; margin: 0 auto; padding: 24px 18px 40px; }
        .catalog-head, .catalog-panel { border: 1px solid #dce5ee; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(20, 43, 66, .06); }
        .catalog-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; padding: 18px 20px; }
        .catalog-head h1 { margin: 0; font-size: 1.45rem; }
        .catalog-head p { margin: 4px 0 0; color: #637589; }
        .catalog-panel { margin-bottom: 18px; padding: 18px; }
        .catalog-panel h2 { margin: 0 0 14px; font-size: 1.08rem; }
        .catalog-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .catalog-field label { display: block; margin-bottom: 5px; font-size: .82rem; font-weight: 700; }
        .catalog-field input, .catalog-field select, .catalog-field textarea { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid #cbd7e2; border-radius: 8px; background: #fff; color: inherit; font: inherit; }
        .catalog-field textarea { min-height: 64px; resize: vertical; }
        .catalog-field.full { grid-column: 1 / -1; }
        .catalog-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .catalog-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; padding: 7px 13px; border: 1px solid #c7d8e8; border-radius: 8px; background: #f3f8fc; color: #214969; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .catalog-button.primary { border-color: #176f68; background: #176f68; color: #fff; }
        .catalog-button.danger { border-color: #f0c5c5; background: #fff4f4; color: #9b2c36; }
        .catalog-button:hover { background: #e7f2fa; color: #17466d; }
        .catalog-button.primary:hover { background: #125e58; color: #fff; }
        .catalog-category { margin-top: 16px; padding: 15px; border: 1px solid #e0e8ef; border-radius: 12px; background: #fbfdff; }
        .catalog-category h3 { margin: 0; font-size: 1rem; }
        .catalog-category-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
        .catalog-item { margin-top: 12px; padding: 13px; border: 1px solid #e0e8ef; border-radius: 10px; background: #fff; }
        .catalog-note, .catalog-empty { color: #637589; font-size: .82rem; }
        .catalog-alert { margin-bottom: 16px; padding: 10px 13px; border-radius: 8px; background: #e9f7f1; color: #175d48; }
        .catalog-errors { background: #fff0f0; color: #8a2930; }
        html[data-theme="dark"] body { background: var(--theme-bg); color: var(--theme-text); }
        html[data-theme="dark"] .catalog-head, html[data-theme="dark"] .catalog-panel { border-color: var(--theme-border); background: var(--theme-surface); }
        html[data-theme="dark"] .catalog-head p, html[data-theme="dark"] .catalog-note, html[data-theme="dark"] .catalog-empty { color: var(--theme-muted); }
        html[data-theme="dark"] .catalog-category, html[data-theme="dark"] .catalog-item { border-color: var(--theme-border); background: var(--theme-surface-raised); }
        html[data-theme="dark"] .catalog-field input, html[data-theme="dark"] .catalog-field select, html[data-theme="dark"] .catalog-field textarea { border-color: #45566a; background: #202c3a; color: #f1f5f9; }
        html[data-theme="dark"] .catalog-button { border-color: #4a5b6e; background: #293747; color: #edf3f8; }
        html[data-theme="dark"] .catalog-button.primary { border-color: #2d8d82; background: #176f68; color: #f4fffd; }
        html[data-theme="dark"] .catalog-button.danger { border-color: #79434a; background: #48272b; color: #ffd2d2; }
        html[data-theme="dark"] .catalog-alert { background: #173b35; color: #b9f2df; }
        html[data-theme="dark"] .catalog-errors { background: #48272b; color: #ffd2d2; }
        @media (max-width: 700px) { .catalog-shell { padding: 14px 12px 28px; } .catalog-head { align-items: flex-start; flex-direction: column; } .catalog-grid { grid-template-columns: 1fr; } }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
<main class="catalog-shell">
    <header class="catalog-head">
        <div>
            <h1>Food &amp; extra services catalog</h1>
            <p>Manage food, laundry, minibar, housekeeping and other service prices. In-house staff can add available items directly to a guest folio. Standard extra-service categories and sample rates are ready to edit below.</p>
        </div>
        <div class="catalog-actions">
            <a class="catalog-button" href="{{ route('frontdesk.inventory') }}">Rooms &amp; categories</a>
            <a class="catalog-button" href="{{ route('frontdesk.dashboard') }}">Back to dashboard</a>
        </div>
    </header>

    @if(session('success'))<div class="catalog-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="catalog-alert catalog-errors" role="alert"><strong>Please review the menu details.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="catalog-panel">
        <h2>Add menu category</h2>
        <form method="POST" action="{{ route('frontdesk.food-catalog.categories.store') }}">
            @csrf
            <div class="catalog-grid">
                <div class="catalog-field"><label for="food-category-name">Category name</label><input id="food-category-name" name="name" placeholder="e.g. Food, Laundry & Pressing, Minibar, Housekeeping" maxlength="120" required></div>
                <div class="catalog-field"><label for="food-category-icon">Icon name (optional)</label><input id="food-category-icon" name="icon" placeholder="e.g. burger, coffee, utensils" maxlength="60"></div>
                <div class="catalog-field"><label for="food-category-order">Display order</label><input id="food-category-order" name="sort_order" type="number" min="0" value="100" required></div>
            </div>
            <div class="catalog-actions"><button class="catalog-button primary" type="submit">Add category</button></div>
        </form>
    </section>

    <section class="catalog-panel">
        <h2>Food and extra service items</h2>
        @forelse($categories as $category)
            <article class="catalog-category">
                <div class="catalog-category-head">
                    <div>
                        <h3>{{ $category->name }}</h3>
                        <div class="catalog-note">{{ $category->items_count }} items @if($category->icon) · {{ $category->icon }} @endif</div>
                        <form class="catalog-actions" method="POST" action="{{ route('frontdesk.food-catalog.categories.update', $category->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="catalog-field"><label for="food-category-name-{{ $category->id }}">Category name</label><input id="food-category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" maxlength="120" required></div>
                            <div class="catalog-field"><label for="food-category-icon-{{ $category->id }}">Icon</label><input id="food-category-icon-{{ $category->id }}" name="icon" value="{{ $category->icon }}" maxlength="60"></div>
                            <div class="catalog-field"><label for="food-category-order-{{ $category->id }}">Sort order</label><input id="food-category-order-{{ $category->id }}" name="sort_order" type="number" min="0" value="{{ $category->sort_order }}" required></div>
                            <button class="catalog-button primary" type="submit">Save category</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('frontdesk.food-catalog.categories.destroy', $category->id) }}" onsubmit="return confirm('Delete this menu category? It must not contain menu items.')">
                        @csrf
                        @method('DELETE')
                        <button class="catalog-button danger" type="submit" @disabled($category->items_count > 0)>Delete category</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('frontdesk.food-catalog.items.store') }}">
                    @csrf
                    <input type="hidden" name="food_category_id" value="{{ $category->id }}">
                    <div class="catalog-grid">
                        <div class="catalog-field"><label for="new-item-name-{{ $category->id }}">Item / package name</label><input id="new-item-name-{{ $category->id }}" name="name" maxlength="160" placeholder="e.g. Mango Milkshake, Continental Breakfast" required></div>
                        <div class="catalog-field"><label for="new-item-price-{{ $category->id }}">Unit price ({{ auth()->user()->tenant->currency_code ?: 'PKR' }})</label><input id="new-item-price-{{ $category->id }}" name="price" type="number" min="0" step="0.01" required></div>
                        <div class="catalog-field"><label for="new-item-billing-{{ $category->id }}">Billing type</label><select id="new-item-billing-{{ $category->id }}" name="billing_type" required><option value="one_time_order">One-time order</option><option value="per_guest_per_night">Per guest per night</option></select></div>
                        <div class="catalog-field"><label for="new-item-description-{{ $category->id }}">Description</label><input id="new-item-description-{{ $category->id }}" name="description" maxlength="2000"></div>
                        <div class="catalog-field"><input type="hidden" name="is_available" value="0"><label><input type="checkbox" name="is_available" value="1" checked> Available for orders</label></div>
                    </div>
                    <div class="catalog-actions"><button class="catalog-button primary" type="submit">Add menu item</button></div>
                </form>

                @forelse($category->items as $item)
                    <form class="catalog-item" method="POST" action="{{ route('frontdesk.food-catalog.items.update', $item->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="catalog-grid">
                            <div class="catalog-field"><label for="item-name-{{ $item->id }}">Name</label><input id="item-name-{{ $item->id }}" name="name" value="{{ $item->name }}" maxlength="160" required></div>
                            <div class="catalog-field"><label for="item-category-{{ $item->id }}">Category</label><select id="item-category-{{ $item->id }}" name="food_category_id" required>@foreach($categories as $optionCategory)<option value="{{ $optionCategory->id }}" @selected($optionCategory->id === $category->id)>{{ $optionCategory->name }}</option>@endforeach</select></div>
                            <div class="catalog-field"><label for="item-price-{{ $item->id }}">Unit price</label><input id="item-price-{{ $item->id }}" name="price" type="number" min="0" step="0.01" value="{{ $item->price }}" required></div>
                            <div class="catalog-field"><label for="item-billing-{{ $item->id }}">Billing type</label><select id="item-billing-{{ $item->id }}" name="billing_type" required><option value="one_time_order" @selected($item->billing_type === 'one_time_order')>One-time order</option><option value="per_guest_per_night" @selected($item->billing_type === 'per_guest_per_night')>Per guest per night</option></select></div>
                            <div class="catalog-field"><label for="item-description-{{ $item->id }}">Description</label><input id="item-description-{{ $item->id }}" name="description" value="{{ $item->description }}" maxlength="2000"></div>
                            <div class="catalog-field"><input type="hidden" name="is_available" value="0"><label><input type="checkbox" name="is_available" value="1" @checked($item->is_available)> Available for orders</label></div>
                        </div>
                        <div class="catalog-actions"><button class="catalog-button primary" type="submit">Save item</button></div>
                    </form>
                    <form class="catalog-actions" method="POST" action="{{ route('frontdesk.food-catalog.items.destroy', $item->id) }}" onsubmit="return confirm('Delete this menu item? Existing reservation charges will be retained.')">
                        @csrf
                        @method('DELETE')
                        <button class="catalog-button danger" type="submit">Delete {{ $item->name }}</button>
                    </form>
                @empty
                    <p class="catalog-empty mt-3 mb-0">No items in this category yet.</p>
                @endforelse
            </article>
        @empty
            <p class="catalog-empty mb-0">No menu categories are configured.</p>
        @endforelse
    </section>
</main>
</body>
</html>
