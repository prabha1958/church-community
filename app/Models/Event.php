<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TenantModel;

class Event extends TenantModel
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'date_of_event',
        'name_of_event',
        'description',
        'event_photos',
        'published'
    ];

    protected $casts = [
        'date_of_event' => 'date',
        'event_photos'  => 'array',
    ];
}
