@extends('frontdesk.quick-actions.layout')

@section('title', 'Reservations')
@section('description', 'Confirmed reservations are listed first. Filter by stay status to see the rest of the history.')

@section('content')
@php
    $filters = [
        'confirmed' => 'Upcoming',
        'checked_in' => 'In house',
        'checked_out' => 'Checked out',
        'cancelled' => 'Cancelled',
    ];
    $pillFor = fn (string $s) => match ($s) {
        'confirmed' => 'pill--inspect',
        'checked_in' => 'pill--clean',
        'checked_out' => 'pill--neutral',
        default => 'pill--dirty',
    };
@endphp

<div class="page-head" style="margin-bottom:16px">
    <div class="chip-row" role="tablist" aria-label="Filter reservations">
        @foreach($filters as $key => $label)
            <a href="{{ route('reservations.index', ['status' => $key]) }}" class="chip {{ $status === $key ? 'is-active' : '' }}" @if($status === $key) aria-current="page" @endif>
                {{ $label }} <span class="count">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
    <a href="{{ route('reservations.booking') }}" class="btn-primary btn-compact text-decoration-none">+ New reservation</a>
</div>

@if(session('success'))
    <div class="notice-ok" role="status">{{ session('success') }}</div>
@endif

<section class="surface" style="overflow:hidden">
    <div class="table-wrap" style="border:0">
        <table>
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Guest</th>
                    <th>Room(s)</th>
                    <th>Arrival</th>
                    <th>Departure</th>
                    <th class="num">Paid / total</th>
                    <th>Status</th>
                    <th class="num">Next step</th>
                </tr>
            </thead>
            <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td><strong>{{ $booking->booking_code }}</strong></td>
                    <td>{{ $booking->guest?->full_name ?? 'Guest' }}<br><span class="muted">{{ $booking->guest?->phone }}</span></td>
                    <td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: '—' }}</td>
                    <td>{{ $booking->check_in_date->format('d M Y') }}</td>
                    <td>{{ $booking->check_out_date->format('d M Y') }}</td>
                    <td class="num">{{ $currencyCode }} {{ number_format((float) $booking->paid_amount, 2) }} / {{ number_format((float) $booking->grand_total, 2) }}</td>
                    <td><span class="pill {{ $pillFor($booking->booking_status) }}">{{ $filters[$booking->booking_status] ?? ucfirst(str_replace('_', ' ', $booking->booking_status)) }}</span></td>
                    <td class="num">
                        @if($booking->booking_status === 'confirmed')
                            <a href="{{ route('frontdesk.check-in') }}" class="badge-soft text-decoration-none">Check in</a>
                        @elseif($booking->booking_status === 'checked_in')
                            <a href="{{ route('frontdesk.check-out') }}" class="badge-soft text-decoration-none">Check out</a>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">
                        No {{ strtolower($filters[$status]) }} reservations.
                        @if($status === 'confirmed')
                            <div style="margin-top:12px"><a href="{{ route('reservations.booking') }}" class="btn-primary btn-compact text-decoration-none">Create the first reservation</a></div>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
