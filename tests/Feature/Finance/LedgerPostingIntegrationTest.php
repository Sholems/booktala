<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\Finance\AgreementResolver;
use App\Services\Finance\JournalService;
use App\Services\FrontDeskBookingService;
use App\Services\PaymentService;
use App\Services\ReservationLifecycle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerPostingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private ManagementAgreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->site = Site::create([
            'name' => 'Lekki Pearl',
            'address' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
        ]);

        $this->owner = Owner::create([
            'name' => 'Chief Okonkwo',
            'type' => 'individual',
            'is_company_owned' => false,
        ]);

        $this->unit = Unit::factory()->create([
            'title' => 'Pearl Suite 1',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
            'price_per_night' => 50000,
        ]);

        $resolver = app(AgreementResolver::class);
        $this->agreement = $resolver->create([
            'unit_id' => $this->unit->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 20.00,
            'deduct_expenses' => true,
            'caution_forfeiture_recipient' => 'owner',
            'effective_from' => '2026-01-01',
        ]);
        $resolver->review($this->agreement);
    }

    public function test_ae1_online_and_walk_in_collections_produce_balanced_journal_with_separate_categories(): void
    {
        // 1. Walk-in offline booking collection
        $frontDesk = app(FrontDeskBookingService::class);
        $checkIn = '2026-06-01';
        $checkOut = '2026-06-03'; // 2 nights * 50,000 = 100,000 subtotal, VAT 7.5% = 7,500, proc 2,000, caution 20,000 = 129,500 total

        $reservation = $frontDesk->create($this->admin, $this->unit, [
            'guest_name' => 'Ada Lovelace',
            'guest_email' => 'ada@example.com',
            'guest_phone' => '08031112222',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
            'payment_method' => 'cash',
            'amount' => 129500,
            'reference' => 'OFF-ADA-001',
        ]);

        $payment = $reservation->payments()->firstOrFail();
        $transaction = FinanceTransaction::where('source_type', 'payment')
            ->where('source_id', (string) $payment->id)
            ->firstOrFail();

        $this->assertSame(0, $transaction->entries->sum('amount_minor'));
        $this->assertSame([
            FinanceEntry::ACCOUNT_CASH,
            FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
            FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION,
            FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME,
            FinanceEntry::ACCOUNT_VAT_PAYABLE,
        ], $transaction->entries->pluck('account')->sort()->values()->all());

        $this->assertSame(12_950_000, $transaction->entries->where('account', FinanceEntry::ACCOUNT_CASH)->value('amount_minor'));
        $this->assertSame(-200_000, $transaction->entries->where('account', FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME)->value('amount_minor'));
        $this->assertSame(-2_000_000, $transaction->entries->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)->value('amount_minor'));
        $this->assertSame(-10_000_000, $transaction->entries->where('account', FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION)->value('amount_minor'));
        $this->assertSame(-750_000, $transaction->entries->where('account', FinanceEntry::ACCOUNT_VAT_PAYABLE)->value('amount_minor'));
    }

    public function test_two_partial_payments_allocate_processing_fee_once_and_hold_caution_only_on_full_payment(): void
    {
        $guest = Guest::create(['name' => 'Partial Guest', 'email' => 'partial@example.com', 'phone' => '08039998888']);
        $reservation = Reservation::create([
            'booking_ref' => Reservation::generateBookingRef(),
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-03',
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 7500,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 129500,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
        ]);

        $payments = app(PaymentService::class);

        // First partial payment: 50,000 NGN
        $payment1 = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 50000,
            'currency' => 'NGN',
            'provider' => 'offline',
            'reference' => 'PART-1',
            'paystack_reference' => 'PART-1',
            'paystack_status' => 'success',
            'payment_method' => 'transfer',
            'type' => 'payment',
        ]);
        $payments->applySuccessfulPayment($payment1, $reservation);

        $tx1 = FinanceTransaction::where('source_id', (string) $payment1->id)->firstOrFail();
        $this->assertSame(0, $tx1->entries->sum('amount_minor'));
        // First payment must take 2,000 processing fee, remainder to deferred accommodation & VAT, 0 to caution
        $this->assertSame(-200_000, $tx1->entries->where('account', FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME)->value('amount_minor'));
        $this->assertNull($tx1->entries->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)->first());

        // Second payment: balance of 79,500 NGN
        $payment2 = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 79500,
            'currency' => 'NGN',
            'provider' => 'offline',
            'reference' => 'PART-2',
            'paystack_reference' => 'PART-2',
            'paystack_status' => 'success',
            'payment_method' => 'transfer',
            'type' => 'payment',
        ]);
        $payments->applySuccessfulPayment($payment2, $reservation->fresh());

        $tx2 = FinanceTransaction::where('source_id', (string) $payment2->id)->firstOrFail();
        $this->assertSame(0, $tx2->entries->sum('amount_minor'));
        // Second payment must not take processing fee again, but must settle caution fee (20,000)
        $this->assertNull($tx2->entries->where('account', FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME)->first());
        $this->assertSame(-2_000_000, $tx2->entries->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)->value('amount_minor'));
        $this->assertSame('held', $reservation->fresh()->caution_fee_status);
    }

    public function test_ae3_duplicate_webhook_callbacks_produce_one_journal_transaction(): void
    {
        $guest = Guest::create(['name' => 'Webhook Guest', 'email' => 'webhook@example.com', 'phone' => '08034445555']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-TEST-WEBHOOK',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-02',
            'nights' => 1,
            'guests' => 1,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 3750,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 75750,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
        ]);

        $paymentService = app(PaymentService::class);
        $webhookData = [
            'reference' => 'WPA-TEST-WEBHOOK',
            'status' => 'success',
            'amount' => 7575000, // 75,750 * 100 kobo
            'currency' => 'NGN',
            'channel' => 'card',
        ];

        // Deliver twice
        $paymentService->markPaymentSuccessful($webhookData);
        $paymentService->markPaymentSuccessful($webhookData);

        $payment = Payment::where('reservation_id', $reservation->id)->firstOrFail();
        $transactions = FinanceTransaction::where('source_type', 'payment')
            ->where('source_id', (string) $payment->id)
            ->get();

        $this->assertCount(1, $transactions);
    }

    public function test_ae4_forced_journal_failure_rolls_back_payment(): void
    {
        $guest = Guest::create(['name' => 'Rollback Guest', 'email' => 'rollback@example.com', 'phone' => '08037778888']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-ROLLBACK-01',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-02',
            'nights' => 1,
            'guests' => 1,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 3750,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 75750,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
        ]);

        // Mock JournalService to simulate a database failure
        $mockJournal = $this->createMock(JournalService::class);
        $mockJournal->method('post')->willThrowException(new \DomainException('Forced journal failure'));
        $this->app->instance(JournalService::class, $mockJournal);

        try {
            app(PaymentService::class)->markPaymentSuccessful([
                'reference' => 'WPA-ROLLBACK-01',
                'status' => 'success',
                'amount' => 7575000,
                'currency' => 'NGN',
            ]);
            $this->fail('Expected exception was not thrown.');
        } catch (\DomainException $e) {
            $this->assertSame('Forced journal failure', $e->getMessage());
        }

        // Neither payment nor confirmed status should persist
        $this->assertDatabaseMissing('payments', ['reservation_id' => $reservation->id]);
        $this->assertSame('pending', $reservation->fresh()->payment_status);
        $this->assertSame('pending', $reservation->fresh()->booking_status);
    }

    public function test_stay_completion_recognizes_accommodation_commission_and_owner_payable(): void
    {
        $guest = Guest::create(['name' => 'Stay Guest', 'email' => 'stay@example.com', 'phone' => '08031239999']);
        $reservation = Reservation::create([
            'booking_ref' => 'WPA-COMPLETION-01',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => today()->subDays(2),
            'check_out' => today(),
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 7500,
            'processing_fee' => 2000,
            'caution_fee' => 20000,
            'total_amount' => 129500,
            'payment_status' => 'paid',
            'booking_status' => 'checked_in',
            'caution_fee_status' => 'held',
        ]);

        $lifecycle = app(ReservationLifecycle::class);
        $lifecycle->checkOut($reservation, $this->admin);

        $tx = FinanceTransaction::where('source_type', 'reservation')
            ->where('source_id', (string) $reservation->id)
            ->where('event_type', 'stay_completion')
            ->firstOrFail();

        $this->assertSame(0, $tx->entries->sum('amount_minor'));
        // Debits deferred accommodation (+100,000 kobo)
        $this->assertSame(10_000_000, $tx->entries->where('account', FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION)->value('amount_minor'));
        // 20% commission to BookTala (-20,000 kobo)
        $this->assertSame(-2_000_000, $tx->entries->where('account', FinanceEntry::ACCOUNT_COMMISSION_INCOME)->value('amount_minor'));
        // 80% payable to owner (-80,000 kobo)
        $this->assertSame(-8_000_000, $tx->entries->where('account', FinanceEntry::ACCOUNT_OWNER_PAYABLE)->value('amount_minor'));
    }

    public function test_caution_refund_clears_liability_and_caution_forfeiture_credits_recipient(): void
    {
        $guest = Guest::create(['name' => 'Caution Guest', 'email' => 'caution@example.com', 'phone' => '08035551212']);
        $resRefund = Reservation::create([
            'booking_ref' => 'WPA-CAUTION-REF',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => today()->subDays(3),
            'check_out' => today()->subDay(),
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 100000,
            'caution_fee' => 20000,
            'total_amount' => 129500,
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
            'caution_fee_status' => 'refund_pending',
        ]);

        // Process caution refund
        $this->actingAs($this->admin)->patch(route('admin.reservations.caution-refund', $resRefund), [
            'caution_fee_status' => 'refunded',
        ]);

        $refundTx = FinanceTransaction::where('source_type', 'reservation')
            ->where('source_id', (string) $resRefund->id)
            ->where('event_type', 'caution_refund')
            ->firstOrFail();

        $this->assertSame(0, $refundTx->entries->sum('amount_minor'));
        $this->assertSame(2_000_000, $refundTx->entries->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)->value('amount_minor'));
        $this->assertSame(-2_000_000, $refundTx->entries->where('account', FinanceEntry::ACCOUNT_PROVIDER_CLEARING)->value('amount_minor'));

        // Process caution forfeiture on another reservation
        $resForfeit = Reservation::create([
            'booking_ref' => 'WPA-CAUTION-FORFEIT',
            'guest_id' => $guest->id,
            'property_id' => $this->unit->id,
            'check_in' => today()->subDays(3),
            'check_out' => today()->subDay(),
            'nights' => 2,
            'guests' => 1,
            'subtotal' => 100000,
            'caution_fee' => 20000,
            'total_amount' => 129500,
            'payment_status' => 'paid',
            'booking_status' => 'checked_out',
            'caution_fee_status' => 'refund_pending',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.reservations.caution-refund', $resForfeit), [
            'caution_fee_status' => 'forfeited',
        ]);

        $forfeitTx = FinanceTransaction::where('source_type', 'reservation')
            ->where('source_id', (string) $resForfeit->id)
            ->where('event_type', 'caution_forfeiture')
            ->firstOrFail();

        $this->assertSame(0, $forfeitTx->entries->sum('amount_minor'));
        $this->assertSame(2_000_000, $forfeitTx->entries->where('account', FinanceEntry::ACCOUNT_CAUTION_LIABILITY)->value('amount_minor'));
        // Agreement sets caution_forfeiture_recipient = 'owner'
        $this->assertSame(-2_000_000, $forfeitTx->entries->where('account', FinanceEntry::ACCOUNT_OWNER_PAYABLE)->value('amount_minor'));
    }

    public function test_expense_posts_to_journal_within_site_scope(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'property_id' => $this->unit->id,
            'category' => 'repairs',
            'description' => 'Fix bathroom leak',
            'amount' => 15000,
            'spent_on' => today()->toDateString(),
            'charge_to' => 'owner',
        ]);
        $response->assertSessionHas('success');

        $expense = Expense::where('property_id', $this->unit->id)->firstOrFail();
        $tx = FinanceTransaction::where('source_type', 'expense')
            ->where('source_id', (string) $expense->id)
            ->firstOrFail();

        $this->assertSame(0, $tx->entries->sum('amount_minor'));
        $this->assertSame(1_500_000, $tx->entries->where('account', FinanceEntry::ACCOUNT_OWNER_RECOVERABLE_EXPENSE)->value('amount_minor'));
        $this->assertSame(-1_500_000, $tx->entries->where('account', FinanceEntry::ACCOUNT_CASH)->value('amount_minor'));
    }
}
