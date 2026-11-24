<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MarketingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'preview_text',
        'target_segment',
        'template_type',
        'headline',
        'content_html',
        'featured_property_ids',
        'cta_text',
        'cta_url',
        'discount_code',
        'hero_image_url',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'featured_property_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CampaignLog::class, 'marketing_campaign_id');
    }

    /**
     * Resolve the featured properties attached to this campaign
     */
    public function getFeaturedProperties()
    {
        if (empty($this->featured_property_ids) || ! is_array($this->featured_property_ids)) {
            return Property::with(['site', 'images'])->where('status', 'active')->latest()->take(3)->get();
        }

        return Property::with(['site', 'images'])
            ->whereIn('id', $this->featured_property_ids)
            ->get();
    }

    /**
     * Retrieve target recipient contacts as a standardized Collection
     * Each item has: [email => ..., name => ..., type => ..., unsubscribe_url => ...]
     */
    public function resolveRecipients(): Collection
    {
        $recipients = collect();

        switch ($this->target_segment) {
            case 'newsletter_only':
                NewsletterSubscriber::active()->each(function ($sub) use (&$recipients) {
                    $recipients->put(strtolower($sub->email), [
                        'email' => strtolower($sub->email),
                        'name' => $sub->name ?: 'Valued Guest',
                        'type' => 'newsletter_subscriber',
                        'unsubscribe_url' => $sub->unsubscribe_url,
                    ]);
                });
                break;

            case 'past_guests':
                // Query guests with past reservations
                Reservation::with('guest', 'user')
                    ->whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'completed'])
                    ->get()
                    ->each(function ($res) use (&$recipients) {
                        $email = $res->guest?->email ?: $res->user?->email;
                        $name = $res->guest?->full_name ?: ($res->user ? $res->user->name : 'Valued Guest');
                        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $email = strtolower(trim($email));
                            $recipients->put($email, [
                                'email' => $email,
                                'name' => trim($name),
                                'type' => 'past_guest',
                                'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => md5($email)]),
                            ]);
                        }
                    });
                break;

            case 'registered_users':
                User::whereNotNull('email')->get()->each(function ($user) use (&$recipients) {
                    $email = strtolower(trim($user->email));
                    $recipients->put($email, [
                        'email' => $email,
                        'name' => trim($user->name) ?: 'Valued Guest',
                        'type' => 'registered_user',
                        'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => md5($email)]),
                    ]);
                });
                break;

            case 'active_affiliates':
                Affiliate::with('user')->where('status', 'active')->get()->each(function ($affiliate) use (&$recipients) {
                    $email = $affiliate->contact_email ?: $affiliate->user?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $email = strtolower(trim($email));
                        $recipients->put($email, [
                            'email' => $email,
                            'name' => $affiliate->company_name ?: ($affiliate->user ? $affiliate->user->name : 'Affiliate Partner'),
                            'type' => 'affiliate',
                            'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => md5($email)]),
                        ]);
                    }
                });
                break;

            case 'all_subscribers':
            default:
                // Combine active newsletter subscribers + registered users
                NewsletterSubscriber::active()->each(function ($sub) use (&$recipients) {
                    $recipients->put(strtolower($sub->email), [
                        'email' => strtolower($sub->email),
                        'name' => $sub->name ?: 'Valued Guest',
                        'type' => 'newsletter_subscriber',
                        'unsubscribe_url' => $sub->unsubscribe_url,
                    ]);
                });

                User::whereNotNull('email')->get()->each(function ($user) use (&$recipients) {
                    $email = strtolower(trim($user->email));
                    if (! $recipients->has($email)) {
                        $recipients->put($email, [
                            'email' => $email,
                            'name' => trim($user->name) ?: 'Valued Guest',
                            'type' => 'registered_user',
                            'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => md5($email)]),
                        ]);
                    }
                });
                break;
        }

        return $recipients->values();
    }

    public static function segmentCounts(): array
    {
        $newsletterCount = NewsletterSubscriber::active()->count();
        $usersCount = User::count();
        $affiliatesCount = Affiliate::where('status', 'active')->count();

        $pastGuestsCount = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'completed'])
            ->distinct('guest_id')
            ->count('guest_id');

        return [
            'all_subscribers' => $newsletterCount + $usersCount,
            'newsletter_only' => $newsletterCount,
            'registered_users' => $usersCount,
            'past_guests' => max($pastGuestsCount, 1),
            'active_affiliates' => $affiliatesCount,
        ];
    }
}
