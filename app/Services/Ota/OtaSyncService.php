<?php

namespace App\Services\Ota;

use App\Models\BlockedDate;
use App\Models\OtaChannelMapping;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtaSyncService
{
    /**
     * Generate outbound iCal calendar for an OTA to consume.
     */
    public function generateExportCalendar(OtaChannelMapping $mapping): string
    {
        $unit = $mapping->unit;
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'booktala.ng';

        $reservations = Reservation::where('property_id', $unit->id)
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->whereDate('check_out', '>=', now()->subDays(7)->toDateString())
            ->get();

        $blockedDates = BlockedDate::where('property_id', $unit->id)
            ->whereDate('end_date', '>=', now()->subDays(7)->toDateString())
            ->get();

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//BookTala//Channel Manager 2.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= 'X-WR-CALNAME:'.addcslashes('BookTala - '.$unit->title, ',;\\')."\r\n";

        foreach ($reservations as $res) {
            $uid = "res-{$res->id}@{$host}";
            $dtstart = Carbon::parse($res->check_in)->format('Ymd');
            $dtend = Carbon::parse($res->check_out)->format('Ymd');

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:{$uid}\r\n";
            $ical .= 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n";
            $ical .= "DTSTART;VALUE=DATE:{$dtstart}\r\n";
            $ical .= "DTEND;VALUE=DATE:{$dtend}\r\n";
            $ical .= "SUMMARY:Reserved - BookTala\r\n";
            $ical .= "DESCRIPTION:Reserved via BookTala ({$res->booking_ref})\r\n";
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        foreach ($blockedDates as $block) {
            $uid = "block-{$block->id}@{$host}";
            $dtstart = Carbon::parse($block->start_date)->format('Ymd');
            $dtend = Carbon::parse($block->end_date)->addDay()->format('Ymd');

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:{$uid}\r\n";
            $ical .= 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n";
            $ical .= "DTSTART;VALUE=DATE:{$dtstart}\r\n";
            $ical .= "DTEND;VALUE=DATE:{$dtend}\r\n";
            $ical .= "SUMMARY:Blocked - Maintenance / Owner Hold\r\n";
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Ingest inbound iCal feed from Airbnb, Booking.com, VRBO, etc.
     */
    public function syncMapping(OtaChannelMapping $mapping, ?string $rawIcalContent = null): array
    {
        $mapping->update(['sync_status' => 'syncing']);

        try {
            $content = $rawIcalContent;
            if ($content === null) {
                if (empty($mapping->import_ical_url)) {
                    throw new \InvalidArgumentException('No import iCal URL configured for this channel.');
                }

                $response = Http::timeout(15)->get($mapping->import_ical_url);
                if (! $response->successful()) {
                    throw new \RuntimeException("Failed to fetch iCal feed (HTTP {$response->status()}).");
                }
                $content = $response->body();
            }

            $events = $this->parseIcalEvents($content);
            $results = [
                'imported' => 0,
                'updated' => 0,
                'existing' => 0,
                'conflicts' => 0,
            ];

            $conflictsList = [];
            $otaUser = $this->getOrCreateOtaUser($mapping->channel);

            foreach ($events as $event) {
                $uid = $event['uid'];
                $checkIn = $event['start'];
                $checkOut = $event['end'];
                $summary = $event['summary'] ?? "{$mapping->channel_meta['name']} Guest";

                if (! $checkIn || ! $checkOut || $checkIn->gte($checkOut)) {
                    continue;
                }

                // Check if this OTA reservation was already imported
                $existing = Reservation::where('property_id', $mapping->property_id)
                    ->where('booking_source', $mapping->channel)
                    ->where('paystack_reference', 'OTA-'.$uid)
                    ->first();

                if ($existing) {
                    if ($existing->check_in->format('Y-m-d') !== $checkIn->format('Y-m-d') || $existing->check_out->format('Y-m-d') !== $checkOut->format('Y-m-d')) {
                        $nights = $checkIn->diffInDays($checkOut);
                        $existing->update([
                            'check_in' => $checkIn->toDateString(),
                            'check_out' => $checkOut->toDateString(),
                            'nights' => $nights,
                        ]);
                        $results['updated']++;
                    } else {
                        $results['existing']++;
                    }

                    continue;
                }

                // Verify availability to prevent double-booking
                $hasCollision = Reservation::where('property_id', $mapping->property_id)
                    ->blockingAvailability()
                    ->whereDate('check_in', '<', $checkOut->toDateString())
                    ->whereDate('check_out', '>', $checkIn->toDateString())
                    ->exists();

                $hasBlockCollision = BlockedDate::where('property_id', $mapping->property_id)
                    ->whereDate('start_date', '<', $checkOut->toDateString())
                    ->whereDate('end_date', '>=', $checkIn->toDateString())
                    ->exists();

                if ($hasCollision || $hasBlockCollision) {
                    $results['conflicts']++;
                    $conflictsList[] = [
                        'uid' => $uid,
                        'check_in' => $checkIn->toDateString(),
                        'check_out' => $checkOut->toDateString(),
                        'summary' => $summary,
                        'detected_at' => now()->toDateTimeString(),
                    ];

                    continue;
                }

                // Import new confirmed reservation
                $unit = $mapping->unit;
                $nights = $checkIn->diffInDays($checkOut);
                $rateMultiplier = (float) ($mapping->rate_multiplier ?: 1.0);
                $subtotal = round(((float) $unit->price_per_night) * $nights * $rateMultiplier, 2);

                Reservation::create([
                    'booking_ref' => Reservation::generateBookingRef(),
                    'user_id' => $otaUser->id,
                    'property_id' => $mapping->property_id,
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'nights' => $nights,
                    'guests' => 1,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'processing_fee' => 0,
                    'caution_fee' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $subtotal,
                    'booking_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_method' => $mapping->channel,
                    'booking_source' => $mapping->channel,
                    'paystack_reference' => 'OTA-'.$uid,
                    'special_requests' => "Imported via {$mapping->channel_meta['name']}. UID: {$uid}. Summary: {$summary}",
                ]);

                $results['imported']++;
            }

            $syncStatus = $results['conflicts'] > 0 ? 'conflict' : 'success';
            $mapping->update([
                'sync_status' => $syncStatus,
                'last_synced_at' => now(),
                'last_error' => null,
                'conflict_details' => ! empty($conflictsList) ? $conflictsList : null,
            ]);

            return $results;
        } catch (\Throwable $e) {
            $mapping->update([
                'sync_status' => 'error',
                'last_synced_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            Log::error('OTA Sync Error for Channel Mapping #'.$mapping->id.': '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Synchronize all active configured OTA mappings.
     */
    public function syncAll(): array
    {
        $mappings = OtaChannelMapping::where('is_active', true)
            ->whereNotNull('import_ical_url')
            ->get();

        $overall = [
            'total_channels' => $mappings->count(),
            'imported' => 0,
            'updated' => 0,
            'existing' => 0,
            'conflicts' => 0,
            'errors' => 0,
        ];

        foreach ($mappings as $mapping) {
            try {
                $res = $this->syncMapping($mapping);
                $overall['imported'] += $res['imported'];
                $overall['updated'] += $res['updated'];
                $overall['existing'] += $res['existing'];
                $overall['conflicts'] += $res['conflicts'];
            } catch (\Throwable $e) {
                $overall['errors']++;
            }
        }

        return $overall;
    }

    /**
     * Parse raw VEVENT components from an iCal string.
     */
    public function parseIcalEvents(string $ical): array
    {
        $events = [];
        $rawEvents = explode('BEGIN:VEVENT', $ical);
        array_shift($rawEvents); // Remove header before first VEVENT

        foreach ($rawEvents as $raw) {
            $eventData = explode('END:VEVENT', $raw)[0];

            $uid = $this->extractIcalValue($eventData, 'UID');
            $dtstart = $this->extractIcalValue($eventData, 'DTSTART');
            $dtend = $this->extractIcalValue($eventData, 'DTEND');
            $summary = $this->extractIcalValue($eventData, 'SUMMARY');

            if (! $uid || ! $dtstart || ! $dtend) {
                continue;
            }

            $startDate = $this->parseIcalDate($dtstart);
            $endDate = $this->parseIcalDate($dtend);

            if ($startDate && $endDate) {
                $events[] = [
                    'uid' => $uid,
                    'start' => $startDate,
                    'end' => $endDate,
                    'summary' => $summary ?: 'OTA Reservation',
                ];
            }
        }

        return $events;
    }

    protected function extractIcalValue(string $eventData, string $key): ?string
    {
        // Matches e.g. DTSTART:20261120 or DTSTART;VALUE=DATE:20261120
        if (preg_match('/(?:^|\r?\n)'.preg_quote($key, '/').'(?:;[^:]*)?:([^\r\n]+)/', $eventData, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function parseIcalDate(string $rawDate): ?CarbonImmutable
    {
        $clean = trim($rawDate);

        // Date format: YYYYMMDD (e.g. 20261120)
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $clean, $m)) {
            return CarbonImmutable::createFromDate((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();
        }

        // DateTime format: YYYYMMDDTHHIISSZ or YYYYMMDDTHHIISS
        if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $clean, $m)) {
            return CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4], (int) $m[5], (int) $m[6])->startOfDay();
        }

        try {
            return CarbonImmutable::parse($clean)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getOrCreateOtaUser(string $channel): User
    {
        $email = "ota-{$channel}@booktala.internal";

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => ucfirst($channel).' Guest',
                'password' => bcrypt('OTA-Channel-Sync-'.config('app.key')),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
