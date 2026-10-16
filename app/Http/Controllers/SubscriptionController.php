<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Subscription;

class SubscriptionController extends Controller
{

    public function show(Request $request)
    {

        $member = $request->user();
        $fy = Subscription::financialYearForDate();

        $previousYearOutstandings = OutstandingSubscription::on('tenant')
            ->where('member_id', $member->id)
            ->where('balance_amount', '>', 0)
            ->where('financial_year', '<', $fy)
            ->orderBy('financial_year')
            ->get([
                'financial_year',
                'balance_amount',
            ]);

        $sub = Subscription::where('member_id', $member->id)
            ->where('financial_year', $fy)
            ->first();

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'name' => trim($member->first_name . ' ' . $member->last_name),
                'membership_fee' => $member->membership_fee,

            ],
            'subscription' => $sub,
            'months' => Subscription::fyMonths(),
            'fy' => $fy
        ]);
    }
}
