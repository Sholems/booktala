<?php

namespace App\Listeners;

use App\Events\ReservationStatusChanged;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Task;
use App\Services\WorkRouter;

/**
 * When a guest checks out, queue the apartment for cleaning.
 *
 * Runs synchronously (not queued) so the task exists the moment check-out
 * is recorded, even where no queue worker is running.
 */
class CreateCleaningTaskAfterCheckout
{
    public function handle(ReservationStatusChanged $event): void
    {
        if ($event->newStatus !== 'checked_out') {
            return;
        }

        $reservation = $event->reservation;

        // One cleaning task per stay, even if the event fires twice.
        if (Task::where('reservation_id', $reservation->id)->where('type', 'cleaning')->exists()) {
            return;
        }

        $nextArrivalToday = Reservation::where('property_id', $reservation->property_id)
            ->where('id', '!=', $reservation->id)
            ->where('booking_status', 'confirmed')
            ->whereDate('check_in', today())
            ->exists();

        // Housekeeping on shift at this site first, anyone on shift there otherwise.
        $router = app(WorkRouter::class);
        $cleaner = $router->pick($reservation->property_id, ['housekeeping', 'laundry'], onShiftOnly: true, strict: false);

        $task = Task::create([
            'title' => 'Clean '.($reservation->property->name ?? 'apartment').' after check-out',
            'description' => 'Guest checked out of booking '.$reservation->booking_ref.'.'
                .($nextArrivalToday ? ' Another guest arrives today: prepare before check-in time.' : ''),
            'property_id' => $reservation->property_id,
            'reservation_id' => $reservation->id,
            'assigned_to' => $cleaner?->id,
            'assigned_by' => $event->actorId,
            'type' => 'cleaning',
            'priority' => $nextArrivalToday ? 'high' : 'medium',
            'status' => 'pending',
            'due_date' => today(),
        ]);

        if ($cleaner) {
            $router->notifyAssigned($cleaner, $task->title, $nextArrivalToday ? 'Next guest arrives today.' : 'Due today.', route('staff.tasks.show', $task));
        }

        if ($reservation->room_id
            && ! HousekeepingTask::where('room_id', $reservation->room_id)
                ->where('scheduled_date', today())
                ->whereIn('status', ['pending', 'in_progress'])
                ->exists()) {
            HousekeepingTask::create([
                'room_id' => $reservation->room_id,
                'assigned_to' => $cleaner?->id,
                'task_type' => 'turnover',
                'priority' => $nextArrivalToday ? 'urgent' : 'high',
                'status' => 'pending',
                'scheduled_date' => today(),
                'notes' => 'Turnover for booking '.$reservation->booking_ref,
            ]);
        }
    }
}
