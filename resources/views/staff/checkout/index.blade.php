@extends('layouts.staff')

@section('page_title', 'Check-out')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-semibold">Check-out Management</h1>
        
        <div class="mb-8">
            <h2 class="mb-4 text-xl font-semibold">Today's Check-outs</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($todayCheckOuts as $reservation)
                        <tr>
                            <td class="px-6 py-4">{{ $reservation->user->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $reservation->property->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $reservation->check_in->format('M d, Y') }}</td>
                            <td class="px-6 py-4">{{ $reservation->check_out->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('staff.checkout.process', $reservation) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 text-white bg-orange-600 rounded text-sm hover:bg-orange-700">Check Out</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No check-outs scheduled for today</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
