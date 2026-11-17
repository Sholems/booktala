@extends('layouts.admin')
@section('page_title', 'Tasks')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold">All Tasks</h2>
    <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i> Create Task
    </button>
</div>

<div id="createTaskModal" class="hidden mb-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Create New Task</h3>
    <form method="POST" action="{{ route('admin.tasks.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Title</label>
                <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Property</label>
                <select name="property_id" class="w-full border rounded px-3 py-2">
                    <option value="">Select Property</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Assigned To</label>
                <select name="assigned_to" class="w-full border rounded px-3 py-2">
                    <option value="">Select Staff</option>
                    @foreach($staff as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Priority</label>
                <select name="priority" class="w-full border rounded px-3 py-2">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Type</label>
                <select name="type" class="w-full border rounded px-3 py-2">
                    <option value="cleaning">Cleaning</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="checkin_prep">Prepare for check-in</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Due Date</label>
                <input type="date" name="due_date" class="w-full border rounded px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2" rows="3"></textarea>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Create Task</button>
            <button type="button" onclick="document.getElementById('createTaskModal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
        </div>
    </form>
</div>

<div class="mb-4 inline-flex rounded-lg border bg-white p-1 text-sm">
    @foreach(['open' => 'Open', 'unassigned' => 'Unassigned', 'completed' => 'Completed', 'all' => 'All'] as $key => $label)
    <a href="{{ route('admin.tasks.index', ['show' => $key]) }}" class="px-4 py-1.5 rounded {{ ($show ?? 'open') === $key ? 'bg-primary text-white' : 'text-gray-600 hover:text-primary' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tasks as $task)
            <tr>
                <td class="px-6 py-4" data-label="Title">
                    <a href="{{ route('admin.tasks.show', $task) }}" class="font-medium text-primary hover:underline">{{ $task->title }}</a>
                    @if($task->task_notes_count)
                    <span class="ml-2 text-xs text-gray-500" title="Notes"><i class="far fa-comment"></i> {{ $task->task_notes_count }}</span>
                    @endif
                    @if($task->latestTaskNote && $task->latestTaskNote->user && !$task->latestTaskNote->isFromAdmin())
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">New note from staff</span>
                    @endif
                </td>
                <td class="px-6 py-4" data-label="Property">{{ $task->property->title ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Assigned To">
                    @if($task->status === 'completed')
                    {{ $task->assignedTo->name ?? 'Unassigned' }}
                    @else
                    <form method="POST" action="{{ route('admin.tasks.update', $task) }}">
                        @csrf
                        @method('PATCH')
                        <select name="assigned_to" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1 {{ $task->assigned_to ? '' : 'border-amber-400 bg-amber-50' }}" aria-label="Assign task">
                            <option value="">Unassigned</option>
                            @foreach($staff as $member)
                            <option value="{{ $member->id }}" @selected($task->assigned_to === $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </td>
                <td class="px-6 py-4" data-label="Due Date">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : ($task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($task->status) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No tasks found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tasks->links() }}</div>
@endsection
