<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Subscription;

class SubscriptionController extends Controller
{

    public function show(Member $member)
    {
        $fy = Subscription::financialYearForDate();

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
        ]);
    }
}
