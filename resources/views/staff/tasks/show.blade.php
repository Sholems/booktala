@extends('layouts.staff')

@section('page_title', 'Task Details')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('staff.tasks.index') }}" class="text-blue-600 hover:underline">&larr; Back to Tasks</a>
        </div>
        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <div class="p-6 bg-white rounded-lg shadow">
                    <h1 class="mb-4 text-2xl font-semibold">{{ $task->title }}</h1>
                    <p class="mb-4 text-gray-600">{{ $task->description }}</p>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-gray-500">Property:</span>
                            <span class="ml-2 font-medium">{{ $task->property->title ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Due Date:</span>
                            <span class="ml-2 font-medium">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Status:</span>
                            <span class="px-2 py-1 ml-2 text-xs font-semibold rounded-full 
                                {{ $task->status == 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($task->status == 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </div>
                    </div>
                    
                    @if($task->status == 'pending')
                    <form action="{{ route('staff.tasks.start', $task) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Start Task</button>
                    </form>
                    @elseif($task->status == 'in_progress')
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('staff.inspections.create', $task) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded font-bold hover:bg-primary-light text-sm shadow">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Complete Inspection Checklist & Photos</span>
                        </a>
                        <form action="{{ route('staff.tasks.complete', $task) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700 text-sm font-semibold">Mark Complete</button>
                        </form>
                    </div>
                    @endif
                </div>
                
                <div class="mt-6 p-6 bg-white rounded-lg shadow">
                    @include('partials.task-thread', ['postRoute' => route('staff.tasks.notes', $task)])
                </div>
            </div>
            
            <div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="mb-4 text-xl font-semibold">Task Images</h2>
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        @forelse($task->images as $image)
                        <img src="{{ url('media/' . ltrim($image->image_path, '/')) }}" class="rounded">
                        @empty
                        <p class="text-gray-500 col-span-2">No images</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
