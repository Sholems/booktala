<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceStoreRequest;
use App\Http\Requests\Admin\MaintenanceUpdateRequest;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Models\User;
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

    public function store(MaintenanceStoreRequest $request)
    {
        $validated = $request->validated();

        MaintenanceReport::create($validated + [
            'reported_by' => auth()->id(),
            'status' => 'pending',
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        return back()->with('success', 'Maintenance report created');
    }

    public function update(MaintenanceUpdateRequest $request, MaintenanceReport $maintenanceReport)
    {
        $validated = $request->validated();

        $maintenanceReport->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'resolution_notes' => $validated['resolution_notes'] ?? $maintenanceReport->resolution_notes,
            'resolved_at' => $validated['status'] === 'resolved' ? ($maintenanceReport->resolved_at ?? now()) : null,
        ]);

        return back()->with('success', 'Report updated');
    }
}
