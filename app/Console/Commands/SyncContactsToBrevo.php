<?php

namespace App\Console\Commands;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\BrevoMarketingService;
use Illuminate\Console\Command;

class SyncContactsToBrevo extends Command
{
    protected $signature = 'brevo:sync-contacts';
    protected $description = 'Synchronize all active users, customers, affiliates, and newsletter subscribers to Brevo';

    public function handle(BrevoMarketingService $brevo): int
    {
        $this->info('Starting Brevo contact synchronization...');

        $subscribers = NewsletterSubscriber::active()->get();
        $users = User::all();

        $this->info("Found {$subscribers->count()} active newsletter subscribers and {$users->count()} registered users.");

        $syncedSubscribers = 0;
        $syncedUsers = 0;

        $bar = $this->output->createProgressBar($subscribers->count() + $users->count());
        $bar->start();

        foreach ($subscribers as $sub) {
            if ($brevo->createOrUpdateSubscriber($sub)) {
                $syncedSubscribers++;
            }
            $bar->advance();
        }

        foreach ($users as $user) {
            if ($user->email && $brevo->syncUser($user)) {
                $syncedUsers++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Synchronization finished!");
        $this->line("• Synced subscribers: {$syncedSubscribers}");
        $this->line("• Synced users:       {$syncedUsers}");

        return self::SUCCESS;
    }
}
