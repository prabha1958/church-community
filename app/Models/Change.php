<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $fillable = [
        'member_id',
        'chng_field',
        'message',
        'image_path',
        'changed_on'
    ];
}
