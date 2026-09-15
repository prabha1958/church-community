<?php

namespace App\Models;



class DeviceToken extends TenantModel
{
    protected $fillable = [
        'member_id',
        'token'
    ];
}
