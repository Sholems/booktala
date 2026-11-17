<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class IcalController extends Controller
{
    public function exportReservations(Request $request)
    {
        $startDate = $request->get('start', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end', now()->addMonths(3)->format('Y-m-d'));
        $status = $request->get('status', 'confirmed');

        $reservations = Reservation::with('property')
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereDate('check_in', '>=', $startDate)
            ->whereDate('check_in', '<=', $endDate)
            ->get();

        $icalContent = $this->generateIcal($reservations);

        return response($icalContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reservations.ics"',
        ]);
    }

    public function exportProperty(Request $request, $propertyId)
    {
        $property = \App\Models\Property::findOrFail($propertyId);
        
        $reservations = Reservation::where('property_id', $propertyId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get();

        $icalContent = $this->generateIcal($reservations, $property->name);

        return response($icalContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $property->slug . '-bookings.ics"',
        ]);
    }

    private function generateIcal($reservations, $calendarName = 'BookTala Reservations')
    {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//BookTala//Management System//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:" . $calendarName . "\r\n";

        foreach ($reservations as $reservation) {
            $uid = $reservation->id . '@' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'booktala');
            $summary = $reservation->property->name . ' - ' . ($reservation->user->name ?? 'Guest');
            $description = "Check-in: " . $reservation->check_in . "\nCheck-out: " . $reservation->check_out . "\nGuest: " . ($reservation->user->name ?? 'Guest') . "\nStatus: " . $reservation->status;
            
            $dtstart = \Carbon\Carbon::parse($reservation->check_in)->format('Ymd');
            $dtend = \Carbon\Carbon::parse($reservation->check_out)->format('Ymd');

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $uid . "\r\n";
            $ical .= "DTSTAMP:" . now()->format('YmdTHis') . "Z\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $dtstart . "\r\n";
            $ical .= "DTEND;VALUE=DATE:" . $dtend . "\r\n";
            $ical .= "SUMMARY:" . $this->escapeIcalText($summary) . "\r\n";
            $ical .= "DESCRIPTION:" . $this->escapeIcalText($description) . "\r\n";
            
            $color = match($reservation->status) {
                'confirmed' => '#3498db',
                'checked_in' => '#27ae60',
                'checked_out' => '#95a5a6',
                default => '#e74c3c',
            };
            $ical .= "COLOR:" . $color . "\r\n";
            
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    private function escapeIcalText($text)
    {
        return preg_replace('/([\,;\\])/', '\\\$1', $text);
    }

    public function getIcalUrl(Request $request)
    {
        $propertyId = $request->get('property_id');
        
        if ($propertyId) {
            $url = route('admin.ical.property', $propertyId);
        } else {
            $start = now()->subMonth()->format('Y-m-d');
            $end = now()->addMonths(3)->format('Y-m-d');
            $url = route('admin.ical.reservations', ['start' => $start, 'end' => $end]);
        }

        return response()->json([
            'url' => $url,
            'instructions' => 'Add this URL to your calendar app (Google Calendar, Apple Calendar, Outlook) to sync bookings.'
        ]);
    }
}