<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PastorateComMember extends Model
{
    protected $fillable = [
        'family_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'dt_from',
        'dt_to',
        'status',
        'designation',
        'profile_photo',
        'achievements',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'dt_from' => 'date',
        'dt_to' => 'date',
    ];

    /**
     * Get full name accessor
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
