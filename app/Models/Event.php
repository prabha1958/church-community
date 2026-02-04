<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
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
