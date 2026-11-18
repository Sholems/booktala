<?php

namespace App\Services;

use App\Models\GuestCheckin;
use App\Models\MaintenanceReport;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\RoomServiceOrder;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What needs doing now, and what just happened, across the operation.
 * Feeds the admin and staff dashboards.
 */
class OperationsOverview
{
    /**
     * Work queues for the admin dashboard, most urgent first. Each links to the list that shows those items.
     *
     * @return array<int, array{label: string, count: int, url: string, icon: string, tone: string}>
     */
    public function adminQueues(): array
    {
        $queues = [];
        foreach ([
            'arrivals' => ['fa-plane-arrival', 'blue'],
            'departures' => ['fa-door-open', 'indigo'],
            'to_confirm' => ['fa-hourglass-half', 'amber'],
            'unpaid' => ['fa-money-bill-wave', 'red'],
            'refunds' => ['fa-undo', 'red'],
        ] as $queue => [$icon, $tone]) {
            $queues[] = [
                'label' => Reservation::QUEUES[$queue],
                'count' => Reservation::inQueue($queue)->count(),
                'url' => route('admin.reservations.index', ['queue' => $queue]),
                'icon' => $icon,
                'tone' => $tone,
            ];
        }

        $queues[] = [
            'label' => 'Open room service',
            'count' => RoomServiceOrder::whereIn('status', RoomServiceOrder::OPEN_STATUSES)->count(),
            'url' => route('admin.room-service.index'),
            'icon' => 'fa-concierge-bell',
            'tone' => 'purple',
        ];
        $queues[] = [
            'label' => 'Unassigned maintenance',
            'count' => MaintenanceReport::where('status', '!=', 'resolved')->whereNull('assigned_to')->count(),
            'url' => route('admin.maintenance.index'),
            'icon' => 'fa-tools',
            'tone' => 'orange',
        ];
        $queues[] = [
            'label' => 'Unassigned tasks',
            'count' => Task::where('status', '!=', 'completed')->whereNull('assigned_to')->count(),
            'url' => route('admin.tasks.index', ['show' => 'unassigned']),
            'icon' => 'fa-broom',
            'tone' => 'teal',
        ];
        $queues[] = [
            'label' => 'Unread guest messages',
            'count' => $this->unreadGuestMessages(),
            'url' => route('admin.messages.index'),
            'icon' => 'fa-envelope',
            'tone' => 'green',
        ];

        return $queues;
    }

    /**
     * Today's work for one staff member, for the staff dashboard.
     *
     * @return array<int, array{label: string, count: int, url: string, icon: string, color: string}>
     */
    public function staffToday(User $staff): array
    {
        return [
            [
                'label' => 'Arrivals today',
                'count' => Reservation::visibleTo($staff)->inQueue('arrivals')->count(),
                'url' => route('staff.checkin.index'),
                'icon' => 'fa-plane-arrival',
                'color' => 'text-blue-600',
            ],
            [
                'label' => 'Departures due',
                'count' => Reservation::visibleTo($staff)->inQueue('departures')->count(),
                'url' => route('staff.checkout.index'),
                'icon' => 'fa-door-open',
                'color' => 'text-indigo-600',
            ],
            [
                'label' => 'My open tasks',
                'count' => Task::where('assigned_to', $staff->id)->where('status', '!=', 'completed')->count(),
                'url' => route('staff.tasks.index'),
                'icon' => 'fa-broom',
                'color' => 'text-teal-600',
            ],
            [
                'label' => 'Room service waiting',
                'count' => RoomServiceOrder::visibleTo($staff)->whereIn('status', RoomServiceOrder::OPEN_STATUSES)->count(),
                'url' => route('staff.room-service.index'),
                'icon' => 'fa-concierge-bell',
                'color' => 'text-purple-600',
            ],
            [
                'label' => 'My maintenance jobs',
                'count' => MaintenanceReport::where('assigned_to', $staff->id)->where('status', '!=', 'resolved')->count(),
                'url' => route('staff.maintenance.index'),
                'icon' => 'fa-tools',
                'color' => 'text-orange-600',
            ],
        ];
    }

