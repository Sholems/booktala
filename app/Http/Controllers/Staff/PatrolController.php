<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PatrolCheckpoint;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;

/**
 * Scanning a checkpoint's QR code opens this page on the guard's phone; one tap
 * records the visit (with location, if allowed).
 */
class PatrolController extends Controller
{
    public function show(Request $request, string $token)
    {
        $checkpoint = $this->checkpoint($request, $token);
        $checkpoint->load(['site', 'lastScan.staff.user']);

        return view('staff.patrol', compact('checkpoint'));
    }

    public function record(Request $request, string $token)
    {
        $checkpoint = $this->checkpoint($request, $token);
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'note' => 'nullable|string|max:255',
        ]);
        $staff = $request->user()->staff;
        abort_unless($staff && $staff->is_active, 403);

        // A double tap or a re-scan within two minutes counts once.
        $recent = $checkpoint->scans()->where('staff_id', $staff->id)->where('scanned_at', '>=', now()->subMinutes(2))->exists();
        if (! $recent) {
            $checkpoint->scans()->create($data + ['staff_id' => $staff->id, 'scanned_at' => now()]);
        }

        return redirect()->route('staff.patrol.scan', $token)->with('success', 'Patrol recorded at '.$checkpoint->name.', '.now()->format('g:i A'));
    }

    private function checkpoint(Request $request, string $token): PatrolCheckpoint
    {
        $checkpoint = PatrolCheckpoint::where('token', $token)->where('is_active', true)->firstOrFail();
        abort_unless(PropertyAccess::activeSiteIds($request->user())->pluck('site_id')->contains($checkpoint->site_id), 404);

        return $checkpoint;
    }
}
