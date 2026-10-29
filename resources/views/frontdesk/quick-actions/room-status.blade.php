@extends('frontdesk.quick-actions.layout')

@section('title', 'Room Status')
@section('description', 'View occupancy and update housekeeping or maintenance status.')

@section('content')
<section class="panel">
    <form class="inline-form" method="GET" action="{{ route('frontdesk.rooms.status') }}" style="align-items:end; margin-bottom:16px;">
        <label for="room-status-category">Filter by category</label>
        <select id="room-status-category" name="category">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->title ?: $category->type_name }}</option>
            @endforeach
        </select>
        <button class="button" type="submit">Filter rooms</button>
        @if($selectedCategory)
            <a class="button" href="{{ route('frontdesk.rooms.status') }}">Clear filter</a>
        @endif
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>Room #</th><th>Category</th><th>Occupancy</th><th>Current guest</th><th>Housekeeping</th><th>Update status</th></tr></thead>
        <tbody>
        @forelse($rooms as $room)
            @php($activeBooking = $room->bookings->first())
            <tr>
                <td><strong>{{ $room->room_number }}</strong></td>
                <td>{{ $room->category?->title ?: $room->category?->type_name ?: $room->room_type ?: '—' }}</td>
                <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $room->occupancy_status)) }}</span></td>
                <td>{{ $activeBooking?->guest?->full_name ?? '—' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $room->housekeeping_status)) }}</td>
                <td>
                    <form class="inline-form" method="POST" action="{{ route('frontdesk.rooms.status.update', $room) }}">
                        @csrf @method('PATCH')
                        @if($selectedCategory)<input type="hidden" name="category" value="{{ $selectedCategory }}">@endif
                        <select name="housekeeping_status" aria-label="Housekeeping status for room {{ $room->room_number }}">
                            @php($statusOptions = auth()->user()->role === 'housekeeping' || auth()->user()->role === 'housekeeper'
                                ? ['clean' => 'Clean & ready', 'dirty' => 'Dirty', 'cleaning' => 'Cleaning', 'inspection_pending' => 'Inspection pending']
                                : ['clean' => 'Clean', 'dirty' => 'Dirty', 'cleaning' => 'Cleaning', 'inspection_pending' => 'Inspection pending', 'maintenance' => 'Maintenance', 'out_of_order' => 'Out of order'])
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($room->housekeeping_status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="button" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No rooms match this category filter.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
