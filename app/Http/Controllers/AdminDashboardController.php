<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\PoorFeeding;
use App\Models\Alliance;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $fy = Subscription::financialYearForDate();

        return response()->json([
            'members' => [
                'total' => Member::where('status_flag', 1)->count(),
                'by_area' => Member::select('area_no', DB::raw('count(*) as total'))
                    ->where('status_flag', 1)
                    ->groupBy('area_no')
                    ->orderBy('area_no')
                    ->get(),
            ],

            'subscriptions' => [
                'total' => Subscription::where('financial_year', $fy)->count(),
                'amount' => Payment::sum('amount'),
            ],

            'alliances' => [
                'published' => Alliance::where('payment_date', '!=', null)->count(),
            ],

            'greetings' => [
                'birthday_last_run' => DB::table('system_runs')
                    ->where('type', 'birthday')->value('last_run_at'),
                'anniversary_last_run' => DB::table('system_runs')
                    ->where('type', 'anniversary')->value('last_run_at'),
            ],

            'poor_feeding' => PoorFeeding::with([
                'sponsor' => function ($q) {
                    $q->select('id', 'family_name', 'first_name', 'last_name');
                }
            ])
                ->latest('date_of_event')
                ->first(),
        ]);
    }
}
