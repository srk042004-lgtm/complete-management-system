@extends('frontdesk.quick-actions.layout')

@section('title', 'In-House Guests')
@section('description', 'Review checked-in guests, room assignments, and current folio balances.')

@section('content')
<style>
    .inhouse-header{display:flex;flex-wrap:wrap;align-items:end;justify-content:space-between;gap:14px;margin-bottom:14px}
    .inhouse-search{display:flex;align-items:end;gap:8px;min-width:min(100%,360px)}
    .inhouse-search label{display:grid;flex:1;gap:5px;font-size:.78rem;font-weight:750}
    .inhouse-search input{width:100%;min-height:40px;padding:8px 11px;border:1px solid #cbd7da;border-radius:9px;background:#fff;color:inherit;font:inherit}
    .room-badge{display:inline-flex;padding:5px 8px;border:1px solid #b8ded4;border-radius:8px;background:#eaf7f3;color:#0f6257;font-size:.78rem;font-weight:850}
    .room-meta,.guest-phone{display:block;margin-top:4px;color:#64748b;font-size:.75rem}
    .charge-total{color:#087568;font-weight:850;white-space:nowrap}
    .action-stack{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px}
    .button.small{min-height:34px;padding:7px 10px;font-size:.74rem}
    .button.tertiary{border:1px solid #cbd7da;background:#f8fafc;color:#334b50}
    .inhouse-dialog{width:min(1040px,calc(100% - 26px));max-height:calc(100% - 26px);padding:0;border:1px solid #dbe5e7;border-radius:18px;background:#fff;color:#17313a;box-shadow:0 24px 70px rgba(11,31,56,.28)}
    .inhouse-dialog::backdrop{background:rgba(15,23,42,.56);backdrop-filter:blur(2px)}
    .inhouse-dialog__content{padding:19px}
    .inhouse-dialog__heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding-bottom:13px;border-bottom:1px solid #edf2f7}
    .inhouse-dialog__heading h2{margin:0;font-size:1rem}
    .inhouse-dialog__heading p{margin:4px 0 0;color:#64748b;font-size:.74rem}
    .inhouse-dialog__close{border:0;background:transparent;color:#64748b;font-size:1.5rem;cursor:pointer}
    .charge-form{display:grid;gap:13px;margin-top:14px}
    .charge-form label{display:grid;gap:5px;font-size:.73rem;font-weight:750}
    .charge-form input,.charge-form select{width:100%;min-height:40px;padding:8px 10px;border:1px solid #cbd7da;border-radius:9px;background:#fff;color:inherit;font:inherit}
    .charge-total-preview{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px;border:1px solid #dbe5e7;border-radius:10px;background:#f8fafc;font-size:.76rem;font-weight:800}
    .charge-payment-options{display:flex;flex-wrap:wrap;gap:12px}
    .charge-payment-options label{display:flex;align-items:center;gap:7px;font-weight:650}
    .charge-payment-options input{width:auto;min-height:auto}
    .pos-context{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}
    .pos-context span{padding:5px 8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#475569;font-size:.68rem}
    .pos-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(290px,.85fr);gap:14px;margin-top:14px}
    .pos-panel{min-width:0;padding:12px;border:1px solid #e2e8f0;border-radius:13px;background:#fff}
    .pos-panel h3{margin:0 0 10px;font-size:.8rem;font-weight:850}
    .pos-categories{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
    .pos-category{padding:6px 9px;border:1px solid #dbe5e7;border-radius:99px;background:#fff;color:#475569;font-size:.68rem;font-weight:750;cursor:pointer}
    .pos-category[aria-pressed="true"]{border-color:#0f766e;background:#e9f7f4;color:#0f6257}
    .pos-products{display:grid;gap:8px;max-height:390px;overflow:auto;padding:1px}
    .pos-service-group{overflow:hidden;border:1px solid #dfe8ea;border-radius:11px;background:#fff}
    .pos-service-group[hidden],.pos-service-items[hidden]{display:none!important}
    .pos-service-toggle{display:flex;width:100%;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border:0;background:#fff;color:#203c43;text-align:left;cursor:pointer}
    .pos-service-toggle:hover{background:#f7fbfa}
    .pos-service-toggle__main{display:flex;min-width:0;align-items:center;gap:9px}
    .pos-service-icon{display:grid;width:32px;height:32px;flex:0 0 32px;place-items:center;border-radius:9px;background:#eff8f6;font-size:1rem}
    .pos-service-title{display:block;font-size:.73rem;font-weight:850}
    .pos-service-count{display:block;margin-top:2px;color:#73858a;font-size:.59rem}
    .pos-service-chevron{color:#71838a;font-size:.85rem;transition:transform .18s ease}
    .pos-service-toggle[aria-expanded="true"] .pos-service-chevron{transform:rotate(180deg)}
    .pos-service-items{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:8px;padding:0 9px 10px}
    .pos-service-items:before{grid-column:1/-1;content:"";height:1px;background:#edf2f4}
    .pos-product-option{display:grid;align-content:start;gap:5px;min-width:0}
    .pos-product-option[hidden]{display:none!important}
    .pos-product-multi{display:flex!important;align-items:center;gap:5px;color:#64748b;font-size:.61rem!important;font-weight:750}
    .pos-product-multi input{width:auto;min-height:auto;margin:0;accent-color:#0f766e}
    .pos-product-option .pos-product{width:100%}
    .pos-product-option:has([data-pos-multi-item]:checked) .pos-product{border-color:#0f766e;background:#eff9f6;box-shadow:0 0 0 2px rgba(15,118,110,.12)}
    .pos-product{display:grid;align-content:space-between;gap:8px;min-height:95px;padding:10px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;text-align:left;color:inherit;cursor:pointer}
    .pos-product[hidden]{display:none!important}
    .pos-product:hover{border-color:#63b7a6;background:#f4fbf8}
    .pos-product:focus-visible{outline:3px solid rgba(15,118,110,.35);outline-offset:2px}
    .pos-product[aria-pressed="true"]{border-color:#0f766e;background:#e9f7f4;box-shadow:0 0 0 2px rgba(15,118,110,.1)}
    .pos-product-multi:has(input:checked){color:#0f766e}
    .pos-multi-add{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 9px;padding:8px 10px;border:1px solid #dbe5e7;border-radius:9px;background:#f8fafc;color:#64748b;font-size:.65rem}
    .pos-multi-add .button{min-height:32px;padding:6px 10px;font-size:.64rem}
    .pos-product strong{font-size:.72rem}
    .pos-product small,.pos-empty{color:#64748b;font-size:.65rem}
    .pos-product b{color:#0f766e;font-size:.72rem}
    .pos-custom{display:grid;grid-template-columns:minmax(0,1fr) 100px 118px auto;align-items:end;gap:7px;margin-top:11px;padding-top:10px;border-top:1px solid #edf2f7}
    .pos-custom label{font-size:.66rem}
    .pos-custom button{min-height:37px;padding:7px 9px;font-size:.68rem}
    .pos-entry-preview{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;padding:9px 10px;border:1px solid #dbe5e7;border-radius:9px;background:#f8fafc;color:#64748b;font-size:.65rem}
    .pos-entry-preview strong{color:#17363c}
    .pos-cart{display:grid;gap:7px;max-height:280px;overflow:auto}
    .pos-cart-empty{padding:14px 6px;color:#64748b;text-align:center;font-size:.7rem}
    .pos-cart-line{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;padding:8px 0;border-bottom:1px solid #edf2f7}
    .pos-cart-line strong{display:block;font-size:.7rem}
    .pos-cart-line small{color:#64748b;font-size:.64rem}
    .pos-quantity{display:flex;align-items:center;gap:7px}
    .pos-quantity button{width:24px;height:24px;border:1px solid #dbe5e7;border-radius:7px;background:#f8fafc;color:inherit;cursor:pointer}
    .pos-quantity span{min-width:14px;text-align:center;font-size:.7rem}
    .pos-cart-footer{display:grid;gap:9px;margin-top:10px}
    .pos-total{display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid #dbe5e7;font-size:.78rem;font-weight:850}
    .pos-cart-footer select{min-height:38px;padding:7px 9px;border:1px solid #cbd7da;border-radius:9px;background:#fff;color:inherit;font:inherit;font-size:.72rem}
    .pos-dialog .inhouse-dialog__content{padding:0}
    .pos-dialog .charge-form{margin:0}
    .pos-dialog__header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 19px;background:#10252b;color:#fff}
    .pos-dialog__header .inhouse-dialog__heading{flex:1;align-items:center;padding:0;border:0}
    .pos-dialog__header .inhouse-dialog__heading h2{color:#fff;font-size:.95rem}
    .pos-dialog__header .inhouse-dialog__heading p{color:#b8c8ca;font-size:.7rem}
    .pos-dialog__header .pos-context span{border-color:#3d5559;background:#1b3439;color:#dbe8e8}
    .pos-dialog__header .inhouse-dialog__close{color:#bdcacc}
    .pos-dialog__body{padding:16px;background:#f5f8f9}
    .pos-layout{grid-template-columns:minmax(0,1.25fr) minmax(310px,.85fr);margin-top:0}
    .pos-panel{padding:14px;border-color:#e3ebed;box-shadow:0 3px 10px rgba(21,51,59,.04)}
    .pos-panel__eyebrow{margin:0 0 8px;color:#71838a;font-size:.62rem;font-weight:850;letter-spacing:.09em;text-transform:uppercase}
    .pos-categories{flex-wrap:nowrap;overflow-x:auto;padding-bottom:5px}
    .pos-category{flex:0 0 auto;padding:7px 10px}
    .pos-category[aria-pressed="true"]{border-color:#0f766e;background:#0f6257;color:#fff}
    .pos-products{grid-template-columns:repeat(auto-fill,minmax(155px,1fr));max-height:260px;padding:1px}
    .pos-product{min-height:88px;padding:10px 11px}
    .pos-product__top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
    .pos-product__tag{padding:3px 6px;border-radius:6px;background:#f0f5f6;color:#61747b;font-size:.58rem}
    .pos-product__add{color:#087568;font-size:.65rem;font-weight:850}
    .pos-custom{grid-template-columns:minmax(0,1.1fr) minmax(94px,.7fr);gap:9px;margin-top:12px;padding-top:12px}
    .pos-custom__wide{grid-column:1/-1}
    .pos-custom label{gap:4px;font-size:.65rem}
    .pos-dialog input.pos-control,.pos-dialog select.pos-control,
    .pos-custom input,.pos-custom select{min-height:37px;padding:7px 9px;border:1px solid #d4dfe1;border-radius:9px;background:#fbfcfc;color:#18323a;font-size:.69rem}
    .pos-dialog input:focus,.pos-dialog select:focus{outline:0;border-color:#49a999;box-shadow:0 0 0 3px rgba(15,118,110,.12)}
    .pos-stepper{display:flex;align-items:center;gap:5px}
    .pos-stepper button{width:28px;height:28px;border:1px solid #d4dfe1;border-radius:8px;background:#fff;color:#26474d;font-weight:850;cursor:pointer}
    .pos-stepper input{flex:1;min-width:45px;text-align:center}
    .pos-cart-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;padding-bottom:10px;border-bottom:1px solid #e7edef}
    .pos-cart-count{padding:4px 8px;border:1px solid #cce4df;border-radius:999px;background:#eff9f6;color:#0f6257;font-size:.61rem;font-weight:850}
    .pos-cart{max-height:248px}
    .pos-cart-line{grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:9px 0}
    .pos-cart-line__name{display:block;max-width:190px;overflow:hidden;color:#223b42;font-size:.68rem;font-weight:850;text-overflow:ellipsis;white-space:nowrap}
    .pos-cart-line__note{display:block;margin-top:4px;color:#647b81;font-size:.6rem;font-style:italic}
    .pos-cart-line__amount{margin-top:3px;color:#51676d;font-size:.6rem}
    .pos-cart-line__actions{display:flex;align-items:center;gap:6px}
    .pos-cart-line__remove{border:0;background:transparent;color:#87969a;font-size:.7rem;cursor:pointer}
    .pos-cart-footer{gap:8px;margin-top:11px}
    .pos-summary{display:grid;gap:6px;padding:10px;border:1px solid #e3ebed;border-radius:10px;background:#f7fafb}
    .pos-summary__row{display:flex;justify-content:space-between;color:#64777d;font-size:.65rem}
    .pos-summary__total{display:flex;justify-content:space-between;margin-top:2px;padding-top:7px;border-top:1px solid #dce6e8;color:#17363c;font-size:.78rem;font-weight:900}
    .pos-payment-options{display:grid;grid-template-columns:1fr 1fr;gap:7px}
    .pos-payment-choice{display:flex;align-items:center;gap:7px;min-height:39px;padding:8px;border:1px solid #d8e2e4;border-radius:9px;background:#fff;color:#53676c;font-size:.64rem;font-weight:800;cursor:pointer}
    .charge-form .pos-payment-choice{display:flex}
    .charge-form .pos-payment-choice input{width:auto;min-height:auto}
    .pos-payment-choice:has(input:checked){border-color:#15927f;background:#eff9f6;color:#0f6257}
    .pos-payment-choice input{accent-color:#0f766e}
    .pos-dialog__actions{display:flex;gap:7px}
    .pos-dialog__actions button{flex:1}
    html[data-theme="dark"] .pos-dialog__body{background:var(--theme-surface-raised)}
    html[data-theme="dark"] .pos-panel,
    html[data-theme="dark"] .pos-summary{border-color:var(--theme-border);background:var(--theme-surface)}
    html[data-theme="dark"] .pos-product__tag,
    html[data-theme="dark"] .pos-cart-count{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-muted)}
    html[data-theme="dark"] .pos-service-group,
    html[data-theme="dark"] .pos-service-toggle{border-color:var(--theme-border);background:var(--theme-surface);color:var(--theme-text)}
    html[data-theme="dark"] .pos-service-toggle:hover{background:var(--theme-surface-raised)}
    html[data-theme="dark"] .pos-service-icon{background:var(--theme-surface-raised)}
    html[data-theme="dark"] .pos-service-count,
    html[data-theme="dark"] .pos-service-chevron{color:var(--theme-muted)}
    html[data-theme="dark"] .pos-service-items:before{background:var(--theme-border)}
    html[data-theme="dark"] .pos-dialog input.pos-control,
    html[data-theme="dark"] .pos-dialog select.pos-control,
    html[data-theme="dark"] .pos-custom input,
    html[data-theme="dark"] .pos-custom select,
    html[data-theme="dark"] .pos-payment-choice{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-text)}
    html[data-theme="dark"] .pos-cart-line__name,
    html[data-theme="dark"] .pos-summary__total,
    html[data-theme="dark"] .pos-entry-preview strong{color:var(--theme-text)}
    html[data-theme="dark"] .pos-entry-preview{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-muted)}
    html[data-theme="dark"] .pos-product__add{color:#6ee7cf}
    html[data-theme="dark"] .pos-product[aria-pressed="true"]{border-color:#5eead4;background:var(--theme-surface-raised)}
    html[data-theme="dark"] .pos-product-multi:has(input:checked){color:#6ee7cf}
    html[data-theme="dark"] .pos-product-option:has([data-pos-multi-item]:checked) .pos-product{border-color:#5eead4;background:var(--theme-surface-raised)}
    html[data-theme="dark"] .pos-multi-add{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-muted)}
    .folio-items{max-height:55vh;overflow:auto}
    .folio-items table{width:100%;min-width:760px;border-collapse:collapse;font-size:.78rem}
    .folio-items th,.folio-items td{padding:8px 7px;border-bottom:1px solid #edf2f7;text-align:left;vertical-align:top}
    .folio-items th:nth-last-child(-n+4),.folio-items td:nth-last-child(-n+4){text-align:right;white-space:nowrap}
    .folio-items__description{min-width:220px;max-width:360px;overflow-wrap:anywhere}
    .inhouse-pagination{display:flex;justify-content:center;padding-top:14px}
    .inhouse-pagination nav>div:first-child{display:none}
    html[data-theme="dark"] .inhouse-dialog{border-color:var(--theme-border);background:var(--theme-surface);color:var(--theme-text)}
    html[data-theme="dark"] .inhouse-dialog__heading{border-color:var(--theme-border)}
    html[data-theme="dark"] .inhouse-dialog__heading p,
    html[data-theme="dark"] .room-meta,
    html[data-theme="dark"] .guest-phone{color:var(--theme-muted)}
    html[data-theme="dark"] .charge-form input,
    html[data-theme="dark"] .charge-form select,
    html[data-theme="dark"] .inhouse-search input{border-color:#45566a;background:#202c3a;color:#f1f5f9}
    html[data-theme="dark"] .pos-panel,
    html[data-theme="dark"] .pos-product,
    html[data-theme="dark"] .pos-category,
    html[data-theme="dark"] .pos-cart-footer select{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-text)}
    html[data-theme="dark"] .pos-context span,
    html[data-theme="dark"] .pos-quantity button{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-text)}
    html[data-theme="dark"] .charge-total-preview,
    html[data-theme="dark"] .button.tertiary{border-color:var(--theme-border);background:var(--theme-surface-raised);color:var(--theme-text)}
    html[data-theme="dark"] .folio-items th,
    html[data-theme="dark"] .folio-items td{border-color:var(--theme-border)}
    @media(max-width:720px){.pos-layout{grid-template-columns:1fr}.pos-custom{grid-template-columns:1fr 1fr}.pos-custom button{grid-column:1/-1}.pos-products{max-height:220px}}
    @media(max-width:640px){.inhouse-search{width:100%}.inhouse-dialog__content{padding:14px}.pos-dialog .inhouse-dialog__content{padding:0}.pos-dialog__header{padding:12px}.pos-dialog__body{padding:10px}.pos-panel{padding:11px}.action-stack{justify-content:flex-start}}
</style>

<section class="panel">
    <header class="inhouse-header">
        <div>
            <h2 class="h5 fw-bold mb-1">In-House Guests Directory</h2>
            <p class="muted small mb-0">Manage checked-in guests and post room service or incidental charges to their folios.</p>
        </div>
        <form class="inhouse-search" method="GET" action="{{ route('frontdesk.in-house') }}">
            <label for="inhouse-search">Search guest, contact, or room
                <input id="inhouse-search" name="search" value="{{ $search }}" placeholder="Guest name, phone, or room #">
            </label>
            <button class="button" type="submit">Search</button>
        </form>
    </header>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Room</th><th>Guest details</th><th>Check-in / checkout</th><th>Current total bill</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($bookings as $booking)
                @php
                    $currentBill = round((float) ($booking->folio_charges ?? 0), 2);
                    $balanceDue = max(0, round($currentBill - (float) ($booking->folio_receipts ?? 0), 2));
                    $addDialogId = 'add-charge-'.$booking->id;
                    $folioDialogId = 'view-folio-'.$booking->id;
                @endphp
                <tr>
                    <td>
                        @forelse($booking->rooms as $room)
                            <span class="room-badge">{{ $room->room_number }}</span>
                            <span class="room-meta">{{ $room->category?->title ?? $room->category?->type_name ?? $room->room_type ?? 'Room' }}</span>
                        @empty
                            <span class="muted">No assigned room</span>
                        @endforelse
                    </td>
                    <td><strong>{{ $booking->guest?->full_name ?? 'Guest' }}</strong><span class="guest-phone">{{ $booking->guest?->phone ?? 'No contact' }}</span></td>
                    <td><span>In: {{ $booking->actual_check_in?->format('d M Y') ?? $booking->check_in_date->format('d M Y') }}</span><span class="room-meta">Out: {{ $booking->check_out_date->format('d M Y') }}</span></td>
                    <td><span class="charge-total">{{ $currencyCode }} {{ number_format($currentBill, 2) }}</span><span class="room-meta">{{ $booking->folioEntries->where('type', 'charge')->count() }} charge(s)</span></td>
                    <td>
                        <div class="action-stack">
                            <button class="button small" type="button" data-open-inhouse-dialog="{{ $addDialogId }}">+ Add Charge</button>
                            <button class="button small tertiary" type="button" data-open-inhouse-dialog="{{ $folioDialogId }}">View Folio</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">{{ $search !== '' ? 'No checked-in guests match your search.' : 'There are no checked-in guests right now.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @foreach($bookings as $booking)
        @php
            $currentBill = round((float) ($booking->folio_charges ?? 0), 2);
            $balanceDue = max(0, round($currentBill - (float) ($booking->folio_receipts ?? 0), 2));
            $addDialogId = 'add-charge-'.$booking->id;
            $folioDialogId = 'view-folio-'.$booking->id;
        @endphp
        <dialog class="inhouse-dialog pos-dialog" id="{{ $addDialogId }}" aria-labelledby="{{ $addDialogId }}-title">
            <div class="inhouse-dialog__content">
                <header class="pos-dialog__header">
                    <div class="inhouse-dialog__heading">
                        <div>
                            <h2 id="{{ $addDialogId }}-title">Room service &amp; incidentals</h2>
                            <p>{{ $booking->guest?->full_name ?? 'Guest' }} · {{ $booking->booking_code }}</p>
                            <div class="pos-context"><span>Room {{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</span><span>Checked in {{ $booking->actual_check_in?->format('d M Y') ?? $booking->check_in_date->format('d M Y') }}</span><span>{{ $currencyCode }}</span></div>
                        </div>
                    </div>
                    <button class="inhouse-dialog__close" type="button" data-close-inhouse-dialog aria-label="Close dialog">&times;</button>
                </header>
                <form class="charge-form" method="POST" action="{{ route('frontdesk.in-house.charges.store', $booking) }}" data-pos-form data-currency="{{ $currencyCode }}">
                    @csrf
                    <input type="hidden" name="cart_items" data-cart-payload>
                    <div class="pos-dialog__body">
                        <div class="pos-layout">
                            <section class="pos-panel">
                                <p class="pos-panel__eyebrow">Guest services</p>
                                <div class="pos-categories" data-pos-categories>
                                    <button class="pos-category" type="button" data-filter="all" aria-pressed="true">All services</button>
                                    <button class="pos-category" type="button" data-filter="food" aria-pressed="false">Food &amp; dining</button>
                                    <button class="pos-category" type="button" data-filter="laundry" aria-pressed="false">Laundry &amp; clothing</button>
                                    <button class="pos-category" type="button" data-filter="minibar" aria-pressed="false">Minibar &amp; drinks</button>
                                    <button class="pos-category" type="button" data-filter="housekeeping" aria-pressed="false">Housekeeping</button>
                                    <button class="pos-category" type="button" data-filter="misc" aria-pressed="false">Miscellaneous</button>
                                </div>
                                <div class="pos-products" data-pos-products>
                                    @foreach($serviceGroups as $groupKey => $serviceGroup)
                                        @php($groupItemCount = $serviceGroup['categories']->sum(fn ($category) => $category->items->count()))
                                        <section class="pos-service-group" data-service-group="{{ $groupKey }}">
                                            <button class="pos-service-toggle" type="button" data-service-toggle aria-expanded="false">
                                                <span class="pos-service-toggle__main">
                                                    <span class="pos-service-icon" aria-hidden="true">{{ $serviceGroup['icon'] }}</span>
                                                    <span><span class="pos-service-title">{{ $serviceGroup['label'] }}</span><span class="pos-service-count">{{ $groupItemCount }} service{{ $groupItemCount === 1 ? '' : 's' }}</span></span>
                                                </span>
                                                <span class="pos-service-chevron" aria-hidden="true">⌄</span>
                                            </button>
                                            <div class="pos-service-items" data-service-items hidden>
                                                @foreach($serviceGroup['categories'] as $menuCategory)
                                                    @foreach($menuCategory->items as $menuItem)
                                                        <div class="pos-product-option" data-pos-option>
                                                            <label class="pos-product-multi"><input type="checkbox" data-pos-multi-item aria-label="Select {{ $menuItem->name }} for multiple add"> Add to selection</label>
                                                            <button class="pos-product" type="button" data-pos-item data-menu-item data-id="{{ $menuItem->id }}" data-name="{{ $menuItem->name }}" data-category="{{ $menuCategory->name }}" data-price="{{ $menuItem->price }}" data-category-group="{{ $groupKey }}" aria-label="Select {{ $menuItem->name }}">
                                                                <span class="pos-product__top"><strong>{{ $menuItem->name }}</strong><span class="pos-product__tag">{{ $menuCategory->name }}</span></span>
                                                                @if($menuItem->description)<small>{{ \Illuminate\Support\Str::limit($menuItem->description, 55) }}</small>@endif
                                                                <b>{{ $currencyCode }} {{ number_format((float) $menuItem->price, 2) }}</b>
                                                                <span class="pos-product__add">Select item</span>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                    @if($menuCategories->isEmpty())<p class="pos-empty">No hotel menu items are available. Use the extra service catalog or add a custom charge below.</p>@endif
                                </div>
                                <div class="pos-multi-add">
                                    <span data-pos-multi-count aria-live="polite">No items selected for bulk add. Each selected item starts at quantity 1.</span>
                                    <button class="button" type="button" data-add-selected-items disabled>Add selected items</button>
                                </div>
                                <p class="pos-empty" data-pos-filter-empty role="status" hidden>No items are available in this category.</p>
                                <div class="pos-custom">
                                    <label class="pos-custom__wide">Item name / description
                                        <input class="pos-control" type="text" maxlength="255" placeholder="e.g. Ironing 2 shirts, extra towel…" data-custom-name>
                                    </label>
                                    <label>Category
                                        <select class="pos-control" data-custom-category>
                                            <option value="food_beverage">Food &amp; beverage</option>
                                            <option value="laundry">Laundry &amp; pressing</option>
                                            <option value="minibar">Minibar</option>
                                            <option value="late_checkout_extra_bed">Housekeeping / extra bed</option>
                                            <option value="damage_miscellaneous">Service fee / miscellaneous</option>
                                        </select>
                                    </label>
                                    <label>Unit price ({{ $currencyCode }})
                                        <input class="pos-control" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" data-custom-price>
                                    </label>
                                    <label>Quantity
                                        <span class="pos-stepper"><button type="button" data-custom-qty-step="-1" aria-label="Decrease custom item quantity">−</button><input class="pos-control" type="number" min="1" max="1000" value="1" data-custom-quantity><button type="button" data-custom-qty-step="1" aria-label="Increase custom item quantity">+</button></span>
                                    </label>
                                    <div class="pos-entry-preview pos-custom__wide" aria-live="polite">
                                        <span>Line total: <strong>{{ $currencyCode }} <span data-entry-subtotal>0.00</span></strong></span>
                                        <span>Tax (10%): <strong>{{ $currencyCode }} <span data-entry-tax>0.00</span></strong></span>
                                        <span>Line total with tax: <strong>{{ $currencyCode }} <span data-entry-total>0.00</span></strong></span>
                                    </div>
                                    <label class="pos-custom__wide">Notes / special instructions (optional)
                                        <input class="pos-control" type="text" maxlength="500" placeholder="e.g. Urgent delivery to the room" data-custom-note>
                                    </label>
                                    <button class="button small pos-custom__wide" type="button" data-add-custom>+ Add custom charge</button>
                                </div>
                            </section>
                            <section class="pos-panel">
                                <p class="pos-panel__eyebrow">Folio posting</p>
                                <div class="pos-cart-head"><h3>Current order ledger</h3><span class="pos-cart-count"><span data-cart-count>0</span> item(s)</span></div>
                                <div class="pos-cart" data-pos-cart><div class="pos-cart-empty">Your order is empty. Choose menu items or add a custom charge.</div></div>
                                <div class="pos-cart-footer">
                                    <div class="pos-summary">
                                        <div class="pos-summary__row"><span>Subtotal</span><strong>{{ $currencyCode }} <span data-pos-subtotal>0.00</span></strong></div>
                                        <div class="pos-summary__row"><span>Tax / service charge (10%)</span><strong>{{ $currencyCode }} <span data-pos-tax>0.00</span></strong></div>
                                        <div class="pos-summary__total"><span>Grand total</span><strong>{{ $currencyCode }} <span data-pos-total>0.00</span></strong></div>
                                    </div>
                                    <div>
                                        <p class="pos-panel__eyebrow">Payment posting mode</p>
                                        <div class="pos-payment-options">
                                            <label class="pos-payment-choice"><input type="radio" name="payment_option" value="post_to_room" checked> Post to room folio</label>
                                            <label class="pos-payment-choice"><input type="radio" name="payment_option" value="paid_immediately"> Paid immediately</label>
                                        </div>
                                    </div>
                                    <label data-payment-method-wrap hidden>Payment method
                                        <select class="pos-control" name="payment_method">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">POS / Bank transfer</option>
                                        </select>
                                    </label>
                                    <label>Charge room
                                        <select class="pos-control" name="room_id" required>
                                            @foreach($booking->rooms as $room)
                                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->room_number }} — {{ $room->category?->title ?? $room->category?->type_name ?? $room->room_type ?? 'Room' }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <div class="actions pos-dialog__actions">
                                        <button class="button secondary" type="button" data-close-inhouse-dialog>Cancel</button>
                                        <button class="button" type="submit" data-save-order disabled>Confirm &amp; post charges</button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </form>
            </div>
        </dialog>
        <dialog class="inhouse-dialog" id="{{ $folioDialogId }}" aria-labelledby="{{ $folioDialogId }}-title">
            <div class="inhouse-dialog__content">
                <header class="inhouse-dialog__heading">
                    <div><h2 id="{{ $folioDialogId }}-title">Guest folio</h2><p>{{ $booking->guest?->full_name ?? 'Guest' }} · {{ $booking->booking_code }}</p></div>
                    <button class="inhouse-dialog__close" type="button" data-close-inhouse-dialog aria-label="Close dialog">&times;</button>
                </header>
                <div class="d-flex flex-wrap justify-content-between gap-3 mt-3 small">
                    <span>Total bill: <strong>{{ $currencyCode }} {{ number_format($currentBill, 2) }}</strong></span>
                    <span>Paid to date: <strong>{{ $currencyCode }} {{ number_format((float) ($booking->folio_receipts ?? 0), 2) }}</strong></span>
                    <span>Balance due: <strong>{{ $currencyCode }} {{ number_format($balanceDue, 2) }}</strong></span>
                </div>
                <div class="folio-items mt-3">
                    <table>
                        <thead><tr><th>Date</th><th>Type</th><th>Item / service details</th><th>Qty</th><th>Unit price</th><th>Subtotal / payment</th><th>Tax</th><th>Total</th></tr></thead>
                        <tbody>
                        @forelse($booking->folioEntries as $entry)
                            @php(preg_match('/\(Qty:\s*(\d+)\)/', (string) $entry->item_category, $quantityMatch))
                            @php($entryQuantity = $entry->type === 'charge' ? max(1, (int) ($quantityMatch[1] ?? 1)) : null)
                            @php($entryAmount = (float) $entry->amount)
                            <tr>
                                <td>{{ $entry->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ ucfirst($entry->type) }}</td>
                                <td class="folio-items__description">{{ $entry->item_category }}</td>
                                <td>{{ $entryQuantity ?? '—' }}</td>
                                <td>{{ $entryQuantity ? $currencyCode.' '.number_format($entryAmount / $entryQuantity, 2) : '—' }}</td>
                                <td>{{ $currencyCode }} {{ number_format($entryAmount, 2) }}</td>
                                <td>{{ $currencyCode }} {{ number_format((float) $entry->tax, 2) }}</td>
                                <td><strong>{{ $currencyCode }} {{ number_format((float) $entry->total, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty">No folio entries recorded.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </dialog>
    @endforeach
    @if($bookings->hasPages())
        <div class="inhouse-pagination">{{ $bookings->links() }}</div>
    @endif
</section>
<script>
    document.querySelectorAll('[data-open-inhouse-dialog]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById(button.dataset.openInhouseDialog)?.showModal();
        });
    });
    document.querySelectorAll('[data-close-inhouse-dialog]').forEach(function (button) {
        button.addEventListener('click', function () {
            button.closest('dialog')?.close();
        });
    });
    document.querySelectorAll('[data-pos-form]').forEach(function (form) {
        const cart = [];
        const currency = form.dataset.currency;
        const cartElement = form.querySelector('[data-pos-cart]');
        const totalElement = form.querySelector('[data-pos-total]');
        const subtotalElement = form.querySelector('[data-pos-subtotal]');
        const taxElement = form.querySelector('[data-pos-tax]');
        const countElement = form.querySelector('[data-cart-count]');
        const entrySubtotalElement = form.querySelector('[data-entry-subtotal]');
        const entryTaxElement = form.querySelector('[data-entry-tax]');
        const entryTotalElement = form.querySelector('[data-entry-total]');
        const payload = form.querySelector('[data-cart-payload]');
        const submitButton = form.querySelector('[data-save-order]');
        const paymentMethod = form.querySelector('[data-payment-method-wrap]');
        const productList = form.querySelector('[data-pos-products]');
        const multiCount = form.querySelector('[data-pos-multi-count]');
        const addSelectedButton = form.querySelector('[data-add-selected-items]');
        const filterEmptyMessage = form.querySelector('[data-pos-filter-empty]');
        const nameInput = form.querySelector('[data-custom-name]');
        const priceInput = form.querySelector('[data-custom-price]');
        const quantityInput = form.querySelector('[data-custom-quantity]');
        const categoryInput = form.querySelector('[data-custom-category]');
        const noteInput = form.querySelector('[data-custom-note]');
        const selectedMultiItems = new Map();
        const serviceGroups = Array.from(form.querySelectorAll('[data-service-group]'));
        let selectedProduct = null;
        let isSubmitting = false;
        const taxRate = 0.10;
        const message = document.createElement('p');
        message.className = 'pos-empty';
        message.setAttribute('role', 'alert');
        message.hidden = true;
        form.querySelector('[data-pos-products]').before(message);

        const money = function (amount) {
            return currency + ' ' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        const updateEntryPreview = function () {
            const unitPrice = Number(priceInput.value);
            const quantity = Number(quantityInput.value);
            const lineTotal = Number.isFinite(unitPrice) && unitPrice > 0 && Number.isInteger(quantity) && quantity > 0
                ? Math.round((unitPrice * quantity + Number.EPSILON) * 100) / 100
                : 0;
            const lineTax = Math.round((lineTotal * taxRate + Number.EPSILON) * 100) / 100;
            entrySubtotalElement.textContent = lineTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            entryTaxElement.textContent = lineTax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            entryTotalElement.textContent = (lineTotal + lineTax).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        const showMessage = function (text) {
            message.textContent = text;
            message.hidden = !text;
        };
        const renderMultiSelection = function () {
            const count = selectedMultiItems.size;
            multiCount.textContent = count === 0
                ? 'No items selected for bulk add. Each selected item starts at quantity 1.'
                : count + ' item' + (count === 1 ? '' : 's') + ' selected. Each starts at quantity 1.';
            addSelectedButton.disabled = count === 0;
        };
        const setExpandedServiceGroup = function (groupToExpand) {
            serviceGroups.forEach(function (group) {
                const toggle = group.querySelector('[data-service-toggle]');
                const items = group.querySelector('[data-service-items]');
                const expanded = group === groupToExpand;
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                items.hidden = !expanded;
            });
        };
        const applyCategoryFilter = function (filter) {
            let visibleItems = 0;
            serviceGroups.forEach(function (group) {
                const matchesFilter = filter === 'all' || group.dataset.serviceGroup === filter;
                group.hidden = !matchesFilter;
                const itemCount = group.querySelectorAll('[data-pos-item]').length;
                if (matchesFilter) visibleItems += itemCount;
            });
            setExpandedServiceGroup(filter === 'all'
                ? null
                : serviceGroups.find(function (group) { return group.dataset.serviceGroup === filter; }) || null);
            filterEmptyMessage.hidden = visibleItems > 0;
        };
        const renderCart = function () {
            cartElement.replaceChildren();
            if (cart.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'pos-cart-empty';
                empty.textContent = 'Your order is empty. Choose menu items or add a custom charge.';
                cartElement.append(empty);
            }
            let subtotal = 0;
            let tax = 0;
            cart.forEach(function (item, index) {
                const lineTotal = item.unit_price * item.quantity;
                const lineTax = Math.round((lineTotal * taxRate + Number.EPSILON) * 100) / 100;
                subtotal += lineTotal;
                tax += lineTax;
                const line = document.createElement('div');
                line.className = 'pos-cart-line';
                const info = document.createElement('div');
                const name = document.createElement('strong');
                name.className = 'pos-cart-line__name';
                name.textContent = item.name;
                const detail = document.createElement('small');
                detail.className = 'pos-cart-line__amount';
                detail.textContent = money(item.unit_price) + ' × ' + item.quantity + ' = ' + money(lineTotal) + ' + ' + money(lineTax) + ' tax';
                info.append(name, detail);
                if (item.note) {
                    const note = document.createElement('small');
                    note.className = 'pos-cart-line__note';
                    note.textContent = 'Note: ' + item.note;
                    info.append(note);
                }
                const quantity = document.createElement('div');
                quantity.className = 'pos-quantity';
                const decrease = document.createElement('button');
                decrease.type = 'button';
                decrease.textContent = '−';
                decrease.setAttribute('aria-label', 'Decrease quantity');
                decrease.addEventListener('click', function () {
                    item.quantity -= 1;
                    if (item.quantity <= 0) cart.splice(index, 1);
                    renderCart();
                });
                const count = document.createElement('span');
                count.textContent = item.quantity;
                const increase = document.createElement('button');
                increase.type = 'button';
                increase.textContent = '+';
                increase.setAttribute('aria-label', 'Increase quantity');
                increase.addEventListener('click', function () {
                    if (item.quantity < 1000) item.quantity += 1;
                    renderCart();
                });
                const controls = document.createElement('div');
                controls.className = 'pos-cart-line__actions';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'pos-cart-line__remove';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'Remove item');
                remove.addEventListener('click', function () {
                    cart.splice(index, 1);
                    renderCart();
                });
                quantity.append(decrease, count, increase);
                controls.append(quantity, remove);
                line.append(info, controls);
                cartElement.append(line);
            });
            const formattedSubtotal = Number(subtotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const formattedTax = Number(tax).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const formattedTotal = Number(subtotal + tax).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            subtotalElement.textContent = formattedSubtotal;
            taxElement.textContent = formattedTax;
            totalElement.textContent = formattedTotal;
            countElement.textContent = cart.reduce(function (count, item) { return count + item.quantity; }, 0);
            submitButton.disabled = cart.length === 0;
            payload.value = JSON.stringify(cart);
        };
        const addItem = function (item) {
            const identity = item.type === 'menu'
                ? 'menu:' + item.id
                : ['custom', item.name, item.unit_price, item.category, item.note || ''].join(':');
            const addedQuantity = Number(item.quantity) || 1;
            const existing = cart.find(function (line) { return line.identity === identity; });
            if (existing) existing.quantity = Math.min(1000, existing.quantity + addedQuantity);
            else cart.push(Object.assign({ quantity: 1, identity: identity }, item));
            showMessage('');
            renderCart();
        };

        form.querySelectorAll('[data-pos-multi-item]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const option = checkbox.closest('[data-pos-option]');
                const product = option?.querySelector('[data-pos-item]');
                if (!product) return;
                if (checkbox.checked) {
                    selectedMultiItems.set(String(product.dataset.id), {
                        type: 'menu',
                        id: Number(product.dataset.id),
                        name: product.dataset.name,
                        unit_price: Number(product.dataset.price),
                        category: product.dataset.category,
                        quantity: 1,
                        note: ''
                    });
                } else {
                    selectedMultiItems.delete(String(product.dataset.id));
                }
                renderMultiSelection();
            });
        });
        form.querySelectorAll('[data-service-toggle]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const group = toggle.closest('[data-service-group]');
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                setExpandedServiceGroup(isExpanded ? null : group);
            });
        });
        addSelectedButton.addEventListener('click', function () {
            if (selectedMultiItems.size === 0) return;
            const selectedItems = Array.from(selectedMultiItems.values());
            const newLineCount = selectedItems.filter(function (item) {
                return !cart.some(function (line) { return line.type === 'menu' && Number(line.id) === item.id; });
            }).length;
            const hasFullQuantityLine = selectedItems.some(function (item) {
                const existing = cart.find(function (line) { return line.type === 'menu' && Number(line.id) === item.id; });
                return existing && existing.quantity >= 1000;
            });
            if (cart.length + newLineCount > 50 || hasFullQuantityLine) {
                showMessage('The selected services would exceed the order item or quantity limit. Remove an item or reduce its quantity, then try again.');
                return;
            }
            selectedItems.forEach(addItem);
            form.querySelectorAll('[data-pos-multi-item]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
            selectedMultiItems.clear();
            renderMultiSelection();
            showMessage('');
        });

        productList.addEventListener('click', function (event) {
            const button = event.target.closest('[data-pos-item]');
            if (!button || !productList.contains(button) || button.hidden) return;
            if (selectedProduct) selectedProduct.setAttribute('aria-pressed', 'false');
            selectedProduct = button;
            selectedProduct.setAttribute('aria-pressed', 'true');
            nameInput.value = button.dataset.name;
            priceInput.value = button.dataset.price;
            priceInput.readOnly = true;
            quantityInput.value = '1';
            noteInput.value = '';
            const selectedCategory = {
                laundry: 'laundry',
                minibar: 'minibar',
                housekeeping: 'late_checkout_extra_bed',
                misc: 'damage_miscellaneous',
                food: 'food_beverage'
            }[button.dataset.categoryGroup] || 'food_beverage';
            categoryInput.value = selectedCategory;
            updateEntryPreview();
            showMessage('Selected ' + button.dataset.name + '. Set quantity and click Add custom charge to add it to the order.');
        });
        nameInput.addEventListener('input', function () {
            if (selectedProduct && nameInput.value.trim() !== selectedProduct.dataset.name) {
                selectedProduct.setAttribute('aria-pressed', 'false');
                selectedProduct = null;
                priceInput.readOnly = false;
            }
        });
        priceInput.addEventListener('input', function () {
            if (selectedProduct && Number(priceInput.value) !== Number(selectedProduct.dataset.price)) {
                selectedProduct.setAttribute('aria-pressed', 'false');
                selectedProduct = null;
                priceInput.readOnly = false;
            }
            updateEntryPreview();
        });
        categoryInput.addEventListener('change', function () {
            if (selectedProduct) {
                selectedProduct.setAttribute('aria-pressed', 'false');
                selectedProduct = null;
                priceInput.readOnly = false;
            }
        });
        quantityInput.addEventListener('input', updateEntryPreview);
        quantityInput.addEventListener('change', updateEntryPreview);
        form.querySelectorAll('[data-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                form.querySelectorAll('[data-filter]').forEach(function (filter) {
                    filter.setAttribute('aria-pressed', filter === button ? 'true' : 'false');
                });
                applyCategoryFilter(button.dataset.filter);
            });
        });
        form.querySelectorAll('[data-custom-qty-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                const quantity = form.querySelector('[data-custom-quantity]');
                quantity.value = Math.max(1, Math.min(1000, (Number(quantity.value) || 1) + Number(button.dataset.customQtyStep)));
                updateEntryPreview();
            });
        });
        form.addEventListener('click', function (event) {
            const button = event.target.closest('[data-add-custom]');
            if (!button || !form.contains(button)) return;
            event.preventDefault();
            const name = nameInput.value.trim();
            const price = Number(priceInput.value);
            const quantity = Number(quantityInput.value);
            if (!name || !Number.isFinite(price) || price <= 0 || price > 99999999.99 || !Number.isInteger(quantity) || quantity < 1 || quantity > 1000) {
                showMessage('Enter an item description, valid unit price, and quantity between 1 and 1,000.');
                (!name ? nameInput : (!Number.isFinite(price) || price <= 0 || price > 99999999.99 ? priceInput : quantityInput)).focus();
                return;
            }
            const isCatalogItem = selectedProduct
                && name === selectedProduct.dataset.name
                && Number(priceInput.value) === Number(selectedProduct.dataset.price);
            addItem({
                type: isCatalogItem ? 'menu' : 'custom',
                id: isCatalogItem ? Number(selectedProduct.dataset.id) : undefined,
                name: name,
                unit_price: price,
                category: categoryInput.value,
                quantity: quantity,
                note: noteInput.value.trim()
            });
            nameInput.value = '';
            priceInput.value = '';
            priceInput.readOnly = false;
            quantityInput.value = '1';
            noteInput.value = '';
            if (selectedProduct) selectedProduct.setAttribute('aria-pressed', 'false');
            selectedProduct = null;
            form.querySelectorAll('[data-pos-multi-item]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
            selectedMultiItems.clear();
            renderMultiSelection();
            updateEntryPreview();
            showMessage('');
        });
        const syncPayment = function () {
            const selected = form.querySelector('input[name="payment_option"]:checked');
            paymentMethod.hidden = selected?.value !== 'paid_immediately';
        };
        form.querySelectorAll('input[name="payment_option"]').forEach(function (option) {
            option.addEventListener('change', syncPayment);
        });
        syncPayment();
        form.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }
            if (cart.length === 0) {
                event.preventDefault();
                showMessage('Add at least one item before saving the order.');
                return;
            }
            payload.value = JSON.stringify(cart.map(function (item) {
                return item.type === 'menu'
                    ? { type: item.type, id: item.id, quantity: item.quantity, note: item.note || '' }
                    : { type: item.type, name: item.name, unit_price: item.unit_price, category: item.category, quantity: item.quantity, note: item.note || '' };
            }));
            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.textContent = 'Posting charges...';
        });
        renderCart();
        renderMultiSelection();
        applyCategoryFilter('all');
        form.closest('dialog').addEventListener('close', function () {
            cart.length = 0;
            form.querySelector('[data-custom-name]').value = '';
            form.querySelector('[data-custom-price]').value = '';
            form.querySelector('[data-custom-price]').readOnly = false;
            form.querySelector('[data-custom-quantity]').value = '1';
            form.querySelector('[data-custom-note]').value = '';
            if (selectedProduct) selectedProduct.setAttribute('aria-pressed', 'false');
            selectedProduct = null;
            form.querySelectorAll('[data-pos-multi-item]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
            selectedMultiItems.clear();
            renderMultiSelection();
            updateEntryPreview();
            showMessage('');
            renderCart();
        });
        updateEntryPreview();
    });
</script>
@endsection
