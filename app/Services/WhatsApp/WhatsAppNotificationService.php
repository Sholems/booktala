<?php

namespace App\Services\WhatsApp;

use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Models\User;
use App\Models\WhatsAppMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * Send automated booking confirmation alert via WhatsApp.
     */
    public function sendBookingConfirmation(Reservation $reservation): ?WhatsAppMessageLog
    {
        $phone = $this->resolveRecipientPhone($reservation);
        if (! $phone) {
            return null;
        }

        $property = $reservation->property;
        $guestName = $reservation->user?->name ?? 'Guest';
        $checkIn = $reservation->check_in?->format('M j, Y') ?? 'TBD';
        $checkInTime = $property?->check_in_time ?: '2:00 PM';
        $total = number_format($reservation->total_amount, 2);
        $portalUrl = route('customer.reservations.show', $reservation->id);

        $message = "🌟 *Booking Confirmed! - BookTala*\n\n"
            ."Hello {$guestName},\n"
            ."Your reservation *{$reservation->booking_ref}* at *{$property?->title}* is confirmed!\n\n"
            ."📅 *Check-in:* {$checkIn} (from {$checkInTime})\n"
            ."📅 *Check-out:* {$reservation->check_out?->format('M j, Y')}\n"
            ."💳 *Total Paid:* ₦{$total}\n\n"
            ."View your stay details & in-room concierge portal: {$portalUrl}\n\n"
            .'We look forward to hosting you!';

        return $this->dispatchMessage($phone, $message, 'booking_confirmed', $reservation->user, $reservation);
    }

    /**
     * Send payment receipt alert via WhatsApp.
     */
    public function sendPaymentReceipt(Reservation $reservation, float $amount, string $reference): ?WhatsAppMessageLog
    {
        $phone = $this->resolveRecipientPhone($reservation);
        if (! $phone) {
            return null;
        }

        $guestName = $reservation->user?->name ?? 'Guest';
        $formattedAmount = number_format($amount, 2);

        $message = "💳 *Payment Received - BookTala*\n\n"
            ."Hello {$guestName},\n"
            ."We have confirmed receipt of *₦{$formattedAmount}* for booking *{$reservation->booking_ref}*.\n\n"
            ."🧾 *Transaction Reference:* {$reference}\n"
            ."🏨 *Property:* {$reservation->property?->title}\n\n"
            .'Thank you for your payment!';

        return $this->dispatchMessage($phone, $message, 'payment_receipt', $reservation->user, $reservation);
    }

    /**
     * Send check-in instructions and Wi-Fi credentials upon check-in.
     */
    public function sendCheckinInstructions(Reservation $reservation): ?WhatsAppMessageLog
    {
        $phone = $this->resolveRecipientPhone($reservation);
        if (! $phone) {
            return null;
        }

        $property = $reservation->property;
        $guestName = $reservation->user?->name ?? 'Guest';
        $wifiSsid = $property?->wifi_ssid ?: 'BookTala-Guest';
        $wifiPassword = $property?->wifi_password ?: 'Welcome@BookTala';
        $accessCode = $property?->door_access_code ?: 'Keycard at Front Desk';
        $instructions = $property?->checkin_instructions ?: 'Please present your ID at the security gate/reception for access.';
        $conciergeUrl = route('customer.room-service.index');

        $message = "🗝️ *Welcome to {$property?->title}! - BookTala*\n\n"
            ."Hello {$guestName}, welcome to your residence!\n\n"
            ."🚪 *Access Code / Key Instructions:*\n{$accessCode}\n{$instructions}\n\n"
            ."📶 *Wi-Fi Access:*\n"
            ."Network: *{$wifiSsid}*\n"
            ."Password: *{$wifiPassword}*\n\n"
            ."🛎️ *In-Room Concierge & Room Service:*\n"
            ."Order meals, laundry, extra housekeeping or airport pickup directly from your phone:\n{$conciergeUrl}\n\n"
            .'Have a pleasant stay!';

        return $this->dispatchMessage($phone, $message, 'checkin_instructions', $reservation->user, $reservation);
    }

    /**
     * Send status update for a room service / concierge order.
     */
    public function sendConciergeUpdate(RoomServiceOrder $order): ?WhatsAppMessageLog
    {
        $reservation = $order->reservation;
        $phone = $order->user?->phone ?? $this->resolveRecipientPhone($reservation);
        if (! $phone) {
            return null;
        }

        $orderType = RoomServiceOrder::orderTypes()[$order->order_type] ?? $order->order_type;
        $statusLabel = RoomServiceOrder::statuses()[$order->status] ?? ucfirst($order->status);
        $total = number_format($order->total_amount, 2);

        $message = "🛎️ *Concierge Request Update - BookTala*\n\n"
            ."Your in-stay request *#{$order->id}* ({$orderType}) is now: *{$statusLabel}*.\n"
            ."💵 *Amount:* ₦{$total} (".($order->payment_method === 'folio' ? 'Charged to Folio' : 'Paid').")\n\n"
            .'Our team is committed to making your stay exceptional.';

        return $this->dispatchMessage($phone, $message, 'concierge_update', $order->user, $reservation);
    }

    /**
     * Send checkout reminder on the morning of checkout.
     */
    public function sendCheckoutReminder(Reservation $reservation): ?WhatsAppMessageLog
    {
        $phone = $this->resolveRecipientPhone($reservation);
        if (! $phone) {
            return null;
        }

        $property = $reservation->property;
        $guestName = $reservation->user?->name ?? 'Guest';
        $checkoutTime = $property?->check_out_time ?: '11:00 AM';

        $message = "☀️ *Good Morning from BookTala!*\n\n"
            ."Hello {$guestName},\n"
            ."Friendly reminder that check-out for *{$property?->title}* is today by *{$checkoutTime}*.\n\n"
            ."If you need airport drop-off, luggage storage, or a late check-out request, please reach out to our concierge team.\n\n"
            .'Thank you for staying with us!';

        return $this->dispatchMessage($phone, $message, 'checkout_reminder', $reservation->user, $reservation);
    }

    /**
     * Send custom ad-hoc message to a guest phone.
     */
    public function sendCustomMessage(string $phone, string $message, ?User $user = null, ?Reservation $reservation = null, ?string $recipientName = null): WhatsAppMessageLog
    {
        return $this->dispatchMessage($phone, $message, 'custom', $user, $reservation, $recipientName);
    }

    /**
     * Normalize Nigerian phone numbers into international E.164 format.
     * e.g. 08012345678 -> +2348012345678
     */
    public function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234'.substr($digits, 1);
        }

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    public function normalizePhone(string $phone): string
    {
        return $this->formatPhoneNumber($phone);
    }

    /**
     * Dispatch message via configured gateway (Termii, Twilio, Meta, or Simulation).
     */
    protected function dispatchMessage(string $rawPhone, string $message, string $template, ?User $user = null, ?Reservation $reservation = null, ?string $recipientName = null): WhatsAppMessageLog
    {
        $formattedPhone = $this->formatPhoneNumber($rawPhone);
        $gateway = config('services.whatsapp.gateway', 'sendchamp');
        $status = 'sent';
        $gatewayReference = 'WA-'.uniqid();
        $errorMessage = null;

        /** @var \App\Services\SendchampService $sendchamp */
        $sendchamp = app(\App\Services\SendchampService::class);

        if (! app()->environment('testing') && ($gateway === 'sendchamp' || $gateway === 'auto') && $sendchamp->isConfigured()) {
            $gateway = 'sendchamp';
            $result = $sendchamp->sendWhatsApp($formattedPhone, $message);
            if ($result['success']) {
                $status = 'sent';
                $gatewayReference = $result['reference'] ?? $gatewayReference;
            } else {
                $status = 'failed';
                $errorMessage = $result['error'] ?? 'Sendchamp WhatsApp delivery failed';
            }
        } elseif ($gateway === 'termii' && config('services.termii.api_key')) {
            try {
                $response = Http::timeout(10)->post('https://api.ng.termii.com/api/send-message', [
                    'to' => ltrim($formattedPhone, '+'),
                    'from' => config('services.termii.sender_id', 'BookTala'),
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => 'whatsapp',
                    'api_key' => config('services.termii.api_key'),
                ]);

                if (! $response->successful()) {
                    $status = 'failed';
                    $errorMessage = $response->body();
                } else {
                    $gatewayReference = $response->json('message_id') ?? $gatewayReference;
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $errorMessage = $e->getMessage();
            }
        } elseif ($gateway === 'twilio' && config('services.twilio.sid')) {
            try {
                $response = Http::timeout(10)
                    ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                    ->asForm()
                    ->post('https://api.twilio.com/2010-04-01/Accounts/'.config('services.twilio.sid').'/Messages.json', [
                        'From' => 'whatsapp:'.config('services.twilio.whatsapp_from'),
                        'To' => 'whatsapp:'.$formattedPhone,
                        'Body' => $message,
                    ]);

                if (! $response->successful()) {
                    $status = 'failed';
                    $errorMessage = $response->body();
                } else {
                    $gatewayReference = $response->json('sid') ?? $gatewayReference;
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $errorMessage = $e->getMessage();
            }
        } else {
            // Simulation / Log mode (active by default in staging & local)
            $status = 'sent';
            $gateway = 'simulation';
            Log::info("WhatsApp [{$template}] Simulated delivery to {$formattedPhone}:\n{$message}");
        }

        return WhatsAppMessageLog::create([
            'recipient_phone' => $formattedPhone,
            'recipient_name' => $recipientName ?: ($user?->name ?: ($reservation?->guest?->name)),
            'direction' => 'outbound',
            'user_id' => $user?->id,
            'reservation_id' => $reservation?->id,
            'template' => $template,
            'message' => $message,
            'status' => $status,
            'gateway' => $gateway,
            'gateway_reference' => $gatewayReference,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }

    protected function resolveRecipientPhone(?Reservation $reservation): ?string
    {
        if (! $reservation) {
            return null;
        }

        return $reservation->user?->phone
            ?: ($reservation->guest?->phone ?? null);
    }
}
