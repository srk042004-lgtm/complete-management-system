@extends('frontdesk.quick-actions.layout')

@section('title', 'Add Payment')
@section('description', 'Record a payment against an active reservation and its guest folio.')

@section('content')
<form method="POST" action="{{ route('frontdesk.payments.store') }}" class="panel">
    @csrf
    <div class="form-grid">
        <div class="field full">
            <label for="booking_id">Reservation / Guest *</label>
            <select id="booking_id" name="booking_id" required>
                <option value="">Select a reservation</option>
                @foreach($bookings as $booking)
                    @php($outstanding = max(0, (float) $booking->grand_total - (float) $booking->paid_amount))
                    <option value="{{ $booking->id }}" data-outstanding="{{ number_format($outstanding, 2, '.', '') }}" @selected((string) old('booking_id') === (string) $booking->id)>
                        {{ $booking->booking_code }} — {{ $booking->guest?->full_name ?? 'Guest' }} — Balance {{ $currencyCode }} {{ number_format($outstanding, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="amount">Payment amount ({{ $currencyCode }}) *</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required><small id="balance-help" class="muted">Select a reservation to view its outstanding balance.</small></div>
        <div class="field"><label for="payment_method">Payment method *</label><select id="payment_method" name="payment_method" required><option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option><option value="card" @selected(old('payment_method') === 'card')>Card</option><option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank transfer</option></select></div>
        <div class="field full"><label for="transaction_reference">Transaction reference</label><input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" maxlength="255" placeholder="Receipt number or bank reference"></div>
    </div>
    <div class="actions"><button class="button" type="submit">Record payment</button></div>
</form>
<script>
    const bookingSelect = document.getElementById('booking_id');
    const amountInput = document.getElementById('amount');
    const balanceHelp = document.getElementById('balance-help');
    function updatePaymentBalance() {
        const option = bookingSelect.options[bookingSelect.selectedIndex];
        const outstanding = Number(option?.dataset.outstanding || 0);
        amountInput.max = outstanding > 0 ? outstanding.toFixed(2) : '';
        balanceHelp.textContent = option?.value
            ? `Outstanding balance: {{ $currencyCode }} ${outstanding.toFixed(2)}. Payment cannot exceed this amount.`
            : 'Select a reservation to view its outstanding balance.';
    }
    bookingSelect.addEventListener('change', updatePaymentBalance);
    updatePaymentBalance();
</script>
@endsection
