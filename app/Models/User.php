<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogsActivity;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'avatar', 'id_document', 'id_verified', 'google_id', 'api_token'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasFactory, Notifiable, InteractsWithMedia, HasRoles, HasApiTokens, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'id_document',
        'id_verified',
        'google_id',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'id_verified' => 'boolean',
        ];
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Shifts are attached to the user's staff profile.
     */
    public function staffSchedules(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(StaffSchedule::class, Staff::class, 'user_id', 'staff_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function wishlists(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'wishlists');
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function generateApiToken()
    {
        $this->api_token = hash('sha256', bin2hex(random_bytes(32)));
        $this->save();
        return $this->api_token;
    }

    public function clearApiToken()
    {
        $this->api_token = null;
        $this->save();
    }
}