<?php

namespace App\Http\Controllers;

use App\Services\BirthdayGreetingService;
use App\Services\AnniversaryGreetingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminGreetingController extends Controller
{
    public function runBirthday(BirthdayGreetingService $service)
    {
        DB::connection('tenant')
            ->table('system_run_logs')
            ->where('type', 'birthday')
            ->delete();

        $service->run();

        return response()->json([
            'success' => true,
            'message' => 'Birthday greetings executed successfully',
        ]);
    }

    public function runAnniversary(AnniversaryGreetingService $service)
    {
        DB::connection('tenant')
            ->table('system_run_logs')
            ->where('type', 'anniversary')
            ->delete();

        $service->run(Carbon::now());

        return response()->json([
            'success' => true,
            'message' => 'Anniversary greetings executed successfully',
        ]);
    }

    public function logs()
    {
        return DB::connection('tenant')
            ->table('system_run_logs')
            ->where('type', 'birthday')
            ->orderBy('id')
            ->get();
    }

    public function annlogs()
    {
        return DB::connection('tenant')
            ->table('system_run_logs')
            ->where('type', 'anniversary')
            ->orderBy('id')
            ->get();
    }
}
