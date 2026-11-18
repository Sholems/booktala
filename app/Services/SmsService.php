<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $apiKey;

    private $from;

    public function __construct()
    {
        $this->apiKey = config('services.termii.key');
        $this->from = config('services.termii.from', 'BOOKTALA');
    }

    public function sendSms($to, $message)
    {
        if (empty($this->apiKey)) {
            Log::warning('Termii API key not configured. SMS not sent.');

            return false;
        }

        $to = $this->formatPhoneNumber($to);

        try {
            $response = Http::post('https://api.termii.com/api/sms/send', [
                'api_key' => $this->apiKey,
                'to' => $to,
                'from' => $this->from,
                'sms' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS sending failed: '.$e->getMessage());

            return false;
        }
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
