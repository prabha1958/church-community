<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDatabase extends Model
{
    use HasFactory;

    protected $connection = 'platform';

    protected $table = 'tenant_databases';

    protected $fillable = [
        'church_id',
        'database_name',
        'database_username',
        'database_password',
        'database_host',
        'database_port',
        'status',
        'error_message',
        'provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'church_id' => 'integer',
            'database_port' => 'integer',

            // Laravel automatically encrypts when storing
            // and decrypts when retrieving.
            'database_password' => 'encrypted',

            'provisioned_at' => 'datetime',
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
