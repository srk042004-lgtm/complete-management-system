@extends('frontdesk.quick-actions.layout')

@section('title', 'Check-out History')
@section('description', 'Review guests who have checked out. Reservation and folio records are retained.')

@section('content')
<div class="actions" style="justify-content:flex-start;margin:0 0 12px">
    <a class="button secondary" href="{{ route('frontdesk.check-out') }}">Current check-outs</a>
</div>
<section class="panel">
    <div class="table-wrap"><table>
        <thead><tr><th>Reservation</th><th>Guest</th><th>Room(s)</th><th>Departure date</th><th>Checked out at</th><th>Final balance</th></tr></thead>
        <tbody>
        @forelse($bookings as $booking)
            @php($balance = round((float) ($booking->folio_charges ?? 0) - (float) ($booking->folio_receipts ?? 0), 2))
            <tr>
                <td><strong>{{ $booking->booking_code }}</strong></td>
                <td>{{ $booking->guest?->full_name ?? 'Guest' }}<br><span class="muted">{{ $booking->guest?->phone }}</span></td>
                <td>{{ $booking->rooms->pluck('room_number')->join(', ') }}</td>
                <td>{{ $booking->check_out_date->format('d M Y') }}</td>
                <td>{{ $booking->actual_check_out?->format('d M Y, H:i') ?? '—' }}</td>
                <td>{{ $currencyCode }} {{ number_format($balance, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No completed check-outs are recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
