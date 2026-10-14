<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class OutstandingSubscription extends TenantModel
{
    protected $table = 'outstanding_subscriptions';

    protected $fillable = [
        'member_id',
        'financial_year',
        'months_outstanding',
        'original_amount',
        'amount_paid',
        'balance_amount',
    ];

    protected function casts(): array
    {
        return [
            'months_outstanding' => 'array',
            'original_amount' => 'integer',
            'amount_paid' => 'integer',
            'balance_amount' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            OutstandingSubscriptionPayment::class,
            'outstanding_subscription_id'
        );
    }
}
