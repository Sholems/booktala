<?php

namespace App\Http\Controllers;

use App\Models\OtaChannelMapping;
use App\Services\Ota\OtaSyncService;
use Illuminate\Http\Response;

class OtaFeedController extends Controller
{
    public function export(string $token, OtaSyncService $syncService): Response
    {
        $mapping = OtaChannelMapping::with('unit')
            ->where('export_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $ical = $syncService->generateExportCalendar($mapping);
        $filename = 'booktala-'.$mapping->channel.'-'.$mapping->property_id.'.ics';

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
