<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\PatrolCheckpoint;
use App\Models\Site;
use App\Models\User;
use App\Notifications\OperationsAlert;
use App\Support\Supervision;
use Carbon\CarbonImmutable;

/**
 * Incident alerts and patrol checks.
 */
class SecurityService
{
    /**
     * High incidents go to the site's supervisors; critical ones also to managers and head office.
     */
    public function alertIncident(Incident $incident): void
    {
        if (!$incident->isSerious()) {
            return;
        }

        $recipients = Supervision::alertRecipients($incident->site_id, 1);
        if ($incident->severity === 'critical') {
            $recipients = $recipients->merge(Supervision::alertRecipients($incident->site_id, 2))->unique('id');
        }

        $site = $incident->site;
        foreach ($recipients->where('id', '!=', $incident->reported_by) as $user) {
            $isHeadOffice = $user->hasAnyRole(['super_admin', 'admin']);
            $user->notify(new OperationsAlert(
                ucfirst($incident->severity) . ' incident at ' . $site->name . ': ' . $incident->title,
                (Incident::CATEGORIES[$incident->category] ?? $incident->category) . ' · reported by ' . ($incident->reportedBy->name ?? 'staff') . ' · ' . $incident->occurred_at->format('D j M, g:i A'),
                route(($isHeadOffice ? 'admin' : 'staff') . '.security.incidents.show', $incident),
                'escalated',
            ));
        }
    }

    /**
     * Checkpoints not visited within twice the site's patrol interval, during
     * patrol hours, are reported once to the supervisor.
     */
    public function reportMissedPatrols(): int
    {
        $count = 0;
        $now = CarbonImmutable::now();

        $checkpoints = PatrolCheckpoint::where('is_active', true)
            ->whereHas('site', fn ($q) => $q->where('is_active', true)->whereNotNull('patrol_interval_minutes'))
            ->with(['site', 'lastScan'])
            ->get();

        foreach ($checkpoints as $checkpoint) {
            $site = $checkpoint->site;
            if (!$site->patrolsAt($now)) {
                continue;
            }

            // The clock starts at the last visit, or when patrol hours began, whichever is later.
            $since = collect([$checkpoint->lastScan?->scanned_at, $this->windowStart($site, $now), $checkpoint->created_at])
                ->filter()
                ->max();
            $overdueAfter = $site->patrol_interval_minutes * 2;
            if (CarbonImmutable::parse($since)->diffInMinutes($now) < $overdueAfter) {
                continue;
            }
            // One alert per gap: re-armed by the next scan.
            if ($checkpoint->missed_alerted_at && $checkpoint->missed_alerted_at->gt($since)) {
                continue;
            }

            foreach (Supervision::alertRecipients($site->id, 1) as $user) {
                $isHeadOffice = $user->hasAnyRole(['super_admin', 'admin']);
                $user->notify(new OperationsAlert(
                    'Patrol missed: ' . $checkpoint->name . ' at ' . $site->name,
                    'Not visited since ' . CarbonImmutable::parse($since)->format('g:i A') . ' (patrols are due every ' . $site->patrol_interval_minutes . ' minutes).',
                    route(($isHeadOffice ? 'admin' : 'staff') . '.security.index', ['site' => $site->id]),
                    'escalated',
                ));
            }
            $checkpoint->forceFill(['missed_alerted_at' => $now])->saveQuietly();
            $count++;
        }

        return $count;
    }

    /**
     * When the current patrol window opened (null for round-the-clock patrols).
     */
    private function windowStart(Site $site, CarbonImmutable $now): ?CarbonImmutable
    {
        if (!$site->patrol_from || !$site->patrol_until) {
            return null;
        }

        $start = $now->setTimeFromTimeString($site->patrol_from);

        return $start->gt($now) ? $start->subDay() : $start;
    }

    /**
     * Whether a user can run security and stock for a site (supervisor there, or head office).
     */
    public static function canManage(?User $user, ?int $siteId): bool
    {
        return \App\Support\PropertyAccess::seesAll($user) || Supervision::supervises($user, $siteId);
    }
}
