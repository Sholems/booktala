<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Reservation $reservation;
    public string $oldStatus;
    public string $newStatus;
    public ?int $actorId;

    public function __construct(Reservation $reservation, string $oldStatus, string $newStatus, ?int $actorId = null)
    {
        $this->reservation = $reservation;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->actorId = $actorId;
    }
}