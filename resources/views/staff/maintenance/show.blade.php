@extends('layouts.staff')

@section('page_title', 'Maintenance Report')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('staff.maintenance.index') }}" class="text-blue-600 hover:underline">&larr; Back to Maintenance</a>
        </div>
        
        <div class="p-6 bg-white rounded-lg shadow">
            <h1 class="mb-4 text-2xl font-semibold">{{ $report->title }}</h1>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <span class="text-gray-500">Property:</span>
                    <span class="ml-2 font-medium">{{ $report->property->name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Severity:</span>
                    <span class="px-2 py-1 ml-2 text-xs font-semibold rounded-full 
                        {{ $report->severity == 'emergency' ? 'bg-red-100 text-red-800' : 
                           ($report->severity == 'high' ? 'bg-orange-100 text-orange-800' : 
                           ($report->severity == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) }}">
                        {{ ucfirst($report->severity) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Status:</span>
                    <span class="px-2 py-1 ml-2 text-xs font-semibold rounded-full 
                        {{ $report->status == 'completed' ? 'bg-green-100 text-green-800' : 
                           ($report->status == 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($report->status) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Reported:</span>
                    <span class="ml-2 font-medium">{{ $report->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="mb-2 font-semibold">Description</h3>
                <p class="text-gray-700">{{ $report->description }}</p>
            </div>
            
            @if($report->images->count() > 0)
            <div>
                <h3 class="mb-2 font-semibold">Images</h3>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach($report->images as $image)
                    <a href="{{ url('media/' . ltrim($image->image_path, '/')) }}" target="_blank">
                        <img src="{{ url('media/' . ltrim($image->image_path, '/')) }}" class="rounded hover:opacity-80">
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
