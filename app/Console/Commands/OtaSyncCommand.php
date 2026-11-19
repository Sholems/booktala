<?php

namespace App\Console\Commands;

use App\Models\OtaChannelMapping;
use App\Services\Ota\OtaSyncService;
use Illuminate\Console\Command;

class OtaSyncCommand extends Command
{
    protected $signature = 'ota:sync {--channel= : Specific channel to sync (airbnb, bookingcom, vrbo)} {--unit= : Specific unit ID}';

    protected $description = 'Synchronize two-way reservations with external OTAs (Airbnb, Booking.com, VRBO)';

    public function handle(OtaSyncService $syncService): int
    {
        $this->info('Starting Two-Way OTA Synchronization...');

        $query = OtaChannelMapping::query()
            ->where('is_active', true)
            ->whereNotNull('import_ical_url');

        if ($channel = $this->option('channel')) {
            $query->where('channel', $channel);
        }

        if ($unitId = $this->option('unit')) {
            $query->where('property_id', $unitId);
        }

        $mappings = $query->with('unit')->get();

        if ($mappings->isEmpty()) {
            $this->warn('No configured OTA channel feeds found to sync.');

            return Command::SUCCESS;
        }

        $totalImported = 0;
        $totalUpdated = 0;
        $totalConflicts = 0;

        foreach ($mappings as $mapping) {
            $this->line("Syncing {$mapping->channel_meta['name']} for unit '{$mapping->unit?->title}' (ID: {$mapping->property_id})...");

            try {
                $results = $syncService->syncMapping($mapping);
                $this->info(" -> Imported: {$results['imported']}, Updated: {$results['updated']}, Existing: {$results['existing']}, Conflicts: {$results['conflicts']}");

                $totalImported += $results['imported'];
                $totalUpdated += $results['updated'];
                $totalConflicts += $results['conflicts'];
            } catch (\Throwable $e) {
                $this->error(" -> Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("OTA Synchronization completed: {$totalImported} imported, {$totalUpdated} updated, {$totalConflicts} conflicts prevented.");

        return Command::SUCCESS;
    }
}
