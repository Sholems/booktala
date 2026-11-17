@extends('layouts.customer')

@section('title', 'Pre Check-in')
@section('page_title', 'Pre Check-in')

@section('content')
<div class="space-y-6">
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-800">Complete Your Pre Check-in</h3>
                <p class="text-blue-700 mt-1">Save time at reception by completing your check-in details online before you arrive.</p>
            </div>
        </div>
    </div>

    @if($upcomingCheckins->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-calendar-check text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">No Upcoming Check-ins</h3>
        <p class="text-gray-500">You don't have any upcoming reservations that require check-in.</p>
        <a href="{{ route('properties.index') }}" class="inline-block mt-4 px-6 py-3 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A] transition">
            Browse Properties
        </a>
    </div>
    @else
    <div class="grid gap-4">
        @foreach($upcomingCheckins as $reservation)
        <div class="bg-white rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-800">{{ $reservation->property->title }}</h3>
                <p class="text-gray-500 text-sm mt-1">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ $reservation->check_in->format('M j, Y') }} - {{ $reservation->check_out->format('M j, Y') }}
                </p>
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-user mr-2"></i>
                    {{ $reservation->guests }} guest(s)
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('customer.checkin.create', $reservation) }}" class="px-6 py-3 bg-[#E11D2E] text-white font-bold rounded-lg hover:bg-[#C21627] transition text-center">
                    <i class="fas fa-clipboard-list mr-2"></i>
                    Complete Check-in
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Recent Check-ins -->
    @php
        $completedCheckins = \App\Models\GuestCheckin::whereHas('reservation', function($q) {
            $q->where('user_id', auth()->id());
        })->with('reservation.property')->latest()->limit(5)->get();
    @endphp

    @if($completedCheckins->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Previous Check-ins</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($completedCheckins as $checkin)
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800">{{ $checkin->reservation->property->title }}</p>
                    <p class="text-sm text-gray-500">{{ $checkin->reservation->check_in->format('M j, Y') }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                    @if($checkin->status === 'completed') bg-green-100 text-green-700
                    @elseif($checkin->status === 'pending') bg-yellow-100 text-yellow-700
                    @else bg-gray-100 text-gray-600 @endif">
                    {{ ucfirst($checkin->status) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection