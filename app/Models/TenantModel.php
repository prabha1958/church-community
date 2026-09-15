<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    /**
     * All models extending TenantModel use
     * the dynamically configured tenant connection.
     */
    protected $connection = 'tenant';
}
