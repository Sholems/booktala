<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        Position::create($this->validated($request));

        return back()->with('success', 'Position added');
    }

    public function edit(Position $position)
    {
        return view('admin.positions.edit', compact('position'));
    }

    public function update(Request $request, Position $position)
    {
        $position->update($this->validated($request, $position));

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

    private function validated(Request $request, ?Position $position = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('positions')->ignore($position?->id)],
            'department' => ['required', Rule::in(array_keys(Position::DEPARTMENTS))],
            'level' => ['required', Rule::in(array_keys(Position::LEVELS))],
            'description' => 'nullable|string|max:255',
        ]);
    }
}
