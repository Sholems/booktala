<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\HousekeepingInspectionStoreRequest;
use App\Models\HousekeepingInspection;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class HousekeepingInspectionController extends Controller
{
    public function create(Task $task)
    {
        abort_unless((int) $task->assigned_to === (int) Auth::id(), 403);
        $task->load('property');

        $checklist = HousekeepingInspection::defaultChecklist();

        return view('staff.inspections.create', compact('task', 'checklist'));
    }

    public function store(HousekeepingInspectionStoreRequest $request, Task $task)
    {
        abort_unless((int) $task->assigned_to === (int) Auth::id(), 403);

        $validated = $request->validated();

        $beforePhotos = [];
        $afterPhotos = [];

        if ($request->hasFile('before_images')) {
            foreach ($request->file('before_images') as $file) {
                $path = $file->store('housekeeping_inspections', 'public');
                $beforePhotos[] = '/storage/'.$path;
            }
        }
        if (! empty($validated['before_urls'])) {
            $urls = array_filter(array_map('trim', explode("\n", $validated['before_urls'])));
            $beforePhotos = array_merge($beforePhotos, $urls);
        }

        if ($request->hasFile('after_images')) {
            foreach ($request->file('after_images') as $file) {
                $path = $file->store('housekeeping_inspections', 'public');
                $afterPhotos[] = '/storage/'.$path;
            }
        }
        if (! empty($validated['after_urls'])) {
            $urls = array_filter(array_map('trim', explode("\n", $validated['after_urls'])));
            $afterPhotos = array_merge($afterPhotos, $urls);
        }

        $inspection = HousekeepingInspection::create([
            'task_id' => $task->id,
            'unit_id' => $task->property_id,
            'cleaner_id' => Auth::id(),
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => $validated['checklist'],
            'before_photos' => $beforePhotos,
            'after_photos' => $afterPhotos,
            'cleaner_notes' => $validated['cleaner_notes'] ?? null,
            'submitted_at' => now(),
        ]);

        $task->update([
            'status' => 'in_progress',
            'notes' => ($task->notes ? $task->notes."\n" : '').'Inspection checklist & photos submitted for supervisor review at '.now()->format('g:i A'),
        ]);

        return redirect()->route('staff.tasks.show', $task)
            ->with('success', 'Inspection checklist and photos submitted. Awaiting supervisor approval.');
    }
}
