<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlliancePayment extends Model
{
    protected $fillable = [
        'alliance_id',
        'member_id',
        'payment_gateway',
        'payment_gateway_order_id',
        'payment_gateway_payment_id',
        'payment_gateway_signature',
        'amount',
        'currency',
        'status',
        'paid_at',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
        'paid_at' => 'datetime',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
