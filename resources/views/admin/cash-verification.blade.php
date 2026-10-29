@extends('frontdesk.quick-actions.layout')

@section('title', 'Cash verification')
@section('description', 'Verify the cash receptionists hand over before it counts as hotel revenue.')

@section('content')
<style>
    .verify-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .verify-card { padding: 14px 16px; border: 1px solid var(--st-line); border-radius: 14px; background: var(--st-card); box-shadow: var(--st-shadow); }
    .verify-card span { display: block; font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--st-muted); }
    .verify-card strong { display: block; margin-top: 6px; font-size: 20px; font-variant-numeric: tabular-nums; color: var(--st-text); }
    .verify-card small { display: block; margin-top: 4px; font-size: 11.5px; color: var(--st-muted); }
    .verify-card--unverified { border-color: #fed7aa; background: #fff7ed; }
    .verify-card--unverified strong { color: #c2410c; }
    .verify-card--verified strong { color: #047857; }
    .verify-section { margin-bottom: 16px; border: 1px solid var(--st-line); border-radius: 16px; background: var(--st-card); overflow: hidden; }
    .verify-section__head { padding: 12px 16px; border-bottom: 1px solid var(--st-line); font-size: 14px; font-weight: 700; color: var(--st-text); }
    .verify-note { margin: 0; padding: 10px 16px; font-size: 12.5px; color: var(--st-muted); border-bottom: 1px solid var(--st-line); background: #fffbeb; }
    html[data-theme="dark"] .verify-note { background: rgba(234, 179, 8, .12); }
    .verify-table-wrap { overflow-x: auto; }
    .verify-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .verify-table th { padding: 9px 12px; background: var(--st-hover); font-size: 11.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--st-muted); text-align: left; white-space: nowrap; }
    .verify-table td { padding: 10px 12px; border-top: 1px solid var(--st-line); color: var(--st-text); vertical-align: middle; }
    .verify-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .verify-sub { display: block; margin-top: 2px; font-size: 11.5px; color: var(--st-muted); }
    .verify-method { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .verify-method--cash { background: #d1fae5; color: #065f46; }
    .verify-method--bank { background: #e0e7ff; color: #3730a3; }
    .verify-method--card { background: #fce7f3; color: #9d174d; }
    .verify-method--wallet { background: #ffedd5; color: #9a3412; }
    .verify-method--mixed { background: #f1f5f9; color: #334155; }
    .verify-ref { display: block; margin-top: 4px; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 11.5px; color: var(--st-text); }
    .verify-receipt { display: inline-flex; align-items: center; gap: 6px; min-height: 30px; padding: 4px 10px; border: 1px solid var(--st-line); border-radius: 8px; background: var(--st-card); color: var(--st-accent-text); font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
    .verify-receipt:hover { background: var(--st-hover); }
    .verify-none { font-size: 12px; color: var(--st-muted); }
    .verify-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .verify-btn { min-height: 32px; padding: 5px 12px; border-radius: 8px; border: 1px solid var(--st-line); background: var(--st-card); color: var(--st-text); font: inherit; font-size: 12.5px; font-weight: 700; cursor: pointer; }
    .verify-btn--ok { background: #0f766e; border-color: #0f766e; color: #fff; }
    .verify-btn--ok:hover { background: #115e59; }
    .verify-btn--danger { border-color: #fecaca; color: #be123c; }
    .verify-status { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
    .verify-status--verified { background: #dcfce7; color: #166534; }
    .verify-status--rejected { background: #fee2e2; color: #991b1b; }
    .verify-empty { padding: 20px; text-align: center; color: var(--st-muted); font-size: 13px; }
    .verify-modal-backdrop { position: fixed; inset: 0; z-index: 9500; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .6); }
    .verify-modal { width: min(720px, 100%); max-height: calc(100vh - 32px); display: flex; flex-direction: column; border-radius: 16px; background: var(--st-card); color: var(--st-text); box-shadow: 0 24px 60px rgba(15, 23, 42, .3); overflow: hidden; }
    .verify-modal__head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--st-line); font-weight: 700; }
    .verify-modal__body { padding: 12px; overflow: auto; background: #f8fafc; text-align: center; }
    .verify-modal__body img { max-width: 100%; max-height: 70vh; border-radius: 8px; }
    .verify-modal__close { border: 0; background: transparent; color: var(--st-muted); font-size: 22px; line-height: 1; cursor: pointer; }
</style>

@if(session('success'))<div class="notice-ok" role="status" style="margin-bottom:12px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="notice-err" role="alert" style="margin-bottom:12px">{{ session('error') }}</div>@endif

<div class="verify-cards">
    <div class="verify-card verify-card--unverified">
        <span>Unverified cash (in reception)</span>
        <strong>{{ $currency }} {{ number_format($unverifiedDrawerCash, 2) }}</strong>
        <small>Open drawers plus drops not yet received. Not counted as revenue.</small>
    </div>
    <div class="verify-card verify-card--verified">
        <span>Verified net revenue</span>
        <strong>{{ $currency }} {{ number_format($verifiedNetRevenue, 2) }}</strong>
        <small>Only money an owner or admin has received and approved.</small>
    </div>
</div>

@if($shortages->isNotEmpty())
    <div class="notice-err" role="alert" style="margin-bottom:14px">
        <strong>Cash shortage detected on {{ $shortages->count() }} shift{{ $shortages->count() === 1 ? '' : 's' }}.</strong>
        <div class="verify-table-wrap" style="margin-top:8px">
            <table class="verify-table">
                <thead><tr><th>Closed</th><th>Receptionist</th><th class="num">Expected</th><th class="num">Counted</th><th class="num">Missing</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($shortages as $shortage)
                    <tr>
                        <td>{{ $shortage->closed_at?->format('d M Y H:i') }}</td>
                        <td>{{ $shortage->closer?->name ?? $shortage->opener?->name ?? '—' }}</td>
                        <td class="num">{{ $currency }} {{ number_format((float) $shortage->expected_cash, 2) }}</td>
                        <td class="num">{{ $currency }} {{ number_format((float) $shortage->counted_cash, 2) }}</td>
                        <td class="num"><strong>{{ $currency }} {{ number_format(abs((float) $shortage->variance), 2) }}</strong></td>
                        <td>
                            <form method="POST" action="{{ route('hotel.cash-shortages.acknowledge', $shortage) }}">
                                @csrf
                                <button type="submit" class="verify-btn" data-tip="Mark this shortage as reviewed. It stays in the shift history">Acknowledge</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<section class="verify-section">
    <div class="verify-section__head">Shift expenses · {{ $currency }} {{ number_format($expensesThisMonth, 2) }} this month</div>
    <p class="verify-note">Petty cash paid from reception drawers. Each expense reduced the drawer's expected cash.</p>
    <div class="verify-table-wrap">
        <table class="verify-table">
            <thead><tr><th>Date</th><th>Receptionist</th><th>Category</th><th>Description</th><th class="num">Amount</th><th>Receipt</th></tr></thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $expense->user?->name ?? '—' }}</td>
                    <td>{{ \App\Models\ShiftExpense::CATEGORIES[$expense->category] ?? $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td class="num">{{ $currency }} {{ number_format((float) $expense->amount, 2) }}</td>
                    <td>@if($expense->receipt_path)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->receipt_path) }}" target="_blank" rel="noopener">View</a>@else<span class="verify-none">None</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="verify-empty">No shift expenses recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="verify-section" x-data="{ receipt: null, receiptTitle: '' }">
    <div class="verify-section__head">Pending verification ({{ $pending->count() }})</div>
    <p class="verify-note">Approve only after physically counting cash and verifying online transaction proofs.</p>
    <div class="verify-table-wrap">
        <table class="verify-table">
            <thead>
                <tr>
                    <th>Submitted</th>
                    <th>From</th>
                    <th>Payment type / TRX ID</th>
                    <th class="num">Amount</th>
                    <th class="num">Kept for morning</th>
                    <th>Receipt</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($pending as $transfer)
                @php
                    $isCash = $transfer->payment_method === 'physical_cash';
                    $methodClass = match ($transfer->payment_method) {
                        'bank_transfer' => 'verify-method--bank',
                        'pos_card' => 'verify-method--card',
                        'online_wallet' => 'verify-method--wallet',
                        'mixed' => 'verify-method--mixed',
                        default => 'verify-method--cash',
                    };
                @endphp
                <tr>
                    <td>{{ $transfer->created_at?->format('d M Y') }}<span class="verify-sub">{{ $transfer->created_at?->format('H:i') }}</span></td>
                    <td>{{ $transfer->sender?->name ?? '—' }}@if($transfer->notes)<span class="verify-sub">{{ $transfer->notes }}</span>@endif</td>
                    <td>
                        <span class="verify-method {{ $methodClass }}">{{ \App\Models\CashTransfer::PAYMENT_METHODS[$transfer->payment_method] ?? 'Physical cash' }}</span>
                        @if($transfer->transaction_reference)<span class="verify-ref">{{ $transfer->transaction_reference }}</span>@endif
                    </td>
                    <td class="num"><strong>{{ $currency }} {{ number_format((float) $transfer->amount, 2) }}</strong></td>
                    <td class="num">{{ $currency }} {{ number_format((float) $transfer->retained_float, 2) }}</td>
                    <td>
                        @if($transfer->proof_image_path)
                            <button type="button" class="verify-receipt" data-tip="Open the uploaded receipt in full size"
                                @click="receipt = '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($transfer->proof_image_path) }}'; receiptTitle = '{{ $currency }} {{ number_format((float) $transfer->amount, 2) }} · {{ $transfer->transaction_reference ?? 'No reference' }}'">
                                &#128196; View receipt
                            </button>
                        @else
                            <span class="verify-none">{{ $isCash ? 'Not needed (cash)' : 'No receipt uploaded' }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="verify-actions">
                            <form method="POST" action="{{ route('hotel.cash-verification.approve', $transfer) }}" onsubmit="return confirm('Confirm you have received {{ $currency }} {{ number_format((float) $transfer->amount, 2) }}{{ $isCash ? ' in cash' : ' in this payment channel' }}?')">
                                @csrf
                                <button type="submit" class="verify-btn verify-btn--ok" data-tip="Confirm the money was received and add it to verified revenue">Approve &amp; receive</button>
                            </form>
                            <form method="POST" action="{{ route('hotel.cash-verification.reject', $transfer) }}">
                                @csrf
                                <button type="submit" class="verify-btn verify-btn--danger" data-tip="Reject this drop. The money stays unverified until recounted">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="verify-empty">No vault drops are waiting for verification.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Receipt viewer --}}
    <div x-show="receipt" x-cloak class="verify-modal-backdrop" @click.self="receipt = null" @keydown.escape.window="receipt = null" role="dialog" aria-modal="true" aria-label="Payment receipt">
        <div class="verify-modal">
            <div class="verify-modal__head">
                <span x-text="receiptTitle"></span>
                <button type="button" class="verify-modal__close" @click="receipt = null" aria-label="Close receipt">&times;</button>
            </div>
            <div class="verify-modal__body">
                <img :src="receipt" alt="Payment receipt">
            </div>
        </div>
    </div>
</section>

<section class="verify-section">
    <div class="verify-section__head">Recent decisions</div>
    <div class="verify-table-wrap">
        <table class="verify-table">
            <thead>
                <tr><th>Decided</th><th>From</th><th>Received by</th><th>Payment</th><th class="num">Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse($history as $transfer)
                <tr>
                    <td>{{ $transfer->verified_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $transfer->sender?->name ?? '—' }}</td>
                    <td>{{ $transfer->receiver?->name ?? '—' }}</td>
                    <td>{{ \App\Models\CashTransfer::PAYMENT_METHODS[$transfer->payment_method] ?? 'Physical cash' }}@if($transfer->transaction_reference)<span class="verify-ref">{{ $transfer->transaction_reference }}</span>@endif</td>
                    <td class="num">{{ $currency }} {{ number_format((float) $transfer->amount, 2) }}</td>
                    <td><span class="verify-status verify-status--{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="verify-empty">No decisions recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
