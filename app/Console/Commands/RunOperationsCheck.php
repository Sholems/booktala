<?php

namespace App\Console\Commands;

use App\Services\EscalationService;
use Illuminate\Console\Command;

class RunOperationsCheck extends Command
{
    protected $signature = 'operations:check';

    protected $description = 'Escalate unstarted work, report missed shifts and close forgotten clock-ins';

    public function handle(EscalationService $escalation): int
    {
        $result = $escalation->run();

        $this->info(sprintf(
            'Escalated %d task(s) and %d repair(s); %d missed shift(s) reported; %d forgotten clock-in(s) closed.',
            $result['tasks'], $result['maintenance'], $result['missed_shifts'], $result['closed_attendance'],
        ));

        return self::SUCCESS;
    }
}
