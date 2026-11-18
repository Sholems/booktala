<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FrontDeskBookingService
{
    public function __construct(
        private BookingService $bookings,
        private GuestService $guests,
        private PaymentService $payments,
        private ReservationLifecycle $lifecycle,
    ) {}

    public function create(User $actor, Unit $unit, array $data): Reservation
    {
        return DB::transaction(function () use ($actor, $unit, $data) {
            $reference = $data['reference'] ?? null;

            if ($reference && Payment::where('provider', 'offline')->where('reference', $reference)->exists()) {
                throw new \DomainException('This payment reference has already been recorded.');
            }
            if (($data['check_in_now'] ?? false) && ! $actor->can('reservation.manage')) {
                throw new \DomainException('You do not have permission to check in this guest immediately.');
            }

            $guest = $this->resolveGuest($actor, $data);
            $reservation = $this->bookings->createReservationForGuest(
                $guest,
                $unit,
                $data,
                $data['booking_source'] ?? 'walk_in',
                $actor,
            );

            $amount = (float) $data['amount'];
            if ($amount > (float) $reservation->total_amount + 0.01) {
                throw new \DomainException('The collection cannot exceed the booking total.');
            }
            $internalReference = 'OFFLINE-'.($reference ?: $reservation->booking_ref.'-'.strtoupper(bin2hex(random_bytes(4))));
            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'guest_id' => $guest->id,
                'amount' => $amount,
                'currency' => 'NGN',
                'provider' => 'offline',
                'reference' => $reference,
                'paystack_reference' => $internalReference,
                'paystack_status' => 'success',
                'payment_method' => $data['payment_method'],
                'type' => 'payment',
                'collected_by' => $actor->id,
                'collected_at' => now(),
            ]);
            $this->payments->applySuccessfulPayment($payment, $reservation);
            $this->issueInvoice($reservation->fresh(), $amount);

            if ($data['check_in_now'] ?? false) {
                $this->lifecycle->checkIn($reservation->fresh(), $actor);
            }

            return $reservation->fresh(['guest', 'payments', 'invoice.items']);
        });
    }

    private function resolveGuest(User $actor, array $data): Guest
    {
        if (! empty($data['guest_id'])) {
            return $this->guests->findForStaff($actor, (int) $data['guest_id'])
                ?? throw new \DomainException('The selected guest is not available within your assigned sites.');
        }

        $match = $this->guests->matchForStaff($actor, [
            'email' => $data['guest_email'] ?? null,
            'phone' => $data['guest_phone'] ?? null,
        ]);

        if ($match->requiresSelection()) {
            throw new \DomainException('Several guests match these details. Select the correct guest before continuing.');
        }

        return $match->guests->first() ?? Guest::create([
            'name' => $data['guest_name'],
            'email' => $data['guest_email'] ?? null,
            'phone' => $data['guest_phone'] ?? null,
        ]);
    }

    private function issueInvoice(Reservation $reservation, float $paidAmount): Invoice
    {
        $balance = max(0, (float) $reservation->total_amount - $paidAmount);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-FD-'.$reservation->booking_ref,
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'guest_id' => $reservation->guest_id,
            'subtotal' => $reservation->subtotal,
            'vat_amount' => $reservation->tax_amount,
            'processing_fee' => $reservation->processing_fee,
            'caution_fee' => $reservation->caution_fee,
            'discount_amount' => $reservation->discount_amount,
            'total_amount' => $reservation->total_amount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => $balance <= 0.01 ? 'paid' : 'partial',
            'due_date' => $reservation->check_in,
            'paid_at' => $balance <= 0.01 ? now() : null,
            'notes' => 'Issued for front-desk booking '.$reservation->booking_ref.'.',
        ]);

        $items = [
            ['description' => 'Accommodation - '.$reservation->unit->title, 'quantity' => $reservation->nights, 'unit_price' => (float) $reservation->subtotal / max(1, $reservation->nights), 'total' => $reservation->subtotal],
            ['description' => 'VAT', 'quantity' => 1, 'unit_price' => $reservation->tax_amount, 'total' => $reservation->tax_amount],
            ['description' => 'Processing fee', 'quantity' => 1, 'unit_price' => $reservation->processing_fee, 'total' => $reservation->processing_fee],
            ['description' => 'Refundable caution fee', 'quantity' => 1, 'unit_price' => $reservation->caution_fee, 'total' => $reservation->caution_fee],
        ];
        if ((float) $reservation->discount_amount > 0) {
            $items[] = ['description' => 'Discount', 'quantity' => 1, 'unit_price' => -(float) $reservation->discount_amount, 'total' => -(float) $reservation->discount_amount];
        }

        foreach ($items as $item) {
            InvoiceItem::create(['invoice_id' => $invoice->id] + $item);
        }

        return $invoice;
    }
}
