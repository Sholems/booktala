<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhanced In-Stay Concierge & Folio/Paystack Payment
        Schema::table('room_service_orders', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('folio')->after('total_amount'); // folio, paystack, cash
            $table->string('payment_status', 20)->default('charged_to_folio')->after('payment_method'); // unpaid, charged_to_folio, paid
            $table->string('paystack_reference', 100)->nullable()->after('payment_status');
            $table->string('pickup_location')->nullable()->after('special_instructions');
            $table->dateTime('pickup_time')->nullable()->after('pickup_location');
            $table->string('flight_number', 50)->nullable()->after('pickup_time');
        });

        // 2. Unit Wi-Fi and Access Credentials for Automated Check-in Delivery
        Schema::table('properties', function (Blueprint $table) {
            $table->string('wifi_ssid', 100)->nullable()->after('check_out_time');
            $table->string('wifi_password', 100)->nullable()->after('wifi_ssid');
            $table->string('door_access_code', 100)->nullable()->after('wifi_password');
            $table->text('checkin_instructions')->nullable()->after('door_access_code');
        });

        // 3. Automated WhatsApp Messaging Logs
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone', 30);
            $table->string('recipient_name', 100)->nullable();
            $table->string('direction', 20)->default('outbound'); // outbound, inbound
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('template', 50); // booking_confirmed, payment_receipt, checkin_instructions, concierge_update, checkout_reminder, custom
            $table->text('message');
            $table->string('status', 20)->default('sent'); // sent, simulated, failed
            $table->string('gateway', 30)->default('simulation'); // termii, twilio, meta, simulation
            $table->string('gateway_reference', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_phone', 'created_at']);
            $table->index(['reservation_id', 'template']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['wifi_ssid', 'wifi_password', 'door_access_code', 'checkin_instructions']);
        });

        Schema::table('room_service_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'paystack_reference', 'pickup_location', 'pickup_time', 'flight_number']);
        });
    }
};
