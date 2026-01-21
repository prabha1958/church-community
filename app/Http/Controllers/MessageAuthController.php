<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Message;

class MessageAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'mobile_number' => 'required|string',
        ]);

        $member = Member::where('id', $data['member_id'])
            ->where('mobile_number', $data['mobile_number'])
            ->first();

        if (! $member) {
            return response()->json([
                'message' => 'Invalid Member ID or Mobile Number'
            ], 401);
        }

        $token = $member->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'member' => [
                'id' => $member->id,
                'family_name' => $member->family_name,
                'first_name' => $member->first_name
            ],
        ]);
    }

    public function index(Request $request)
    {
        $member = $request->user(); // authenticated member (Sanctum)

        $messages = Message::query()
            ->where('is_published', 1)
            ->where(function ($query) use ($member) {
                $query->whereNull('member_id')           // general messages
                    ->orWhere('member_id', $member->id); // personal messages
            })
            ->orderByDesc('published_at')
            ->paginate(20);

        return response()->json($messages);
    }
}
