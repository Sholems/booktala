@extends('layouts.customer')

@section('title', 'Book Event Space')
@section('page_title', 'Event Booking')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('customer.event-bookings.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Event Space Selection -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-building text-accent"></i>
                Select Event Space
            </h3>
            
            @if($spaces->isEmpty())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-700">No event spaces available at the moment.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($spaces as $space)
                    <label class="cursor-pointer">
                        <input type="radio" name="event_space_id" value="{{ $space->id }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                        <div class="p-4 border border-gray-200 rounded-lg peer-checked:border-accent peer-checked:bg-accent/10 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ $space->name }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">{{ $space->description }}</p>
                                    <p class="text-sm text-gray-500">
                                        <i class="fas fa-users mr-1"></i> Capacity: {{ $space->capacity }} | 
                                        <i class="fas fa-clock mr-1"></i> ₦{{ number_format($space->hourly_rate) }}/hr
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Event Details -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-calendar-day text-accent"></i>
                Event Details
            </h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Name *</label>
                    <input type="text" name="event_name" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Type *</label>
                    <select name="event_type" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="">Select type</option>
                        @foreach($eventTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Date *</label>
                        <input type="date" name="event_date" required min="{{ date('Y-m-d') }}"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Guests *</label>
                        <input type="number" name="num_guests" required min="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
                        <input type="time" name="start_time" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time *</label>
                        <input type="time" name="end_time" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Special Requests</label>
                    <textarea name="notes" rows="3" placeholder="Any special requirements..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('customer.event-bookings.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-light transition" {{ $spaces->isEmpty() ? 'disabled' : '' }}>
                <i class="fas fa-calendar-check mr-2"></i>
                Submit Booking
            </button>
        </div>
    </form>
</div>
@endsection