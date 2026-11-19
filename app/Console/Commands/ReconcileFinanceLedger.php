<?php

namespace App\Console\Commands;

use App\Services\Finance\LedgerReconciliationService;
use Illuminate\Console\Command;

class ReconcileFinanceLedger extends Command
{
    protected $signature = 'finance:ledger-reconcile {--site=} {--unit=} {--period-start=} {--period-end=} {--json}';

    protected $description = 'Verify transaction balancing, agreement attribution, and source-to-journal parity.';

    public function handle(LedgerReconciliationService $service): int
    {
        $options = [
            'site_id' => $this->option('site') ? (int) $this->option('site') : null,
            'unit_id' => $this->option('unit') ? (int) $this->option('unit') : null,
            'period_start' => $this->option('period-start'),
            'period_end' => $this->option('period-end'),
        ];

        $results = $service->reconcile($options);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return $results['passed'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('--- Ledger Reconciliation Report ---');

        $this->table(
            ['Category', 'Source Total (₦)', 'Journal Total (₦)', 'Difference (₦)'],
            [
                [
                    'Collections',
                    number_format($results['totals']['source_collections_minor'] / 100, 2),
                    number_format($results['totals']['journal_collections_minor'] / 100, 2),
                    number_format(($results['totals']['source_collections_minor'] - $results['totals']['journal_collections_minor']) / 100, 2),
                ],
                [
                    'Expenses',
                    number_format($results['totals']['source_expenses_minor'] / 100, 2),
                    number_format($results['totals']['journal_expenses_minor'] / 100, 2),
                    number_format(($results['totals']['source_expenses_minor'] - $results['totals']['journal_expenses_minor']) / 100, 2),
                ],
            ]
        );

        if (! empty($results['differences'])) {
            $this->error('Differences detected between source and journal records:');
            foreach ($results['differences'] as $diff) {
                $this->line(" - [{$diff['type']}] {$diff['message']}");
            }
        }

        if (! empty($results['unresolved'])) {
            $this->error('Unresolved records detected (external units lacking agreement coverage):');
            foreach ($results['unresolved'] as $item) {
                $this->line(" - [{$item['type']}] {$item['message']}");
            }
        }

        if ($results['passed']) {
            $this->info('✓ Reconciliation PASSED: All transactions balanced, agreements covered, zero discrepancies.');

            return self::SUCCESS;
        }

        $this->error('✗ Reconciliation FAILED: Discrepancies or unresolved attribution must be resolved before read cutover.');

        return self::FAILURE;
    }
}
