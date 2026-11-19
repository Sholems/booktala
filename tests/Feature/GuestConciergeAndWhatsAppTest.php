<?php

namespace Tests\Feature;

use App\Events\ReservationStatusChanged;
use App\Models\Guest;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestConciergeAndWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guestUser;

    private Owner $owner;

    private Unit $unit;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));

        $this->admin = User::create([
            'name' => 'Admin Ops',
            'email' => 'admin@booktala.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->guestUser = User::create([
            'name' => 'Emeka Okafor',
            'email' => 'emeka@example.com',
            'phone' => '08023456789',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->guestUser->assignRole('customer');

        $site = Site::create([
            'name' => 'Ikoyi Prime',
            'address' => '12 Kingsway Road, Ikoyi',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'is_active' => true,
        ]);

        $this->owner = Owner::create([
            'name' => 'Ikoyi Properties Ltd',
            'type' => 'individual',
            'is_company_owned' => true,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'IKY-V1',
            'title' => 'Ikoyi Azure Villa',
            'description' => 'Luxury Penthouse in Ikoyi',
            'address' => '12 Kingsway Road, Ikoyi',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 85000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $site->id,
            'owner_id' => $this->owner->id,
            'wifi_ssid' => 'AzureVilla-Guest',
            'wifi_password' => 'WelcomeGuest2026',
            'door_access_code' => '4491#',
            'checkin_instructions' => 'Present your ID at security gate and dial 4491# on the smart lock.',
        ]);

        $guest = Guest::firstOrCreate(
            ['email' => $this->guestUser->email],
            [
                'user_id' => $this->guestUser->id,
                'name' => $this->guestUser->name,
                'phone' => $this->guestUser->phone,
            ]
        );

        $this->reservation = Reservation::create([
            'booking_ref' => 'WPA-TEST-001',
            'user_id' => $this->guestUser->id,
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-11-20',
            'check_out' => '2026-11-25',
            'nights' => 5,
            'guests' => 2,
            'guests_count' => 2,
            'subtotal' => 425000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 425000,
            'paid_amount' => 425000,
            'booking_status' => 'checked_in',
            'payment_status' => 'paid',
            'reference' => 'RES-AZURE-001',
        ]);
    }

    public function test_phone_normalization_for_nigerian_numbers(): void
    {
        $service = app(WhatsAppNotificationService::class);

        $this->assertSame('+2348023456789', $service->normalizePhone('08023456789'));
        $this->assertSame('+2348023456789', $service->normalizePhone('2348023456789'));
        $this->assertSame('+2348023456789', $service->normalizePhone('+2348023456789'));
        $this->assertSame('+2347011223344', $service->normalizePhone('0701 122 3344'));
        $this->assertSame('+14155552671', $service->normalizePhone('+1 (415) 555-2671'));
    }

    public function test_guest_can_order_airport_pickup_charged_to_folio(): void
    {
        $response = $this->actingAs($this->guestUser)->post(route('customer.room-service.store'), [
            'reservation_id' => $this->reservation->id,
            'order_type' => 'airport_pickup',
            'items' => [
                ['name' => 'Airport Pickup (Murtala Muhammed Int. to Island)', 'quantity' => 1],
            ],
            'payment_method' => 'folio',
            'pickup_location' => 'Murtala Muhammed International Terminal 2',
            'pickup_time' => '2026-11-21 16:30:00',
            'flight_number' => 'BA075',
            'special_instructions' => 'Guest will have 3 large suitcases.',
        ]);

        $order = RoomServiceOrder::latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('customer.room-service.show', $order));

        $this->assertSame('airport_pickup', $order->order_type);
        $this->assertSame('folio', $order->payment_method);
        $this->assertSame(RoomServiceOrder::PAYMENT_STATUS_CHARGED_TO_FOLIO, $order->payment_status);
        $this->assertEquals(30000, (float) $order->total_amount);
        $this->assertSame('BA075', $order->flight_number);
        $this->assertSame('Murtala Muhammed International Terminal 2', $order->pickup_location);

        // Verify WhatsApp alert was recorded
        $this->assertDatabaseHas('whatsapp_message_logs', [
            'template' => 'concierge_update',
            'recipient_phone' => '+2348023456789',
            'status' => 'sent',
        ]);
    }

    public function test_guest_can_order_with_paystack_payment_method(): void
    {
        $response = $this->actingAs($this->guestUser)->post(route('customer.room-service.store'), [
            'reservation_id' => $this->reservation->id,
            'order_type' => 'food',
            'items' => [
                ['name' => 'Nigerian Jollof Rice with Grilled Chicken', 'quantity' => 2],
                ['name' => 'Late Night Beverage & Snack Pack', 'quantity' => 1],
            ],
            'payment_method' => 'paystack',
            'special_instructions' => 'Mild pepper please.',
        ]);

        $order = RoomServiceOrder::latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('customer.room-service.show', $order));

        $this->assertSame('paystack', $order->payment_method);
        $this->assertSame(RoomServiceOrder::PAYMENT_STATUS_UNPAID, $order->payment_status);
        $this->assertStringStartsWith('PST-CON-', $order->paystack_reference);
        $this->assertEquals(23000, (float) $order->total_amount);
    }

    public function test_whatsapp_automated_booking_confirmation(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $log = $service->sendBookingConfirmation($this->reservation);

        $this->assertNotNull($log);
        $this->assertSame('booking_confirmed', $log->template);
        $this->assertSame('+2348023456789', $log->recipient_phone);
        $this->assertStringContainsString('Booking Confirmed!', $log->message);
        $this->assertStringContainsString($this->reservation->booking_ref, $log->message);
        $this->assertSame('sent', $log->status);
    }

    public function test_whatsapp_checkin_instructions_and_wifi(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $log = $service->sendCheckinInstructions($this->reservation);

        $this->assertNotNull($log);
        $this->assertSame('checkin_instructions', $log->template);
        $this->assertStringContainsString('Welcome to Ikoyi Azure Villa!', $log->message);
        $this->assertStringContainsString('AzureVilla-Guest', $log->message);
        $this->assertStringContainsString('WelcomeGuest2026', $log->message);
        $this->assertStringContainsString('4491#', $log->message);
    }

    public function test_reservation_status_events_trigger_whatsapp_notifications(): void
    {
        // 1. Confirmed event
        $pendingRes = Reservation::create([
            'booking_ref' => 'WPA-TEST-002',
            'user_id' => $this->guestUser->id,
            'guest_id' => $this->reservation->guest_id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-04',
            'nights' => 3,
            'guests' => 1,
            'guests_count' => 1,
            'subtotal' => 255000,
            'discount_amount' => 0,
            'processing_fee' => 0,
            'caution_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 255000,
            'paid_amount' => 255000,
            'booking_status' => 'pending',
            'payment_status' => 'paid',
            'reference' => 'RES-EVT-001',
        ]);

        $pendingRes->booking_status = 'confirmed';
        $pendingRes->save();

        event(new ReservationStatusChanged($pendingRes, 'pending', 'confirmed'));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'template' => 'booking_confirmed',
            'recipient_phone' => '+2348023456789',
        ]);

        // 2. Check-in event
        $pendingRes->booking_status = 'checked_in';
        $pendingRes->save();

        event(new ReservationStatusChanged($pendingRes, 'confirmed', 'checked_in'));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'template' => 'checkin_instructions',
            'recipient_phone' => '+2348023456789',
        ]);
    }

    public function test_admin_can_view_whatsapp_logs_and_dispatch_custom_message(): void
    {
        WhatsAppMessageLog::create([
            'recipient_phone' => '+2348011223344',
            'recipient_name' => 'VIP Guest',
            'direction' => 'outbound',
            'template' => 'custom',
            'message' => 'Your private driver has arrived at the lobby.',
            'status' => 'sent',
            'gateway' => 'simulation',
        ]);

        $indexResponse = $this->actingAs($this->admin)->get(route('admin.whatsapp.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('WhatsApp Automated Messaging Hub');
        $indexResponse->assertSee('+2348011223344');
        $indexResponse->assertSee('Your private driver has arrived at the lobby.');

        // Dispatch new custom message
        $sendResponse = $this->actingAs($this->admin)->post(route('admin.whatsapp.send'), [
            'phone' => '08099887766',
            'name' => 'Chidi Okonkwo',
            'message' => 'Complimentary champagne is chilled in your suite.',
        ]);

        $sendResponse->assertRedirect(route('admin.whatsapp.index'));
        $sendResponse->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'recipient_phone' => '+2348099887766',
            'recipient_name' => 'Chidi Okonkwo',
            'message' => 'Complimentary champagne is chilled in your suite.',
            'status' => 'sent',
        ]);
    }
}
