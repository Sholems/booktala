<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Models\User;
use App\Rules\WorksAtProperty;
use Illuminate\Http\Request;

/**
 * Maintenance flow: anyone reports -> admin assigns a staff member ->
 * staff start and resolve it with notes. Admin can update any report.
 */
class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('show', 'open');

        $reports = MaintenanceReport::with(['property', 'reportedBy', 'assignedTo'])
            ->when($filter === 'open', fn ($q) => $q->where('status', '!=', 'resolved'))
            ->when($filter === 'resolved', fn ($q) => $q->where('status', 'resolved'))
            ->orderByRaw("CASE status WHEN 'resolved' THEN 2 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $properties = Property::active()->get();
        $staff = User::role('staff')->where('status', 'active')->orderBy('name')->get();

        return view('admin.maintenance.index', compact('reports', 'properties', 'staff', 'filter'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'property_id' => 'required|exists:properties,id',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high,urgent,emergency',
            'assigned_to' => ['nullable', new WorksAtProperty($request->integer('property_id') ?: null)],
        ]);

        MaintenanceReport::create($validated + [
            'reported_by' => auth()->id(),
            'status' => 'pending',
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        return back()->with('success', 'Maintenance report created');
    }

    public function update(Request $request, MaintenanceReport $maintenanceReport)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved',
            'assigned_to' => ['nullable', new WorksAtProperty($maintenanceReport->property_id)],
            'resolution_notes' => 'nullable|string|max:2000|required_if:status,resolved',
        ], [
            'resolution_notes.required_if' => 'Add a note describing the fix before marking it resolved.',
        ]);

        $maintenanceReport->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'resolution_notes' => $validated['resolution_notes'] ?? $maintenanceReport->resolution_notes,
            'resolved_at' => $validated['status'] === 'resolved' ? ($maintenanceReport->resolved_at ?? now()) : null,
        ]);

        return back()->with('success', 'Report updated');
    }
}
