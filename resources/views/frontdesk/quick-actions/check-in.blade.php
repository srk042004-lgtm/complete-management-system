@extends('frontdesk.quick-actions.layout')

@section('title', 'Check-in')
@section('description', 'Check in confirmed reservations due today or earlier.')

@section('content')
<section class="panel">
    <div class="table-wrap"><table>
        <thead><tr><th>Reservation</th><th>Guest</th><th>Room(s)</th><th>Arrival</th><th>Guests</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($bookings as $booking)
            <tr>
                <td><strong>{{ $booking->booking_code }}</strong></td>
                <td>{{ $booking->guest?->full_name ?? 'Guest' }}<br><span class="muted">{{ $booking->guest?->phone }}</span></td>
                <td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Unassigned' }}</td>
                <td>{{ $booking->check_in_date->format('d M Y') }}</td>
                <td>{{ $booking->total_guests }}</td>
                <td><form method="POST" action="{{ route('frontdesk.check-in.store', $booking) }}">@csrf<button class="button" type="submit">Check in</button></form></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No confirmed reservations are due for check-in.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
