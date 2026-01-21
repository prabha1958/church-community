<?php

// app/Services/AdminActionLogger.php
namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminActionLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        ?string $modelType = null,
        ?int $modelId = null
    ): void {
        $admin = Auth::user();

        if (! $admin) return;

        AdminActivityLog::create([
            'admin_id'   => $admin->id,
            'action'     => $action,
            'description' => $description,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
