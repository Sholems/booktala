<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliatePayout;
use App\Models\AffiliateReferral;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AffiliateCommissionService;
use App\Services\ReservationLifecycle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_affiliate_landing_page(): void
    {
        $response = $this->get(route('affiliate.join'));
        $response->assertOk();
        $response->assertSeeText('Earn 5% Commission on Every Shortlet Booking');
        $response->assertSee(route('register'));
        $response->assertSee(route('login'));

        $aliasResponse = $this->get('/affiliate');
        $aliasResponse->assertOk();
        $aliasResponse->assertSeeText('Earn 5% Commission on Every Shortlet Booking');
    }

    public function test_user_can_view_affiliate_join_page_and_register_as_partner(): void
    {
        $user = User::factory()->create(['name' => 'Babatunde Adeleke']);

        $response = $this->actingAs($user)->get(route('affiliate.join'));
        $response->assertOk();
        $response->assertSeeText('Earn 5% Commission on Every Shortlet Booking');

        $postResponse = $this->actingAs($user)->post(route('affiliate.join.store'), [
            'code' => 'ADELEKE5',
            'bank_name' => 'Guaranty Trust Bank',
            'account_number' => '0123456789',
            'account_name' => 'Babatunde Adeleke',
        ]);

        $postResponse->assertRedirect(route('affiliate.dashboard'));

        $this->assertDatabaseHas('affiliates', [
            'user_id' => $user->id,
            'code' => 'ADELEKE5',
            'commission_rate' => 5.00,
            'bank_name' => 'Guaranty Trust Bank',
            'account_number' => '0123456789',
            'status' => 'active',
        ]);
    }

    public function test_affiliate_referral_tracking_middleware_sets_cookie_and_logs_click(): void
    {
        $partnerUser = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'LEKKIVIP',
            'commission_rate' => 5.00,
            'status' => 'active',
        ]);

        $response = $this->get('/apartments?ref=LEKKIVIP');
        $response->assertOk();
        $response->assertCookie('bt_affiliate', 'LEKKIVIP');

        $this->assertDatabaseHas('affiliate_clicks', [
            'affiliate_id' => $affiliate->id,
        ]);
    }

    public function test_confirmed_reservation_attributes_commission_on_net_accommodation_base_only(): void
    {
        $partnerUser = User::factory()->create(['email' => 'partner@example.com']);
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'PARTNER5',
            'commission_rate' => 5.00,
            'status' => 'active',
        ]);

        $guestUser = User::factory()->create(['email' => 'traveler@example.com']);
        $property = Property::create([
            'title' => 'Lekki Waterways Residence',
            'slug' => 'lekki-waterways-residence',
            'description' => 'Serviced unit in Lekki.',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 4,
            'min_nights' => 1,
            'max_nights' => 30,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ]);

        // 3 nights @ 100k = 300k room base. Plus 20k caution fee + 5k processing fee = 325k total.
        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guestUser->id,
            'property_id' => $property->id,
            'check_in' => '2030-05-01',
            'check_out' => '2030-05-04',
            'nights' => 3,
            'guests' => 2,
            'subtotal' => 300000,
            'total_amount' => 325000,
            'caution_fee' => 20000,
            'processing_fee' => 5000,
            'booking_status' => 'pending',
            'payment_status' => 'paid',
        ]);

        // Call lifecycle confirmation with affiliate cookie attached
        $service = app(AffiliateCommissionService::class);
        $referral = $service->recordReferral($reservation, 'PARTNER5');

        $this->assertNotNull($referral);
        $this->assertEquals(300000.00, (float) $referral->base_accommodation_amount);
        $this->assertEquals(15000.00, (float) $referral->commission_amount); // 5% of 300,000 = 15,000
        $this->assertEquals('pending', $referral->status);

        $this->assertDatabaseHas('affiliate_referrals', [
            'affiliate_id' => $affiliate->id,
            'reservation_id' => $reservation->id,
            'commission_amount' => 15000.00,
            'status' => 'pending',
        ]);
    }

    public function test_self_referral_is_strictly_rejected(): void
    {
        $affiliateUser = User::factory()->create(['email' => 'self@example.com']);
        $affiliate = Affiliate::create([
            'user_id' => $affiliateUser->id,
            'code' => 'SELF5',
            'commission_rate' => 5.00,
            'status' => 'active',
        ]);

        $property = Property::create([
            'title' => 'Self Book Villa',
            'slug' => 'self-book-villa',
            'description' => 'Nice villa',
            'address' => 'Victoria Island',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 2,
            'min_nights' => 1,
            'max_nights' => 10,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
        ]);

        // Affiliate tries booking for themselves
        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $affiliateUser->id,
            'property_id' => $property->id,
            'check_in' => '2030-06-01',
            'check_out' => '2030-06-03',
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 200000,
            'total_amount' => 200000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $service = app(AffiliateCommissionService::class);
        $referral = $service->recordReferral($reservation, 'SELF5');

        $this->assertNull($referral);
        $this->assertDatabaseMissing('affiliate_referrals', [
            'affiliate_id' => $affiliate->id,
            'reservation_id' => $reservation->id,
        ]);
    }

    public function test_commissions_mature_and_update_balance_post_stay(): void
    {
        $partnerUser = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'MATURE5',
            'commission_rate' => 5.00,
            'current_balance' => 0.00,
            'lifetime_earnings' => 0.00,
            'status' => 'active',
        ]);

        $guestUser = User::factory()->create();
        $property = Property::create([
            'title' => 'Mature Suite',
            'slug' => 'mature-suite',
            'description' => 'Suite',
            'address' => 'Ikoyi',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 2,
            'min_nights' => 1,
            'max_nights' => 10,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
        ]);

        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guestUser->id,
            'property_id' => $property->id,
            'check_in' => '2026-05-01',
            'check_out' => '2026-05-03',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 200000,
            'total_amount' => 200000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id,
            'reservation_id' => $reservation->id,
            'guest_user_id' => $guestUser->id,
            'base_accommodation_amount' => 200000.00,
            'commission_rate' => 5.00,
            'commission_amount' => 10000.00,
            'status' => 'pending',
            'check_in' => '2026-05-01',
            'check_out' => '2026-05-03',
            'matured_at' => now()->subHour(), // Past maturity
        ]);

        $this->artisan('affiliates:mature-commissions')->assertSuccessful();

        $referral->refresh();
        $affiliate->refresh();

        $this->assertEquals('approved', $referral->status);
        $this->assertEquals(10000.00, (float) $affiliate->current_balance);
        $this->assertEquals(10000.00, (float) $affiliate->lifetime_earnings);
    }

    public function test_cancelled_reservation_voids_commission(): void
    {
        $partnerUser = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'CANCEL5',
            'commission_rate' => 5.00,
            'status' => 'active',
        ]);

        $guestUser = User::factory()->create();
        $property = Property::create([
            'title' => 'Cancel Suite',
            'slug' => 'cancel-suite',
            'description' => 'Suite',
            'address' => 'Ikoyi',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'price_per_night' => 100000,
            'max_guests' => 2,
            'min_nights' => 1,
            'max_nights' => 10,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'toilets' => 1,
            'status' => 'active',
        ]);

        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'user_id' => $guestUser->id,
            'property_id' => $property->id,
            'check_in' => Carbon::tomorrow()->toDateString(),
            'check_out' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 200000,
            'total_amount' => 200000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id,
            'reservation_id' => $reservation->id,
            'guest_user_id' => $guestUser->id,
            'base_accommodation_amount' => 200000.00,
            'commission_rate' => 5.00,
            'commission_amount' => 10000.00,
            'status' => 'pending',
            'check_in' => $reservation->check_in,
            'check_out' => $reservation->check_out,
            'matured_at' => Carbon::parse($reservation->check_out)->addDay(),
        ]);

        app(ReservationLifecycle::class)->cancel($reservation, $guestUser, 'Guest requested cancellation');

        $referral->refresh();
        $this->assertEquals('void', $referral->status);
    }

    public function test_affiliate_can_request_payout_with_minimum_threshold(): void
    {
        $partnerUser = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'PAYOUT5',
            'commission_rate' => 5.00,
            'current_balance' => 50000.00,
            'lifetime_earnings' => 50000.00,
            'bank_name' => 'Zenith Bank',
            'account_number' => '1012345678',
            'account_name' => 'John Partner',
            'status' => 'active',
        ]);

        // Attempt below minimum threshold 20,000 should fail
        $failResponse = $this->actingAs($partnerUser)->post(route('affiliate.payouts.request'), [
            'amount' => 15000,
        ]);
        $failResponse->assertSessionHasErrors('amount');

        // Valid withdrawal request of 25,000
        $response = $this->actingAs($partnerUser)->post(route('affiliate.payouts.request'), [
            'amount' => 25000,
        ]);
        $response->assertSessionHas('success');

        $affiliate->refresh();
        $this->assertEquals(25000.00, (float) $affiliate->current_balance);

        $this->assertDatabaseHas('affiliate_payouts', [
            'affiliate_id' => $affiliate->id,
            'amount' => 25000.00,
            'status' => 'requested',
            'account_number' => '1012345678',
        ]);
    }

    public function test_admin_can_view_affiliates_and_complete_payout(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $partnerUser = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $partnerUser->id,
            'code' => 'ADMINTEST',
            'commission_rate' => 5.00,
            'current_balance' => 10000.00,
            'bank_name' => 'Access Bank',
            'account_number' => '0011223344',
            'account_name' => 'Partner Guy',
            'status' => 'active',
        ]);

        $payout = AffiliatePayout::create([
            'affiliate_id' => $affiliate->id,
            'payout_ref' => 'PAY-TEST12345',
            'amount' => 20000.00,
            'bank_name' => 'Access Bank',
            'account_number' => '0011223344',
            'account_name' => 'Partner Guy',
            'status' => 'requested',
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.affiliates.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('ADMINTEST');

        $payoutsResponse = $this->actingAs($admin)->get(route('admin.affiliates.payouts'));
        $payoutsResponse->assertOk();
        $payoutsResponse->assertSee('PAY-TEST12345');

        $completeResponse = $this->actingAs($admin)->post(route('admin.affiliates.payouts.complete', $payout->id), [
            'transfer_reference' => 'NIP-WIRE-987654321',
            'notes' => 'Settled via GTBank online banking transfer.',
        ]);
        $completeResponse->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('completed', $payout->status);
        $this->assertEquals('NIP-WIRE-987654321', $payout->transfer_reference);
        $this->assertNotNull($payout->paid_at);
    }
}
