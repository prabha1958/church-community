<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthdayGreeting extends Model
{
    protected $fillable = [
        'member_id',
        'greeted_on',
        'greeted_year',
        'email_sent',
        'whatsapp_sent',
    ];
}
