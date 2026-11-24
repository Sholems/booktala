<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalServiceReliabilityTest extends TestCase
{
    public function test_paystack_verification_retries_a_transient_failure(): void
    {
        config(['services.paystack.secret' => 'sk_test_example']);

        Http::fakeSequence()
            ->push(['status' => false], 503)
            ->push(['status' => true, 'data' => ['status' => 'success']], 200);

        $result = app(PaymentService::class)->verifyPayment('reference/with spaces');

        $this->assertTrue($result['status']);
        Http::assertSentCount(2);
    }

    public function test_termii_fallback_uses_configured_sender_and_normalized_phone(): void
    {
        config([
            'services.sendchamp.public_key' => null,
            'services.termii.key' => 'termii-test-key',
            'services.termii.sender_id' => 'BookTala',
        ]);
        Http::fake(['api.termii.com/*' => Http::response(['message_id' => 'sms-1'], 200)]);

        $this->assertTrue(app(SmsService::class)->sendSms('0801 234 5678', 'Your stay is confirmed.'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.termii.com/api/sms/send'
            && $request['to'] === '2348012345678'
            && $request['from'] === 'BookTala');
    }
}
