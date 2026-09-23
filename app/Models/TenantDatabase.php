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

    protected $hidden = [
        'database_password',
    ];

    protected function casts(): array
    {
        return [
            'church_id' => 'integer',
            'database_port' => 'integer',
            'provisioned_at' => 'datetime',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
