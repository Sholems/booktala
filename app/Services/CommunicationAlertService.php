<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\SiteMessage;
use App\Models\StaffAssignment;
use App\Models\User;
use App\Notifications\OperationsAlert;
use Illuminate\Support\Str;

class CommunicationAlertService
{
    /**
     * Dispatch in-app notifications when an operational message is posted to a site feed.
     */
    public function notifySiteMessage(SiteMessage $siteMessage, User $sender): void
    {
        $site = $siteMessage->site;
        if (! $site) {
            return;
        }

        $categoryLabel = SiteMessage::CATEGORIES[$siteMessage->category]['label'] ?? ucfirst($siteMessage->category);
        $preview = Str::limit($siteMessage->message, 80);
        $title = "📣 [{$site->name}] {$categoryLabel}";
        $body = "{$sender->name}: {$preview}";
        $url = route('staff.team.feed.index', ['site_id' => $site->id, 'category' => $siteMessage->category]);
        $kind = in_array($siteMessage->category, ['urgent', 'security']) ? 'escalated' : 'info';

        // 1. Notify all active staff members assigned to this site (excluding sender)
        $staffUserIds = StaffAssignment::active()
            ->where('site_id', $site->id)
            ->join('staff', 'staff.id', '=', 'staff_assignments.staff_id')
            ->where('staff.is_active', true)
            ->where('staff.user_id', '!=', $sender->id)
            ->pluck('staff.user_id')
            ->unique();

        $staffUsers = User::whereIn('id', $staffUserIds)->get();
        foreach ($staffUsers as $staffUser) {
            $staffUser->notify(new OperationsAlert($title, $body, $url, $kind, email: false));
        }

        // 2. If urgent or security, also notify supervisors & admins
        if (in_array($siteMessage->category, ['urgent', 'security'])) {
            $adminUrl = route('admin.site-feed.index', ['site_id' => $site->id, 'category' => $siteMessage->category]);
            $admins = User::role(['admin', 'super_admin'])->where('id', '!=', $sender->id)->get();
            foreach ($admins as $admin) {
                $admin->notify(new OperationsAlert("[URGENT ALERT] {$site->name}", $body, $adminUrl, 'escalated', email: false));
            }
        }
    }

    /**
     * Dispatch in-app notifications for direct conversations (Staff, Owner, Guest).
     */
    public function notifyDirectMessage(Message $message, Conversation $conversation, User $sender): void
    {
        $preview = Str::limit($message->message, 80);

        if ($conversation->type === 'staff') {
            $staffUser = $conversation->user;
            if (! $staffUser) {
                return;
            }

            if ($sender->id === $staffUser->id) {
                // Staff sent a message to Admin
                $title = "📩 Staff Inquiry from {$staffUser->name}";
                $body = "{$preview}";
                $url = route('admin.messages.show', $conversation);

                $admins = User::role(['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new OperationsAlert($title, $body, $url, 'info', email: false));
                }
            } else {
                // Admin replied to Staff
                $title = "💬 Management Reply: {$sender->name}";
                $body = "{$preview}";
                $url = route('staff.support.show', $conversation);

                $staffUser->notify(new OperationsAlert($title, $body, $url, 'info', email: false));
            }
        } elseif ($conversation->type === 'owner') {
            $ownerUser = $conversation->user;
            if (! $ownerUser) {
                return;
            }

            if ($sender->id === $ownerUser->id) {
                // Owner sent an inquiry to Management
                $propTitle = $conversation->property->title ?? 'Portfolio';
                $title = "🏛️ Owner Inquiry: {$ownerUser->name} ({$propTitle})";
                $body = "{$preview}";
                $url = route('admin.messages.show', $conversation);

                $admins = User::role(['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new OperationsAlert($title, $body, $url, 'info', email: false));
                }
            } else {
                // Management replied to Owner
                $title = '💬 Portfolio Management Reply';
                $body = "{$preview}";
                $url = route('owner.messages.show', $conversation);

                $ownerUser->notify(new OperationsAlert($title, $body, $url, 'info', email: false));
            }
        } else {
            // Guest conversation
            $guestUser = $conversation->user;
            if ($sender->id === ($guestUser?->id)) {
                // Guest sent a message
                $title = '🛎️ Guest Message: '.($guestUser->name ?? 'Guest');
                $body = "{$preview}";
                $url = route('admin.messages.show', $conversation);

                $admins = User::role(['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new OperationsAlert($title, $body, $url, 'info', email: false));
                }
            }
        }
    }

    /**
     * Total unread messages across all conversations visible to Admin.
     */
    public function adminUnreadMessagesCount(User $user): int
    {
        return Message::where('read', false)
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', fn ($q) => $q->visibleTo($user))
            ->count();
    }

    /**
     * Count of urgent site feed notices across any site in the last 24h.
     */
    public function adminUrgentSiteFeedsCount(): int
    {
        return SiteMessage::where('created_at', '>=', now()->subDay())
            ->where(fn ($q) => $q->whereIn('category', ['urgent', 'security'])->orWhere('pinned', true))
            ->count();
    }

    /**
     * Unread direct messages from Admin for a staff member.
     */
    public function staffUnreadDeskCount(User $user): int
    {
        return Message::where('read', false)
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('type', 'staff');
            })
            ->count();
    }

    /**
     * Count of new site updates posted today at the staff member's assigned sites.
     */
    public function staffSiteFeedCount(User $user): int
    {
        $staff = $user->staff;
        if (! $staff || ! $staff->is_active) {
            return 0;
        }

        $siteIds = $staff->activeAssignments()->pluck('site_id');
        if ($siteIds->isEmpty()) {
            return 0;
        }

        return SiteMessage::whereIn('site_id', $siteIds)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('user_id', '!=', $user->id)
            ->count();
    }

    /**
     * Unread guest messages at the staff member's assigned properties.
     */
    public function staffUnreadGuestMessagesCount(User $user): int
    {
        return Message::where('read', false)
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->visibleTo($user)->where(function ($sub) {
                    $sub->whereNull('type')->orWhere('type', 'guest');
                });
            })
            ->count();
    }

    /**
     * Unread messages from Management for a property owner.
     */
    public function ownerUnreadMessagesCount(User $user): int
    {
        return Message::where('read', false)
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('type', 'owner');
            })
            ->count();
    }
}
