<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'mail:test {email? : Destination email address}';

    protected $description = 'Dispatch a test email to verify Brevo SMTP and mail configuration';

    public function handle(): int
    {
        $to = $this->argument('email') ?: $this->ask('Enter the destination email address to send the test email to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: [{$to}]");

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');
        $from = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info("Mailer Driver: {$mailer}");
        $this->info("SMTP Host:     {$host}:{$port}");
        $this->info("SMTP User:     {$username}");
        $this->info("From Address:  {$fromName} <{$from}>");
        $this->info("Sending test message to: [{$to}]...");

        try {
            Mail::html(
                '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px;">'
                .'<div style="background-color: #0B2D5B; color: #ffffff; padding: 16px 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">'
                .'<h2 style="margin: 0; color: #ffffff;">BookTala Email Test</h2>'
                .'</div>'
                .'<p>Hello,</p>'
                .'<p>This is a test notification confirming that your <strong>Brevo SMTP</strong> connection for <strong>BookTala Luxury Stays</strong> is active and delivering emails properly.</p>'
                .'<ul style="background: #f8fafc; padding: 16px 24px; border-radius: 8px; font-size: 13px; color: #334155;">'
                .'<li><strong>Service:</strong> Brevo SMTP Relay</li>'
                .'<li><strong>Timestamp:</strong> '.now()->toRfc2822String().'</li>'
                .'<li><strong>Target:</strong> '.htmlspecialchars($to).'</li>'
                .'</ul>'
                .'<p style="color: #64748b; font-size: 12px; margin-top: 24px;">BookTala Concierge · Stays · Travels · Better Living</p>'
                .'</div>',
                function ($message) use ($to, $from, $fromName) {
                    $message->to($to)
                        ->from($from, $fromName)
                        ->subject('BookTala — Brevo SMTP Delivery Test ('.now()->format('H:i:s').')');
                }
            );

            $this->info("SUCCESS: Test email successfully sent to [{$to}] via Brevo!");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FAILED to send test email: '.$e->getMessage());
            $this->line('');
            $this->warn('Troubleshooting Tips for Brevo:');
            $this->line("1. Verify your sender email address [{$from}] is registered and validated under Brevo 'Senders & IP'.");
            $this->line('2. Ensure MAIL_HOST=smtp-relay.brevo.com and MAIL_PORT=587 in .env.');
            $this->line('3. Ensure MAIL_USERNAME matches your Brevo SMTP login (often your account email).');
            $this->line('4. Ensure MAIL_PASSWORD matches the master SMTP key generated in Brevo.');

            return self::FAILURE;
        }
    }
}
