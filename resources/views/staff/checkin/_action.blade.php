{{-- Check-in button for a confirmed reservation. Unpaid bookings need an admin. --}}
@if(in_array($reservation->payment_status, ['paid', 'partial'], true))
<form action="{{ route('staff.checkin.process', $reservation) }}" method="POST" class="flex items-center gap-2">
    @csrf
    @method('PATCH')
    @if(isset($availableRooms) && $availableRooms->isNotEmpty())
    <select name="room_id" class="border rounded px-2 py-1 text-sm" @unless($reservation->room_id) required @endunless>
        <option value="">Assign room</option>
        @foreach($availableRooms as $room)
        <option value="{{ $room->id }}" {{ $reservation->room_id === $room->id ? 'selected' : '' }}>
            {{ $room->room_number }}{{ $room->building ? ' · '.$room->building : '' }}
        </option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="px-3 py-1 text-white bg-green-600 rounded text-sm hover:bg-green-700">Check In</button>
</form>
@else
<span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded bg-amber-100 text-amber-800" title="No payment recorded. An admin must approve this check-in.">
    <i class="fas fa-exclamation-triangle"></i> Unpaid: ask admin
</span>
@endif
