<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaskAddNoteRequest;
use App\Http\Requests\Admin\TaskStoreRequest;
use App\Http\Requests\Admin\TaskUpdateRequest;
use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $show = $request->query('show', 'open');

        $tasks = Task::with(['property', 'assignedTo', 'latestTaskNote.user'])
            ->withCount('taskNotes')
            ->when($show === 'open', fn ($q) => $q->where('status', '!=', 'completed'))
            ->when($show === 'unassigned', fn ($q) => $q->where('status', '!=', 'completed')->whereNull('assigned_to'))
            ->when($show === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->orderByRaw("CASE status WHEN 'completed' THEN 2 ELSE 1 END")
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();
        $properties = Property::active()->get();
        $staff = User::role('staff')->where('status', 'active')->orderBy('name')->get();

        return view('admin.tasks.index', compact('tasks', 'properties', 'staff', 'show'));
    }

    public function store(TaskStoreRequest $request)
    {
        $validated = $request->validated();

        Task::create($validated + [
            'assigned_by' => auth()->id(),
            'status' => 'pending',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? today(),
        ]);

        return back()->with('success', 'Task created');
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $validated = $request->validated();

        if (array_key_exists('status', $validated)) {
            $validated['completed_at'] = $validated['status'] === 'completed' ? ($task->completed_at ?? now()) : null;
        }

        $task->update($validated);

        return back()->with('success', 'Task updated');
    }

    public function show(Task $task)
    {
        $task->load(['property', 'assignedTo', 'assignedBy', 'reservation', 'taskNotes.user']);
        $staff = User::role('staff')->where('status', 'active')->orderBy('name')->get();

        return view('admin.tasks.show', compact('task', 'staff'));
    }

    /**
     * Reply in the task's note thread; the assigned staff member sees it on their task.
     */
    public function addNote(TaskAddNoteRequest $request, Task $task)
    {
        $validated = $request->validated();

        $task->taskNotes()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['notes']),
        ]);

        return back()->with('success', 'Reply added'.($task->assignedTo ? ' for '.$task->assignedTo->name : ''));
    }
}
