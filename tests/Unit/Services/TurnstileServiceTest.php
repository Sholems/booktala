<?php

namespace Tests\Unit\Services;

use App\Services\TurnstileService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('turnstile', [
            'enabled' => true,
            'secret' => 'test-secret',
            'hostnames' => ['localhost', 'booktala.com'],
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            'timeout' => 10,
        ]);
    }

    public function test_it_accepts_a_successful_response_for_the_expected_action_and_hostname(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'localhost',
            ]),
        ]);

        $verified = app(TurnstileService::class)->verify('valid-token', 'login', '127.0.0.1');

        $this->assertTrue($verified);
        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-token'
            && $request['remoteip'] === '127.0.0.1');
    }

    public function test_it_rejects_a_token_issued_for_another_action(): void
    {
        Http::fake(['*' => Http::response([
            'success' => true,
            'action' => 'register',
            'hostname' => 'localhost',
        ])]);

        $this->assertFalse(app(TurnstileService::class)->verify('valid-token', 'login'));
    }

    public function test_it_rejects_a_token_from_an_unapproved_hostname(): void
    {
        Http::fake(['*' => Http::response([
            'success' => true,
            'action' => 'login',
            'hostname' => 'attacker.example',
        ])]);

        $this->assertFalse(app(TurnstileService::class)->verify('valid-token', 'login'));
    }

    public function test_it_fails_closed_when_cloudflare_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Unavailable'));

        $this->assertFalse(app(TurnstileService::class)->verify('valid-token', 'login'));
    }

    public function test_it_skips_remote_verification_when_disabled(): void
    {
        config()->set('turnstile.enabled', false);
        Http::fake();

        $this->assertTrue(app(TurnstileService::class)->verify('', 'login'));
        Http::assertNothingSent();
    }
}
