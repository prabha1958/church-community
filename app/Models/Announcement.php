<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'date',
        'title',
        'description',
        'published',
        'exp_date'
    ];
}
