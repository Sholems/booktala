@extends('layouts.staff')

@section('page_title', 'My Tasks')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-4 md:mb-6 text-xl md:text-2xl font-semibold">My Tasks</h1>
        
        <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
            <table class="min-w-full divide-y divide-gray-200 mobile-card-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tasks as $task)
                    <tr>
                        <td class="px-6 py-4" data-label="Task">
                            <a href="{{ route('staff.tasks.show', $task) }}" class="text-blue-600 hover:underline">{{ $task->title }}</a>
                            @if($task->task_notes_count)
                            <span class="ml-2 text-xs text-gray-500"><i class="far fa-comment"></i> {{ $task->task_notes_count }}</span>
                            @endif
                            @if($task->latestTaskNote?->isFromAdmin())
                            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">Admin replied</span>
                            @endif
                        </td>
                        <td class="px-6 py-4" data-label="Property">{{ $task->property->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4" data-label="Due Date">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td class="px-6 py-4" data-label="Status">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $task->status == 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($task->status == 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4" data-label="Actions">
                            @if($task->status == 'pending')
                            <form action="{{ route('staff.tasks.start', $task) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-blue-600 hover:underline">Start</button>
                            </form>
                            @elseif($task->status == 'in_progress')
                            <form action="{{ route('staff.tasks.complete', $task) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-green-600 hover:underline">Complete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No tasks assigned</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $tasks->links() }}
        </div>
    </div>
</div>
@endsection
