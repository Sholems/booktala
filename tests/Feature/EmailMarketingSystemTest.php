<?php

namespace Tests\Feature;

use App\Jobs\DispatchCampaignJob;
use App\Mail\MarketingCampaignMail;
use App\Mail\NewsletterWelcomeMail;
use App\Models\MarketingCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailMarketingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin@booktala.com',
            'name' => 'Admin User',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_guest_can_subscribe_to_newsletter_and_receives_welcome_email(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'vipguest@example.com',
            'name' => 'Victoria Guest',
            'source' => 'homepage_vip_banner',
        ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'vipguest@example.com',
            'name' => 'Victoria Guest',
            'status' => 'subscribed',
        ]);

        $subscriber = NewsletterSubscriber::where('email', 'vipguest@example.com')->first();
        $this->assertNotNull($subscriber->unsubscribe_token);

        Mail::assertQueued(NewsletterWelcomeMail::class, function ($mail) use ($subscriber) {
            return $mail->subscriber->id === $subscriber->id;
        });
    }

    public function test_subscriber_can_unsubscribe_via_secure_token(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'removeme@example.com',
            'name' => 'Remove Me',
            'status' => 'subscribed',
            'unsubscribe_token' => 'sample-unique-token-12345',
        ]);

        $response = $this->get(route('newsletter.unsubscribe', ['token' => 'sample-unique-token-12345']));
        $response->assertOk();
        $response->assertSeeText('You Have Been Unsubscribed');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $subscriber->id,
            'status' => 'unsubscribed',
        ]);

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_subscriber_can_resubscribe_from_unsubscribed_page(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'mistake@example.com',
            'status' => 'unsubscribed',
            'unsubscribe_token' => 'mistake-token-777',
            'unsubscribed_at' => now()->subDay(),
        ]);

        $response = $this->post(route('newsletter.resubscribe', ['token' => 'mistake-token-777']));
        $response->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $subscriber->id,
            'status' => 'subscribed',
            'unsubscribed_at' => null,
        ]);
    }

    public function test_admin_can_create_campaign(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.marketing.campaigns.store'), [
            'title' => 'December Luxury Festive 2026',
            'subject' => '✨ Exclusive December Rates at BookTala Stays',
            'preview_text' => 'Unlock special holiday packages with 24/7 guaranteed power.',
            'target_segment' => 'all_subscribers',
            'template_type' => 'luxury_showcase',
            'headline' => 'Experience Elevated Festive Living',
            'content_html' => 'Celebrate the holidays in our premier serviced apartments in Lagos.',
            'discount_code' => 'FESTIVE15',
            'cta_text' => 'Reserve Holiday Stay',
            'cta_url' => 'https://booktala.com',
        ]);

        $this->assertDatabaseHas('marketing_campaigns', [
            'title' => 'December Luxury Festive 2026',
            'status' => 'draft',
            'discount_code' => 'FESTIVE15',
            'target_segment' => 'all_subscribers',
        ]);
    }

    public function test_admin_can_preview_campaign_email_rendering(): void
    {
        $campaign = MarketingCampaign::create([
            'title' => 'Sample Preview Campaign',
            'subject' => 'Preview Subject Line',
            'target_segment' => 'all_subscribers',
            'template_type' => 'luxury_showcase',
            'headline' => 'Preview Headline',
            'content_html' => 'This is a test body paragraph.',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.marketing.campaigns.preview', $campaign));
        $response->assertOk();
        $response->assertSeeText('Preview Headline');
        $response->assertSeeText('This is a test body paragraph.');
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();

        $campaign = MarketingCampaign::create([
            'title' => 'Test Dispatch Campaign',
            'subject' => 'Test Subject Line',
            'target_segment' => 'all_subscribers',
            'template_type' => 'promotional_deal',
            'content_html' => 'Testing email dispatch.',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.marketing.campaigns.test', $campaign), [
            'test_email' => 'test-admin@booktala.com',
        ]);

        $response->assertRedirect();
        Mail::assertSent(MarketingCampaignMail::class, function ($mail) {
            return $mail->hasTo('test-admin@booktala.com');
        });
    }

    public function test_admin_can_dispatch_campaign_to_queue(): void
    {
        Queue::fake();

        $campaign = MarketingCampaign::create([
            'title' => 'Queue Dispatch Campaign',
            'subject' => 'Broadcasting to all contacts',
            'target_segment' => 'all_subscribers',
            'template_type' => 'concierge_update',
            'content_html' => 'Broadcast message.',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.marketing.campaigns.dispatch', $campaign));
        $response->assertRedirect();

        Queue::assertPushed(DispatchCampaignJob::class, function ($job) use ($campaign) {
            return $job->campaign->id === $campaign->id;
        });
    }

    public function test_admin_can_manage_subscribers(): void
    {
        // 1. Add subscriber manually
        $response = $this->actingAs($this->admin)->post(route('admin.marketing.subscribers.store'), [
            'email' => 'manual@example.com',
            'name' => 'Manual Guest',
            'source' => 'admin_panel',
        ]);
        $response->assertRedirect();

        $sub = NewsletterSubscriber::where('email', 'manual@example.com')->first();
        $this->assertNotNull($sub);
        $this->assertEquals('subscribed', $sub->status);

        // 2. Toggle status to unsubscribed
        $this->actingAs($this->admin)->patch(route('admin.marketing.subscribers.toggle', $sub));
        $this->assertEquals('unsubscribed', $sub->fresh()->status);

        // 3. Delete subscriber
        $this->actingAs($this->admin)->delete(route('admin.marketing.subscribers.destroy', $sub));
        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $sub->id]);
    }
}
