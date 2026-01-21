<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'ip_address',
        'user_agent',
    ];
}
