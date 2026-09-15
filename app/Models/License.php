<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasFactory;

    protected $connection = 'platform';

    protected $table = 'licenses';

    protected $fillable = [
        'church_id',
        'license_key',
        'plan',
        'purchase_date',
        'activation_date',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'church_id' => 'integer',
            'purchase_date' => 'date',
            'activation_date' => 'date',
            'expiry_date' => 'date',
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
