<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchRegistrationRequest extends Model
{
    protected $connection = 'platform';

    protected $table = 'church_registration_requests';

    protected $fillable = [
        'church_name',
        'address',
        'city',
        'state',
        'country',
        'admin_name',
        'admin_email',
        'admin_phone',
        'status',
        'reviewed_at',
        'reviewed_by',
        'church_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class, 'church_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
