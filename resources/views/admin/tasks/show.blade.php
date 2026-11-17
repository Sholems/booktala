@extends('layouts.admin')
@section('page_title', 'Task')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Back to tasks</a>
    <h2 class="mt-1 text-2xl font-bold">{{ $task->title }}</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        @include('partials.task-thread', ['postRoute' => route('admin.tasks.notes', $task)])
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Details</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Apartment</dt><dd class="font-medium text-right">{{ $task->property->title ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $task->type)) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Priority</dt><dd class="font-medium">{{ ucfirst($task->priority) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Due</dt><dd class="font-medium">{{ $task->due_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Created by</dt><dd class="font-medium text-right">{{ $task->assignedBy->name ?? 'System' }}</dd></div>
                @if($task->reservation)
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Booking</dt><dd><a href="{{ route('admin.reservations.show', $task->reservation) }}" class="font-medium text-primary hover:underline">{{ $task->reservation->booking_ref }}</a></dd></div>
                @endif
                @if($task->completed_at)
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Completed</dt><dd class="font-medium">{{ $task->completed_at->format('M d, g:i A') }}</dd></div>
                @endif
            </dl>
            @if($task->description)
            <p class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $task->description }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.tasks.update', $task) }}" class="bg-white rounded-lg shadow p-6 space-y-3">
            @csrf
            @method('PATCH')
            <h3 class="text-lg font-bold">Assignment</h3>
            <label class="block text-sm text-gray-600">Assigned to
                <select name="assigned_to" class="mt-1 w-full border rounded px-3 py-2">
                    <option value="">Unassigned</option>
                    @foreach($staff as $member)
                    <option value="{{ $member->id }}" @selected($task->assigned_to === $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm text-gray-600">Status
                <select name="status" class="mt-1 w-full border rounded px-3 py-2">
                    @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="w-full bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">Save</button>
        </form>
    </div>
</div>
@endsection
