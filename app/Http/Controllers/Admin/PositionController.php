<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PositionRequest;
use App\Models\Position;

/**
 * Job positions staff can hold at a site (Security Guard, Receptionist, ...).
 */
class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::withCount(['assignments as active_count' => fn ($q) => $q->active()])
            ->orderBy('department')
            ->orderByRaw("CASE level WHEN 'manager' THEN 1 WHEN 'supervisor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get()
            ->groupBy('department');

        return view('admin.positions.index', compact('positions'));
    }

    public function store(PositionRequest $request)
    {
        Position::create($request->validated());

        return back()->with('success', 'Position added');
    }

    public function edit(Position $position)
    {
        return view('admin.positions.edit', compact('position'));
    }

    public function update(PositionRequest $request, Position $position)
    {
        $position->update($request->validated());

        return redirect()->route('admin.positions.index')->with('success', 'Position updated');
    }

    public function destroy(Position $position)
    {
        if ($position->assignments()->exists()) {
            return back()->with('error', 'This position has staff assignments (current or past), so it is kept for the record.');
        }

        $position->delete();

        return back()->with('success', 'Position deleted');
    }
}
