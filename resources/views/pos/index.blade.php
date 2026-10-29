<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurant POS | ArtFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page"
      x-data="posRegister({
          charge: @js($chargeRouteTemplate),
          guests: @js($guests),
          categories: @js($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'items' => $c->items->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'price' => (float) $i->price, 'description' => $i->description])->values()])->values()),
      })">
    <div class="page-head">
        <div>
            <p class="eyebrow">Point of sale</p>
            <h1>Restaurant POS</h1>
            <p>Build an order and post it to a guest's room folio, or take payment now.</p>
        </div>
        <a href="{{ route('frontdesk.in-house') }}" class="badge-soft text-decoration-none">In-house guests</a>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="notice-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="pos-layout">
        <section>
            <div class="chip-row" role="tablist" aria-label="Menu categories" style="margin-bottom:16px">
                <template x-for="cat in categories" :key="cat.id">
                    <button type="button" class="chip" :class="activeCategory === cat.id && 'is-active'" @click="activeCategory = cat.id">
                        <span x-text="cat.name"></span>
                    </button>
                </template>
            </div>

            <template x-if="categories.length === 0">
                <div class="surface" style="padding:40px;text-align:center">
                    <h2>No menu items yet</h2>
                    <p class="muted">Add categories and available items in the food catalog to start taking orders.</p>
                </div>
            </template>

            <div class="menu-grid">
                <template x-for="item in visibleItems()" :key="item.id">
                    <button type="button" class="menu-card" @click="add(item)" :aria-label="'Add ' + item.name">
                        <div>
                            <div class="name" x-text="item.name"></div>
                            <div class="desc" x-text="item.description || ''"></div>
                        </div>
                        <div class="price" x-text="money(item.price)"></div>
                    </button>
                </template>
            </div>
        </section>

        <aside class="surface cart" aria-label="Current order">
            <h2 style="margin:0 0 14px">Current order</h2>

            <label class="field-label" for="pos-guest">Guest (in-house)</label>
            <select id="pos-guest" class="select" x-model="bookingId" style="margin-bottom:14px">
                <option value="">Select a guest</option>
                <template x-for="g in guests" :key="g.id">
                    <option :value="g.id" x-text="g.label"></option>
                </template>
            </select>

            <div>
                <template x-if="lines.length === 0">
                    <p class="muted" style="font-size:13px;margin:8px 0">Tap a menu item to add it to the order.</p>
                </template>
                <template x-for="line in lines" :key="line.id">
                    <div class="cart-line">
                        <div style="min-width:0">
                            <div style="font-weight:600" x-text="line.name"></div>
                            <div class="muted" style="font-size:12px" x-text="money(line.price) + ' each'"></div>
                        </div>
                        <div class="qty">
                            <button type="button" @click="change(line, -1)" aria-label="Decrease">&minus;</button>
                            <span x-text="line.qty"></span>
                            <button type="button" @click="change(line, 1)" aria-label="Increase">+</button>
                        </div>
                    </div>
                </template>
            </div>

            <div style="margin-top:14px">
                <div class="sum-row"><span>Subtotal</span><span x-text="money(subtotal())"></span></div>
                <div class="sum-total" style="display:flex;justify-content:space-between"><span>Total</span><span x-text="money(subtotal())"></span></div>
            </div>

            <form method="POST" :action="actionUrl()" x-ref="form" style="margin-top:16px;display:grid;gap:12px">
                @csrf
                <input type="hidden" name="room_id" :value="roomId()">
                <input type="hidden" name="cart_items" :value="cartJson()">
                <input type="hidden" name="category" value="food_beverage">
                <input type="hidden" name="description" value="POS order">
                <input type="hidden" name="amount" value="1">
                <input type="hidden" name="quantity" value="1">

                <div>
                    <label class="field-label" for="pos-option">Settle</label>
                    <select id="pos-option" name="payment_option" class="select" x-model="option">
                        <option value="post_to_room">Post to room folio</option>
                        <option value="paid_immediately">Paid now</option>
                    </select>
                </div>
                <div x-show="option === 'paid_immediately'" x-cloak>
                    <label class="field-label" for="pos-method">Payment method</label>
                    <select id="pos-method" name="payment_method" class="select">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                    </select>
                </div>

                <button type="submit" class="btn-block" :disabled="!canSubmit()">Place order</button>
                <button type="button" class="chip" style="justify-self:center" @click="clear()" x-show="lines.length" x-cloak>Clear order</button>
            </form>
        </aside>
    </div>
</main>
@include('partials.hotel-shell-end')
<script>
    function posRegister(config) {
        return {
            charge: config.charge,
            guests: config.guests,
            categories: config.categories,
            activeCategory: config.categories.length ? config.categories[0].id : null,
            bookingId: '',
            option: 'post_to_room',
            lines: [],
            visibleItems() {
                const cat = this.categories.find((c) => c.id === this.activeCategory);
                return cat ? cat.items : [];
            },
            add(item) {
                const existing = this.lines.find((l) => l.id === item.id);
                if (existing) { existing.qty++; return; }
                this.lines.push({ id: item.id, name: item.name, price: item.price, qty: 1 });
            },
            change(line, delta) {
                line.qty += delta;
                if (line.qty <= 0) this.lines = this.lines.filter((l) => l.id !== line.id);
            },
            clear() { this.lines = []; },
            subtotal() { return this.lines.reduce((sum, l) => sum + l.price * l.qty, 0); },
            money(value) { return 'PKR ' + Number(value).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            selectedGuest() { return this.guests.find((g) => String(g.id) === String(this.bookingId)); },
            roomId() { return this.selectedGuest()?.room_id ?? ''; },
            actionUrl() { return this.bookingId ? this.charge.replace('__BOOKING__', this.bookingId) : '#'; },
            cartJson() {
                return JSON.stringify(this.lines.map((l) => ({ type: 'menu', id: l.id, quantity: l.qty })));
            },
            canSubmit() { return this.bookingId !== '' && this.lines.length > 0; },
        };
    }
</script>
</body>
</html>
