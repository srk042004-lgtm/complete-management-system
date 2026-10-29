<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Reservation | {{ config('app.name', 'ArtFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{--ink:#17313a;--muted:#64748b;--line:#dbe5e7;--teal:#0f766e;--surface:#fff;--bg:#f4f8f7}
        *{box-sizing:border-box}
        body{margin:0;background:linear-gradient(135deg,#edf5f4,#f6f8fc);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .booking-shell{width:min(1180px,calc(100% - 32px));margin:22px auto 48px}
        .hero,.panel{border:1px solid rgba(18,52,84,.1);border-radius:20px;background:var(--surface);box-shadow:0 14px 34px rgba(11,31,56,.07)}
        .hero{padding:16px 22px;margin-bottom:16px}
        .hero h1{margin:0;font-size:1.25rem;font-weight:800}
        .hero p{margin:4px 0 0;color:var(--muted);font-size:.85rem}
        .panel{padding:18px 20px;margin-bottom:14px}
        .panel-heading{display:flex;align-items:flex-start;gap:13px;margin-bottom:19px}
        .panel-number{display:grid;place-items:center;flex:0 0 34px;height:34px;border-radius:11px;background:#e6f5f2;color:var(--teal);font-weight:850}
        .panel-heading h2{margin:0;font-size:1.05rem;font-weight:800}
        .panel-heading p{margin:3px 0 0;color:var(--muted);font-size:.85rem}
        .field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .field{min-width:0}
        .field.full{grid-column:1/-1}
        .field label{display:block;margin-bottom:6px;font-size:.84rem;font-weight:750}
        .required{color:#c2413b}
        .field input,.field select,.field textarea{width:100%;min-height:44px;padding:10px 12px;border:1px solid #cbd7da;border-radius:10px;background:#fff;color:var(--ink);font:inherit}
        .field textarea{min-height:90px;resize:vertical}
        .field input:focus,.field select:focus,.field textarea:focus{outline:3px solid rgba(15,118,110,.14);border-color:var(--teal)}
        .field input[readonly]{background:#f6f9f9;color:#52656a}
        .help{margin:5px 0 0;color:var(--muted);font-size:.75rem}
        .error-list{margin:0 0 18px;padding:13px 18px;border:1px solid #fecaca;border-radius:12px;background:#fff1f2;color:#9f1239}
        .error-list ul{margin:6px 0 0;padding-left:20px}
        .notice{padding:13px 16px;border:1px solid #a7f3d0;border-radius:12px;background:#ecfdf5;color:#065f46;margin-bottom:18px}
        .category-summary{grid-column:1/-1;padding:16px;border:1px solid #d7e9e7;border-radius:13px;background:#f3faf9}
        .category-summary h3{margin:0 0 6px;font-size:.92rem}
        .category-summary p{margin:0;color:#50666a;font-size:.84rem}
        .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:13px}
        .summary-item{padding:10px;border-radius:10px;background:#fff}
        .summary-item span{display:block;color:var(--muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.05em}
        .summary-item strong{display:block;margin-top:3px;font-size:.9rem}
        .food-list{display:grid;gap:12px}
        .food-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;align-items:end;padding:15px;border:1px solid var(--line);border-radius:13px;background:#fbfdfd}
        .food-row .field label{font-size:.76rem}
        .remove-food{height:42px;padding:0 13px;border:1px solid #fecaca;border-radius:10px;background:#fff;color:#b91c1c;font:inherit;font-weight:750;cursor:pointer}
        .billing-summary{display:grid;gap:10px;margin-top:16px;padding:16px;border-radius:13px;background:#f3faf9}
        .billing-line{display:flex;justify-content:space-between;gap:14px;color:#415b60}
        .billing-line.total{padding-top:10px;border-top:1px solid #cbdedc;color:var(--ink);font-size:1.05rem;font-weight:850}
        .booking-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:16px;align-items:start}
        .booking-main{min-width:0}
        .booking-side{min-width:0}
        @media(min-width:1024px){.booking-grid{grid-template-columns:minmax(0,8fr) minmax(0,4fr)}.booking-side{position:sticky;top:20px}}
        .side-card{padding:18px 18px 20px;border:1px solid rgba(18,52,84,.1);border-radius:18px;background:#fff;box-shadow:0 14px 34px rgba(11,31,56,.07)}
        .side-title{margin:0 0 10px;font-size:.98rem;font-weight:800}
        .side-line{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid #eef3f3;font-size:.86rem;color:#415b60}
        .side-line strong{color:var(--ink);font-variant-numeric:tabular-nums;text-align:right}
        .side-total{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:14px 0 8px;padding:13px 14px;border-radius:12px;background:#e6f5f2;color:var(--ink);font-weight:750}
        .side-total strong{font-size:1.1rem;font-weight:850;color:#0f766e;font-variant-numeric:tabular-nums}
        .side-card .help{margin:0 0 12px}
        .button.cta{width:100%;background:#115e59;box-shadow:0 6px 16px rgba(17,94,89,.22)}
        .button.cta:hover{background:#0f4f4a;filter:none}
        .button.block{width:100%;margin-top:8px}
        .actions{display:flex;justify-content:flex-end;gap:10px;padding:5px 2px}
        .button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 18px;border:0;border-radius:11px;background:var(--teal);color:#fff;font:inherit;font-weight:800;text-decoration:none;cursor:pointer}
        .button.secondary{border:1px solid #cbd7da;background:#fff;color:#334b50}
        .button:hover{filter:brightness(.96)}
        @media(max-width:640px){.booking-shell{margin:12px auto 32px}.hero,.panel{padding:16px;border-radius:16px}.field-grid,.food-row{grid-template-columns:1fr}.field.full,.category-summary{grid-column:auto}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        /* Compact pass: tighter spacing, smaller controls, same layout. */
        .hero{padding:12px 18px;margin-bottom:12px}
        .hero h1{font-size:1.1rem}
        .hero p{font-size:.8rem;margin-top:2px}
        .booking-grid{gap:12px}
        .panel{padding:14px 16px;margin-bottom:12px;border-radius:16px}
        .panel-heading{gap:10px;margin-bottom:12px}
        .panel-number{flex:0 0 28px;height:28px;font-size:.8rem;border-radius:9px}
        .panel-heading h2{font-size:.95rem}
        .panel-heading p{font-size:.78rem;margin-top:1px}
        .field-grid{gap:10px 14px}
        .field label{margin-bottom:3px;font-size:.78rem}
        .field input,.field select,.field textarea{min-height:36px;padding:7px 10px;font-size:.88rem;border-radius:9px}
        .field textarea{min-height:52px}
        .guest-match__box{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid #99f6e4;border-radius:11px;background:#f0fdfa;color:#134e4a;font-size:.84rem}
        .guest-match__box strong{display:block;font-weight:800}
        .guest-match__box span{display:block;margin-top:2px;color:#0f766e}
        .guest-match__actions{display:flex;flex-wrap:wrap;gap:8px}
        .guest-match__actions .button{min-height:34px;padding:6px 12px;font-size:.8rem}
        .field input[type=file]{min-height:0;padding:5px 8px;font-size:.78rem}
        .help{margin-top:2px;font-size:.7rem;line-height:1.35}
        .category-summary{padding:10px 12px;border-radius:11px}
        .category-summary h3{font-size:.85rem;margin-bottom:3px}
        .category-summary p{font-size:.78rem}
        .summary-grid{gap:8px;margin-top:9px}
        .summary-item{padding:7px 9px}
        .summary-item span{font-size:.64rem}
        .summary-item strong{font-size:.82rem;margin-top:1px}
        .food-list{gap:8px}
        .food-row{padding:10px;gap:9px;border-radius:11px}
        .food-row .field label{font-size:.72rem}
        .remove-food{height:36px;font-size:.8rem}
        .button{min-height:38px;padding:7px 14px;font-size:.88rem;border-radius:10px}
        .side-card{padding:14px 14px 16px;border-radius:15px}
        .side-title{margin-bottom:6px;font-size:.9rem}
        .side-line{padding:6px 0;font-size:.8rem}
        .side-total{margin:10px 0 4px;padding:9px 11px;font-size:.84rem}
        .side-total strong{font-size:1rem}
        .side-card .help{margin-bottom:8px}
        .button.cta{min-height:40px}
        .button.block{margin-top:6px;min-height:36px}
        @media(min-width:1024px){.booking-side{top:12px}}
    </style>
</head>
<body>
<main class="booking-shell">
    <header class="hero">
        <h1>New Reservation</h1>
        <p>Create a reservation, capture guest details, and assign an available room.</p>
    </header>

    @if(session('success'))
        <div class="notice" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="error-list" role="alert">
            <strong>Please check the reservation details.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('reservations.booking.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="booking-grid">
        <div class="booking-main">
        <section class="panel" aria-labelledby="guest-heading">
            <div class="panel-heading">
                <span class="panel-number">01</span>
                <div><h2 id="guest-heading">Guest information</h2><p>Primary guest identity and contact details.</p></div>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label for="guest_name">Full legal name <span class="required">*</span></label>
                    <input id="guest_name" name="guest_name" value="{{ old('guest_name') }}" autocomplete="name" required maxlength="255">
                </div>
                <div class="field">
                    <label for="phone">Mobile <span class="required">*</span></label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="03XX-XXXXXXX" autocomplete="tel" required maxlength="50">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255">
                </div>
                <div class="field">
                    <label for="id_type">CNIC / ID type <span class="required">*</span></label>
                    <select id="id_type" name="id_type" required>
                        <option value="cnic_national_id" @selected(old('id_type', 'cnic_national_id') === 'cnic_national_id')>CNIC / National ID</option>
                        <option value="passport" @selected(old('id_type') === 'passport')>Passport</option>
                        <option value="drivers_license" @selected(old('id_type') === 'drivers_license')>Driver's license</option>
                    </select>
                </div>
                <div class="field">
                    <label for="id_number">ID number <span class="required">*</span></label>
                    <input id="id_number" name="id_number" value="{{ old('id_number') }}" required maxlength="255">
                </div>
                <div class="field full guest-match" id="guest-match" role="status" hidden>
                    <input type="hidden" name="guest_id" id="guest_id" value="{{ old('guest_id') }}">
                    <div class="guest-match__box">
                        <div><strong>Returning guest recognised</strong><span id="guest-match-text"></span></div>
                        <div class="guest-match__actions">
                            <button type="button" class="button" id="guest-match-yes">Yes, use saved details</button>
                            <button type="button" class="button secondary" id="guest-match-no">No, different person</button>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label for="opening_balance">Opening balance</label>
                    <input id="opening_balance" name="opening_balance" type="number" step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
                    <p class="help">Guest account opening balance; defaults to 0.00.</p>
                </div>
                <div class="field full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="2" autocomplete="street-address" maxlength="2000">{{ old('address') }}</textarea>
                </div>
                <div class="field">
                    <label for="photo">Guest photo</label>
                    <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <p class="help">Optional JPG, PNG, or WebP, up to 5 MB.</p>
                </div>
                <div class="field">
                    <label for="id_document">CNIC / ID document upload</label>
                    <input id="id_document" name="id_document" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                    <p class="help">Optional JPG, PNG, or PDF, up to 2 MB.</p>
                </div>
            </div>
        </section>

        <section class="panel" aria-labelledby="stay-heading">
            <div class="panel-heading">
                <span class="panel-number">02</span>
                <div><h2 id="stay-heading">Stay and room</h2><p>Select the stay dates, room category, and physical room.</p></div>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label for="check_in_date">Check-in date <span class="required">*</span></label>
                    <input id="check_in_date" name="check_in_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('check_in_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="check_out_date">Check-out date <span class="required">*</span></label>
                    <input id="check_out_date" name="check_out_date" type="date" min="{{ now()->addDay()->toDateString() }}" value="{{ old('check_out_date', now()->addDay()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="room_type_id">Room category <span class="required">*</span></label>
                    <select id="room_type_id" name="room_type_id" required>
                        <option value="">Select a category</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('room_type_id') === (string) $type->id)>{{ $type->title ?: $type->type_name }}</option>
                        @endforeach
                    </select>
                    @if($roomTypes->isEmpty())
                        <p class="help">No categories are set up yet. <a href="{{ route('frontdesk.inventory') }}">Add room categories and rooms</a> first.</p>
                    @endif
                </div>
                <div class="field">
                    <label for="room_id">Room # <span class="required">*</span></label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Select a room</option>
                        @foreach($rooms as $room)
                            <option
                                value="{{ $room->id }}"
                                data-category="{{ $room->room_type_id }}"
                                data-name="{{ $room->category_name }}"
                                data-adults="{{ $room->category_adults }}"
                                data-children="{{ $room->category_children }}"
                                data-total-capacity="{{ $room->category_total_occupancy }}"
                                data-base-occupancy="{{ $room->base_occupancy }}"
                                data-rate="{{ $room->base_price_per_night }}"
                                data-description="{{ $room->category_description }}"
                                data-bed-type="{{ $room->bed_type }}"
                                data-bed-count="{{ $room->bed_count }}"
                                data-view-type="{{ $room->view_type }}"
                                data-balcony="{{ $room->has_balcony ? 'Yes' : 'No' }}"
                                data-amenities="{{ json_encode(json_decode($room->amenities ?? '[]', true) ?: []) }}"
                                data-cover-image="{{ $room->cover_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($room->cover_image) : '' }}"
                                data-floor="{{ $room->floor }}"
                                data-status="{{ ucfirst(str_replace('_', ' ', $room->occupancy_status)) }} · {{ ucfirst(str_replace('_', ' ', $room->housekeeping_status)) }}"
                                @selected((string) old('room_id') === (string) $room->id)
                            >{{ $room->room_number }} — {{ $room->category_name }}</option>
                        @endforeach
                    </select>
                    @if($rooms->isEmpty())
                        <p class="help">No rooms are set up yet. <a href="{{ route('frontdesk.inventory') }}">Add rooms to this hotel</a>.</p>
                    @endif
                </div>
                <div class="field">
                    <label for="floor_filter">Floor</label>
                    <select id="floor_filter" aria-label="Filter rooms by floor">
                        <option value="">All floors</option>
                        @foreach($rooms->pluck('floor')->filter(fn ($floor) => $floor !== null)->unique()->sort() as $floor)
                            <option value="{{ $floor }}" data-floor-option="{{ $floor }}" @selected((string) old('floor_filter') === (string) $floor)>Floor {{ $floor }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="status_display">Room status</label>
                    <input id="status_display" readonly placeholder="Select a room to see its status">
                </div>
                <div class="field">
                    <label for="adults">Adults <span class="required">*</span></label>
                    <input id="adults" name="adults" type="number" min="1" max="9" value="{{ old('adults', 1) }}" required>
                    <p id="adult-capacity" class="help">Select a category to see adult capacity.</p>
                </div>
                <div class="field">
                    <label for="children">Children <span class="required">*</span></label>
                    <input id="children" name="children" type="number" min="0" max="9" value="{{ old('children', 0) }}" required>
                    <p id="child-capacity" class="help">Select a category to see child capacity.</p>
                </div>
                <div class="category-summary" aria-live="polite">
                    <h3 id="category-name">Room category details</h3>
                    <p id="category-description">Choose a room to view its category description.</p>
                    <div id="category-amenities" class="help"></div>
                    <div id="category-features" class="help"></div>
                    <img id="category-cover-image" alt="Room category photo" hidden style="max-width:180px;max-height:120px;object-fit:cover;border-radius:9px;margin-top:10px">
                    <div class="summary-grid">
                        <div class="summary-item"><span>Maximum adults</span><strong id="summary-adults">—</strong></div>
                        <div class="summary-item"><span>Maximum children</span><strong id="summary-children">—</strong></div>
                        <div class="summary-item"><span>Base rate / night</span><strong id="summary-rate">—</strong></div>
                        <div class="summary-item"><span>Estimated total</span><strong id="summary-total">—</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" aria-labelledby="food-heading">
            <div class="panel-heading">
                <span class="panel-number">03</span>
                <div><h2 id="food-heading">Food packages &amp; room service</h2><p>Select items from the categorized menu. Catalog prices are filled automatically; customize quantity, package guests, and kitchen notes.</p></div>
            </div>
            @if($foodCategories->sum(fn ($foodCategory) => $foodCategory->items->count()) === 0)
                <p class="help">Your menu categories are ready. Add priced items in <a href="{{ route('frontdesk.food-catalog') }}">Manage Food Menu</a> to select them here.</p>
            @endif
            <div id="food-package-list" class="food-list"></div>
            <div class="actions" style="justify-content:flex-start">
                <button id="add-food-package" class="button secondary" type="button">Add food or room-service item</button>
                <a class="button secondary" href="{{ route('frontdesk.food-catalog') }}">Manage menu catalog</a>
            </div>
            <template id="food-package-template">
                <div class="food-row">
                    <div class="field"><label>Category</label><select data-food-field="category_id"><option value="">Choose a category</option>@foreach($foodCategories as $foodCategory)<option value="{{ $foodCategory->id }}">{{ $foodCategory->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Menu item / package</label><select data-food-field="menu_item_id" required><option value="">Select a category first</option><option value="custom">Custom item (manual price)</option>@foreach($foodCategories as $foodCategory)@foreach($foodCategory->items as $item)<option value="{{ $item->id }}" data-category="{{ $foodCategory->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}" data-billing="{{ $item->billing_type }}" data-category-name="{{ $foodCategory->name }}">{{ $item->name }} — {{ $currencyCode }} {{ number_format((float) $item->price, 2) }}</option>@endforeach @endforeach</select></div>
                    <div class="field food-custom-field" hidden><label>Custom item name</label><input data-food-field="name" maxlength="160" placeholder="Enter item or package name"></div>
                    <div class="field food-custom-billing-field" hidden><label>Custom billing type</label><select data-food-field="billing_type"><option value="one_time_order">One-time order</option><option value="per_guest_per_night">Per guest per night</option></select></div>
                    <div class="field food-price-field"><label>Unit price ({{ $currencyCode }})</label><input data-food-field="price_per_person_per_night" type="number" min="0" step="0.01" readonly></div>
                    <div class="field food-guests-field" hidden><label>Package guests</label><input data-food-field="guest_count" type="number" min="1" value="1"></div>
                    <div class="field food-quantity-field" hidden><label>Order quantity</label><input data-food-field="quantity" type="number" min="1" max="1000" value="1"></div>
                    <div class="field full"><label>Kitchen notes</label><input data-food-field="kitchen_notes" maxlength="1000" placeholder="e.g. Less sugar, extra sauce, serve at 8 AM"></div>
                    <div class="field full"><strong class="food-line-total">{{ $currencyCode }} 0.00</strong><span class="help food-billing-label"></span></div>
                    <input type="hidden" data-food-field="is_custom" value="0">
                    <button class="remove-food" type="button">Remove item</button>
                </div>
            </template>
            <script type="application/json" id="old-food-packages">@json(old('food_packages', []))</script>
        </section>

        </div>

        <aside class="booking-side" aria-label="Payment summary">
            <div class="side-card">
                <h2 class="side-title">Payment summary</h2>
                <div class="side-line"><span>Nights</span><strong id="bill-nights">0</strong></div>
                <div class="side-line"><span>Base rate / night</span><strong id="bill-rate">—</strong></div>
                <div class="side-line"><span>Room subtotal</span><strong id="bill-room">{{ $currencyCode }} 0.00</strong></div>
                <div class="side-line"><span>Food &amp; room service</span><strong id="bill-food">{{ $currencyCode }} 0.00</strong></div>
                <div class="side-total"><span>Estimated reservation total</span><strong id="bill-total">{{ $currencyCode }} 0.00</strong></div>
                <p class="help">Estimate only. The final bill is settled at check-out.</p>
                <button class="button cta" type="submit">Create reservation</button>
                <a class="button secondary block" href="{{ route('frontdesk.dashboard') }}">Cancel</a>
            </div>
        </aside>
        </div>
    </form>
</main>
<script>
    const categorySelect = document.getElementById('room_type_id');
    const roomSelect = document.getElementById('room_id');
    const floorSelect = document.getElementById('floor_filter');
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    const adultsInput = document.getElementById('adults');
    const childrenInput = document.getElementById('children');
    const roomOptions = Array.from(roomSelect.options);
    const foodPackageList = document.getElementById('food-package-list');
    const foodPackageTemplate = document.getElementById('food-package-template');
    const currencyCode = @json($currencyCode);

    function selectedRoom() {
        return roomSelect.options[roomSelect.selectedIndex];
    }

    function stayNights() {
        return Math.max(0, Math.round((new Date(checkOut.value) - new Date(checkIn.value)) / 86400000));
    }

    function updateRoomOptions() {
        const categoryId = categorySelect.value;
        const floorOptions = Array.from(floorSelect.options).slice(1);
        floorOptions.forEach((option) => {
            const hasCategoryRoom = roomOptions.some((roomOption) => (
                roomOption.value
                && roomOption.dataset.floor === option.dataset.floorOption
                && (categoryId === '' || roomOption.dataset.category === categoryId)
            ));
            option.hidden = !hasCategoryRoom;
        });
        if (floorSelect.selectedOptions[0]?.hidden) floorSelect.value = '';
        const floor = floorSelect.value;
        roomOptions.forEach((option, index) => {
            if (index === 0) return;
            option.hidden = (categoryId !== '' && option.dataset.category !== categoryId)
                || (floor !== '' && option.dataset.floor !== floor);
            option.disabled = option.hidden;
        });
        if (selectedRoom()?.disabled) roomSelect.value = '';
        updateRoomSummary();
    }

    function updateRoomSummary() {
        const room = selectedRoom();
        if (!room || !room.value) {
            document.getElementById('status_display').value = '';
            document.getElementById('category-name').textContent = 'Room category details';
            document.getElementById('category-description').textContent = 'Choose a room to view its category description.';
            document.getElementById('category-amenities').textContent = '';
            document.getElementById('category-features').textContent = '';
            document.getElementById('category-cover-image').hidden = true;
            document.getElementById('summary-adults').textContent = '—';
            document.getElementById('summary-children').textContent = '—';
            document.getElementById('summary-rate').textContent = '—';
            document.getElementById('summary-total').textContent = '—';
            document.getElementById('adult-capacity').textContent = 'Select a category to see adult capacity.';
            document.getElementById('child-capacity').textContent = 'Select a category to see child capacity.';
            document.getElementById('bill-nights').textContent = String(stayNights());
            document.getElementById('bill-rate').textContent = '—';
            updateBilling(0, stayNights());
            return;
        }

        const rate = Number(room.dataset.rate || 0);
        const maxAdults = Number(room.dataset.adults || 0);
        const maxChildren = Number(room.dataset.children || 0);
        const maxTotalGuests = Number(room.dataset.totalCapacity || maxAdults + maxChildren);
        const nights = stayNights();
        if (room.dataset.floor) floorSelect.value = room.dataset.floor;
        document.getElementById('status_display').value = room.dataset.status || '';
        document.getElementById('category-name').textContent = room.dataset.name || 'Room category';
        document.getElementById('category-description').textContent = room.dataset.description || 'No category description provided.';
        const amenities = JSON.parse(room.dataset.amenities || '[]');
        document.getElementById('category-amenities').textContent = amenities.length ? `Amenities: ${amenities.join(', ')}` : '';
        document.getElementById('category-features').textContent = [
            `${room.dataset.bedCount || 1} ${String(room.dataset.bedType || 'KING').replaceAll('_', ' ')} bed(s)`,
            String(room.dataset.viewType || 'CITY_VIEW').replaceAll('_', ' ').replace('VIEW', 'view'),
            room.dataset.balcony === 'Yes' ? 'Balcony' : '',
            `Base occupancy ${room.dataset.baseOccupancy || 2}; maximum ${room.dataset.totalCapacity || maxAdults + maxChildren} guests`,
        ].filter(Boolean).join(' · ');
        const coverImage = document.getElementById('category-cover-image');
        coverImage.hidden = !room.dataset.coverImage;
        if (room.dataset.coverImage) coverImage.src = room.dataset.coverImage;
        document.getElementById('summary-adults').textContent = String(maxAdults);
        document.getElementById('summary-children').textContent = String(maxChildren);
        document.getElementById('summary-rate').textContent = `{{ $currencyCode }} ${rate.toFixed(2)}`;
        document.getElementById('summary-total').textContent = `${currencyCode} ${(rate * nights).toFixed(2)}`;
        adultsInput.max = String(Math.min(maxAdults || 1, maxTotalGuests - Number(childrenInput.value || 0)));
        childrenInput.max = String(Math.min(maxChildren, maxTotalGuests - Number(adultsInput.value || 1)));
        // Keep typed guest counts inside the room's capacity.
        if (Number(adultsInput.value) > Number(adultsInput.max)) adultsInput.value = adultsInput.max;
        if (Number(childrenInput.value) > Number(childrenInput.max)) childrenInput.value = childrenInput.max;
        document.getElementById('adult-capacity').textContent = `Up to ${maxAdults} adults for this category.`;
        document.getElementById('child-capacity').textContent = `Up to ${maxChildren} children for this category.`;
        document.getElementById('bill-nights').textContent = String(nights);
        document.getElementById('bill-rate').textContent = `{{ $currencyCode }} ${rate.toFixed(2)}`;
        updateBilling(rate * nights, nights);
    }

    function updateBilling(roomSubtotal, nights) {
        let foodSubtotal = 0;
        const guestCount = Number(adultsInput.value || 0) + Number(childrenInput.value || 0);
        foodPackageList.querySelectorAll('.food-row').forEach((row) => {
            const billingType = row.dataset.billingType || 'one_time_order';
            const guestInput = row.querySelector('[data-food-field="guest_count"]');
            const quantityInput = row.querySelector('[data-food-field="quantity"]');
            const maxGuests = Math.max(guestCount, 1);
            guestInput.max = String(maxGuests);
            if (Number(guestInput.value || 0) > maxGuests) guestInput.value = String(maxGuests);
            const guests = Number(guestInput.value || guestCount || 1);
            const quantity = Number(quantityInput.value || 1);
            const rate = Number(row.querySelector('[data-food-field="price_per_person_per_night"]').value || 0);
            const lineTotal = billingType === 'per_guest_per_night' ? guests * rate * nights : quantity * rate;
            row.querySelector('.food-line-total').textContent = `${currencyCode} ${lineTotal.toFixed(2)}`;
            foodSubtotal += lineTotal;
        });
        const total = roomSubtotal + foodSubtotal;
        document.getElementById('bill-room').textContent = `${currencyCode} ${roomSubtotal.toFixed(2)}`;
        document.getElementById('bill-food').textContent = `${currencyCode} ${foodSubtotal.toFixed(2)}`;
        document.getElementById('bill-total').textContent = `${currencyCode} ${total.toFixed(2)}`;
        document.getElementById('summary-total').textContent = `${currencyCode} ${total.toFixed(2)}`;
    }

    function reindexFoodPackageInputs() {
        foodPackageList.querySelectorAll('.food-row').forEach((row, index) => {
            row.querySelectorAll('[data-food-field]').forEach((input) => {
                input.name = `food_packages[${index}][${input.dataset.foodField}]`;
            });
        });
        document.getElementById('add-food-package').disabled = foodPackageList.querySelectorAll('.food-row').length >= 10;
    }

    function updateFoodRow(row, oldLine = null) {
        const categorySelect = row.querySelector('[data-food-field="category_id"]');
        const itemSelect = row.querySelector('[data-food-field="menu_item_id"]');
        const selectedItem = itemSelect.options[itemSelect.selectedIndex];
        const custom = itemSelect.value === 'custom';
        const nameInput = row.querySelector('[data-food-field="name"]');
        const priceInput = row.querySelector('[data-food-field="price_per_person_per_night"]');
        const customFlag = row.querySelector('[data-food-field="is_custom"]');
        const billingInput = row.querySelector('[data-food-field="billing_type"]');
        const guestField = row.querySelector('.food-guests-field');
        const quantityField = row.querySelector('.food-quantity-field');

        Array.from(itemSelect.options).forEach((option) => {
            if (option.dataset.category) {
                option.hidden = categorySelect.value !== '' && option.dataset.category !== categorySelect.value;
                option.disabled = option.hidden;
            }
        });
        if (selectedItem?.disabled) itemSelect.value = '';
        const activeItem = itemSelect.options[itemSelect.selectedIndex];

        row.querySelector('[data-food-field="category_id"]').required = false;
        row.querySelector('[data-food-field="name"]').required = custom;
        row.querySelector('[data-food-field="price_per_person_per_night"]').readOnly = !custom;
        row.querySelector('.food-custom-field').hidden = !custom;
        row.querySelector('.food-custom-billing-field').hidden = !custom;
        customFlag.value = custom ? '1' : '0';

        if (custom) {
            row.dataset.billingType = billingInput.value || 'one_time_order';
            if (oldLine) {
                nameInput.value = oldLine.name || '';
                priceInput.value = oldLine.price_per_person_per_night || '';
                billingInput.value = oldLine.billing_type || 'per_guest_per_night';
                row.dataset.billingType = billingInput.value;
                row.querySelector('[data-food-field="guest_count"]').value = oldLine.guest_count || 1;
                row.querySelector('[data-food-field="quantity"]').value = oldLine.quantity || 1;
                row.querySelector('[data-food-field="kitchen_notes"]').value = oldLine.kitchen_notes || '';
            }
        } else if (activeItem?.value) {
            categorySelect.value = activeItem.dataset.category;
            nameInput.value = activeItem.dataset.name;
            priceInput.value = activeItem.dataset.price;
            row.dataset.billingType = activeItem.dataset.billing;
            if (oldLine || document.activeElement === itemSelect) {
                row.querySelector('[data-food-field="guest_count"]').value = oldLine?.guest_count || String(Math.max(1, Number(adultsInput.value || 1) + Number(childrenInput.value || 0)));
                row.querySelector('[data-food-field="quantity"]').value = oldLine?.quantity || 1;
                row.querySelector('[data-food-field="kitchen_notes"]').value = oldLine?.kitchen_notes || '';
            }
        } else {
            nameInput.value = '';
            priceInput.value = '';
            row.dataset.billingType = 'one_time_order';
        }

        const packageBilling = row.dataset.billingType === 'per_guest_per_night';
        guestField.hidden = !packageBilling;
        quantityField.hidden = packageBilling;
        row.querySelector('.food-billing-label').textContent = packageBilling
            ? `Per guest per night · ${stayNights()} night(s)`
            : 'One-time order · quantity × unit price';
    }

    function addFoodPackage(oldLine = null) {
        if (foodPackageList.querySelectorAll('.food-row').length >= 10) return;
        const fragment = foodPackageTemplate.content.cloneNode(true);
        const row = fragment.querySelector('.food-row');
        if (oldLine?.menu_item_id) {
            row.querySelector('[data-food-field="category_id"]').value = String(oldLine.food_category_id || '');
            row.querySelector('[data-food-field="menu_item_id"]').value = String(oldLine.menu_item_id);
        } else if (oldLine?.name) {
            row.querySelector('[data-food-field="menu_item_id"]').value = 'custom';
        }
        foodPackageList.append(fragment);
        const insertedRow = foodPackageList.lastElementChild;
        updateFoodRow(insertedRow, oldLine);
        reindexFoodPackageInputs();
        updateRoomSummary();
    }

    document.getElementById('add-food-package').addEventListener('click', addFoodPackage);
    foodPackageList.addEventListener('input', (event) => {
        const row = event.target.closest('.food-row');
        if (row) updateFoodRow(row);
        reindexFoodPackageInputs();
        updateRoomSummary();
    });
    foodPackageList.addEventListener('change', (event) => {
        const row = event.target.closest('.food-row');
        if (row) updateFoodRow(row);
        reindexFoodPackageInputs();
        updateRoomSummary();
    });
    foodPackageList.addEventListener('click', (event) => {
        if (event.target.closest('.remove-food')) {
            event.target.closest('.food-row').remove();
            reindexFoodPackageInputs();
            updateRoomSummary();
        }
    });

    function updateCheckoutMinimum() {
        if (checkIn.value) {
            const nextDay = new Date(`${checkIn.value}T00:00:00`);
            nextDay.setDate(nextDay.getDate() + 1);
            const minimum = `${nextDay.getFullYear()}-${String(nextDay.getMonth() + 1).padStart(2, '0')}-${String(nextDay.getDate()).padStart(2, '0')}`;
            checkOut.min = minimum;
            if (checkOut.value && checkOut.value < minimum) checkOut.value = minimum;
        }
        updateRoomSummary();
    }

    categorySelect.addEventListener('change', updateRoomOptions);
    floorSelect.addEventListener('change', updateRoomOptions);
    roomSelect.addEventListener('change', updateRoomSummary);
    checkIn.addEventListener('change', updateCheckoutMinimum);
    checkOut.addEventListener('change', updateRoomSummary);
    adultsInput.addEventListener('input', updateRoomSummary);
    childrenInput.addEventListener('input', updateRoomSummary);
    const oldFoodPackages = JSON.parse(document.getElementById('old-food-packages').textContent || '[]');
    oldFoodPackages.forEach((line) => addFoodPackage(line));
    // Recognise a returning guest by exact ID type and number. The receptionist confirms before any details are filled.
    const idNumberInput = document.getElementById('id_number');
    const idTypeInput = document.getElementById('id_type');
    const guestMatch = document.getElementById('guest-match');
    const guestIdInput = document.getElementById('guest_id');
    const guestMatchText = document.getElementById('guest-match-text');
    let pendingGuest = null;
    let lookupTimer = null;

    function clearGuestMatch() {
        guestIdInput.value = '';
        pendingGuest = null;
        guestMatch.hidden = true;
    }

    async function lookupGuest() {
        const number = idNumberInput.value.trim();
        if (number.length < 4) {
            clearGuestMatch();
            return;
        }
        const params = new URLSearchParams({ id_type: idTypeInput.value, id_number: number });
        try {
            const response = await fetch(`{{ route('reservations.guest-lookup') }}?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.found) {
                clearGuestMatch();
                return;
            }
            pendingGuest = data.guest;
            guestMatchText.textContent = `${data.guest.name} · ${data.guest.phone || 'no phone saved'} · ID ${number}. Is this the same person?`;
            guestMatch.hidden = false;
        } catch (error) {
            // Lookup is optional; the form still works without it.
        }
    }

    idNumberInput.addEventListener('input', () => {
        clearGuestMatch();
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(lookupGuest, 350);
    });
    idTypeInput.addEventListener('change', () => {
        clearGuestMatch();
        lookupGuest();
    });
    document.getElementById('guest-match-yes').addEventListener('click', () => {
        if (!pendingGuest) return;
        document.getElementById('guest_name').value = pendingGuest.name;
        document.getElementById('phone').value = pendingGuest.phone || '';
        document.getElementById('email').value = pendingGuest.email || '';
        document.getElementById('address').value = pendingGuest.address || '';
        guestIdInput.value = String(pendingGuest.id);
        guestMatch.hidden = true;
    });
    document.getElementById('guest-match-no').addEventListener('click', clearGuestMatch);

    updateRoomOptions();
    updateCheckoutMinimum();
    reindexFoodPackageInputs();
</script>
</body>
</html>