    public function unreadGuestMessages(): int
    {
        // Messages sent by guests (customers) that nobody has read yet.
        return Message::where('read', false)
            ->whereHas('sender', fn ($q) => $q->role('customer'))
            ->count();
    }

    /**
     * A readable feed of recent operational events, newest first.
     *
     * @return Collection<int, array{at: CarbonInterface, icon: string, text: string, url: ?string}>
     */
    public function recentActivity(int $limit = 12): Collection
    {
        $since = now()->subDays(14);
        $events = collect();

        $bookings = Reservation::with(['user', 'property', 'confirmedBy', 'checkedInBy', 'checkedOutBy', 'cancelledBy'])
            ->where(fn ($q) => $q->where('created_at', '>=', $since)
                ->orWhere('confirmed_at', '>=', $since)
                ->orWhere('checked_in_at', '>=', $since)
                ->orWhere('checked_out_at', '>=', $since)
                ->orWhere('cancelled_at', '>=', $since))
            ->latest('updated_at')
            ->limit(50)
            ->get();

        foreach ($bookings as $r) {
            $guest = $r->user->name ?? 'A guest';
            $apt = $r->property->name ?? 'an apartment';
            $url = route('admin.reservations.show', $r);
            $by = fn ($user) => $user ? ' by '.$user->name : '';

            $events->push(['at' => $r->created_at, 'icon' => 'fa-calendar-plus', 'text' => "{$guest} booked {$apt} ({$r->booking_ref})", 'url' => $url]);
            if ($r->confirmed_at) {
                $events->push(['at' => $r->confirmed_at, 'icon' => 'fa-check-circle', 'text' => "Booking {$r->booking_ref} confirmed".$by($r->confirmedBy), 'url' => $url]);
            }
            if ($r->checked_in_at) {
                $events->push(['at' => $r->checked_in_at, 'icon' => 'fa-sign-in-alt', 'text' => "{$guest} checked in to {$apt}".$by($r->checkedInBy), 'url' => $url]);
            }
            if ($r->checked_out_at) {
                $events->push(['at' => $r->checked_out_at, 'icon' => 'fa-sign-out-alt', 'text' => "{$guest} checked out of {$apt}".$by($r->checkedOutBy), 'url' => $url]);
            }
            if ($r->cancelled_at) {
                $events->push(['at' => $r->cancelled_at, 'icon' => 'fa-times-circle', 'text' => "Booking {$r->booking_ref} cancelled".$by($r->cancelledBy), 'url' => $url]);
            }
        }

        GuestCheckin::with(['reservation.user', 'verifiedBy'])->where('verified_at', '>=', $since)->latest('verified_at')->limit(10)->get()
            ->each(fn ($c) => $events->push([
                'at' => $c->verified_at,
                'icon' => 'fa-id-card',
                'text' => 'ID verified for '.($c->reservation->user->name ?? 'a guest').' by '.($c->verifiedBy->name ?? 'staff'),
                'url' => $c->reservation ? route('admin.reservations.show', $c->reservation) : null,
            ]));

        RoomServiceOrder::with(['user', 'handledBy'])->where('delivered_at', '>=', $since)->latest('delivered_at')->limit(10)->get()
            ->each(fn ($o) => $events->push([
                'at' => $o->delivered_at,
                'icon' => 'fa-concierge-bell',
                'text' => 'Room service delivered to '.($o->user->name ?? 'a guest').($o->handledBy ? ' by '.$o->handledBy->name : ''),
                'url' => route('admin.room-service.index', ['show' => 'done']),
            ]));

        MaintenanceReport::with(['property', 'assignedTo'])->where('resolved_at', '>=', $since)->latest('resolved_at')->limit(10)->get()
            ->each(fn ($m) => $events->push([
                'at' => $m->resolved_at,
                'icon' => 'fa-wrench',
                'text' => "Fixed: {$m->title}".($m->assignedTo ? ' by '.$m->assignedTo->name : ''),
                'url' => route('admin.maintenance.index', ['show' => 'resolved']),
            ]));

        return $events->filter(fn ($e) => $e['at'] !== null)->sortByDesc('at')->take($limit)->values();
    }
}
