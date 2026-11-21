<?php

namespace App\Console\Commands;

use App\Services\SendchampService;
use Illuminate\Console\Command;

class TestSendchamp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendchamp:test 
                            {phone? : The recipient phone number (e.g. 09116110242 or +2349116110242)}
                            {--channel=sms : Delivery channel (sms, whatsapp, otp)}
                            {--message= : Custom text message to dispatch}
                            {--balance : Query wallet balance only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Sendchamp SMS, WhatsApp notifications, OTP verification, or query wallet balance';

    /**
     * Execute the console command.
     */
    public function handle(SendchampService $sendchamp): int
    {
        $this->info('=== BookTala Sendchamp Diagnostic & Test Tool ===');

        if (!$sendchamp->isConfigured()) {
            $this->error('SENDCHAMP_PUBLIC_KEY is missing from your .env file!');
            $this->line('Add the following to your .env:');
            $this->line('  SENDCHAMP_PUBLIC_KEY=sendchamp_live_...');
            $this->line('  SENDCHAMP_MODE=live');
            $this->line('  SENDCHAMP_SMS_SENDER_ID=BookTala');
            $this->line('  SENDCHAMP_WHATSAPP_PHONE_NUMBER_ID=...');
            return Command::FAILURE;
        }

        $this->info('Sendchamp configuration detected.');

        // Wallet balance check
        if ($this->option('balance')) {
            $this->line('Querying Sendchamp wallet balance...');
            $balance = $sendchamp->getWalletBalance();
            if ($balance) {
                $this->table(['Currency', 'Available Balance'], [
                    [$balance['currency'] ?? 'NGN', $balance['available_balance'] ?? $balance['amount'] ?? 'N/A'],
                ]);
                return Command::SUCCESS;
            }
            $this->warn('Unable to retrieve wallet balance or endpoint returned empty.');
            return Command::FAILURE;
        }

        $phone = $this->argument('phone');
        if (!$phone) {
            $phone = $this->ask('Enter destination phone number (e.g. 09116110242)');
        }

        if (empty($phone)) {
            $this->error('A valid recipient phone number is required.');
            return Command::FAILURE;
        }

        $channel = strtolower($this->option('channel') ?? 'sms');
        $customMessage = $this->option('message');

        $this->line("Target Phone: {$phone}");
        $this->line("Formatted: {$sendchamp->formatPhone($phone)}");
        $this->line("Channel: {$channel}");

        if ($channel === 'sms') {
            $message = $customMessage ?: 'BookTala: Test SMS alert sent via Sendchamp. Luxury Stays & Serviced Living.';
            $this->line("Sending SMS: \"{$message}\"...");
            $res = $sendchamp->sendSms($phone, $message);

            if ($res['success']) {
                $this->info("SMS dispatched successfully!");
                $this->line("Reference: " . ($res['reference'] ?? 'N/A'));
                return Command::SUCCESS;
            }

            $this->error("SMS failed: " . ($res['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        if ($channel === 'whatsapp') {
            $message = $customMessage ?: "🌟 *BookTala Concierge*\n\nHello! This is a test WhatsApp notification from BookTala via Sendchamp.\n\nEverything is working properly!";
            $this->line("Sending WhatsApp message...");
            $res = $sendchamp->sendWhatsApp($phone, $message);

            if ($res['success']) {
                $this->info("WhatsApp message dispatched successfully!");
                $this->line("Reference: " . ($res['reference'] ?? 'N/A'));
                return Command::SUCCESS;
            }

            $this->error("WhatsApp failed: " . ($res['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        if ($channel === 'otp') {
            $this->line("Generating OTP verification token...");
            $res = $sendchamp->sendOtp($phone, 'sms', 6, 10);

            if ($res['success']) {
                $this->info("OTP sent successfully!");
                $ref = $res['reference'] ?? 'N/A';
                $this->line("Reference: {$ref}");
                
                $code = $this->ask('Enter OTP code received on phone to test confirmation (or press Enter to skip)');
                if (!empty($code)) {
                    $verifyRes = $sendchamp->verifyOtp($ref, $code);
                    if ($verifyRes['success']) {
                        $this->info("OTP successfully confirmed!");
                        return Command::SUCCESS;
                    }
                    $this->error("OTP verification failed: " . ($verifyRes['error'] ?? 'Code did not match'));
                    return Command::FAILURE;
                }
                return Command::SUCCESS;
            }

            $this->error("OTP request failed: " . ($res['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $this->error("Unsupported channel [{$channel}]. Choose 'sms', 'whatsapp', or 'otp'.");
        return Command::FAILURE;
    }
}
