<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BirthdayGreetingService;
use App\Services\AnniversaryGreetingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


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
        // Clear previous logs (optional)
        DB::table('system_run_logs')
            ->where('type', 'anniversary')
            ->delete();

        // Run for today, collect logs silently
        $service->run(Carbon::now());

        return response()->json([
            'success' => true,
            'message' => 'Anniversary greetings executed successfully',
        ]);
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
