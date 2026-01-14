<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Member extends Model
{
    use HasFactory;
    use HasApiTokens, Notifiable, HasFactory;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    protected $table = 'members';

    protected $fillable = [
        'family_name',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'wedding_date',
        'email',
        'mobile_number',
        'spouse_name',
        'gender',
        'status_flag',
        'occupation',
        'status',
        'role',
        'area_no',
        'membership_fee',
        'address_flat_number',
        'address_premises',
        'address_area',
        'address_landmark',
        'address_city',
        'address_pin',
        'profile_photo',
        'couple_pic',
    ];



    protected $casts = [
        'date_of_birth'  => 'date',
        'email_verified_at' => 'datetime',
        'wedding_date' => 'date',
        'status_flag' => 'boolean',

    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Subscriptions for this member.
     * Assumes subscriptions table has member_id FK.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class, 'member_id');
    }

    /**
     * Poor feedings sponsored by this member.
     * Assumes poor_feedings table has sponsored_by FK referencing members.id.
     */
    public function poorFeedings()
    {
        return $this->hasMany(\App\Models\PoorFeeding::class, 'sponsored_by');
    }

    /**
     * Optional helper: latest subscription (if you want quick access).
     */
    public function latestSubscription()
    {
        return $this->hasOne(\App\Models\Subscription::class, 'member_id')->latestOfMany();
    }

    /**
     * Optional helper: total subscriptions amount aggregated from related subscriptions.
     * Note: This is a convenience method — for big datasets prefer using DB aggregates.
     */
    public function subscriptionsTotal(): float
    {
        // Sum 'total' column across related subscriptions; returns float 0.00 if none.
        return (float) $this->subscriptions()->sum('total');
    }
}
