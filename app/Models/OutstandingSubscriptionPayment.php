<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutstandingSubscriptionPayment extends TenantModel
{
    protected $table = 'outstanding_subscription_payments';

    protected $fillable = [
        'outstanding_subscription_id',
        'member_id',
        'amount',
        'payment_mode',
        'reference_no',
        'admin_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function outstandingSubscription(): BelongsTo
    {
        return $this->belongsTo(
            OutstandingSubscription::class,
            'outstanding_subscription_id'
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'admin_id');
    }
}
