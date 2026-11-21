<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->string('target_segment')->default('all_subscribers'); 
            // all_subscribers, past_guests, registered_users, active_affiliates, newsletter_only
            $table->string('template_type')->default('luxury_showcase'); 
            // luxury_showcase, promotional_deal, concierge_update
            $table->string('headline')->nullable();
            $table->longText('content_html')->nullable();
            $table->json('featured_property_ids')->nullable();
            $table->string('cta_text')->nullable()->default('Explore VIP Stays');
            $table->string('cta_url')->nullable();
            $table->string('discount_code')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->string('status')->default('draft'); 
            // draft, scheduled, sending, sent, cancelled
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
