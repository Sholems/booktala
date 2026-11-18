@extends('layouts.admin')

@section('page_title', 'Reservation Details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h2 class="text-2xl font-bold">Reservation: {{ $reservation->booking_ref }}</h2>
    <a href="{{ route('admin.reservations.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Back</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Guest & Property</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Guest</dt>
                    <dd class="font-medium">{{ $reservation->user->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd class="font-medium">{{ $reservation->user->email ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Property</dt>
                    <dd class="font-medium">{{ $reservation->property->title ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Location</dt>
                    <dd class="font-medium">{{ $reservation->property->city ?? 'N/A' }}, {{ $reservation->property->state ?? '' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Stay Details</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Check In</dt>
                    <dd class="font-medium">{{ optional($reservation->check_in)->format('M d, Y') ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Check Out</dt>
                    <dd class="font-medium">{{ optional($reservation->check_out)->format('M d, Y') ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Guests</dt>
                    <dd class="font-medium">{{ $reservation->guests ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Nights</dt>
                    <dd class="font-medium">{{ $reservation->nights ?? 0 }}</dd>
                </div>
            </dl>
        </div>

        @php $online = $reservation->guestCheckin; @endphp
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Online Check-in</h3>
            @if(!$online)
            <p class="text-sm text-gray-500">The guest has not completed online check-in.</p>
            @else
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Guest</dt><dd class="font-medium">{{ $online->guest_first_name }} {{ $online->guest_last_name }} · {{ $online->guest_phone }}</dd></div>
                <div><dt class="text-gray-500">ID</dt><dd class="font-medium">{{ \App\Models\GuestCheckin::idTypes()[$online->id_type] ?? $online->id_type }} · {{ $online->id_number }}</dd></div>
                <div><dt class="text-gray-500">Party</dt><dd class="font-medium">{{ $online->num_adults }} adults, {{ $online->num_children ?? 0 }} children</dd></div>
                <div><dt class="text-gray-500">Expected arrival</dt><dd class="font-medium">{{ $online->arrival_time ?: 'Not given' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-gray-500">Verification</dt>
                    <dd class="font-medium">
                        @if($online->isVerified())
                        <span class="text-green-700">ID verified by {{ $online->verifiedBy->name ?? 'staff' }} on {{ $online->verified_at->format('M d, Y g:i A') }}</span>
                        @else
                        <span class="text-amber-700">Not yet verified at the desk</span>
                        @endif
                    </dd>
                </div>
                @if($online->special_requests)
                <div class="md:col-span-2"><dt class="text-gray-500">Special requests</dt><dd class="font-medium">{{ $online->special_requests }}</dd></div>
                @endif
            </dl>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Status</h3>
            <p class="mb-4">
                <span class="px-2 py-1 text-xs rounded-full
                    {{ $reservation->booking_status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $reservation->booking_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $reservation->booking_status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $reservation->booking_status === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $reservation->booking_status === 'checked_out' ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ ucfirst(str_replace('_', ' ', $reservation->booking_status)) }}
                </span>
            </p>

            <p class="text-sm text-gray-600">Payment: <strong>{{ ucfirst($reservation->payment_status ?? 'unpaid') }}</strong></p>
            <p class="text-sm text-gray-600">Processing fee: <strong>&#8358;{{ number_format((float) $reservation->processing_fee, 2) }}</strong></p>
            <p class="text-sm text-gray-600">Caution fee: <strong>&#8358;{{ number_format((float) $reservation->caution_fee, 2) }}</strong> · {{ ucfirst(str_replace('_', ' ', $reservation->caution_fee_status ?? 'pending')) }}</p>
            <p class="text-sm text-gray-600">Total: <strong>&#8358;{{ number_format((float) ($reservation->total_amount ?? 0), 2) }}</strong></p>
            @if($reservation->room)
            <p class="text-sm text-gray-600">Room: <strong>{{ $reservation->room->room_number }}</strong></p>
            @endif
        </div>

        @if($reservation->caution_fee_status === 'refund_pending')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-blue-900 mb-1">Caution fee refund</h3>
            <p class="text-sm text-blue-800 mb-4">Refund &#8358;{{ number_format((float) $reservation->caution_fee, 2) }} after the property inspection, or record that it was forfeited.</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.reservations.caution-refund', $reservation) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="caution_fee_status" value="refunded">
                    <button class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Mark refunded</button>
                </form>
                <form method="POST" action="{{ route('admin.reservations.caution-refund', $reservation) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="caution_fee_status" value="forfeited">
                    <button class="w-full px-4 py-2 rounded-lg border border-blue-600 text-blue-800 hover:bg-blue-100">Mark forfeited</button>
                </form>
            </div>
        </div>
        @endif

        @php
            $lifecycle = app(\App\Services\ReservationLifecycle::class);
            $isPaid = in_array($reservation->payment_status, ['paid', 'partial'], true);
            $arrivalDue = !$reservation->check_in->isAfter(today());
        @endphp

        @if($reservation->refund_status === 'pending')
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-amber-900 mb-1">Refund to review</h3>
            <p class="text-sm text-amber-800 mb-1">This booking was paid and then cancelled. Check the cancellation policy, pay any refund through Paystack or bank transfer, then record the outcome here.</p>
            <p class="text-sm font-medium text-amber-900 mb-4">Maximum booking refund: &#8358;{{ number_format($reservation->booking_refundable_amount, 2) }}. The processing fee is non-refundable and the caution fee is handled separately.</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.reservations.refund', $reservation) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="refund_status" value="refunded">
                    <button class="w-full px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700" onclick="return confirm('Record that the refund has been paid?')">Refund paid</button>
                </form>
                <form method="POST" action="{{ route('admin.reservations.refund', $reservation) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="refund_status" value="declined">
                    <button class="w-full px-4 py-2 rounded-lg border border-amber-600 text-amber-800 hover:bg-amber-100" onclick="return confirm('Record that no refund is due?')">No refund due</button>
                </form>
            </div>
        </div>
        @elseif($reservation->refund_status)
        <p class="text-sm text-gray-600">Refund: <strong>{{ ucfirst($reservation->refund_status) }}</strong></p>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Actions</h3>

            <div class="space-y-3">
                @if($lifecycle->canTransition($reservation, 'confirmed'))
                <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="w-full px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Confirm Reservation</button>
                </form>
                @endif

                @if($lifecycle->canTransition($reservation, 'checked_in'))
                    @if(!$arrivalDue)
                    <p class="text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i>Check-in opens on {{ $reservation->check_in->format('j M Y') }}.</p>
                    @else
                    <form method="POST" action="{{ route('admin.reservations.checkin', $reservation) }}">
                        @csrf
                        @method('PATCH')
                        @if($availableRooms->isNotEmpty())
                        <label class="block text-sm text-gray-600 mb-1" for="room_id">Assign room</label>
                        <select id="room_id" name="room_id" class="w-full border rounded-lg px-3 py-2 mb-3">
                            <option value="">Select an available room</option>
                            @foreach($availableRooms as $room)
                            <option value="{{ $room->id }}">
                                {{ $room->room_number }}{{ $room->building ? ' · '.$room->building : '' }} · {{ ucfirst($room->status) }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                        @unless($isPaid)
                        <input type="hidden" name="allow_unpaid" value="1">
                        <p class="text-sm text-amber-700 mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>No payment recorded for this booking.</p>
                        @endunless
                        <button class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                            @unless($isPaid) onclick="return confirm('No payment is recorded. Check this guest in anyway?')" @endunless>
                            {{ $isPaid ? 'Check In Guest' : 'Check In Unpaid Guest' }}
                        </button>
                    </form>
                    @endif
                @endif

                @if($lifecycle->canTransition($reservation, 'checked_out'))
                <form method="POST" action="{{ route('admin.reservations.checkout', $reservation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="w-full px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Check Out Guest</button>
                </form>
                @endif

                @if($lifecycle->canTransition($reservation, 'no_show') && $arrivalDue)
                <form method="POST" action="{{ route('admin.reservations.no-show', $reservation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" onclick="return confirm('Mark this guest as a no-show?')">Mark as No-show</button>
                </form>
                @endif

                @if($lifecycle->canTransition($reservation, 'cancelled'))
                <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <textarea name="reason" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Cancellation reason (optional)"></textarea>
                    <button class="w-full px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700" onclick="return confirm('Cancel this reservation? The guest will be notified.')">Cancel Reservation</button>
                </form>
                @endif

                @if(empty(\App\Services\ReservationLifecycle::TRANSITIONS[$reservation->booking_status]))
                <p class="text-sm text-gray-500">This booking is closed. No further actions are available.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">History</h3>
            <ol class="space-y-3 text-sm">
                <li><span class="text-gray-500">Booked</span> {{ $reservation->created_at->format('j M Y, g:i A') }}</li>
                @foreach([
                    ['Confirmed', $reservation->confirmed_at, $reservation->confirmedBy],
                    ['Checked in', $reservation->checked_in_at, $reservation->checkedInBy],
                    ['Checked out', $reservation->checked_out_at, $reservation->checkedOutBy],
                    ['Cancelled', $reservation->cancelled_at, $reservation->cancelledBy],
                ] as [$label, $at, $by])
                    @if($at)
                    <li><span class="text-gray-500">{{ $label }}</span> {{ $at->format('j M Y, g:i A') }}@if($by) by <strong>{{ $by->name }}</strong>@endif</li>
                    @endif
                @endforeach
                @if($reservation->cancellation_reason)
                <li class="text-gray-600">Reason: {{ $reservation->cancellation_reason }}</li>
                @endif
            </ol>
        </div>
    </div>
</div>
@endsection
