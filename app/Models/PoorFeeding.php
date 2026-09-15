<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoorFeeding extends TenantModel
{
    protected $fillable = [
        'date_of_event',
        'sponsored_by',
        'no_of_persons_fed',
        'event_photos',
        'brief_description',
        'published',
    ];

    protected $casts = [
        'date_of_event' => 'date',
        'event_photos' => 'array',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sponsored_by');
    }
}
