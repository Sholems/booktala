<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\Unit;
use Illuminate\Console\Command;

class AuditUnitMigration extends Command
{
    protected $signature = 'units:audit-migration {--apply : Save automatic mappings and audit classifications}';

    protected $description = 'Audit canonical unit dimensions and classify every legacy room mapping';

    public function handle(): int
    {
        $missingDimensions = Unit::query()
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('owner_id')->orWhereNull('site_id'))
            ->count();
        $counts = ['automatic' => 0, 'manual' => 0, 'demo' => 0, 'blocking' => 0];

        Room::query()
            ->withCount('reservations')
            ->orderBy('id')
            ->each(function (Room $room) use (&$counts) {
                [$classification, $unitId, $notes] = $this->classify($room);
                $counts[$classification]++;

                if ($this->option('apply')) {
                    $room->forceFill([
                        'unit_id' => $unitId,
                        'unit_mapping_status' => $classification,
                        'unit_mapping_notes' => $notes,
                    ])->save();
                }

                $this->line(sprintf(
                    'Room %s: %s%s',
                    $room->room_number,
                    $classification,
                    $notes ? ' — '.$notes : '',
                ));
            });

        $this->newLine();
        $this->line('active units missing owner/site: '.$missingDimensions);
        foreach ($counts as $classification => $count) {
            $this->line($classification.': '.$count);
        }

        if ($missingDimensions > 0 || $counts['blocking'] > 0) {
            $this->error('Unit cutover is blocked until every reported issue is resolved.');

            return self::FAILURE;
        }

        $this->info('Unit inventory is ready for the next migration stage.');

        return self::SUCCESS;
    }

    private function classify(Room $room): array
    {
        $propertyIds = $room->reservations()
            ->distinct()
            ->orderBy('property_id')
            ->pluck('property_id');

        if ($room->unit_mapping_status === 'demo') {
            return $propertyIds->isEmpty()
                ? ['demo', null, 'Explicitly classified as inactive demo inventory.']
                : ['blocking', null, 'A demo room is referenced by reservations.'];
        }

        if ($room->unit_id) {
            $mappingMatchesHistory = $propertyIds->isEmpty()
                || ($propertyIds->count() === 1 && $propertyIds->first() === $room->unit_id);

            if (! $mappingMatchesHistory) {
                return ['blocking', null, 'The saved unit conflicts with reservation history.'];
            }

            $classification = $room->unit_mapping_status === 'automatic' ? 'automatic' : 'manual';

            return [$classification, $room->unit_id, 'Mapped to unit '.$room->unit_id.'.'];
        }

        if ($propertyIds->count() === 1) {
            $unitId = $propertyIds->first();

            return ['automatic', $unitId, 'Reservation history identifies unit '.$unitId.'.'];
        }

        if ($propertyIds->count() > 1) {
            return ['blocking', null, 'Reservations point to multiple units: '.$propertyIds->implode(', ').'.'];
        }

        return ['blocking', null, 'No production relationship identifies a unit; map it manually or classify it as demo.'];
    }
}
