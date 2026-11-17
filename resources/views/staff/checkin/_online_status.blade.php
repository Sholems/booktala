{{-- Online check-in state for a reservation, linking to the details staff verify. --}}
@php $online = $reservation->guestCheckin; @endphp
@if(!$online)
<span class="text-xs text-gray-500">Not submitted</span>
@elseif($online->isVerified())
<a href="{{ route('staff.checkin.details', $reservation) }}" class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded bg-green-100 text-green-800 hover:underline">
    <i class="fas fa-id-card"></i> ID verified
</a>
@else
<a href="{{ route('staff.checkin.details', $reservation) }}" class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 hover:underline">
    <i class="fas fa-file-alt"></i> Submitted: verify ID
</a>
@endif
