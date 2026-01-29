<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;

class MemberController extends Controller
{
    public function show(Member $member)
    {
        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }
}
