<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'member_id',
        'contact',
        'code_hash',
        'expires_at',
        'used',
        'device_name'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Member::class);
    }
}
