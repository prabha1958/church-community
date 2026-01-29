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

        $member->load([
            'alliance:id,member_id,alliance_type,payment_date'
        ]);


        return response()->json([
            'token' => $token,
            'member' => $member,
            'alliance' => $member->alliance
                ? [
                    'alliance_id' => $member->alliance->id,
                    'alliance_type' => $member->alliance->alliance_type,
                    'payment_date' => $member->alliance->payment_date,
                ]
                : null,
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

    public function show(Message $message, Request $request)
    {
        $member = $request->user();

        // Security: ensure member can see this message
        if (
            $message->member_id !== null &&
            $message->member_id !== $member->id
        ) {
            abort(403);
        }

        return response()->json($message);
    }
}
