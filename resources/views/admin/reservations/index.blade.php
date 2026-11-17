@extends('layouts.admin')

@section('page_title', 'Reservations')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">{{ \App\Models\Reservation::QUEUES[request('queue')] ?? 'All Reservations' }}</h2>
    @if(isset(\App\Models\Reservation::QUEUES[request('queue')]))
    <a href="{{ route('admin.reservations.index') }}" class="text-sm text-primary hover:underline">&larr; Show all reservations</a>
    @endif
</div>

{{-- Search & Filters --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.reservations.index') }}" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by booking ref, guest name, property..." 
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:w-40">
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div class="md:w-40">
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary" placeholder="From Date">
        </div>
        <div class="md:w-40">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary" placeholder="To Date">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                <i class="fas fa-search"></i>
            </button>
            <a href="{{ route('admin.reservations.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking Ref</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check In</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check Out</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($reservations as $reservation)
            <tr>
                <td class="px-6 py-4" data-label="Booking Ref">{{ $reservation->booking_ref }}</td>
                <td class="px-6 py-4" data-label="Property">{{ $reservation->property->title ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Guest">{{ $reservation->user->name ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Check In">{{ $reservation->check_in->format('M d, Y') }}</td>
                <td class="px-6 py-4" data-label="Check Out">{{ $reservation->check_out->format('M d, Y') }}</td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full 
                        {{ $reservation->booking_status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $reservation->booking_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $reservation->booking_status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $reservation->booking_status === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $reservation->booking_status === 'checked_out' ? 'bg-gray-100 text-gray-800' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $reservation->booking_status)) }}
                    </span>
                </td>
                <td class="px-6 py-4" data-label="Actions">
                    <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="text-primary hover:underline">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No reservations found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $reservations->appends(request()->query())->links() }}
</div>
@endsection