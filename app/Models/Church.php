<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Church extends Model
{
    use HasFactory;

    protected $connection = 'platform';

    protected $table = 'churches';

    protected $fillable = [
        'church_code',
        'church_name',
        'short_name',
        'email',
        'mobile',
        'address',
        'city',
        'state',
        'country',
        'logo',
        'timezone',
        'financial_year_start_month',
        'status',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'financial_year_start_month' => 'integer',
            'activated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function tenantDatabase(): HasOne
    {
        return $this->hasOne(TenantDatabase::class);
    }

    public function platformUsers(): HasMany
    {
        return $this->hasMany(PlatformUser::class);
    }
}
