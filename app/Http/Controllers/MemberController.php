<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * Get the authenticated member's own profile.
     */
    public function profile(Request $request)
    {
        $member = $request->user();

        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    /**
     * Update the authenticated member's own profile.
     */
    public function updateProfile(MemberProfileRequest $request)
    {
        $member = $request->user();

        $data = $request->validated();

        /*
         * Profile photo
         */
        if ($request->hasFile('profile_photo')) {
            if (
                $member->profile_photo &&
                Storage::disk('public')->exists($member->profile_photo)
            ) {
                Storage::disk('public')->delete(
                    $member->profile_photo
                );
            }

            $data['profile_photo'] = $request
                ->file('profile_photo')
                ->store('members/photos', 'public');
        }

        /*
         * Couple photo
         */
        if ($request->hasFile('couple_pic')) {
            if (
                $member->couple_pic &&
                Storage::disk('public')->exists($member->couple_pic)
            ) {
                Storage::disk('public')->delete(
                    $member->couple_pic
                );
            }

            $data['couple_pic'] = $request
                ->file('couple_pic')
                ->store('members/photos', 'public');
        }

        $member->update($data);

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data' => $member,
        ]);
    }

    /**
     * Change the authenticated member's email address.
     */
    public function updateEmail(Request $request)
    {
        $member = $request->user();

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('members', 'email')
                    ->ignore($member->id),
            ],
        ]);

        $member->update([
            'email' => $data['email'],
        ]);

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Email address updated.',
            'data' => $member->only([
                'email',
            ]),
        ]);
    }

    /**
     * Change the authenticated member's mobile number.
     *
     * NOTE:
     * This endpoint should ultimately be protected by
     * OTP verification before we allow the actual update.
     */
    public function updateMobile(Request $request)
    {
        $member = $request->user();

        $data = $request->validate([
            'mobile_number' => [
                'required',
                'string',
                Rule::unique('members', 'mobile_number')
                    ->ignore($member->id),
            ],
        ]);

        $member->update([
            'mobile_number' => $data['mobile_number'],
        ]);

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Mobile number updated.',
            'data' => $member->only([
                'mobile_number',
            ]),
        ]);
    }
}
