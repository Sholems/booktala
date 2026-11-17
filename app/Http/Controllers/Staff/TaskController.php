<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::where('assigned_to', Auth::id())
            ->with('property', 'assignedBy', 'latestTaskNote.user')
            ->withCount('taskNotes')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->paginate(20);

        return view('staff.tasks.index', compact('tasks'));
    }

    public function show(Task $task)
    {
        $this->ensureAssigned($task);
        $task->load('property', 'assignedBy', 'images', 'taskNotes.user');

        return view('staff.tasks.show', compact('task'));
    }

    public function start(Task $task)
    {
        $this->ensureAssigned($task);

        if ($task->status !== 'pending') {
            return back()->with('error', 'Only tasks that have not been started can be started.');
        }

        $task->update(['status' => 'in_progress']);

        return back()->with('success', 'Task started');
    }

    public function complete(Task $task)
    {
        $this->ensureAssigned($task);

        if ($task->status === 'completed') {
            return back()->with('error', 'This task is already complete.');
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Task completed');
    }

    public function addNotes(Request $request, Task $task)
    {
        $this->ensureAssigned($task);
        $validated = $request->validate(['notes' => 'required|string|max:1000']);

        $task->taskNotes()->create([
            'user_id' => Auth::id(),
            'body' => trim($validated['notes']),
        ]);

        return back()->with('success', 'Note added');
    }

    /**
     * Staff may only see and work on tasks assigned to them.
     */
    private function ensureAssigned(Task $task): void
    {
        abort_unless((int) $task->assigned_to === (int) Auth::id(), 403);
    }
}
