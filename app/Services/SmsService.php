<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private ?string $termiiKey;
    private string $termiiFrom;
    private SendchampService $sendchamp;

    public function __construct(SendchampService $sendchamp)
    {
        $this->sendchamp = $sendchamp;
        $this->termiiKey = config('services.termii.key');
        $this->termiiFrom = config('services.termii.from', 'BOOKTALA');
    }

    public function sendSms($to, $message)
    {
        // 1. Sendchamp (Unified engine for SMS)
        if ($this->sendchamp->isConfigured()) {
            $result = $this->sendchamp->sendSms($to, $message);
            if ($result['success']) {
                return true;
            }
            Log::warning('Sendchamp SMS dispatch unsuccessful, falling back to Termii if available: ' . ($result['error'] ?? 'Unknown error'));
        }

        // 2. Termii Fallback
        if (!empty($this->termiiKey)) {
            $to = $this->formatPhoneNumber($to);

            try {
                $response = Http::post('https://api.termii.com/api/sms/send', [
                    'api_key' => $this->termiiKey,
                    'to' => $to,
                    'from' => $this->termiiFrom,
                    'sms' => $message,
                ]);

                return $response->successful();
            } catch (\Exception $e) {
                Log::error('Termii SMS fallback failed: ' . $e->getMessage());
                return false;
            }
        }

        if (!$this->sendchamp->isConfigured()) {
            Log::warning('Neither Sendchamp nor Termii is configured. SMS not sent.');
        }

        return false;
    }

    public function sendBookingConfirmation($phone, $name, $bookingRef, $property, $checkIn, $checkOut)
    {
        $message = "Hi {$name}, your booking at BookTala is confirmed!\n";
        $message .= "Ref: {$bookingRef}\n";
        $message .= "Property: {$property}\n";
        $message .= "Check-in: {$checkIn}\n";
        $message .= "Check-out: {$checkOut}\n";
        $message .= 'Thank you for choosing us!';

        return $this->sendSms($phone, $message);
    }

    public function sendBookingReminder($phone, $name, $property, $checkIn)
    {
        $message = "Reminder: Hi {$name}, your check-in at {$property} is tomorrow ({$checkIn}). We look forward to hosting you!";

        return $this->sendSms($phone, $message);
    }

    public function sendCheckInNotification($phone, $name, $property)
    {
        $message = "Welcome! Hi {$name}, you have successfully checked in to {$property}. Enjoy your stay!";

        return $this->sendSms($phone, $message);
    }

    public function sendCheckOutNotification($phone, $name, $property)
    {
        $message = "Thank you! Hi {$name}, you have checked out from {$property}. We hope you enjoyed your stay. See you again soon!";

        return $this->sendSms($phone, $message);
    }

    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '234'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '234')) {
            return '234'.$phone;
        }

        return $phone;
    }
}
