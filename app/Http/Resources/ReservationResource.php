<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'property_name' => $this->property?->title,
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'guests' => $this->guests,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'processing_fee' => (float) $this->processing_fee,
            'caution_fee' => (float) $this->caution_fee,
            'caution_fee_status' => $this->caution_fee_status,
            'total_price' => (float) $this->total_amount,
            'status' => $this->booking_status,
            'payment_status' => $this->payment_status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
