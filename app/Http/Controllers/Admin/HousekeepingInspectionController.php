<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HousekeepingInspectionApproveRequest;
use App\Http\Requests\Admin\HousekeepingInspectionRejectRequest;
use App\Http\Requests\Admin\HousekeepingInspectionStoreRequest;
use App\Models\HousekeepingInspection;
use App\Models\Unit;
use Illuminate\Http\Request;

class HousekeepingInspectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending_review');

        $inspections = HousekeepingInspection::with(['unit.site', 'cleaner', 'supervisor', 'task'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->integer('unit_id')))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending_review' => HousekeepingInspection::where('status', 'pending_review')->count(),
            'approved_today' => HousekeepingInspection::where('status', 'approved')->whereDate('reviewed_at', today())->count(),
            'rejected' => HousekeepingInspection::where('status', 'rejected')->count(),
        ];

        $units = Unit::query()->orderBy('title')->get();

        return view('admin.inspections.index', compact('inspections', 'stats', 'units', 'status'));
    }

    public function show(HousekeepingInspection $inspection)
    {
        $inspection->load(['unit.site', 'cleaner', 'supervisor', 'task']);

        return view('admin.inspections.show', compact('inspection'));
    }

    public function approve(HousekeepingInspectionApproveRequest $request, HousekeepingInspection $inspection)
    {
        $validated = $request->validated();

        $inspection->approve($request->user(), $validated['notes'] ?? null);

        return back()->with('success', "Room {$inspection->unit?->title} inspection approved! Unit is now AVAILABLE for guests.");
    }

    public function reject(HousekeepingInspectionRejectRequest $request, HousekeepingInspection $inspection)
    {
        $validated = $request->validated();

        $inspection->reject($request->user(), $validated['notes']);

        return back()->with('error', "Room {$inspection->unit?->title} inspection rejected. Rework notes sent to cleaner; unit remains in CLEANING.");
    }

    public function store(HousekeepingInspectionStoreRequest $request)
    {
        $validated = $request->validated();

        $beforePhotos = $validated['before_photos'] ?? [];
        $afterPhotos = $validated['after_photos'] ?? [];

        // Handle uploaded image files
        if ($request->hasFile('before_images')) {
            foreach ($request->file('before_images') as $file) {
                $path = $file->store('housekeeping_inspections', 'public');
                $beforePhotos[] = '/storage/'.$path;
            }
        }

        if ($request->hasFile('after_images')) {
            foreach ($request->file('after_images') as $file) {
                $path = $file->store('housekeeping_inspections', 'public');
                $afterPhotos[] = '/storage/'.$path;
            }
        }

        $checklist = ! empty($validated['checklist']) ? $validated['checklist'] : HousekeepingInspection::defaultChecklist();

        $inspection = HousekeepingInspection::create([
            'unit_id' => $validated['unit_id'],
            'cleaner_id' => $validated['cleaner_id'],
            'task_id' => $validated['task_id'] ?? null,
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => $checklist,
            'before_photos' => $beforePhotos,
            'after_photos' => $afterPhotos,
            'cleaner_notes' => $validated['cleaner_notes'] ?? null,
            'submitted_at' => now(),
        ]);

        return redirect()->route('admin.inspections.show', $inspection)
            ->with('success', 'Housekeeping inspection submitted and queued for supervisor review.');
    }
}
