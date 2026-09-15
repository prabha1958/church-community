<?php

namespace App\Models;



class Announcement extends TenantModel
{
    protected $fillable = [
        'date',
        'title',
        'description',
        'published',
        'exp_date'
    ];
}
