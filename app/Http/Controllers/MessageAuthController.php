<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class MessageAuthController extends Controller
{



    public function login(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],

        ]);

        $member = Member::where('id', $data['member_id'])->first();



        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Member ID or Mobile Number.',
            ], 401);
        }

        if (empty($member->email)) {
            return response()->json([
                'success' => false,
                'message' => 'No email address is registered for this member. Please contact the church administrator.',
            ], 422);
        }

        $tenantDatabase = $member->getConnection()->getDatabaseName();

        $key = 'member-email-otp:' . hash(
            'sha256',
            $tenantDatabase . '|' . $member->id
        );

        // Enforce a 60-second resend cooldown.
        if (Cache::has($key . ':resend')) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        // Store only the OTP hash, never the plain OTP.
        Cache::put($key, [
            'otp_hash' => hash('sha256', $otp),
            'attempts' => 0,
        ], now()->addMinutes(5));

        Cache::put(
            $key . ':resend',
            true,
            now()->addSeconds(60)
        );

        try {
            Mail::raw(
                "Your Church Message App login OTP is: {$otp}\n\n"
                    . "This code expires in 5 minutes. Do not share it with anyone.",
                function ($message) use ($member) {
                    $message
                        ->to($member->email)
                        ->subject('Your Church Message App Login OTP');
                }
            );
        } catch (\Throwable $e) {
            Cache::forget($key);
            Cache::forget($key . ':resend');

            Log::error('EMAIL OTP SEND ERROR', [
                'member_id' => $member->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send the OTP email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your registered email address.',
            'email' => $this->maskEmail($member->email),
        ]);
    }



    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],
            'otp' => ['required', 'digits:6'],
        ]);

        $member = Member::find($data['member_id']);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Member ID or OTP.',
            ], 401);
        }

        $tenantDatabase = $member->getConnection()->getDatabaseName();

        $key = 'member-email-otp:' . hash(
            'sha256',
            $tenantDatabase . '|' . $member->id
        );

        $otpData = Cache::get($key);

        if (!$otpData) {
            return response()->json([
                'success' => false,
                'message' => 'Your OTP has expired or was not requested. Please request a new OTP.',
            ], 422);
        }

        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($key);

            return response()->json([
                'success' => false,
                'message' => 'Too many incorrect attempts. Please request a new OTP.',
            ], 429);
        }

        if (!hash_equals(
            $otpData['otp_hash'],
            hash('sha256', $data['otp'])
        )) {
            $otpData['attempts']++;

            if ($otpData['attempts'] >= 5) {
                Cache::forget($key);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many incorrect attempts. Please request a new OTP.',
                ], 429);
            }

            Cache::put($key, $otpData, now()->addMinutes(5));

            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP. Please try again.',
            ], 422);
        }

        // OTP is single-use.
        Cache::forget($key);
        Cache::forget($key . ':resend');

        $token = $member->createToken('mobile')->plainTextToken;

        $alliance = $member->alliance;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'member' => $member,
            'alliance' => $alliance,
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        $visible = mb_substr($local, 0, 1);

        return $visible . '***@' . $domain;
    }



    public function index(Request $request)
    {

        $member = Auth::user(); // authenticated member (Sanctum)

        $messages = Message::query()
            ->where('is_published', 1)
            ->where(function ($query) use ($member) {
                $query->whereNull('member_id')           // general messages
                    ->orWhere('member_id', $member->id); // personal messages
            })
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $messages
        ]);
    }

    public function show(Message $message, Request $request)
    {
        $member = $request->user();

        // Unpublished messages are not visible to members
        if (! $message->is_published) {
            abort(404);
        }

        // Member-specific messages can only be viewed by the intended member
        if (
            $message->member_id !== null &&
            $message->member_id !== $member->id
        ) {
            abort(403);
        }

        return response()->json($message);
    }
}
