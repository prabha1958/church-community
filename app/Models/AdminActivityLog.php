<?php

namespace App\Models;



class AdminActivityLog extends TenantModel
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
