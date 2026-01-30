<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pastor extends Model
{
    protected $fillable = [
        'name',
        'designation',
        'qualifications',
        'date_of_joining',
        'date_of_leaving',
        'past_service_description',
        'photo',
        'order_no'

    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'date_of_leaving' => 'date',
    ];

    /**
     * Return public URL for photo if set.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        // returns /storage/... URL (requires php artisan storage:link)
        return Storage::url($this->photo);
    }

    // Optionally add accessor to include photo_url when model serialized
    protected $appends = ['photo_url'];
}
