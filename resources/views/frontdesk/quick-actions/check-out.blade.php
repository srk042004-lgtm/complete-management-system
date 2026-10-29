@extends('frontdesk.quick-actions.layout')

@section('title', 'Check-out')
@section('description', 'Review each folio, settle the balance, and release the room.')

@section('content')
<style>
    .co-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .co-list { display: grid; gap: 18px; }
    .co-card { background: var(--st-card); border: 1px solid var(--st-line); border-radius: 18px; box-shadow: var(--st-shadow); overflow: hidden; }
    .co-card__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; border-bottom: 1px solid var(--st-line); }
    .co-guest { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .co-avatar { display: grid; place-items: center; flex: 0 0 44px; width: 44px; height: 44px; border-radius: 50%; background: var(--st-accent-soft); color: var(--st-accent-text); font-weight: 800; font-size: 15px; }
    .co-guest__name { font-size: 16px; font-weight: 700; color: var(--st-text); }
    .co-guest__meta { margin-top: 2px; font-size: 12.5px; color: var(--st-muted); }
    .co-facts { display: flex; flex-wrap: wrap; gap: 8px; }
    .co-fact { padding: 6px 12px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-hover); font-size: 12.5px; color: var(--st-muted); white-space: nowrap; }
    .co-fact strong { display: block; font-size: 13px; color: var(--st-text); }
    .co-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 12.5px; font-weight: 700; white-space: nowrap; }
    .co-status--due { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .co-status--clear { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .co-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border-bottom: 1px solid var(--st-line); }
    .co-summary > div { padding: 14px 22px; }
    .co-summary > div + div { border-left: 1px solid var(--st-line); }
    .co-summary span { display: block; font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--st-muted); }
    .co-summary strong { display: block; margin-top: 4px; font-size: 17px; font-weight: 700; color: var(--st-text); font-variant-numeric: tabular-nums; }
    .co-summary .is-due strong { color: #c2410c; }
    .co-summary .is-clear strong { color: #047857; }
    .co-folio { padding: 0 22px; }
    .co-folio__title { margin: 16px 0 8px; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--st-muted); }
    .co-table-wrap { overflow-x: auto; border: 1px solid var(--st-line); border-radius: 12px; }
    .co-table { width: 100%; min-width: 640px; border-collapse: separate; border-spacing: 0; font-size: 13px; }
    .co-table th { padding: 10px 12px; background: var(--st-hover); font-size: 11.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--st-muted); text-align: left; }
    .co-table td { padding: 11px 12px; border-top: 1px solid var(--st-line); color: var(--st-text); vertical-align: middle; }
    .co-table .num, .co-table th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .co-table td.item { min-width: 240px; }
    .co-table tfoot td { font-weight: 700; background: var(--st-hover); }
    .co-type { display: inline-block; padding: 2px 8px; border-radius: 6px; background: var(--st-accent-soft); color: var(--st-accent-text); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .co-type--payment { background: #ecfdf5; color: #047857; }
    .co-empty-lines { padding: 18px; text-align: center; color: var(--st-muted); }
    .co-pay { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 14px; padding: 18px 22px; margin-top: 16px; background: var(--st-hover); border-top: 1px solid var(--st-line); }
    .co-field { display: grid; gap: 6px; min-width: 220px; }
    .co-field label { font-size: 12px; font-weight: 700; color: var(--st-text); }
    .co-field select { min-height: 42px; padding: 9px 12px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 14px; }
    .co-field select:focus-visible { outline: 3px solid var(--st-accent); outline-offset: 2px; }
    .co-submit { min-height: 42px; padding: 10px 20px; border: 0; border-radius: 10px; background: var(--st-accent); color: #fff; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; white-space: nowrap; }
    .co-submit:hover { background: var(--st-accent-hover); }
    .co-submit:focus-visible { outline: 3px solid var(--st-accent); outline-offset: 2px; }
    .co-submit--plain { background: #0f766e; }
    .co-online { flex-basis: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; padding: 14px; border: 1px dashed var(--st-line); border-radius: 12px; background: var(--st-card); }
    .co-online[hidden] { display: none; }
    .co-online__hint { grid-column: 1 / -1; margin: 0; font-size: 12px; color: var(--st-muted); }
    .co-input { min-height: 42px; padding: 9px 12px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 14px; width: 100%; }
    .co-input:focus-visible { outline: 3px solid var(--st-accent); outline-offset: 2px; }
    .co-file { font: inherit; font-size: 13px; color: var(--st-text); }
    .co-field-error { margin: 0; font-size: 12px; font-weight: 600; color: #be123c; }
    .co-error { margin-bottom: 14px; padding: 12px 16px; border: 1px solid #fecdd3; border-radius: 12px; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 600; }
    .co-empty { padding: 48px 24px; text-align: center; color: var(--st-muted); }
    .co-empty h2 { margin: 0 0 6px; font-size: 17px; color: var(--st-text); }
    @media (max-width: 720px) {
        .co-summary { grid-template-columns: 1fr; }
        .co-summary > div + div { border-left: 0; border-top: 1px solid var(--st-line); }
        .co-pay { flex-direction: column; align-items: stretch; }
        .co-field { min-width: 0; }
        .co-submit { width: 100%; }
    }
    html[data-theme="dark"] .co-status--due, html[data-theme="dark"] .co-summary .is-due strong { color: #fdba74; }
    html[data-theme="dark"] .co-status--due { background: rgba(249, 115, 22, .14); border-color: rgba(251, 146, 60, .35); }
    html[data-theme="dark"] .co-status--clear, html[data-theme="dark"] .co-summary .is-clear strong { color: #6ee7b7; }
    html[data-theme="dark"] .co-status--clear { background: rgba(16, 185, 129, .14); border-color: rgba(52, 211, 153, .35); }
    html[data-theme="dark"] .co-type--payment { background: rgba(16, 185, 129, .16); color: #6ee7b7; }
</style>

<div class="co-toolbar">
    <p class="eyebrow" style="margin:0">Departures</p>
    <a class="badge-soft text-decoration-none" href="{{ route('frontdesk.check-out.history') }}">Check-out history</a>
</div>

@if($errors->has('payment_method'))
    <div class="co-error" role="alert">{{ $errors->first('payment_method') }}</div>
@endif

<div class="co-list">
@forelse($bookings as $booking)
    @php
        $charges = (float) ($booking->folio_charges ?? 0);
        $receipts = (float) ($booking->folio_receipts ?? 0);
        $balance = round($charges - $receipts, 2);
        $isDue = $balance > 0.009;
        $roomNumbers = $booking->rooms->pluck('room_number')->join(', ') ?: '—';
        $guestName = $booking->guest?->full_name ?? 'Guest';
        $initials = strtoupper(substr($guestName, 0, 1));
        $selectId = 'payment-method-'.$booking->id;
    @endphp

    <article class="co-card" aria-labelledby="co-title-{{ $booking->id }}">
        <header class="co-card__head">
            <div class="co-guest">
                <span class="co-avatar" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <div class="co-guest__name" id="co-title-{{ $booking->id }}">{{ $guestName }}</div>
                    <div class="co-guest__meta">{{ $booking->guest?->phone ?: 'No phone on file' }} · {{ $booking->booking_code }}</div>
                </div>
            </div>
            <div class="co-facts">
                <div class="co-fact"><strong>{{ $roomNumbers }}</strong>Room</div>
                <div class="co-fact"><strong>{{ $booking->check_out_date->format('d M Y') }}</strong>Departure</div>
            </div>
            <span class="co-status {{ $isDue ? 'co-status--due' : 'co-status--clear' }}">{{ $isDue ? 'Balance outstanding' : 'Settled' }}</span>
        </header>

        <div class="co-summary" aria-label="Folio summary">
            <div><span>Total bill</span><strong>{{ $currencyCode }} {{ number_format($charges, 2) }}</strong></div>
            <div><span>Paid to date</span><strong>{{ $currencyCode }} {{ number_format($receipts, 2) }}</strong></div>
            <div class="{{ $isDue ? 'is-due' : 'is-clear' }}"><span>Balance due</span><strong>{{ $currencyCode }} {{ number_format($balance, 2) }}</strong></div>
        </div>

        <div class="co-folio">
            <h3 class="co-folio__title">Folio items</h3>
            <div class="co-table-wrap">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Item / service</th>
                            <th class="num">Qty</th>
                            <th class="num">Unit price</th>
                            <th class="num">Amount</th>
                            <th class="num">Tax</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($booking->folioEntries as $entry)
                        @php(preg_match('/\(Qty:\s*(\d+)\)/', (string) $entry->item_category, $quantityMatch))
                        @php($entryQuantity = $entry->type === 'charge' ? max(1, (int) ($quantityMatch[1] ?? 1)) : null)
                        @php($entryAmount = (float) $entry->amount)
                        <tr>
                            <td><span class="co-type {{ $entry->type === 'charge' ? '' : 'co-type--payment' }}">{{ ucfirst($entry->type) }}</span></td>
                            <td class="item">{{ $entry->item_category }}</td>
                            <td class="num">{{ $entryQuantity ?? '—' }}</td>
                            <td class="num">{{ $entryQuantity ? $currencyCode.' '.number_format($entryAmount / $entryQuantity, 2) : '—' }}</td>
                            <td class="num">{{ $currencyCode }} {{ number_format($entryAmount, 2) }}</td>
                            <td class="num">{{ $currencyCode }} {{ number_format((float) $entry->tax, 2) }}</td>
                            <td class="num"><strong>{{ $currencyCode }} {{ number_format((float) $entry->total, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="co-empty-lines">No folio entries recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('frontdesk.check-out.store', $booking) }}" enctype="multipart/form-data" class="co-pay" data-checkout-form>
            @csrf
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
            <div class="co-field">
                <label for="{{ $selectId }}">Payment method</label>
                <select id="{{ $selectId }}" name="payment_method" required>
                    <option value="cash" @selected(old('booking_id') == $booking->id && old('payment_method') === 'cash')>Cash</option>
                    <option value="card" @selected(old('booking_id') == $booking->id && old('payment_method') === 'card')>Credit Card</option>
                    <option value="bank_transfer" @selected(old('booking_id') == $booking->id && old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                </select>
            </div>
            @if($isDue)
                @php($isOnlineSelected = in_array(old('booking_id') == $booking->id ? old('payment_method') : null, ['card', 'bank_transfer'], true))
                <div class="co-online" data-online-fields {{ $isOnlineSelected ? '' : 'hidden' }}>
                    <p class="co-online__hint">Card and bank transfer payments need the transaction ID from the bank or gateway. A screenshot of the receipt is optional but helps the audit.</p>
                    <div class="co-field">
                        <label for="{{ $selectId }}-reference">Transaction ID</label>
                        <input id="{{ $selectId }}-reference" class="co-input" type="text" name="transaction_reference" maxlength="100" value="{{ old('booking_id') == $booking->id ? old('transaction_reference') : '' }}" placeholder="e.g. TRX 8842 9911" data-required-online>
                        @if(old('booking_id') == $booking->id && $errors->has('transaction_reference'))<p class="co-field-error">{{ $errors->first('transaction_reference') }}</p>@endif
                    </div>
                    <div class="co-field">
                        <label for="{{ $selectId }}-proof">Payment screenshot (optional)</label>
                        <input id="{{ $selectId }}-proof" class="co-file" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp">
                        @if(old('booking_id') == $booking->id && $errors->has('payment_proof'))<p class="co-field-error">{{ $errors->first('payment_proof') }}</p>@endif
                    </div>
                </div>
                <button class="co-submit" type="submit">Settle &amp; check out · {{ $currencyCode }} {{ number_format($balance, 2) }}</button>
            @else
                <button class="co-submit co-submit--plain" type="submit">Check out</button>
            @endif
        </form>
    </article>
@empty
    <div class="surface co-empty">
        <h2>No guests are due to check out</h2>
        <p>Checked-in guests with a departure date will appear here.</p>
    </div>
@endforelse
</div>

<script>
    (() => {
        document.querySelectorAll('[data-checkout-form]').forEach((form) => {
            const select = form.querySelector('select[name=payment_method]');
            const online = form.querySelector('[data-online-fields]');
            const reference = form.querySelector('[data-required-online]');
            if (!select || !online) return;

            const sync = () => {
                const needsOnline = ['card', 'bank_transfer'].includes(select.value);
                online.hidden = !needsOnline;
                if (reference) reference.required = needsOnline;
            };
            select.addEventListener('change', sync);
            sync();
        });
    })();
</script>
@endsection
