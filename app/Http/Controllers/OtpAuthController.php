<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Mail\OtpMail;
use App\Models\Member;
use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpAuthController extends Controller
{
    protected int $otpLength = 6;
    protected int $otpTTLMinutes = 10;

    /**
     * Send OTP to an existing member's email only.
     *
     * Request input:
     *  - contact (must be an email string)
     *
     * Returns 422 if email is not present in members table or invalid.
     */
    public function send(SendOtpRequest $request)
    {
        $contactRaw = trim($request->input('contact'));


        // Determine channel: email or phone
        $isEmail = filter_var($contactRaw, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9+\-\s()]+$/', $contactRaw); // loose phone check

        if (! $isEmail && ! $isPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email address or mobile number.'
            ], 422);
        }

        // Normalize contact for storage/lookup
        if ($isEmail) {
            $contact = strtolower($contactRaw);
        } else {
            // keep only digits and plus
            $digits = preg_replace('/[^0-9+]/', '', $contactRaw);

            // If it's 10 digits (likely Indian local), prefix +91
            if (preg_match('/^[0-9]{10}$/', $digits)) {
                $digits = '+91' . $digits;
            }

            // If it starts with digits but missing plus, add plus
            if (preg_match('/^[0-9]+$/', $digits)) {
                $digits = '+' . $digits;
            }

            $contact = $digits;
        }

        // Find member by email or mobile_number
        if ($isEmail) {
            $member = Member::where('email', $contact)->first();
        } else {
            $member = Member::where('mobile_number', $contact)
                ->orWhere('mobile_number', preg_replace('/^\+?/', '', $contact)) // try without plus
                ->first();
        }


        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => $isEmail
                    ? 'Email not found. Please contact your church secretary.'
                    : 'Mobile number not found. Please contact your church secretary.'
            ], 422);
        }

        // Generate numeric OTP
        $rawCode = $this->generateNumericOtp($this->otpLength);

        // Hash before storing
        $codeHash = Hash::make($rawCode);

        // Clean up previous unused OTPs for this contact
        OtpCode::where('contact', $contact)->where('used', false)->delete();

        // Store OTP record
        $otp = OtpCode::create([
            'member_id'   => $member->id,
            'contact'     => $contact,
            'code_hash'   => $codeHash,
            'expires_at'  => Carbon::now()->addMinutes($this->otpTTLMinutes),
            'device_name' => null,
        ]);


        // Compose message text
        $smsMessage = "Your CSI Centenary Wesley Church verification code is: {$rawCode}. It expires in {$this->otpTTLMinutes} minutes.";

        // Send via appropriate channel
        if ($isEmail) {
            try {
                Mail::to($contact)->send(new OtpMail($contact, $rawCode));
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to the registered email.'
                ]);
            } catch (\Throwable $e) {
                // remove OTP record on send failure
                $otp->delete();
                Log::error('Failed to send OTP email: ' . $e->getMessage(), ['contact' => $contact, 'member_id' => $member->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email. Please try again later.' . $e
                ], 500);
            }
        }
    }


    /**
     * Verify OTP and return a persistent Sanctum token if successful.
     *
     * Request input:
     *  - contact (email)
     *  - code (OTP string)
     *
     * Returns 422 on invalid/expired OTP or if email not registered.
     */
    public function verify(VerifyOtpRequest $request)
    {
        $contactRaw  = trim((string) $request->input('contact'));
        $otpProvided = trim((string) $request->input('code'));
        $deviceName  = $request->input('device_name', 'cwcr-login'); // optional

        if (empty($contactRaw) || empty($otpProvided)) {
            return response()->json([
                'success' => false,
                'message' => 'Both contact and OTP are required.'
            ], 422);
        }

        // Detect if input is email or mobile
        $isEmail = filter_var($contactRaw, FILTER_VALIDATE_EMAIL) !== false;

        // Normalize contact if mobile
        if (! $isEmail) {
            $contact = preg_replace('/[^\d+]/', '', $contactRaw);
            $contact = preg_replace('/^\++/', '+', $contact);
            if (preg_match('/^[0-9]{10}$/', $contact)) {
                $contact = '+91' . $contact; // assume India for 10-digit
            } elseif (preg_match('/^[0-9]+$/', $contact)) {
                $contact = '+' . $contact;
            }
        } else {
            $contact = strtolower($contactRaw);
        }

        // Retrieve OTP record (unused + not expired)
        $otpRecord = \App\Models\OtpCode::where('contact', $contact)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired or invalid. Please request a new one.'
            ], 404);
        }

        // Verify OTP
        if (! \Illuminate\Support\Facades\Hash::check($otpProvided, $otpRecord->code_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.'
            ], 401);
        }

        // Mark OTP as used
        $otpRecord->update(['used' => true]);

        // Load member
        /** @var \App\Models\Member|null $member */
        $member = \App\Models\Member::find($otpRecord->member_id);
        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found.'
            ], 404);
        }

        // --- Create Sanctum token ---
        // Make sure Member model uses HasApiTokens
        // use Laravel\Sanctum\HasApiTokens; in Member model + use HasApiTokens;
        $accessToken = $member->createToken($deviceName)->plainTextToken;

        // --- Eager load related data ---
        // Alliances with latest payments
        $alliances = [];
        if (method_exists($member, 'alliances')) {
            $alliances = $member->alliances()
                ->with(['payments' => function ($q) {
                    $q->orderByDesc('id')->select([
                        'id',
                        'alliance_id',
                        'member_id',
                        'payment_gateway',
                        'payment_gateway_order_id',
                        'payment_gateway_payment_id',
                        'status',
                        'amount',
                        'currency',
                        'paid_at',
                        'created_at',
                    ]);
                }])
                ->orderByDesc('id')
                ->get([
                    'id',
                    'member_id',
                    'match_type',
                    'alliance_type',
                    'family_name',
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'profile_photo',
                    'photo1',
                    'photo2',
                    'photo3',
                    'father_name',
                    'mother_name',
                    'father_occupation',
                    'mother_occupation',
                    'educational_qualifications',
                    'profession',
                    'designation',
                    'company_name',
                    'place_of_working',
                    'about_self',
                    'about_family',
                    'is_published',
                    'created_at',
                    'updated_at',
                ]);
        }

        // Subscriptions (if relation exists). Adjust relation name/fields to your schema.
        $subscriptions = [];
        if (method_exists($member, 'subscriptions')) {
            $subscriptions = $member->subscriptions()
                ->orderByDesc('id')
                ->get(); // or select specific columns if you prefer
        }

        // --- Build member profile payload ---
        $memberProfile = [
            'id'                 => $member->id,
            'family_name'        => $member->family_name,
            'first_name'         => $member->first_name,
            'middle_name'        => $member->middle_name,
            'last_name'          => $member->last_name,
            'date_of_birth'      => $member->date_of_birth,
            'wedding_date'       => $member->wedding_date,
            'spouse_name'        => $member->spouse_name,
            'gender'             => $member->gender,
            'status_flag'        => (bool) $member->status_flag,
            'email'              => $member->email,
            'mobile_number'      => $member->mobile_number,
            'residential_address' => $member->residential_address,
            'occupation'         => $member->occupation,
            'status'             => $member->status, // in_service/retired/other
            'profile_photo'      => $member->profile_photo,
            'role'               => $member->role,
            'area_no'            => $member->area_no,
            'membership_fee'     => $member->membership_fee,
            'created_at'         => $member->created_at,
            'updated_at'         => $member->updated_at,
        ];

        return response()->json([
            'success'       => true,
            'message'       => 'OTP verified successfully.',
            'access_token'  => $accessToken,
            'token_type'    => 'Bearer',
            'member'        => $memberProfile,
            'alliances'     => $alliances,
            'subscriptions' => $subscriptions,
        ], 200);
    }



    /**
     * Optional SMS sending stub — kept for completeness (not used here).
     */
    protected function sendSms(string $mobile, string $message)
    {
        // Example using a provider SDK:
        // Twilio::message($mobile, $message);
        // Or use HTTP request to your SMS API.
        logger()->info("sendSms stub for {$mobile}: {$message}");
    }

    /**
     * Generate numeric OTP allowing leading zeros.
     */
    protected function generateNumericOtp(int $length = 6): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }
}
