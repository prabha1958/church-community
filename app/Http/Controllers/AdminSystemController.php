<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AdminSystemController extends Controller
{
    public function runBirthday()
    {

        Artisan::call('send:birthday-wishes');
        DB::table('system_runs')->updateOrInsert(
            ['type' => 'birthday'],
            ['last_run_at' => now(), 'status' => 'success']
        );

        return response()->json(['success' => true]);
    }

    public function runAnniversary()
    {
        Artisan::call('greetings:anniversary');
        DB::table('system_runs')->updateOrInsert(
            ['type' => 'anniversary'],
            ['last_run_at' => now(), 'status' => 'success']
        );

        return response()->json(['success' => true]);
    }
}
