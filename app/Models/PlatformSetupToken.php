<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetupToken extends Model
{
    protected $connection = 'platform';

    protected $table = 'platform_setup_tokens';

    protected $fillable = [
        'church_id',
        'registration_request_id',
        'email',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
