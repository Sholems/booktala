<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicRateLimitTest extends TestCase
{
    public function test_public_mutations_use_named_rate_limiters(): void
    {
        $this->assertContains('throttle:booking', Route::getRoutes()->getByName('booking.process')->gatherMiddleware());
        $this->assertContains('throttle:payment', Route::getRoutes()->getByName('booking.webhook')->gatherMiddleware());
        $this->assertContains('throttle:contact', Route::getRoutes()->getByName('pages.contact.submit')->gatherMiddleware());
    }

    public function test_api_auth_endpoints_are_rate_limited(): void
    {
        $login = Route::getRoutes()->match(request()->create('/api/auth/login', 'POST'));
        $register = Route::getRoutes()->match(request()->create('/api/auth/register', 'POST'));

        $this->assertContains('throttle:auth-api', $login->gatherMiddleware());
        $this->assertContains('throttle:auth-api', $register->gatherMiddleware());
    }
}
