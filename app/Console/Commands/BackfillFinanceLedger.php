<?php

namespace App\Console\Commands;

use App\Services\Finance\LedgerReconciliationService;
use Illuminate\Console\Command;

class BackfillFinanceLedger extends Command
{
    protected $signature = 'finance:ledger-backfill {--chunk=100} {--site=} {--unit=}';

    protected $description = 'Idempotently backfill historical financial events into the balanced operational subledger.';

    public function handle(LedgerReconciliationService $service): int
    {
        $this->info('Starting historical finance ledger backfill...');

        $options = [
            'chunk' => (int) $this->option('chunk'),
            'site_id' => $this->option('site') ? (int) $this->option('site') : null,
            'unit_id' => $this->option('unit') ? (int) $this->option('unit') : null,
        ];

        $results = $service->backfill($options);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Payments processed', $results['payments_processed']],
                ['Completed stays processed', $results['stays_processed']],
                ['Caution resolutions processed', $results['cautions_processed']],
                ['Expenses processed', $results['expenses_processed']],
                ['Unresolved records', $results['unresolved_count']],
            ]
        );

        if ($results['unresolved_count'] > 0) {
            $this->warn("There are {$results['unresolved_count']} unresolved records that require agreement or unit attribution before cutover.");
            foreach (array_slice($results['unresolved'], 0, 10) as $item) {
                $this->line(" - [{$item['source_type']} #{$item['source_id']}] {$item['reason']}");
            }
            if ($results['unresolved_count'] > 10) {
                $this->line(' ... and '.($results['unresolved_count'] - 10).' more.');
            }
        } else {
            $this->info('Backfill completed with zero unresolved records.');
        }

        return self::SUCCESS;
    }
}
