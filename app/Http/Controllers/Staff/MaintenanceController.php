<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index()
    {
        $priorityOrder = "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END";

        $myReports = MaintenanceReport::where('assigned_to', Auth::id())
            ->where('status', '!=', 'resolved')
            ->with('property', 'reportedBy')
            ->orderByRaw($priorityOrder)
            ->get();

        // Other open reports at this staff member's sites.
        $reports = MaintenanceReport::visibleTo(Auth::user())
            ->where('status', '!=', 'resolved')
            ->where(fn ($q) => $q->whereNull('assigned_to')->orWhere('assigned_to', '!=', Auth::id()))
            ->with('property', 'assignedTo')
            ->orderByRaw($priorityOrder)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $properties = Property::active()->visibleTo(Auth::user())->orderBy('title')->get();

        return view('staff.maintenance.index', compact('myReports', 'reports', 'properties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Staff report problems at their own sites only.
            'property_id' => ['required', Rule::in(PropertyAccess::visiblePropertyIds(Auth::user())->pluck('id'))],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent,emergency',
        ]);

        MaintenanceReport::create($validated + [
            'reported_by' => Auth::id(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Maintenance report submitted. An admin will assign it.');
    }

    /**
     * Staff update reports assigned to them: start work, or resolve with a note.
     */
    public function update(Request $request, MaintenanceReport $maintenanceReport)
    {
        abort_unless((int) $maintenanceReport->assigned_to === (int) Auth::id(), 403);

        $validated = $request->validate([
            'status' => 'required|in:in_progress,resolved',
            'resolution_notes' => 'nullable|string|max:2000|required_if:status,resolved',
        ], [
            'resolution_notes.required_if' => 'Describe what you fixed before marking it resolved.',
        ]);

        if ($maintenanceReport->status === 'resolved') {
            return back()->with('error', 'This report is already resolved.');
        }

        $maintenanceReport->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $maintenanceReport->resolution_notes,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        return back()->with('success', $validated['status'] === 'resolved' ? 'Marked as resolved' : 'Marked as in progress');
    }
}
