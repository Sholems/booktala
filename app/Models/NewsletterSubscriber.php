<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'unsubscribe_token',
        'source',
        'ip_address',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($subscriber) {
            if (empty($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = Str::random(32);
            }
            if (empty($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function unsubscribe(): bool
    {
        return $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function resubscribe(): bool
    {
        return $this->update([
            'status' => 'subscribed',
            'unsubscribed_at' => null,
            'subscribed_at' => now(),
        ]);
    }

    public function getUnsubscribeUrlAttribute(): string
    {
        if (empty($this->unsubscribe_token)) {
            $this->unsubscribe_token = Str::random(32);
            $this->saveQuietly();
        }

        return route('newsletter.unsubscribe', ['token' => $this->unsubscribe_token]);
    }
}
