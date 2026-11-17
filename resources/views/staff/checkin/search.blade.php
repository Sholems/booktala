@extends('layouts.staff')

@section('page_title', 'Search Guest')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-semibold">Check-in Search Results</h1>
        
        <div class="mb-6">
            <form action="{{ route('staff.checkin.search') }}" method="GET" class="flex gap-4">
                <input type="text" name="search" class="flex-1 p-2 border rounded" placeholder="Search by name, email, or confirmation code..." value="{{ $search ?? '' }}">
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Search</button>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reservations as $reservation)
                    <tr>
                        <td class="px-6 py-4">
                            <div>{{ $reservation->user->name ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ $reservation->user->email ?? '' }}</div>
                            <div class="text-sm text-gray-500">Code: {{ $reservation->confirmation_code }}</div>
                            <div class="mt-1">@include('staff.checkin._online_status')</div>
                        </td>
                        <td class="px-6 py-4">{{ $reservation->property->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{{ $reservation->check_in->format('M d, Y') }}</td>
                        <td class="px-6 py-4">{{ $reservation->check_out->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $reservation->status == 'checked_in' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ ucfirst($reservation->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($reservation->status == 'confirmed')
@include('staff.checkin._action')
                            @elseif($reservation->status == 'checked_in')
                            <span class="text-green-600 font-medium">Checked In</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No reservations found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            <a href="{{ route('staff.checkin.index') }}" class="text-blue-600 hover:underline">&larr; Back to Check-in</a>
        </div>
    </div>
</div>
@endsection
