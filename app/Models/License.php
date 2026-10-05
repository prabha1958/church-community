<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class License extends Model
{
    protected $connection = 'platform';

    protected $table = 'licenses';

    protected $fillable = [
        'church_id',
        'license_key',
        'plan',
        'purchase_date',
        'activation_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'activation_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function isLifetime(): bool
    {
        return $this->plan === 'lifetime';
    }

    public function isExpired(): bool
    {
        if ($this->isLifetime()) {
            return false;
        }

        if (!$this->expiry_date) {
            return true;
        }

        return $this->expiry_date->isBefore(Carbon::today());
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return !$this->isExpired();
    }

    public function daysRemaining(): ?int
    {
        if ($this->isLifetime()) {
            return null;
        }

        if (!$this->expiry_date) {
            return 0;
        }

        return max(
            0,
            Carbon::today()->diffInDays(
                $this->expiry_date,
                false
            )
        );
    }
}
