@extends('layouts.staff')

@section('page_title', 'Online Check-in')

@section('content')
@php $online = $reservation->guestCheckin; @endphp
<div class="py-6">
    <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
        <a href="{{ route('staff.checkin.index') }}" class="text-blue-600 hover:underline">&larr; Back to arrivals</a>

        <div class="mt-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ $reservation->user->name ?? 'Guest' }}</h1>
                <p class="text-sm text-gray-500">{{ $reservation->booking_ref }} · {{ $reservation->property->name ?? '' }} · {{ $reservation->check_in->format('M d') }} – {{ $reservation->check_out->format('M d, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @include('staff.checkin._online_status')
                @if($reservation->booking_status === 'confirmed')
                    @include('staff.checkin._action')
                @else
                    <span class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $reservation->booking_status)) }}</span>
                @endif
            </div>
        </div>

        @if(!$online)
        <div class="bg-white rounded-lg shadow p-6 text-gray-600">The guest has not completed online check-in. Collect their details and ID at the desk.</div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Guest details</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Name</dt><dd class="font-medium text-right">{{ $online->guest_first_name }} {{ $online->guest_last_name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Phone</dt><dd class="font-medium text-right">{{ $online->guest_phone }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Email</dt><dd class="font-medium text-right break-all">{{ $online->guest_email }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Guests</dt><dd class="font-medium text-right">{{ $online->num_adults }} adults, {{ $online->num_children ?? 0 }} children</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Expected arrival</dt><dd class="font-medium text-right">{{ $online->arrival_time ?: 'Not given' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Vehicle plate</dt><dd class="font-medium text-right">{{ $online->vehicle_plate ?: 'None' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Emergency contact</dt><dd class="font-medium text-right">{{ $online->emergency_contact_name }} ({{ $online->emergency_contact_phone }})</dd></div>
                </dl>
                @if($online->special_requests)
                <div class="mt-4 rounded bg-amber-50 p-3 text-sm text-amber-900"><strong>Special requests:</strong> {{ $online->special_requests }}</div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Identification</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">ID type</dt><dd class="font-medium">{{ \App\Models\GuestCheckin::idTypes()[$online->id_type] ?? $online->id_type }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">ID number</dt><dd class="font-medium">{{ $online->id_number }}</dd></div>
                </dl>

                @if($online->isVerified())
                <p class="mt-6 text-sm text-green-700"><i class="fas fa-check-circle mr-1"></i>Verified by {{ $online->verifiedBy->name ?? 'staff' }} on {{ $online->verified_at->format('M d, Y g:i A') }}.</p>
                @else
                <p class="mt-6 text-sm text-gray-600">Check the guest's physical ID matches these details before verifying.</p>
                <form method="POST" action="{{ route('staff.checkin.verify', $reservation) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700" onclick="return confirm('Confirm the guest\'s ID matches these details?')">Mark ID as verified</button>
                </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
