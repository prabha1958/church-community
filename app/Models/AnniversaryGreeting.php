<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnniversaryGreeting extends TenantModel
{
    protected $fillable = [
        "member_id",
        "wedding_date",
        "sent_on",
        "channel",
        "message"
    ];
}
