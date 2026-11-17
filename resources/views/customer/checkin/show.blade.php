@extends('layouts.customer')

@section('title', 'Check-in Details')
@section('page_title', 'Pre Check-in')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $checkin->reservation->property->title }}</h2>
                <p class="text-gray-500 text-sm mt-1">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    {{ $checkin->reservation->check_in->format('M j, Y') }} - {{ $checkin->reservation->check_out->format('M j, Y') }}
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-bold 
                @if($checkin->status === 'completed') bg-green-100 text-green-700
                @elseif($checkin->status === 'pending') bg-yellow-100 text-yellow-700
                @else bg-gray-100 text-gray-600 @endif">
                {{ ucfirst($checkin->status) }}
            </span>
        </div>
    </div>

    <div class="grid gap-6">
        <!-- Guest Info -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-user text-[#E11D2E]"></i> Guest Information
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Name:</span> <span class="font-medium">{{ $checkin->guest_first_name }} {{ $checkin->guest_last_name }}</span></div>
                <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $checkin->guest_email }}</span></div>
                <div><span class="text-gray-500">Phone:</span> <span class="font-medium">{{ $checkin->guest_phone }}</span></div>
            </div>
        </div>

        <!-- Identity Info -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-id-card text-[#E11D2E]"></i> Identity Information
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">ID Type:</span> <span class="font-medium capitalize">{{ str_replace('_', ' ', $checkin->id_type) }}</span></div>
                <div><span class="text-gray-500">ID Number:</span> <span class="font-medium">{{ $checkin->id_number }}</span></div>
            </div>
        </div>

        <!-- Arrival -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-plane-arrival text-[#E11D2E]"></i> Arrival Details
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Flight:</span> <span class="font-medium">{{ $checkin->arrival_flight ?? 'Not provided' }}</span></div>
                <div><span class="text-gray-500">Arrival Time:</span> <span class="font-medium">{{ $checkin->arrival_time ? \Carbon\Carbon::parse($checkin->arrival_time)->format('g:i A') : 'Not provided' }}</span></div>
            </div>
        </div>

        <!-- Guests & Vehicle -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-users text-[#E11D2E]"></i> Guests & Vehicle
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Adults:</span> <span class="font-medium">{{ $checkin->num_adults }}</span></div>
                <div><span class="text-gray-500">Children:</span> <span class="font-medium">{{ $checkin->num_children ?? 0 }}</span></div>
                <div><span class="text-gray-500">Vehicle Plate:</span> <span class="font-medium">{{ $checkin->vehicle_plate ?? 'Not provided' }}</span></div>
                <div><span class="text-gray-500">Special Requests:</span> <span class="font-medium">{{ $checkin->special_requests ?? 'None' }}</span></div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-phone-alt text-[#E11D2E]"></i> Emergency Contact
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Name:</span> <span class="font-medium">{{ $checkin->emergency_contact_name }}</span></div>
                <div><span class="text-gray-500">Phone:</span> <span class="font-medium">{{ $checkin->emergency_contact_phone }}</span></div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('customer.checkin.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <a href="{{ route('customer.checkin.create', $checkin->reservation) }}" class="px-6 py-3 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A] transition">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
    </div>
</div>
@endsection