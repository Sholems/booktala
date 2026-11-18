<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

/**
 * Clocking in and out from the staff dashboard. The browser sends the phone's
 * location when the staff member allows it.
 */
class AttendanceController extends Controller
{
    public function clockIn(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $record = $attendance->clockIn(
            $this->staff($request),
            Site::findOrFail($data['site_id']),
            isset($data['lat']) ? (float) $data['lat'] : null,
            isset($data['lng']) ? (float) $data['lng'] : null,
            isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
        );

        $message = 'Clocked in at '.$record->clock_in_at->format('g:i A').' — '.$record->site->name.'.';
        if ($record->clock_in_location === 'off_site') {
            $message .= ' Your location looks '.number_format($record->clock_in_distance_m).' m from the site; your supervisor will see this.';
        } elseif ($record->clock_in_location === 'unknown' && $record->site->hasLocation()) {
            $message .= ' Location was not shared, so your supervisor will see this clock-in as unverified.';
        }

        return back()->with('success', $message);
    }

    public function clockOut(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'handover_note' => 'nullable|string|max:2000',
        ]);

        $record = $attendance->clockOut(
            $this->staff($request),
            isset($data['lat']) ? (float) $data['lat'] : null,
            isset($data['lng']) ? (float) $data['lng'] : null,
            isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
            $data['handover_note'] ?? null,
        );

        return back()->with('success', 'Clocked out. You worked '.$record->workedLabel().' this shift.');
    }

    private function staff(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff && $staff->is_active, 403, 'Your staff profile is not active.');

        return $staff;
    }
}
