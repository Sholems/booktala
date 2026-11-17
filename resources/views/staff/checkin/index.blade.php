@extends('layouts.staff')

@section('page_title', 'Check-in')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-semibold">Arrivals</h1>

        <div class="mb-6">
            <form action="{{ route('staff.checkin.search') }}" method="GET" class="flex gap-4">
                <input type="text" name="search" class="flex-1 p-2 border rounded" placeholder="Search by name, email, or booking reference...">
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Search</button>
            </form>
        </div>

        <div class="mb-8">
            <h2 class="mb-4 text-xl font-semibold">Due to arrive today</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
                <table class="min-w-full divide-y divide-gray-200 mobile-card-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apartment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Online check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($todayCheckIns as $reservation)
                        <tr>
                            <td class="px-6 py-4" data-label="Guest">
                                <div class="font-medium">{{ $reservation->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $reservation->booking_ref }}</div>
                            </td>
                            <td class="px-6 py-4" data-label="Apartment">{{ $reservation->property->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm" data-label="Stay">
                                {{ $reservation->check_in->format('M d') }} – {{ $reservation->check_out->format('M d, Y') }}
                                <div class="text-xs text-gray-500">{{ $reservation->guests }} {{ \Illuminate\Support\Str::plural('guest', $reservation->guests) }}</div>
                            </td>
                            <td class="px-6 py-4" data-label="Online check-in">
                                @include('staff.checkin._online_status')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Actions">
                                @include('staff.checkin._action')
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No arrivals due today</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
