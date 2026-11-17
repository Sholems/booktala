@extends('layouts.admin')
@section('page_title', 'Manage Rooms - ' . $roomType->name)
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <a href="{{ route('admin.room-types.index') }}" class="text-primary hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>Back to Room Types
        </a>
        <h2 class="text-2xl font-bold mt-2">{{ $roomType->name }} - Rooms</h2>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Room Number</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Floor</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Building</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Current Guest</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($rooms as $room)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap font-medium">Room {{ $room->room_number }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $room->floor }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $room->building ?? '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                    $statusColors = [
                        'available' => 'bg-green-100 text-green-800',
                        'occupied' => 'bg-yellow-100 text-yellow-800',
                        'reserved' => 'bg-blue-100 text-blue-800',
                        'maintenance' => 'bg-red-100 text-red-800',
                        'cleaning' => 'bg-purple-100 text-purple-800',
                    ];
                    @endphp
                    <span class="px-3 py-1 text-xs rounded-full {{ $statusColors[$room->status] }}">
                        {{ ucfirst($room->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($room->currentReservation)
                    <a href="{{ route('admin.reservations.show', $room->currentReservation) }}" class="text-primary hover:underline">
                        {{ $room->currentReservation->user->name }}
                    </a>
                    @else
                    <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <form method="POST" action="{{ route('admin.rooms.update-status', $room) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <select name="status" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                            <option value="available" {{ $room->status == 'available' ? 'selected' : '' }}>Available</option>
                            <option value="occupied" {{ $room->status == 'occupied' ? 'selected' : '' }}>Occupied</option>
                            <option value="reserved" {{ $room->status == 'reserved' ? 'selected' : '' }}>Reserved</option>
                            <option value="maintenance" {{ $room->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="cleaning" {{ $room->status == 'cleaning' ? 'selected' : '' }}>Cleaning</option>
                        </select>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No rooms found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <div class="text-2xl font-bold text-green-600">{{ $rooms->where('status', 'available')->count() }}</div>
        <div class="text-sm text-gray-600">Available</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ $rooms->where('status', 'occupied')->count() }}</div>
        <div class="text-sm text-gray-600">Occupied</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $rooms->where('status', 'reserved')->count() }}</div>
        <div class="text-sm text-gray-600">Reserved</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <div class="text-2xl font-bold text-red-600">{{ $rooms->where('status', 'maintenance')->count() }}</div>
        <div class="text-sm text-gray-600">Maintenance</div>
    </div>
</div>
@endsection