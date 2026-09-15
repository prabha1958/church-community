<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PlatformUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'platform';

    protected $table = 'platform_users';

    protected $fillable = [
        'church_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'church_id' => 'integer',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
