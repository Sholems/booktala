@extends('layouts.customer')

@section('title', 'Complete Check-in')
@section('page_title', 'Pre Check-in')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">{{ $reservation->property->name }}</h2>
        <p class="text-gray-500 text-sm">
            <i class="fas fa-calendar-alt mr-1"></i>
            {{ $reservation->check_in->format('M j, Y') }} - {{ $reservation->check_out->format('M j, Y') }}
        </p>
    </div>

    @if($checkin)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
        <span class="text-yellow-700">You have already submitted a check-in form for this reservation. You can update it below.</span>
    </div>
    @endif

    <form action="{{ route('customer.checkin.store', $reservation) }}" method="POST" class="space-y-6">
        @csrf

        <!-- Guest Information -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-user text-accent"></i>
                Guest Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" name="guest_first_name" value="{{ old('guest_first_name', $checkin->guest_first_name ?? $user->name ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="guest_last_name" value="{{ old('guest_last_name', $checkin->guest_last_name ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="guest_email" value="{{ old('guest_email', $checkin->guest_email ?? $user->email ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                    <input type="text" name="guest_phone" value="{{ old('guest_phone', $checkin->guest_phone ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Identity Information -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-id-card text-accent"></i>
                Identity Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Type *</label>
                    <select name="id_type" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="">Select ID type</option>
                        <option value="passport" {{ old('id_type', $checkin->id_type ?? '') == 'passport' ? 'selected' : '' }}>Passport</option>
                        <option value="national_id" {{ old('id_type', $checkin->id_type ?? '') == 'national_id' ? 'selected' : '' }}>National ID</option>
                        <option value="drivers_license" {{ old('id_type', $checkin->id_type ?? '') == 'drivers_license' ? 'selected' : '' }}>Driver's License</option>
                        <option value="voters_card" {{ old('id_type', $checkin->id_type ?? '') == 'voters_card' ? 'selected' : '' }}>Voter's Card</option>
                        <option value="other" {{ old('id_type', $checkin->id_type ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Number *</label>
                    <input type="text" name="id_number" value="{{ old('id_number', $checkin->id_number ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Arrival Details -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-plane-arrival text-accent"></i>
                Arrival Details
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flight Number</label>
                    <input type="text" name="arrival_flight" value="{{ old('arrival_flight', $checkin->arrival_flight ?? '') }}" placeholder="e.g., BA123"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Arrival Time</label>
                    <input type="time" name="arrival_time" value="{{ old('arrival_time', $checkin->arrival_time ?? '') }}"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Guest Count -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-users text-accent"></i>
                Guest Count
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Number of Adults *</label>
                    <input type="number" name="num_adults" value="{{ old('num_adults', $checkin->num_adults ?? $reservation->guests) }}" required min="1"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Number of Children</label>
                    <input type="number" name="num_children" value="{{ old('num_children', $checkin->num_children ?? 0) }}" min="0"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Vehicle & Additional -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-car text-accent"></i>
                Vehicle & Additional Info
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Plate Number</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $checkin->vehicle_plate ?? '') }}" placeholder="e.g., ABC-1234-XX"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                    <input type="text" name="special_requests" value="{{ old('special_requests', $checkin->special_requests ?? '') }}" placeholder="Any special requests"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-phone-alt text-accent"></i>
                Emergency Contact
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name *</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $checkin->emergency_contact_name ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone *</label>
                    <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $checkin->emergency_contact_phone ?? '') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('customer.checkin.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-light transition">
                <i class="fas fa-save mr-2"></i>
                Submit Check-in
            </button>
        </div>
    </form>
</div>
@endsection