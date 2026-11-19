@extends('layouts.admin')

@section('title', 'Event Spaces')
@section('page_title', 'Event Spaces')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Event Spaces</h2>
            <p class="text-gray-500 text-sm">Manage event venues and spaces</p>
        </div>
        <a href="{{ route('admin.event-spaces.create') }}" class="px-5 py-2.5 bg-accent text-white font-bold rounded-lg hover:bg-accent-dark transition">
            <i class="fas fa-plus mr-2"></i>Add Space
        </a>
    </div>

    @if($spaces->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <p class="text-gray-500">No event spaces created yet.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($spaces as $space)
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">{{ $space->name }}</h3>
                    <span class="text-xs px-2 py-1 rounded-full {{ $space->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $space->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.event-spaces.edit', $space) }}" class="text-gray-400 hover:text-accent">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.event-spaces.destroy', $space) }}" method="POST" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-4">{{ $space->description }}</p>
            <div class="flex justify-between text-sm">
                <div>
                    <span class="text-gray-500">Capacity</span>
                    <p class="font-medium text-gray-800">{{ $space->capacity }} guests</p>
                </div>
                <div class="text-right">
                    <span class="text-gray-500">Hourly Rate</span>
                    <p class="font-medium text-gray-800">₦{{ number_format($space->hourly_rate) }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection