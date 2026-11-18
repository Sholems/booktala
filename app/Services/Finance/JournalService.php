<?php

namespace App\Services\Finance;

use App\Models\FinanceEntry;
use App\Models\FinanceTransaction;
use App\Models\ManagementAgreement;
use App\Models\Reservation;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalService
{
    public function post(array $payload): FinanceTransaction
    {
        $payload = $this->normalizePayload($payload);
        $this->validatePayload($payload);
        $payloadHash = $this->payloadHash($payload);

        if ($existing = FinanceTransaction::query()->where('idempotency_key', $payload['idempotency_key'])->first()) {
            return $this->matchingExisting($existing, $payloadHash);
        }

        try {
            return DB::transaction(function () use ($payload, $payloadHash) {
                if ($existing = FinanceTransaction::query()->where('idempotency_key', $payload['idempotency_key'])->first()) {
                    return $this->matchingExisting($existing, $payloadHash);
                }

                $transaction = FinanceTransaction::create([
                    'reference' => (string) Str::ulid(),
                    'idempotency_key' => $payload['idempotency_key'],
                    'payload_hash' => $payloadHash,
                    'source_type' => $payload['source_type'],
                    'source_id' => $payload['source_id'] ?? null,
                    'event_type' => $payload['event_type'],
                    'currency' => $payload['currency'],
                    'occurred_at' => $payload['occurred_at'],
                    'description' => $payload['description'] ?? null,
                    'reversal_of_id' => $payload['reversal_of_id'] ?? null,
                    'created_by' => $payload['created_by'] ?? null,
                ]);

                foreach ($payload['entries'] as $entry) {
                    $transaction->entries()->create($entry);
                }

                return $transaction->load('entries');
            }, 3);
        } catch (QueryException $exception) {
            $existing = FinanceTransaction::query()->where('idempotency_key', $payload['idempotency_key'])->first();

            if ($existing) {
                return $this->matchingExisting($existing, $payloadHash);
            }

            throw $exception;
        }
    }

    public function reverse(
        FinanceTransaction $original,
        string $idempotencyKey,
        CarbonInterface|string $occurredAt,
        ?string $description = null,
        ?int $createdBy = null,
    ): FinanceTransaction {
        $original->loadMissing('entries');
        $occurredAt = $occurredAt instanceof CarbonInterface
            ? CarbonImmutable::instance($occurredAt)
            : CarbonImmutable::parse($occurredAt);
        $payload = [
            'idempotency_key' => $idempotencyKey,
            'source_type' => 'finance_transaction',
            'source_id' => (string) $original->id,
            'event_type' => 'reversal',
            'currency' => $original->currency,
            'occurred_at' => $occurredAt->toDateTimeString(),
            'description' => $description,
            'reversal_of_id' => $original->id,
            'created_by' => $createdBy,
            'entries' => $original->entries->map(fn (FinanceEntry $entry) => [
                'account' => $entry->account,
                'amount_minor' => -$entry->amount_minor,
                'owner_id' => $entry->owner_id,
                'site_id' => $entry->site_id,
                'unit_id' => $entry->unit_id,
                'reservation_id' => $entry->reservation_id,
                'management_agreement_id' => $entry->management_agreement_id,
                'occurred_on' => $occurredAt->toDateString(),
            ])->all(),
        ];

        if ($original->reversal()->exists()) {
            $reversal = $original->reversal()->firstOrFail();

            if ($reversal->idempotency_key !== $idempotencyKey) {
                throw new \DomainException('This journal transaction has already been reversed.');
            }
        }

        return $this->post($payload);
    }

    private function normalizePayload(array $payload): array
    {
        $payload['currency'] = strtoupper((string) ($payload['currency'] ?? 'NGN'));
        $payload['occurred_at'] = CarbonImmutable::parse($payload['occurred_at'] ?? now())->toDateTimeString();
        $payload['source_id'] = $payload['source_id'] ?? null;
        $payload['description'] = $payload['description'] ?? null;
        $payload['reversal_of_id'] = $payload['reversal_of_id'] ?? null;
        $payload['created_by'] = $payload['created_by'] ?? null;
        $payload['entries'] = array_map(function (array $entry) {
            $entry['amount_minor'] = (int) ($entry['amount_minor'] ?? 0);
            $entry['occurred_on'] = CarbonImmutable::parse($entry['occurred_on'] ?? now())->toDateString();
            $entry['reservation_id'] = $entry['reservation_id'] ?? null;
            $entry['management_agreement_id'] = $entry['management_agreement_id'] ?? null;

            return Arr::only($entry, [
                'account',
                'amount_minor',
                'owner_id',
                'site_id',
                'unit_id',
                'reservation_id',
                'management_agreement_id',
                'occurred_on',
            ]);
        }, $payload['entries'] ?? []);

        return $payload;
    }

    private function validatePayload(array $payload): void
    {
        foreach (['idempotency_key', 'source_type', 'event_type', 'occurred_at'] as $field) {
            if (! isset($payload[$field]) || $payload[$field] === '') {
                throw new \DomainException("The journal {$field} is required.");
            }
        }

        if ($payload['currency'] !== 'NGN') {
            throw new \DomainException('The finance journal currently supports NGN only.');
        }

        if (count($payload['entries']) < 2) {
            throw new \DomainException('A journal transaction requires at least two entries.');
        }

        foreach ($payload['entries'] as $entry) {
            if (! in_array($entry['account'] ?? null, FinanceEntry::ACCOUNTS, true)) {
                throw new \DomainException('The journal contains an unsupported account.');
            }

            if (($entry['amount_minor'] ?? 0) === 0) {
                throw new \DomainException('Journal entry amounts cannot be zero.');
            }

            foreach (['owner_id', 'site_id', 'unit_id', 'occurred_on'] as $field) {
                if (! isset($entry[$field]) || $entry[$field] === '') {
                    throw new \DomainException("Every journal entry requires {$field} attribution.");
                }
            }
        }

        if (array_sum(array_column($payload['entries'], 'amount_minor')) !== 0) {
            throw new \DomainException('Journal entries must balance to zero.');
        }

        $this->validateDimensions($payload['entries']);
    }

    private function validateDimensions(array $entries): void
    {
        $units = Unit::query()
            ->whereKey(array_unique(array_column($entries, 'unit_id')))
            ->get()
            ->keyBy('id');
        $agreements = ManagementAgreement::query()
            ->whereKey(array_values(array_unique(array_filter(array_column($entries, 'management_agreement_id')))))
            ->get()
            ->keyBy('id');
        $reservations = Reservation::query()
            ->whereKey(array_values(array_unique(array_filter(array_column($entries, 'reservation_id')))))
            ->get()
            ->keyBy('id');

        foreach ($entries as $entry) {
            $unit = $units->get($entry['unit_id']);

            if (! $unit || $unit->site_id !== $entry['site_id']) {
                throw new \DomainException('Journal unit and site attribution do not match.');
            }

            if ($entry['management_agreement_id'] !== null) {
                $agreement = $agreements->get($entry['management_agreement_id']);

                if (! $agreement || $agreement->unit_id !== $entry['unit_id'] || $agreement->owner_id !== $entry['owner_id']) {
                    throw new \DomainException('Journal agreement, owner, and unit attribution do not match.');
                }
            }

            if ($entry['reservation_id'] !== null) {
                $reservation = $reservations->get($entry['reservation_id']);

                if (! $reservation || $reservation->property_id !== $entry['unit_id']) {
                    throw new \DomainException('Journal reservation and unit attribution do not match.');
                }
            }
        }
    }

    private function payloadHash(array $payload): string
    {
        unset($payload['idempotency_key']);

        $payload['entries'] = array_map(fn (array $entry) => $this->canonicalize($entry), $payload['entries']);
        usort($payload['entries'], function (array $left, array $right) {
            return strcmp(
                json_encode($left, JSON_THROW_ON_ERROR),
                json_encode($right, JSON_THROW_ON_ERROR),
            );
        });

        return hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR));
    }

    private function canonicalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        return $value;
    }

    private function matchingExisting(FinanceTransaction $existing, string $payloadHash): FinanceTransaction
    {
        if (! hash_equals($existing->payload_hash, $payloadHash)) {
            throw new \DomainException('The idempotency key is already attached to a different journal payload.');
        }

        return $existing->load('entries');
    }
}
