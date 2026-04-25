<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Http\Requests\MemberRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class MemberController extends Controller
{
    public function show(Member $member)
    {
        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    public function update(MemberRequest $request, Member $member)
    {


        $data = $request->validated();



        if ($request->hasFile('profile_photo')) {
            // delete old photo if present
            if ($member->profile_photo && Storage::disk('public')->exists($member->profile_photo)) {
                Storage::disk('public')->delete($member->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('members/photos', 'public');
        }

        if ($request->hasFile('couple_pic')) {
            // delete old photo if present
            if ($member->couple_pic && Storage::disk('public')->exists($member->couple_pic)) {
                Storage::disk('public')->delete($member->couple_pic);
            }
            $data['couple_pic'] = $request->file('couple_pic')->store('members/photos', 'public');
        }



        $member->update($data);




        return response()->json([
            'success' => true,
            'message' => 'Member updated.',
            'data' => $member,
        ]);
    }

    public function updateEmail(Request $request, Member $member)
    {
        $data = $request->validate([
            'email' => [
                'sometimes',
                'email',
                Rule::unique('members', 'email')->ignore($member->id),
            ],
            'mobile_number' => [
                'sometimes',
                'string',
                Rule::unique('members', 'mobile_number')->ignore($member->id),
            ],
        ]);

        $member->update($data);



        return response()->json([
            'success' => true,
            'message' => 'Email details updated',
            'data' => $member->only(['email']),
        ]);
    }

    public function updateMobile(Request $request, Member $member)
    {
        $data = $request->validate([

            'mobile_number' => [
                'sometimes',
                'string',
                Rule::unique('members', 'mobile_number')->ignore($member->id),
            ],
        ]);

        $member->update($data);


        return response()->json([
            'success' => true,
            'message' => 'Mobile details updated',
            'data' => $member->only(['mobile_number']),
        ]);
    }
}
