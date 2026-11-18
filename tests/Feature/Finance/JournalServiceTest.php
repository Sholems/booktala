<?php

namespace Tests\Feature\Finance;

use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\Unit;
use App\Services\Finance\AgreementResolver;
use App\Services\Finance\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private ManagementAgreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::factory()->create();
        $owner = Owner::create(['name' => 'Ledger Owner', 'type' => 'individual']);
        $resolver = app(AgreementResolver::class);
        $this->agreement = $resolver->create([
            'unit_id' => $this->unit->id,
            'owner_id' => $owner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-01-01',
        ]);
        $resolver->review($this->agreement);
    }

    public function test_posts_a_balanced_collection_with_separate_categories_and_attribution(): void
    {
        $transaction = app(JournalService::class)->post($this->collectionPayload());

        $this->assertSame(0, $transaction->entries->sum('amount_minor'));
        $this->assertSame([
            FinanceEntry::ACCOUNT_CASH,
            FinanceEntry::ACCOUNT_CAUTION_LIABILITY,
            FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION,
            FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME,
            FinanceEntry::ACCOUNT_VAT_PAYABLE,
        ], $transaction->entries->pluck('account')->sort()->values()->all());
        $this->assertTrue($transaction->entries->every(fn (FinanceEntry $entry) => $entry->owner_id === $this->agreement->owner_id
            && $entry->site_id === $this->unit->site_id
            && $entry->unit_id === $this->unit->id
            && $entry->management_agreement_id === $this->agreement->id
        ));
    }

    public function test_rejects_an_imbalanced_transaction_without_partial_rows(): void
    {
        $payload = $this->collectionPayload();
        $payload['entries'][0]['amount_minor']--;

        try {
            app(JournalService::class)->post($payload);
            $this->fail('An imbalanced transaction should be rejected.');
        } catch (\DomainException $exception) {
            $this->assertSame('Journal entries must balance to zero.', $exception->getMessage());
        }

        $this->assertSame(0, FinanceTransaction::count());
        $this->assertSame(0, FinanceEntry::count());
    }

    public function test_equivalent_idempotent_posts_return_one_transaction_and_entry_set_regardless_of_entry_order(): void
    {
        $journal = app(JournalService::class);
        $retry = $this->collectionPayload();
        $retry['entries'] = array_reverse($retry['entries']);

        $first = $journal->post($this->collectionPayload());
        $second = $journal->post($retry);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, FinanceTransaction::count());
        $this->assertSame(5, FinanceEntry::count());
    }

    public function test_conflicting_reuse_of_an_idempotency_key_is_rejected(): void
    {
        $journal = app(JournalService::class);
        $journal->post($this->collectionPayload());
        $conflict = $this->collectionPayload();
        $conflict['description'] = 'Different financial event';

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The idempotency key is already attached to a different journal payload.');

        $journal->post($conflict);
    }

    public function test_posted_transactions_and_entries_cannot_be_updated_or_deleted(): void
    {
        $transaction = app(JournalService::class)->post($this->collectionPayload());
        $entry = $transaction->entries->firstOrFail();

        foreach ([
            fn () => $transaction->update(['description' => 'Changed']),
            fn () => $transaction->delete(),
            fn () => $entry->update(['amount_minor' => 1]),
            fn () => $entry->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Posted journal records must be immutable.');
            } catch (\LogicException $exception) {
                $this->assertSame('Posted journal records are immutable.', $exception->getMessage());
            }
        }

        $this->assertSame(1, FinanceTransaction::count());
        $this->assertSame(5, FinanceEntry::count());
    }

    public function test_reversal_creates_opposite_entries_and_cannot_be_repeated(): void
    {
        $journal = app(JournalService::class);
        $original = $journal->post($this->collectionPayload());
        $reversal = $journal->reverse($original, 'payment:123:reversal', '2026-03-02 12:00:00', 'Collection reversed');
        $retry = $journal->reverse($original, 'payment:123:reversal', '2026-03-02 12:00:00', 'Collection reversed');

        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertTrue($reversal->is($retry));
        $this->assertSame(
            $original->entries->pluck('amount_minor')->map(fn (int $amount) => -$amount)->sort()->values()->all(),
            $reversal->entries->pluck('amount_minor')->sort()->values()->all(),
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This journal transaction has already been reversed.');

        $journal->reverse($original, 'payment:123:second-reversal', '2026-03-02 12:00:00', 'Duplicate reversal');
    }

    public function test_rejects_dimensions_that_conflict_with_the_referenced_agreement(): void
    {
        $otherOwner = Owner::create(['name' => 'Other Owner', 'type' => 'individual']);
        $payload = $this->collectionPayload();
        $payload['entries'][0]['owner_id'] = $otherOwner->id;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Journal agreement, owner, and unit attribution do not match.');

        app(JournalService::class)->post($payload);
    }

    private function collectionPayload(): array
    {
        $dimensions = [
            'owner_id' => $this->agreement->owner_id,
            'site_id' => $this->unit->site_id,
            'unit_id' => $this->unit->id,
            'management_agreement_id' => $this->agreement->id,
            'occurred_on' => '2026-03-01',
        ];

        return [
            'idempotency_key' => 'payment:123:collection',
            'source_type' => 'payment',
            'source_id' => '123',
            'event_type' => 'collection',
            'currency' => 'NGN',
            'occurred_at' => '2026-03-01 12:00:00',
            'description' => 'Booking collection',
            'entries' => [
                ['account' => FinanceEntry::ACCOUNT_CASH, 'amount_minor' => 6_500_000, ...$dimensions],
                ['account' => FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION, 'amount_minor' => -4_000_000, ...$dimensions],
                ['account' => FinanceEntry::ACCOUNT_VAT_PAYABLE, 'amount_minor' => -300_000, ...$dimensions],
                ['account' => FinanceEntry::ACCOUNT_PROCESSING_FEE_INCOME, 'amount_minor' => -200_000, ...$dimensions],
                ['account' => FinanceEntry::ACCOUNT_CAUTION_LIABILITY, 'amount_minor' => -2_000_000, ...$dimensions],
            ],
        ];
    }
}
