<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $member = $request->user();

        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            ['member_id' => $member->id]
        );

        return response()->json(['status' => 'ok']);
    }
}
