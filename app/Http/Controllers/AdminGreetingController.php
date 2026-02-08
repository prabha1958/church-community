<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BirthdayGreetingService;
use App\Services\AnniversaryGreetingService;
use Illuminate\Support\Facades\DB;

class AdminGreetingController extends Controller
{
    public function runBirthday(BirthdayGreetingService $service)
    {
        DB::table('system_run_logs')->where('type', 'birthday')->delete();

        $service->run();

        return response()->json(['success' => true]);
    }
    public function runAnniversary(AnniversaryGreetingService $service)
    {
        DB::table('system_run_logs')->where('type', 'anniversary')->delete();

        $service->run();

        return response()->json(['success' => true]);
    }


    public function logs()
    {
        return DB::table('system_run_logs')
            ->where('type', 'birthday')
            ->orderBy('id')
            ->get();
    }

    public function annlogs()
    {
        return DB::table('system_run_logs')
            ->where('type', 'anniversary')
            ->orderBy('id')
            ->get();
    }
}
