@extends('layouts.customer')

@section('page_title', 'My Bookings')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-4 md:mb-6 text-xl md:text-2xl font-semibold">My Reservations</h1>
        
        <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
            <table class="min-w-full divide-y divide-gray-200 mobile-card-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reservations as $reservation)
                    <tr>
                        <td class="px-6 py-4" data-label="Property">
                            <a href="{{ route('customer.reservations.show', $reservation) }}" class="text-blue-600 hover:underline">
                                {{ $reservation->property->name ?? 'N/A' }}
                            </a>
                            <div class="text-sm text-gray-500">Code: {{ $reservation->confirmation_code }}</div>
                        </td>
                        <td class="px-6 py-4" data-label="Check-in">{{ $reservation->check_in->format('M d, Y') }}</td>
                        <td class="px-6 py-4" data-label="Check-out">{{ $reservation->check_out->format('M d, Y') }}</td>
                        <td class="px-6 py-4" data-label="Total">₦{{ number_format($reservation->total_price) }}</td>
                        <td class="px-6 py-4" data-label="Status">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $reservation->status == 'confirmed' ? 'bg-green-100 text-green-800' : 
                                   ($reservation->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($reservation->status == 'cancelled' ? 'bg-red-100 text-red-800' : 
                                   ($reservation->status == 'checked_out' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) }}">
                                {{ ucfirst($reservation->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4" data-label="Actions">
                            <a href="{{ route('customer.reservations.show', $reservation) }}" class="text-blue-600 hover:underline">View</a>
                            @if(in_array($reservation->status, ['pending', 'confirmed']) && $reservation->check_in->isAfter(today()))
                            <a href="{{ route('customer.reservations.show', $reservation) }}" class="text-red-600 hover:underline ml-2">Cancel</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No reservations yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $reservations->links() }}
        </div>
    </div>
</div>
@endsection
