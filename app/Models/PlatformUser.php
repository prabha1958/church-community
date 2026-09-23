<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Platform users are stored in the platform database.
     */
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

    /**
     * Every platform user belongs to exactly one church.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Every platform user is a setup administrator.
     */
    public function isSetupAdmin(): bool
    {
        return $this->role === 'setup_admin';
    }
}
