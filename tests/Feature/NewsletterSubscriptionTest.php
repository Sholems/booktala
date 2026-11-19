<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe_to_newsletter_and_receive_promo_code(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'guest@example.com',
            'name' => 'John Doe',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('newsletter_success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'guest@example.com',
            'name' => 'John Doe',
            'status' => 'subscribed',
        ]);
    }

    public function test_ajax_newsletter_subscription_returns_json(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'vip.guest@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'promo_code' => 'WELCOME10',
        ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'vip.guest@example.com',
            'status' => 'subscribed',
        ]);
    }

    public function test_subscription_validates_email_format(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'invalid-email-address',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
