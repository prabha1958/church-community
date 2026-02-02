<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberSessionController extends Controller
{
    public function show(Request $request)
    {
        $member = $request->user(); // sanctum user

        // load alliance if exists
        $member->load([
            'alliance:id,member_id,alliance_type,payment_date'
        ]);

        return response()->json([
            'success' => true,

            'member' => [
                'id'               => $member->id,
                'family_name'      => $member->family_name,
                'first_name'       => $member->first_name,
                'middle_name'      => $member->middle_name,
                'last_name'        => $member->last_name,
                'email'            => $member->email,
                'mobile_number'    => $member->mobile_number,
                'profile_photo'    => $member->profile_photo,
                'couple_pic'       => $member->couple_pic,
                'membership_fee'   => $member->membership_fee,
                'status'           => $member->status,
            ],

            'alliance' => $member->alliance
                ? [
                    'alliance_id'   => $member->alliance->id,
                    'alliance_type' => $member->alliance->alliance_type,
                    'payment_date'  => $member->alliance->payment_date,
                ]
                : null,
        ]);
    }
}
