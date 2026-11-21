<?php

namespace App\Console\Commands;

use App\Services\AffiliateCommissionService;
use Illuminate\Console\Command;

class MatureAffiliateCommissions extends Command
{
    protected $signature = 'affiliates:mature-commissions';
    protected $description = 'Matures pending affiliate commissions for fulfilled stays (24h post check-out)';

    public function handle(AffiliateCommissionService $service): int
    {
        $count = $service->matureCommissions();
        $this->info("Matured {$count} affiliate commission(s).");

        return self::SUCCESS;
    }
}
