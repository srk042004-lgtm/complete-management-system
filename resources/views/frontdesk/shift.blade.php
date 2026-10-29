<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cash Shift | {{ auth()->user()->tenant->hotel_name ?? 'Front Desk' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        .shift-modal-backdrop { position: fixed; inset: 0; z-index: 9500; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .45); }
        .shift-modal { width: min(460px, 100%); max-height: calc(100vh - 32px); overflow-y: auto; padding: 20px; border-radius: 16px; background: var(--st-card); color: var(--st-text); box-shadow: 0 24px 60px rgba(15, 23, 42, .25); }
        .shift-modal h2 { margin: 0; font-size: 1.05rem; }
        .shift-modal > p { margin: 4px 0 14px; font-size: .84rem; color: var(--st-muted); }
        .shift-modal .form-stack { display: grid; gap: 12px; }
        .shift-modal .form-stack label { display: block; margin-bottom: 4px; font-size: .8rem; font-weight: 700; }
        .shift-modal .input, .shift-modal select, .shift-modal textarea { width: 100%; min-height: 38px; padding: 7px 10px; border: 1px solid var(--st-line); border-radius: 9px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: .9rem; }
        .shift-modal textarea { min-height: 60px; }
        .shift-modal__actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
        .shift-hint { padding: 9px 11px; border-radius: 10px; background: var(--st-hover); font-size: .8rem; color: var(--st-muted); }
        .shift-hint strong { color: var(--st-text); }
        .shift-variance.is-short { color: #be123c; }
        .shift-variance.is-excess { color: #047857; }
        .shift-error { color: #be123c; font-size: .8rem; font-weight: 600; margin-top: 4px; }
        .stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; }
        .form-row > div { flex: 1 1 200px; min-width: 0; }
        @media (max-width: 767.98px) {
            main.page { padding: 12px 12px 28px; min-width: 0; width: 100%; box-sizing: border-box; }
            .page-head { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-head h1 { font-size: 1.25rem; }
            .surface { padding: 16px !important; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
            .form-row > div { flex: 1 1 100%; }
            .form-row > button, .form-row .btn-block { width: 100% !important; }
            .shift-modal { padding: 16px; }
            main.page th { white-space: nowrap; }
        }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="page" x-data="{
        modal: null,
        expected: {{ $openShift ? number_format((float) $openShift->expected_cash, 2, '.', '') : 0 }},
        counted: '',
        retained: '',
        handover: '',
        method: 'physical_cash',
        get toOwner() { return this.handover ? 0 : Math.round(((Number(this.counted) || 0) - (Number(this.retained) || 0)) * 100) / 100; },
        opening: '{{ $suggestion ? number_format($suggestion['amount'], 2, '.', '') : '' }}',
        get variance() { return Math.round(((Number(this.counted) || 0) - this.expected) * 100) / 100; },
        close() { this.modal = null; },
    }" @keydown.escape.window="close()">
    <div class="page-head">
        <div>
            <p class="eyebrow">Cash drawer</p>
            <h1>Shift &amp; cash reconciliation</h1>
            <p>Start your shift with the float you received, then count the drawer when you close it or hand it over.</p>
        </div>
        <a href="{{ route('frontdesk.dashboard') }}" class="badge-soft text-decoration-none">Back to front desk</a>
    </div>

    @if(session('success'))
        <div class="notice-ok" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="notice-err" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="notice-err" role="alert">{{ $errors->first() }}</div>
    @endif

    @if($openShift)
        <section class="surface" style="padding:24px;margin-bottom:20px">
            <div class="stat-grid">
                <div class="stat-tile"><div class="kpi-label">Opened by</div><div class="stat-value">{{ $openShift->opener?->name ?? '—' }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Opened at</div><div class="stat-value">{{ $openShift->opened_at->format('d M, H:i') }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Opening float</div><div class="stat-value">{{ number_format((float) $openShift->opening_float, 2) }}</div></div>
                <div class="stat-tile"><div class="kpi-label">Expected in drawer</div><div class="stat-value">{{ number_format((float) $openShift->expected_cash, 2) }}</div></div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;margin-top:18px">
                <button type="button" class="btn-block" style="width:auto;padding:10px 20px" @click="modal = 'close'; counted = ''">Close &amp; hand over shift</button>
            </div>
        </section>

        <section class="surface" style="padding:22px;margin-bottom:20px">
            <div class="section-head"><div><h2>Cash payments this shift</h2><p class="muted">Cash received since the shift opened.</p></div></div>
            <div class="table-wrap" style="border:0">
                <table>
                    <thead><tr><th>Time</th><th>Booking</th><th>Method</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentCashPayments as $payment)
                        <tr><td>{{ $payment->created_at->format('H:i') }}</td><td>{{ $payment->booking?->booking_code ?? '—' }}</td><td>Cash</td><td class="num">{{ number_format((float) $payment->amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No cash payments yet in this shift.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface" style="padding:22px;margin-bottom:20px">
            <div class="section-head" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                <div><h2>Shift expenses</h2><p class="muted">Petty cash paid from this drawer. Each one reduces the expected cash.</p></div>
                <button type="button" class="badge-soft" @click="modal = 'expense'">Add expense</button>
            </div>
            <div class="table-wrap" style="border:0">
                <table>
                    <thead><tr><th>Time</th><th>Category</th><th>Description</th><th>Receipt</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @forelse($openShiftExpenses as $expense)
                        <tr>
                            <td>{{ $expense->created_at->format('H:i') }}</td>
                            <td>{{ \App\Models\ShiftExpense::CATEGORIES[$expense->category] ?? $expense->category }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>@if($expense->receipt_path)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->receipt_path) }}" target="_blank" rel="noopener">View</a>@else<span class="muted">None</span>@endif</td>
                            <td class="num">{{ number_format((float) $expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No expenses in this shift.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Add expense modal --}}
        <div x-show="modal === 'expense'" x-cloak class="shift-modal-backdrop" @click.self="close()" role="dialog" aria-modal="true" aria-labelledby="expense-title">
            <div class="shift-modal">
                <h2 id="expense-title">Add expense</h2>
                <p>Petty cash paid from the drawer, for example guest tea or cleaning supplies.</p>
                <form method="POST" action="{{ route('frontdesk.shift.expenses.store', $openShift) }}" enctype="multipart/form-data" class="form-stack">
                    @csrf
                    <div>
                        <label for="expense_amount">Amount</label>
                        <input id="expense_amount" class="input" name="amount" type="number" min="0.01" step="0.01" required placeholder="0.00">
                    </div>
                    <div>
                        <label for="expense_category">Category</label>
                        <select id="expense_category" name="category" required>
                            @foreach(\App\Models\ShiftExpense::CATEGORIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="expense_description">Reason / description</label>
                        <input id="expense_description" class="input" name="description" maxlength="255" required placeholder="e.g. Tea for guest in room 204">
                    </div>
                    <div>
                        <label for="expense_receipt">Receipt photo (optional)</label>
                        <input id="expense_receipt" class="input" type="file" name="receipt" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="shift-modal__actions">
                        <button type="button" class="badge-soft" @click="close()">Cancel</button>
                        <button type="submit" class="btn-block" style="width:auto;padding:10px 18px">Save expense</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Close and hand over modal --}}
        <div x-show="modal === 'close'" x-cloak class="shift-modal-backdrop" @click.self="close()" role="dialog" aria-modal="true" aria-labelledby="close-shift-title">
            <div class="shift-modal">
                <h2 id="close-shift-title">Close &amp; hand over shift</h2>
                <p>Count the cash, keep a float for the morning, and the rest goes to the owner or admin.</p>
                <form method="POST" action="{{ route('frontdesk.shift.close', $openShift) }}" enctype="multipart/form-data" class="form-stack">
                    @csrf
                    <div>
                        <label for="counted_cash">Counted cash</label>
                        <input id="counted_cash" class="input" name="counted_cash" type="number" min="0" step="0.01" required x-model="counted" placeholder="0.00">
                    </div>
                    <div class="shift-hint">
                        Expected <strong>{{ number_format((float) $openShift->expected_cash, 2) }}</strong> ·
                        Difference <strong class="shift-variance" :class="variance < 0 ? 'is-short' : (variance > 0 ? 'is-excess' : '')" x-text="(variance > 0 ? '+' : '') + variance.toFixed(2)"></strong>
                        <span x-show="variance < 0">(short)</span><span x-show="variance > 0">(excess)</span>
                    </div>
                    <div class="shift-error" x-show="counted !== '' && variance < 0" role="alert">
                        CASH SHORTAGE WARNING: PKR <span x-text="Math.abs(variance).toFixed(2)"></span> is missing. It is logged against your name for the owner to review.
                    </div>
                    <div>
                        <label for="retained_float">Keep in drawer for the morning</label>
                        <input id="retained_float" class="input" name="retained_float" type="number" min="0" step="0.01" placeholder="0.00" x-model="retained">
                    </div>
                    <div x-show="!handover && toOwner > 0">
                        <label for="payment_method">How the amount sent was paid</label>
                        <select id="payment_method" name="payment_method" x-model="method">
                            <option value="physical_cash">Physical cash</option>
                            <option value="bank_transfer">Bank transfer / online</option>
                            <option value="pos_card">POS card</option>
                            <option value="online_wallet">Online wallet</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                    <div class="form-stack" x-show="!handover && toOwner > 0 && method !== 'physical_cash'">
                        <div>
                            <label for="transaction_reference">TRX ID / reference number</label>
                            <input id="transaction_reference" class="input" name="transaction_reference" maxlength="100" placeholder="e.g. JazzCash or bank reference">
                        </div>
                        <div>
                            <label for="payment_proof">Receipt or screenshot (optional)</label>
                            <input id="payment_proof" class="input" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                    <div class="shift-hint">
                        Sent to the owner or admin: <strong x-text="toOwner.toFixed(2)"></strong>
                        <span x-show="handover">(nothing sent, drawer handed over)</span>
                    </div>
                    <div>
                        <label for="handover_to_id">Or hand the drawer to the next receptionist</label>
                        <select id="handover_to_id" name="handover_to_id" x-model="handover">
                            <option value="">Nobody (end of day, use a vault drop instead)</option>
                            @foreach($handoverOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) old('handover_to_id') === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        @if($handoverOptions->isEmpty())
                            <p class="muted" style="font-size:.75rem;margin:4px 0 0">No other receptionist is set up yet.</p>
                        @endif
                    </div>
                    <div>
                        <label for="close_notes">Notes or discrepancy reason</label>
                        <textarea id="close_notes" name="notes" maxlength="500" placeholder="Required if the drawer does not match">{{ old('notes') }}</textarea>
                    </div>
                    <div class="shift-modal__actions">
                        <button type="button" class="badge-soft" @click="close()">Cancel</button>
                        <button type="submit" class="btn-block" style="width:auto;padding:10px 18px">Close shift</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <section class="surface" style="padding:24px;margin-bottom:20px">
            <h2 style="margin:0">No shift is open</h2>
            <p class="muted" style="margin:6px 0 0">Start a shift with the float you received. Cash payments taken while it is open are added to its drawer.</p>
            @if($suggestion)
                <div class="shift-hint" style="margin-top:14px">
                    @if($suggestion['source'] === 'handover')
                        A handover of <strong>{{ number_format($suggestion['amount'], 2) }}</strong> from the previous receptionist is waiting for you.
                    @else
                        The float retained from the last vault drop is <strong>{{ number_format($suggestion['amount'], 2) }}</strong> and will be used as your opening float.
                    @endif
                </div>
            @endif
            <div style="margin-top:16px">
                <button type="button" class="btn-block" style="width:auto;padding:10px 20px" @click="modal = 'open'">Start new shift</button>
            </div>
        </section>

        {{-- Start new shift modal --}}
        <div x-show="modal === 'open'" x-cloak class="shift-modal-backdrop" @click.self="close()" role="dialog" aria-modal="true" aria-labelledby="open-shift-title">
            <div class="shift-modal">
                <h2 id="open-shift-title">Start new shift</h2>
                <p>Confirm the cash you are starting with. The retained float or handover is pre-filled when there is one.</p>
                <form method="POST" action="{{ route('frontdesk.shift.open') }}" class="form-stack">
                    @csrf
                    <div>
                        <label for="opening_float">Opening cash balance</label>
                        <input id="opening_float" class="input" name="opening_float" type="number" min="0" step="0.01" placeholder="0.00" x-model="opening">
                        @if($suggestion)
                            <p class="muted" style="font-size:.75rem;margin:4px 0 0">Leave as is to accept the {{ $suggestion['source'] === 'handover' ? 'handed-over' : 'retained' }} amount. Change it only if the count differs, and explain in the notes.</p>
                        @endif
                    </div>
                    <div>
                        <label for="open_notes">Notes</label>
                        <textarea id="open_notes" name="notes" maxlength="500" placeholder="Optional">{{ old('notes') }}</textarea>
                    </div>
                    <div class="shift-modal__actions">
                        <button type="button" class="badge-soft" @click="close()">Cancel</button>
                        <button type="submit" class="btn-block" style="width:auto;padding:10px 18px">Start shift</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <section class="surface" style="padding:22px">
        <div class="section-head"><div><h2>Closed shifts</h2><p class="muted">Reconciled drawers, newest first.</p></div></div>
        <div class="table-wrap" style="border:0">
            <table>
                <thead>
                    <tr>
                        <th>Closed</th>
                        <th>Opened by</th>
                        <th>Closed by</th>
                        <th>Handed over to</th>
                        <th class="num">Expected</th>
                        <th class="num">Counted</th>
                        <th class="num">Variance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($history as $shift)
                    @php($variance = (float) $shift->variance)
                    <tr>
                        <td>{{ $shift->closed_at?->format('d M Y, H:i') }}</td>
                        <td>{{ $shift->opener?->name ?? '—' }}</td>
                        <td>{{ $shift->closer?->name ?? '—' }}</td>
                        <td>{{ $shift->handoverTo?->name ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $shift->expected_cash, 2) }}</td>
                        <td class="num">{{ number_format((float) $shift->counted_cash, 2) }}</td>
                        <td class="num">
                            <span class="pill {{ $variance === 0.0 ? 'pill--neutral' : ($variance < 0 ? 'pill--dirty' : 'pill--clean') }}">{{ number_format($variance, 2) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No closed shifts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@include('partials.hotel-shell-end')
</body>
</html>
