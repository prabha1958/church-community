<?php

namespace App\Models;


use Illuminate\Support\Facades\Storage;

class Change extends TenantModel
{
    protected $fillable = [
        'name',
        'designation',
        'qualifications',
        'date_of_joining',
        'date_of_leaving',
        'past_service_description',
        'photo',
        'order_no',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'date_of_leaving' => 'date',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return Storage::url($this->photo);
    }

    protected $appends = ['photo_url'];
}
